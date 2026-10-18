<?php

class UsuarioForm extends TPage
{
    protected $form;
    protected $gruposChecklist;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_usuario');
        $this->form->setFormTitle('<i class="fas fa-user-edit"></i> Cadastro de Usuário');

        // 1. Instanciação dos Campos
        $id             = new THidden('id');
        $nome           = new TEntry('nome');
        $email          = new TEntry('email');
        $cpf            = new TEntry('cpf');
        $celular        = new TEntry('celular');
        $theme          = new TCombo('theme');
        $status         = new TCombo('status');
        $senha          = new TPassword('senha');
        $senha_confirm  = new TPassword('senha_confirm');

        // Checklist de Grupos (N:N) - Exatamente 4 argumentos por coluna
        $this->gruposChecklist = new TCheckList('grupos_ids');
        $this->gruposChecklist->addColumn('id', 'ID', 'center', '60px');
        $this->gruposChecklist->addColumn('nome', 'Grupo de Permissão', 'left', '220px');
        $this->gruposChecklist->addColumn('descricao', 'Descrição', 'left', '100%');
        $this->gruposChecklist->setHeight(220);

        // 2. Configurações de Tamanhos e Máscaras
        $nome->setSize('100%');
        $email->setSize('100%');
        $cpf->setSize('100%');
        $celular->setSize('100%');
        $theme->setSize('100%');
        $status->setSize('100%');
        $senha->setSize('100%');
        $senha_confirm->setSize('100%');

        $cpf->setMask('999.999.999-99');
        $celular->setMask('(99) 99999-9999');

        $senha->placeholder         = 'Preencha para cadastrar ou trocar a senha';
        $senha_confirm->placeholder = 'Confirme a senha';

        // Opções de Tema
        $theme->addItems([
            'light' => 'Tema Claro (Light)',
            'dark'  => 'Tema Escuro (Dark)'
        ]);
        $theme->setDefaultOption(false);
        $theme->setValue('light');

        // Opções de Status
        $status->addItems([
            'ativo'     => 'Ativo',
            'bloqueado' => 'Bloqueado',
            'pendente'  => 'Pendente'
        ]);
        $status->setDefaultOption(false);
        $status->setValue('ativo');

        // Validações
        $nome->addValidation('Nome Completo', new TRequiredValidator);
        $email->addValidation('E-mail / Login', new TRequiredValidator);
        $email->addValidation('E-mail / Login', new TEmailValidator);
        $cpf->addValidation('CPF', new TRequiredValidator);
        $theme->addValidation('Tema Padrão', new TRequiredValidator);
        $status->addValidation('Status', new TRequiredValidator);

        // 3. Montagem das Abas
        $this->form->appendPage('Identificação e Acesso');
        $this->form->addFields([$id]);
        $this->form->addFields(
            [new TLabel('Nome Completo <span class="text-danger">*</span>')], [$nome],
            [new TLabel('E-mail / Login <span class="text-danger">*</span>')], [$email]
        );
        $this->form->addFields(
            [new TLabel('CPF (Criptografado) <span class="text-danger">*</span>')], [$cpf],
            [new TLabel('Celular / WhatsApp')], [$celular]
        );
        $this->form->addFields(
            [new TLabel('Tema Padrão <span class="text-danger">*</span>')], [$theme],
            [new TLabel('Status da Conta <span class="text-danger">*</span>')], [$status]
        );
        $this->form->addFields(
            [new TLabel('Senha')], [$senha],
            [new TLabel('Confirmar Senha')], [$senha_confirm]
        );

        $this->form->appendPage('Grupos de Permissão');
        $this->form->addFields([$this->gruposChecklist]);

        // 4. Ações
        $btnSave = $this->form->addAction('Salvar Usuário', new TAction([$this, 'onSave']), 'fas:save');
        $btnSave->class = 'btn btn-primary';

        $btnBack = $this->form->addAction('Voltar', new TAction(['UsuarioList', 'onReload']), 'fas:arrow-left');
        $btnBack->class = 'btn btn-default';

