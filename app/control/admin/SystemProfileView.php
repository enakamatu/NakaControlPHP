<?php

class SystemProfileView extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_profile');
        $this->form->setFormTitle('<i class="fas fa-user-cog"></i> Meu Perfil e Preferências');

        // 1. Definição dos campos
        $id       = new THidden('id');
        $nome     = new TEntry('nome');
        $email    = new TEntry('email');
        $cpf      = new TEntry('cpf');
        $celular  = new TEntry('celular');
        $theme    = new TCombo('theme');
        $senha    = new TPassword('senha');
        $senha_cf = new TPassword('senha_confirm');

        $nome->setSize('100%');
        $email->setSize('100%');
        $cpf->setSize('100%');
        $celular->setSize('100%');
        $theme->setSize('100%');
        $senha->setSize('100%');
        $senha_cf->setSize('100%');

        $theme->addItems([
            'light' => 'Tema Claro (Light)',
            'dark'  => 'Tema Escuro (Dark)'
        ]);
        $theme->setDefaultOption(false);

        $email->setEditable(false);
        $cpf->setEditable(false);

        $celular->setMask('(99) 99999-9999');
        $senha->placeholder    = 'Preencha apenas se desejar trocar';
        $senha_cf->placeholder = 'Confirme a nova senha';

        $nome->addValidation('Nome Completo', new TRequiredValidator);
        $theme->addValidation('Tema Padrão', new TRequiredValidator);

        // 2. Montagem dos campos
        $this->form->addFields([$id]);

        $this->form->addContent(['<h5 class="text-primary mb-3"><i class="fas fa-id-card"></i> Dados Cadastrais</h5>']);
        $this->form->addFields(
            [new TLabel('Nome Completo <span class="text-danger">*</span>')], [$nome],
            [new TLabel('E-mail (Login)')], [$email]
        );

        $this->form->addFields(
            [new TLabel('CPF')], [$cpf],
            [new TLabel('Celular / WhatsApp')], [$celular]
        );

        $this->form->addContent(['<hr><h5 class="text-primary mb-3"><i class="fas fa-palette"></i> Preferências Visuais</h5>']);
        $this->form->addFields(
            [new TLabel('Tema Padrão do Sistema <span class="text-danger">*</span>')], [$theme],
            [], []
        );

        $this->form->addContent(['<hr><h5 class="text-primary mb-3"><i class="fas fa-key"></i> Alteração de Senha (Opcional)</h5>']);
        $this->form->addFields(
            [new TLabel('Nova Senha')], [$senha],
            [new TLabel('Confirmação da Nova Senha')], [$senha_cf]
        );

        // 3. Ações
        $btnSave = $this->form->addAction('Salvar Preferências', new TAction([$this, 'onSave']), 'fas:save');
        $btnSave->class = 'btn btn-primary';

        $btnCancel = $this->form->addAction('Voltar', new TAction(['HomeView', 'onReload']), 'fas:arrow-left');
        $btnCancel->class = 'btn btn-default';

        parent::add($this->form);

        $this->onLoad();
    }

    public function onLoad()
    {
        try {
            $userId = TSession::getValue('userid');
            if (empty($userId)) {
                throw new Exception('Sessão expirada ou utilizador não autenticado.');
            }

            TTransaction::open('libsql_admin');

            $usuario = new AdminUsuario($userId);

            if (empty($usuario->id) || !empty($usuario->delete_date)) {
                throw new Exception('Utilizador não localizado na base administrativa.');
            }

            $data = new stdClass;
            $data->id      = $usuario->id;
            $data->nome    = $usuario->nome;
            $data->email   = $usuario->email;
            $data->cpf     = $usuario->get_cpf();
            $data->celular = $usuario->celular;
            $data->theme   = $usuario->theme ?? 'light';

            $this->form->setData($data);

            TTransaction::close();
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

            $userId = TSession::getValue('userid');
            if (empty($userId) || $data->id != $userId) {
                throw new Exception('Operação de gravação não autorizada.');
            }

            if (!empty($data->senha)) {
                if (strlen($data->senha) < 4) {
                    throw new Exception('A nova senha deve ter no mínimo 4 caracteres.');
                }
                if ($data->senha !== $data->senha_confirm) {
                    throw new Exception('A confirmação da nova senha não confere.');
                }
            }

            TTransaction::open('libsql_admin');

            $usuario = new AdminUsuario($userId);
            $usuario->nome    = $data->nome;
            $usuario->celular = $data->celular;
            $usuario->theme   = $data->theme;

            if (!empty($data->senha)) {
                $usuario->set_senha($data->senha);
            }

            $usuario->user_update = $userId;
            $usuario->update_date = date('Y-m-d H:i:s');
            $usuario->store();

            TSession::setValue('username', $usuario->nome);
            TSession::setValue('theme', $usuario->theme);

            TTransaction::close();

            new TMessage('info', 'Perfil e preferências atualizados com sucesso!', new TAction(['SystemProfileView', 'onLoad']));

            AdiantiCoreApplication::gotoPage('SystemProfileView');

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}