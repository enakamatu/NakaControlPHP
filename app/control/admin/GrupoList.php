<?php

class GrupoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        // 1. Formulário de Filtro/Busca
        $this->form = new BootstrapFormBuilder('form_search_grupo');
        $this->form->setFormTitle('<i class="fas fa-search"></i> Filtrar Grupos de Usuários');

        $nome   = new TEntry('nome');
        $status = new TCombo('status');

        $nome->setSize('100%');
        $status->setSize('100%');

        $status->addItems([
            'ativo'   => 'Ativo',
            'inativo' => 'Inativo'
        ]);
        $status->enableSearch();

        $this->form->addFields(
            [new TLabel('Nome do Perfil / Grupo')], [$nome],
            [new TLabel('Status')], [$status]
        );

        $btnFind = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fas:search');
        $btnFind->class = 'btn btn-primary';

        $btnClean = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $btnClean->class = 'btn btn-default';

        if ($this->can('inserir')) {
            $btnNew = $this->form->addAction('Novo Grupo', new TAction(['GrupoForm', 'onEdit']), 'fas:plus-circle green');
            $btnNew->class = 'btn btn-success';
        }

        // 2. Grid de Dados
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id        = new TDataGridColumn('id', 'ID', 'center', '60px');
        $col_nome      = new TDataGridColumn('nome', 'Nome do Grupo', 'left', '220px');
        $col_desc      = new TDataGridColumn('descricao', 'Descrição', 'left');
        $col_rotinas   = new TDataGridColumn('total_rotinas', 'Rotinas Liberadas', 'center', '140px');
        $col_usuarios  = new TDataGridColumn('total_usuarios', 'Membros Ativos', 'center', '130px');
        $col_status    = new TDataGridColumn('status_badge', 'Status', 'center', '100px');

        $col_status->setTransformer(function($value, $object, $row) {
            return ($object->status === 'ativo') 
                ? '<span class="badge bg-success px-2 py-1">Ativo</span>' 
                : '<span class="badge bg-danger px-2 py-1">Inativo</span>';
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_desc);
        $this->datagrid->addColumn($col_rotinas);
        $this->datagrid->addColumn($col_usuarios);
        $this->datagrid->addColumn($col_status);

        if ($this->can('editar') || $this->can('ler')) {
            $actionEdit = new TDataGridAction(['GrupoForm', 'onEdit'], ['id' => '{id}']);
            $this->datagrid->addAction($actionEdit, 'Editar Permissões', 'far:edit blue');
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

            $repository = new TRepository('AdminGrupo');
            $limit = 10;

            $criteria = TSession::getValue(__CLASS__ . '_filter');
            if (empty($criteria)) {
                $criteria = new TCriteria;
            }

            $criteria->add(new TFilter('delete_date', 'IS', NULL));

            if (empty($param['order'])) {
                $criteria->setProperty('order', 'id');
                $criteria->setProperty('direction', 'asc');
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $grupos = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($grupos) {
                foreach ($grupos as $grupo) {
                    $item = new stdClass;
                    $item->id        = $grupo->id;
                    $item->nome      = $grupo->nome;
                    $item->descricao = $grupo->descricao ?? '-';
                    $item->status    = $grupo->status;

                    // Quantidade de rotinas ativas liberadas para este grupo
                    $totalRotinas = AdminGrupoRotina::where('admin_grupo_id', '=', $grupo->id)
                                                    ->where('delete_date', 'IS', NULL)
                                                    ->where('pode_ler', '=', 1)
                                                    ->count();
                    $item->total_rotinas = '<span class="badge bg-info">' . $totalRotinas . ' rotinas</span>';

                    // Quantidade de membros vinculados
                    $totalMembros = AdminUsuarioGrupo::where('admin_grupo_id', '=', $grupo->id)
                                                     ->where('delete_date', 'IS', NULL)
                                                     ->count();
                    $item->total_usuarios = '<span class="badge bg-secondary">' . $totalMembros . ' usuários</span>';

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
            if ($id == 1) {
                new TMessage('error', 'O grupo Administradores principal não pode ser desativado.');
                return;
            }
            $action = new TAction([$this, 'onDeleteConfirm'], ['id' => $id]);
            new TQuestion("Deseja realmente desativar o grupo #{$id}?", $action);
        }
    }

    public function onDeleteConfirm($param)
    {
        try {
            $id = $param['id'] ?? null;
            if ($id) {
                TTransaction::open('libsql_admin');
                $grupo = new AdminGrupo($id);
                $grupo->delete();
                TTransaction::close();

                new TMessage('info', 'Grupo desativado com sucesso!');
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
        return !empty($permissions['GrupoList'][$action]) || !empty($permissions['GrupoForm'][$action]);
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}