        parent::add($this->form);
    }

    private function loadGruposDisponiveis()
    {
        TTransaction::open('libsql_admin');
        $grupos = AdminGrupo::where('delete_date', 'IS', NULL)
                            ->where('status', '=', 'ativo')
                            ->orderBy('nome')
                            ->load();
        $this->gruposChecklist->clear();
        if ($grupos) {
            foreach ($grupos as $grupo) {
                $item = new stdClass;
                $item->id        = $grupo->id;
                $item->nome      = $grupo->nome;
                $item->descricao = $grupo->descricao ?? '-';
                $this->gruposChecklist->addItem($item);
            }
        }
        TTransaction::close();
    }

    public function onEdit($param)
    {
        try {
            $this->loadGruposDisponiveis();

            if (isset($param['id'])) {
                $id = $param['id'];

                TTransaction::open('libsql_admin');

                $usuario = new AdminUsuario($id);

                if (empty($usuario->id) || !empty($usuario->delete_date)) {
                    throw new Exception('Usuário não localizado ou inativo.');
                }

                $data = new stdClass;
                $data->id      = $usuario->id;
                $data->nome    = $usuario->nome;
                $data->email   = $usuario->email;
                $data->celular = $usuario->celular;
                $data->theme   = $usuario->theme ?? 'light';
                $data->status  = $usuario->status;
                $data->cpf     = $usuario->get_cpf();

                $vinculos = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuario->id)
                                            ->where('delete_date', 'IS', NULL)
                                            ->load();

                $gruposMarcados = [];
                if ($vinculos) {
                    foreach ($vinculos as $vinculo) {
                        $gruposMarcados[] = $vinculo->admin_grupo_id;
                    }
                }
                $data->grupos_ids = $gruposMarcados;

                $this->form->setData($data);

                TTransaction::close();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onSave($param)
    {
        try {
            $this->form->validate();
            $data = $this->form->getData();

            if (empty($data->id) && empty($data->senha)) {
                throw new Exception('A senha é obrigatória para a criação de um novo usuário.');
            }

            if (!empty($data->senha)) {
                if (strlen($data->senha) < 4) {
                    throw new Exception('A senha deve possuir no mínimo 4 caracteres.');
                }
                if ($data->senha !== $data->senha_confirm) {
                    throw new Exception('A confirmação da senha não confere.');
                }
            }

            TTransaction::open('libsql_admin');

            $existente = AdminUsuario::where('email', '=', $data->email)
                                     ->where('delete_date', 'IS', NULL)
                                     ->first();

            if ($existente && (empty($data->id) || $existente->id != $data->id)) {
                throw new Exception('Já existe um usuário ativo com este e-mail.');
            }

            $usuario = new AdminUsuario;
            if (!empty($data->id)) {
                $usuario = new AdminUsuario($data->id);
            }

            $usuario->nome    = $data->nome;
            $usuario->email   = $data->email;
            $usuario->celular = $data->celular;
            $usuario->theme   = $data->theme;
            $usuario->status  = $data->status;

            $usuario->set_cpf($data->cpf);

            if (!empty($data->senha)) {
                $usuario->set_senha($data->senha);
            }

            $usuario->store();

            $gruposSelecionados = $data->grupos_ids ?? [];
            if (!is_array($gruposSelecionados)) {
                $gruposSelecionados = !empty($gruposSelecionados) ? [$gruposSelecionados] : [];
            }

            $vinculosAtuais = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuario->id)
                                               ->where('delete_date', 'IS', NULL)
                                               ->load();

            $vinculosAtuaisIds = [];
            if ($vinculosAtuais) {
                foreach ($vinculosAtuais as $v) {
                    $vinculosAtuaisIds[$v->admin_grupo_id] = $v;
                }
            }

            foreach ($vinculosAtuaisIds as $grupoId => $registroVinculo) {
                if (!in_array($grupoId, $gruposSelecionados)) {
                    $registroVinculo->delete();
                }
            }

            $userIdLogado = TSession::getValue('userid') ?? 1;
            foreach ($gruposSelecionados as $grupoId) {
                if (!isset($vinculosAtuaisIds[$grupoId])) {
                    $vinculoAntigo = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuario->id)
                                                      ->where('admin_grupo_id', '=', $grupoId)
                                                      ->first();

                    if ($vinculoAntigo) {
                        $vinculoAntigo->delete_date = null;
                        $vinculoAntigo->user_delete = null;
                        $vinculoAntigo->update_date = date('Y-m-d H:i:s');
                        $vinculoAntigo->user_update = $userIdLogado;
                        $vinculoAntigo->store();
                    } else {
                        $novoVinculo = new AdminUsuarioGrupo;
                        $novoVinculo->admin_usuario_id = $usuario->id;
                        $novoVinculo->admin_grupo_id   = $grupoId;
                        $novoVinculo->create_date      = date('Y-m-d H:i:s');
                        $novoVinculo->user_create      = $userIdLogado;
                        $novoVinculo->store();
                    }
                }
            }

            $data->id = $usuario->id;
            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', 'Usuário salvo com sucesso!', new TAction(['UsuarioList', 'onReload']));

        } catch (Exception $e) {
            TTransaction::rollback();
            $this->loadGruposDisponiveis();
            new TMessage('error', $e->getMessage());
        }
    }
}