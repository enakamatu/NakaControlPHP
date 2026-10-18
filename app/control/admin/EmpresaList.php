<?php

class EmpresaList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        // 1. Formulário de Filtro/Busca
        $this->form = new BootstrapFormBuilder('form_search_empresa');
        $this->form->setFormTitle('<i class="fas fa-search"></i> Filtrar Empresas');

        $razao_social = new TEntry('razao_social');
        $status       = new TCombo('status');

        $razao_social->setSize('100%');
        $status->setSize('100%');

        $status->addItems([
            'ativo'    => 'Ativo',
            'inativo'  => 'Inativo',
            'suspenso' => 'Suspenso'
        ]);
        $status->enableSearch();

        $this->form->addFields(
            [new TLabel('Razão Social / Fantasia')], [$razao_social],
            [new TLabel('Status')], [$status]
        );

        // Ações do Filtro
        $btnFind = $this->form->addAction('Buscar', new TAction([$this, 'onSearch']), 'fas:search');
        $btnFind->class = 'btn btn-primary';

        $btnClean = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $btnClean->class = 'btn btn-default';

        // Botão Novo Registro (respeita a matriz de permissões)
        if ($this->can('inserir')) {
            $btnNew = $this->form->addAction('Nova Empresa', new TAction(['EmpresaForm', 'onEdit']), 'fas:plus-circle green');
            $btnNew->class = 'btn btn-success';
        }

        // 2. Montagem da Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id      = new TDataGridColumn('id', 'ID', 'center', '60px');
        $col_razao   = new TDataGridColumn('razao_social', 'Razão Social', 'left');
        $col_fantasia= new TDataGridColumn('nome_fantasia', 'Nome Fantasia', 'left');
        $col_cnpj    = new TDataGridColumn('cnpj_exibicao', 'CNPJ', 'center', '160px');
        $col_cidade  = new TDataGridColumn('cidade_uf', 'Cidade / UF', 'left', '180px');
        $col_status  = new TDataGridColumn('status_badge', 'Status', 'center', '100px');

        // Formatação visual da coluna de status
        $col_status->setTransformer(function($value, $object, $row) {
            $badges = [
                'ativo'    => '<span class="badge badge-success px-2 py-1">Ativo</span>',
                'inativo'  => '<span class="badge badge-danger px-2 py-1">Inativo</span>',
                'suspenso' => '<span class="badge badge-warning px-2 py-1">Suspenso</span>'
            ];
            return $badges[$object->status] ?? $object->status;
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_razao);
        $this->datagrid->addColumn($col_fantasia);
        $this->datagrid->addColumn($col_cnpj);
        $this->datagrid->addColumn($col_cidade);
        $this->datagrid->addColumn($col_status);

        // Ação de Edição
        if ($this->can('editar') || $this->can('ler')) {
            $actionEdit = new TDataGridAction(['EmpresaForm', 'onEdit'], ['id' => '{id}']);
            $this->datagrid->addAction($actionEdit, 'Editar', 'far:edit blue');
        }

        // Ação de Exclusão (Soft Delete)
        if ($this->can('deletar')) {
            $actionDelete = new TDataGridAction([$this, 'onDeletePrompt'], ['id' => '{id}']);
            $this->datagrid->addAction($actionDelete, 'Excluir', 'far:trash-alt red');
        }

        $this->datagrid->createModel();

        // 3. Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->enableCounters();

        // 4. Empacotamento Vertical
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

        if (!empty($data->razao_social)) {
            $filter->add(new TFilter('razao_social', 'like', "%{$data->razao_social}%"));
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

            $repository = new TRepository('AdminEmpresa');
            $limit = 10;

            $criteria = TSession::getValue(__CLASS__ . '_filter');
            if (empty($criteria)) {
                $criteria = new TCriteria;
            }

            // OBRIGATÓRIO: Apenas registros não excluídos
            $criteria->add(new TFilter('delete_date', 'IS', NULL));

            // Filtro Multiempresa
            $userEmpresas = TSession::getValue('user_empresas') ?? [];
            if (!empty($userEmpresas)) {
                $criteria->add(new TFilter('id', 'IN', $userEmpresas));
            }

            if (empty($param['order'])) {
                $criteria->setProperty('order', 'id');
                $criteria->setProperty('direction', 'desc');
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $empresas = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($empresas) {
                foreach ($empresas as $empresa) {
                    $item = new stdClass;
                    $item->id            = $empresa->id;
                    $item->razao_social  = $empresa->razao_social;
                    $item->nome_fantasia = $empresa->nome_fantasia ?? '-';
                    $item->status        = $empresa->status;
                    $item->cidade_uf     = ($empresa->cidade ? $empresa->cidade : '') . ($empresa->uf ? ' / ' . $empresa->uf : '-');

                    // Decripta em memória para visualização segura no grid
                    $item->cnpj_exibicao = $empresa->get_cnpj() ?: '-';

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
            new TQuestion("Deseja realmente desativar a empresa #{$id}?", $action);
        }
    }

    public function onDeleteConfirm($param)
    {
        try {
            $id = $param['id'] ?? null;
            if ($id) {
                TTransaction::open('libsql_admin');
                $empresa = new AdminEmpresa($id);
                $empresa->delete();
                TTransaction::close();

                new TMessage('info', 'Empresa desativada com sucesso!');
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
        return !empty($permissions['EmpresaList'][$action]) || !empty($permissions['EmpresaForm'][$action]);
    }

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}