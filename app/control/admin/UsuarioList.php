<?php

class UsuarioList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        // 1. Formulário de Filtro/Pesquisa
        $this->form = new BootstrapFormBuilder('form_search_usuario');
        $this->form->setFormTitle('<i class="fas fa-search"></i> Filtrar Utilizadores');

        $nome   = new TEntry('nome');
        $email  = new TEntry('email');
        $status = new TCombo('status');

        $nome->setSize('100%');
        $email->setSize('100%');
        $status->setSize('100%');

        $status->addItems([
            'ativo'     => 'Ativo',
            'bloqueado' => 'Bloqueado',
            'pendente'  => 'Pendente'
        ]);
        $status->enableSearch();

        $this->form->addFields(
            [new TLabel('Nome')], [$nome],
            [new TLabel('E-mail')], [$email],
            [new TLabel('Status')], [$status]
        );

        // Ações do Filtro
        $btnFind = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fas:search');
        $btnFind->class = 'btn btn-primary';

        $btnClean = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $btnClean->class = 'btn btn-default';

        if ($this->can('inserir')) {
            $btnNew = $this->form->addAction('Novo Utilizador', new TAction(['UsuarioForm', 'onEdit']), 'fas:plus-circle green');
            $btnNew->class = 'btn btn-success';
        }

        // 2. Grelha de Resultados (Datagrid)
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id      = new TDataGridColumn('id', 'ID', 'center', '60px');
        $col_nome    = new TDataGridColumn('nome', 'Nome Completo', 'left');
        $col_email   = new TDataGridColumn('email', 'E-mail / Login', 'left');
        $col_cpf     = new TDataGridColumn('cpf_exibicao', 'CPF', 'center', '140px');
        $col_grupos  = new TDataGridColumn('grupos_nomes', 'Grupos Vinculados', 'left');
        $col_theme   = new TDataGridColumn('theme_badge', 'Tema', 'center', '100px');
        $col_status  = new TDataGridColumn('status_badge', 'Status', 'center', '110px');

        // Formatação visual do Tema
        $col_theme->setTransformer(function($value, $object, $row) {
            return ($object->theme === 'dark') 
                ? '<span class="badge badge-dark px-2 py-1"><i class="fas fa-moon"></i> Dark</span>' 
                : '<span class="badge badge-light border px-2 py-1"><i class="fas fa-sun text-warning"></i> Light</span>';
        });

        // Formatação visual do Status
        $col_status->setTransformer(function($value, $object, $row) {
            $badges = [
                'ativo'     => '<span class="badge badge-success px-2 py-1">Ativo</span>',
                'bloqueado' => '<span class="badge badge-danger px-2 py-1">Bloqueado</span>',
                'pendente'  => '<span class="badge badge-warning px-2 py-1">Pendente</span>'
            ];
            return $badges[$object->status] ?? $object->status;
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_email);
        $this->datagrid->addColumn($col_cpf);
        $this->datagrid->addColumn($col_grupos);
        $this->datagrid->addColumn($col_theme);
        $this->datagrid->addColumn($col_status);

        // Ações por Linha
        if ($this->can('editar') || $this->can('ler')) {
            $actionEdit = new TDataGridAction(['UsuarioForm', 'onEdit'], ['id' => '{id}']);
            $this->datagrid->addAction($actionEdit, 'Editar', 'far:edit blue');
        }

        if ($this->can('deletar')) {
            $actionDelete = new TDataGridAction([$this, 'onDeletePrompt'], ['id' => '{id}']);
            $this->datagrid->addAction($actionDelete, 'Desativar', 'far:trash-alt red');
        }

        $this->datagrid->createModel();

        // 3. Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->enableCounters();

        // 4. Estrutura Vertical
        $box = new TVBox;
        $box->style = 'width: 100%';
        $box->add($this->form);
        $box->add($this->datagrid);
        $box->add($this->pageNavigation);

        parent::add($box);
    }

    public function onSearch($param)
    {
        $data = $this->form->getData();
        $this->form->setData($data);

        $filter = new TCriteria;

        if (!empty($data->nome)) {
            $filter->add(new TFilter('nome', 'like', "%{$data->nome}%"));
        }
        if (!empty($data->email)) {
            $filter->add(new TFilter('email', 'like', "%{$data->email}%"));
        }
        if (!empty($data->status)) {
            $filter->add(new TFilter('status', '=', $data->status));
        }

        TSession::setValue(__CLASS__ . '_filter', $filter);
        TSession::setValue(__CLASS__ . '_data', $data);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onClear()
    {
        $this->form->clear();
        TSession::setValue(__CLASS__ . '_filter', null);
        TSession::setValue(__CLASS__ . '_data', null);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onReload($param = null)
    {
        try {
            TTransaction::open('libsql_admin');

            $repository = new TRepository('AdminUsuario');
            $limit = 10;

            $criteria = TSession::getValue(__CLASS__ . '_filter');
            if (empty($criteria)) {
                $criteria = new TCriteria;
            }

            // Exclui registos marcados com exclusão lógica (soft delete)
            $criteria->add(new TFilter('delete_date', 'IS', NULL));

            if (empty($param['order'])) {
                $criteria->setProperty('order', 'id');
                $criteria->setProperty('direction', 'desc');
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $usuarios = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($usuarios) {
                foreach ($usuarios as $usuario) {
                    $item = new stdClass;
                    $item->id           = $usuario->id;
                    $item->nome         = $usuario->nome;
                    $item->email        = $usuario->email;
                    $item->theme        = $usuario->theme ?? 'light';
                    $item->status       = $usuario->status;

                    // Desencriptação transparente do CPF via getter do model
                    $item->cpf_exibicao = $usuario->get_cpf() ?: '-';

                    // Resgata os grupos vinculados
                    $grupos = $usuario->getGrupos();
                    $nomesGrupos = array_map(fn($g) => $g->nome, $grupos);
                    $item->grupos_nomes = !empty($nomesGrupos) ? implode(', ', $nomesGrupos) : '<span class="text-muted">Nenhum</span>';

                    $this->datagrid->addItem($item);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($limit);

            TTransaction::close();

            $data = TSession::getValue(__CLASS__ . '_data');
            if ($data) {
                $this->form->setData($data);
            }

            $this->loaded = true;
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onDeletePrompt($param)
    {
        $id = $param['id'] ?? null;
        if ($id) {
            if ($id == TSession::getValue('userid')) {
                new TMessage('error', 'Não é permitido desativar o próprio utilizador autenticado.');
                return;
            }
            $action = new TAction([$this, 'onDeleteConfirm'], ['id' => $id]);
            new TQuestion("Deseja realmente desativar o utilizador #{$id}?", $action);
        }
    }

    public function onDeleteConfirm($param)
    {
        try {
            $id = $param['id'] ?? null;
            if ($id) {
                TTransaction::open('libsql_admin');
                $usuario = new AdminUsuario($id);
                $usuario->delete();
                TTransaction::close();

                new TMessage('info', 'Utilizador desativado com sucesso!');
                $this->onReload();
            }
        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private function can($action)
    {
        $permissions = TSession::getValue('user_permissions') ?? [];
        return !empty($permissions['UsuarioList'][$action]) || !empty($permissions['UsuarioForm'][$action]);
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}