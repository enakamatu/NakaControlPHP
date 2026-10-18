<?php

class GrupoForm extends TPage
{
    protected $form;

    public function __construct()
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder('form_grupo');
        $this->form->setFormTitle('<i class="fas fa-users-cog"></i> Gestão de Grupos e Permissões');

        // 1. Campos Básicos
        $id        = new THidden('id');
        $nome      = new TEntry('nome');
        $status    = new TCombo('status');
        $descricao = new TText('descricao');

        $nome->setSize('100%');
        $status->setSize('100%');
        $descricao->setSize('100%', 70);

        $status->addItems([
            'ativo'   => 'Ativo',
            'inativo' => 'Inativo'
        ]);
        $status->setDefaultOption(false);
        $status->setValue('ativo');

        $nome->addValidation('Nome do Grupo', new TRequiredValidator);
        $status->addValidation('Status', new TRequiredValidator);

        // 2. Aba de Identificação
        $this->form->appendPage('Dados do Perfil');
        $this->form->addFields([$id]);
        $this->form->addFields(
            [new TLabel('Nome do Perfil <span class="text-danger">*</span>')], [$nome],
            [new TLabel('Status <span class="text-danger">*</span>')], [$status]
        );
        $this->form->addFields(
            [new TLabel('Descrição / Finalidade')], [$descricao]
        );

        // 3. Aba de Matriz de Permissões
        $this->form->appendPage('Matriz de Permissões de Rotina');

        $containerMatriz = new TElement('div');
        $containerMatriz->class = 'table-responsive mt-2';
        $containerMatriz->style = 'max-height: 480px; overflow-y: auto;';

        $table = new TElement('table');
        $table->class = 'table table-hover table-striped table-bordered text-center align-middle';
        $table->style = 'width: 100%; font-size: 0.9rem;';

        $thead = new TElement('thead');
        $thead->class = 'thead-light';
        $thead->add('<tr>
            <th class="text-left" style="width: 250px;">Rotina / Recurso</th>
            <th class="text-left" style="width: 180px;">Controlador</th>
            <th class="text-left" style="width: 150px;">Módulo</th>
            <th style="width: 80px;" title="Leitura / Visualização"><i class="fas fa-eye text-info"></i> Ler</th>
            <th style="width: 80px;" title="Criação / Inserção"><i class="fas fa-plus text-success"></i> Inserir</th>
            <th style="width: 80px;" title="Modificação"><i class="fas fa-edit text-warning"></i> Editar</th>
            <th style="width: 80px;" title="Exclusão"><i class="fas fa-trash text-danger"></i> Deletar</th>
        </tr>');
        $table->add($thead);

        $tbody = new TElement('tbody');

        TTransaction::open('libsql_admin');
        $rotinas = AdminRotina::where('delete_date', 'IS', NULL)
                              ->orderBy('modulo')
                              ->orderBy('ordem')
                              ->load();

        if ($rotinas) {
            foreach ($rotinas as $rotina) {
                $tr = new TElement('tr');

                $tdNome = new TElement('td');
                $tdNome->class = 'text-left font-weight-bold';
                $tdNome->add("<i class='{$rotina->icone} mr-1 text-muted'></i> {$rotina->nome}");

                $tdCtrl = new TElement('td');
                $tdCtrl->class = 'text-left text-monospace text-muted';
                $tdCtrl->add("<small>{$rotina->controller}</small>");

                $tdMod = new TElement('td');
                $tdMod->class = 'text-left';
                $tdMod->add("<span class='badge badge-light border'>{$rotina->modulo}</span>");

                $chkLer = new TCheckButton("perm_{$rotina->id}_ler");
                $chkLer->setIndexValue(1);

                $chkIns = new TCheckButton("perm_{$rotina->id}_inserir");
                $chkIns->setIndexValue(1);

                $chkEdt = new TCheckButton("perm_{$rotina->id}_editar");
                $chkEdt->setIndexValue(1);

                $chkDel = new TCheckButton("perm_{$rotina->id}_deletar");
                $chkDel->setIndexValue(1);

                $this->form->addField($chkLer);
                $this->form->addField($chkIns);
                $this->form->addField($chkEdt);
                $this->form->addField($chkDel);

                $tdLer = new TElement('td');
                $tdLer->add($chkLer);

                $tdIns = new TElement('td');
                $tdIns->add($chkIns);

                $tdEdt = new TElement('td');
                $tdEdt->add($chkEdt);

                $tdDel = new TElement('td');
                $tdDel->add($chkDel);

                $tr->add($tdNome);
                $tr->add($tdCtrl);
                $tr->add($tdMod);
                $tr->add($tdLer);
                $tr->add($tdIns);
                $tr->add($tdEdt);
                $tr->add($tdDel);

                $tbody->add($tr);
            }
        }
        TTransaction::close();

        $table->add($tbody);
        $containerMatriz->add($table);
        $this->form->addFields([$containerMatriz]);

        // 4. Ações
        $btnSave = $this->form->addAction('Salvar Perfil e Permissões', new TAction([$this, 'onSave']), 'fas:save');
        $btnSave->class = 'btn btn-primary';

        $btnBack = $this->form->addAction('Voltar', new TAction(['GrupoList', 'onReload']), 'fas:arrow-left');
        $btnBack->class = 'btn btn-default';

        parent::add($this->form);
    }

