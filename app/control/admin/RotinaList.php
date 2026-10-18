<?php

class RotinaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        // 1. Filtro de Busca
        $this->form = new BootstrapFormBuilder('form_search_rotina');
        $this->form->setFormTitle('<i class="fas fa-search"></i> Filtrar Rotinas do Sistema');

        $nome       = new TEntry('nome');
        $controller = new TEntry('controller');
        $modulo     = new TEntry('modulo');

        $nome->setSize('100%');
        $controller->setSize('100%');
        $modulo->setSize('100%');

        $this->form->addFields(
            [new TLabel('Nome da Rotina')], [$nome],
            [new TLabel('Controller (Classe)')], [$controller],
            [new TLabel('Módulo Agrupador')], [$modulo]
        );

        $btnFind = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fas:search');
        $btnFind->class = 'btn btn-primary';

        $btnClean = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $btnClean->class = 'btn btn-default';

        if ($this->can('inserir')) {
            $btnNew = $this->form->addAction('Nova Rotina', new TAction(['RotinaForm', 'onEdit']), 'fas:plus-circle green');
            $btnNew->class = 'btn btn-success';
        }

        // 2. Grelha de Resultados (Datagrid)
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id      = new TDataGridColumn('id', 'ID', 'center', '60px');
        $col_ordem   = new TDataGridColumn('ordem', 'Ordem', 'center', '70px');
        $col_modulo  = new TDataGridColumn('modulo_badge', 'Módulo', 'left', '150px');
        $col_nome    = new TDataGridColumn('nome_fmt', 'Nome da Rotina', 'left', '220px');
        $col_ctrl    = new TDataGridColumn('controller_fmt', 'Controller', 'left');
        $col_menu    = new TDataGridColumn('menu_badge', 'No Menu', 'center', '100px');

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_ordem);
        $this->datagrid->addColumn($col_modulo);
        $this->datagrid->addColumn($col_nome);
        $this->datagrid->addColumn($col_ctrl);
        $this->datagrid->addColumn($col_menu);

        if ($this->can('editar') || $this->can('ler')) {
            $actionEdit = new TDataGridAction(['RotinaForm', 'onEdit'], ['id' => '{id}']);
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
        if (!empty($data->controller)) {
            $filter->add(new TFilter('controller', 'like', "%{$data->controller}%"));
        }
        if (!empty($data->modulo)) {
            $filter->add(new TFilter('modulo', 'like', "%{$data->modulo}%"));
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

            $repository = new TRepository('AdminRotina');
            $limit = 12;

            $criteria = TSession::getValue(__CLASS__ . '_filter');
            if (empty($criteria)) {
                $criteria = new TCriteria;
            }

            $criteria->add(new TFilter('delete_date', 'IS', NULL));

            if (empty($param['order'])) {
                $criteria->setProperty('order', 'modulo, ordem');
                $criteria->setProperty('direction', 'asc');
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $rotinas = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($rotinas) {
                foreach ($rotinas as $rotina) {
                    $item = new stdClass;
                    $item->id             = $rotina->id;
                    $item->ordem          = $rotina->ordem;
                    $item->modulo_badge   = '<span class="badge bg-secondary">' . ($rotina->modulo ?: 'Geral') . '</span>';
                    
                    $iconeFmt = !empty($rotina->icone) ? str_replace(':', ' ', $rotina->icone) : 'fas fa-circle';
                    $item->nome_fmt       = "<i class=\"{$iconeFmt} me-2 text-muted\"></i>" . htmlspecialchars($rotina->nome);
                    $item->controller_fmt = '<code>' . htmlspecialchars($rotina->controller) . '</code>';
                    
                    $item->menu_badge     = ($rotina->visivel_menu == 1)
                        ? '<span class="badge bg-success px-2 py-1"><i class="fas fa-eye"></i> Sim</span>'
                        : '<span class="badge bg-light text-dark border px-2 py-1"><i class="fas fa-eye-slash"></i> Oculto</span>';

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
            $action = new TAction([$this, 'onDeleteConfirm'], ['id' => $id]);
            new TQuestion("Deseja realmente desativar a rotina #{$id}?", $action);
        }
    }

    public function onDeleteConfirm($param)
    {
        try {
            $id = $param['id'] ?? null;
            if ($id) {
                TTransaction::open('libsql_admin');
                $rotina = new AdminRotina($id);
                $rotina->delete();
                TTransaction::close();

                new TMessage('info', 'Rotina desativada com sucesso!');
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
        return !empty($permissions['RotinaList'][$action]) || !empty($permissions['RotinaForm'][$action]);
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}