    public function onEdit($param)
    {
        try {
            if (isset($param['id'])) {
                $id = $param['id'];

                TTransaction::open('libsql_admin');

                $grupo = new AdminGrupo($id);
                if (empty($grupo->id) || !empty($grupo->delete_date)) {
                    throw new Exception('Grupo não localizado ou inativo.');
                }

                $data = new stdClass;
                $data->id        = $grupo->id;
                $data->nome      = $grupo->nome;
                $data->status    = $grupo->status;
                $data->descricao = $grupo->descricao;

                $permissoes = AdminGrupoRotina::where('admin_grupo_id', '=', $grupo->id)
                                              ->where('delete_date', 'IS', NULL)
                                              ->load();

                if ($permissoes) {
                    foreach ($permissoes as $p) {
                        $fLer = "perm_{$p->admin_rotina_id}_ler";
                        $fIns = "perm_{$p->admin_rotina_id}_inserir";
                        $fEdt = "perm_{$p->admin_rotina_id}_editar";
                        $fDel = "perm_{$p->admin_rotina_id}_deletar";

                        $data->$fLer = $p->pode_ler;
                        $data->$fIns = $p->pode_inserir;
                        $data->$fEdt = $p->pode_editar;
                        $data->$fDel = $p->pode_deletar;
                    }
                }

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

            TTransaction::open('libsql_admin');

            $grupo = new AdminGrupo;
            if (!empty($data->id)) {
                $grupo = new AdminGrupo($data->id);
            }

            $grupo->nome      = $data->nome;
            $grupo->status    = $data->status;
            $grupo->descricao = $data->descricao;
            $grupo->store();

            $userId = TSession::getValue('userid') ?? 1;
            $now = date('Y-m-d H:i:s');

            $rotinas = AdminRotina::where('delete_date', 'IS', NULL)->load();

            if ($rotinas) {
                foreach ($rotinas as $rotina) {
                    $podeLer     = !empty($param["perm_{$rotina->id}_ler"]) ? 1 : 0;
                    $podeInserir = !empty($param["perm_{$rotina->id}_inserir"]) ? 1 : 0;
                    $podeEditar  = !empty($param["perm_{$rotina->id}_editar"]) ? 1 : 0;
                    $podeDeletar = !empty($param["perm_{$rotina->id}_deletar"]) ? 1 : 0;

                    $perm = AdminGrupoRotina::where('admin_grupo_id', '=', $grupo->id)
                                            ->where('admin_rotina_id', '=', $rotina->id)
                                            ->first();

                    if ($podeLer || $podeInserir || $podeEditar || $podeDeletar) {
                        if (!$perm) {
                            $perm = new AdminGrupoRotina;
                            $perm->admin_grupo_id  = $grupo->id;
                            $perm->admin_rotina_id = $rotina->id;
                            $perm->create_date     = $now;
                            $perm->user_create     = $userId;
                        } else {
                            $perm->delete_date = null;
                            $perm->user_delete = null;
                            $perm->update_date = $now;
                            $perm->user_update = $userId;
                        }

                        $perm->pode_ler     = $podeLer;
                        $perm->pode_inserir = $podeInserir;
                        $perm->pode_editar  = $podeEditar;
                        $perm->pode_deletar = $podeDeletar;
                        $perm->store();

                    } else {
                        if ($perm && empty($perm->delete_date)) {
                            $perm->delete();
                        }
                    }
                }
            }

            $data->id = $grupo->id;
            $this->form->setData($data);

            TTransaction::close();

            new TMessage('info', 'Perfil e permissões gravados com sucesso!', new TAction(['GrupoList', 'onReload']));

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}