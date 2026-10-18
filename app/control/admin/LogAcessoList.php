<?php

class LogAcessoList extends TPage
{
    protected $form;
    protected $datagrid;
    protected $pageNavigation;
    protected $loaded;

    public function __construct()
    {
        parent::__construct();

        // 1. Formulário de Filtros de Auditoria
        $this->form = new BootstrapFormBuilder('form_search_log_acesso');
        $this->form->setFormTitle('<i class="fas fa-history"></i> Auditoria de Logs de Acesso');

        $login_attempt = new TEntry('login_attempt');
        $data_inicio   = new TDate('data_inicio');
        $data_fim      = new TDate('data_fim');
        $status_acesso = new TCombo('status_acesso');
        $canal_acesso  = new TCombo('canal_acesso');

        $login_attempt->setSize('100%');
        $data_inicio->setSize('100%');
        $data_fim->setSize('100%');
        $status_acesso->setSize('100%');
        $canal_acesso->setSize('100%');

        $login_attempt->placeholder = 'E-mail informado no login';

        // Configuração dos seletores de data
        $data_inicio->setMask('dd/mm/yyyy');
        $data_inicio->setDatabaseMask('yyyy-mm-dd');
        $data_fim->setMask('dd/mm/yyyy');
        $data_fim->setDatabaseMask('yyyy-mm-dd');

        // Opções de Status
        $status_acesso->addItems([
            'sucesso'                => 'Sucesso',
            'credenciais_invalidas'  => 'Credenciais Inválidas',
            'bloqueado'              => 'Usuário Bloqueado/Inativo',
            'expirado'               => 'Sessão Expirada'
        ]);
        $status_acesso->enableSearch();

        // Opções de Canal
        $canal_acesso->addItems([
            'web'        => 'Portal Web',
            'mobile_app' => 'App Mobile',
            'api'        => 'Integração API'
        ]);
        $canal_acesso->enableSearch();

        // Linhas de Filtro
        $this->form->addFields(
            [new TLabel('Login / E-mail')], [$login_attempt],
            [new TLabel('Canal de Acesso')], [$canal_acesso]
        );

        $this->form->addFields(
            [new TLabel('Data Inicial')], [$data_inicio],
            [new TLabel('Data Final')], [$data_fim],
            [new TLabel('Status da Tentativa')], [$status_acesso]
        );

        // Ações de Busca e Limpeza
        $btnFind = $this->form->addAction('Filtrar Logs', new TAction([$this, 'onSearch']), 'fas:search');
        $btnFind->class = 'btn btn-primary';

        $btnClean = $this->form->addAction('Limpar', new TAction([$this, 'onClear']), 'fas:eraser red');
        $btnClean->class = 'btn btn-default';

        // 2. Definição da Datagrid
        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(360);

        $col_id      = new TDataGridColumn('id', 'ID', 'center', '60px');
        $col_login   = new TDataGridColumn('login_attempt', 'Login Informado', 'left');
        $col_user    = new TDataGridColumn('usuario_nome', 'Usuário Vinculado', 'left');
        $col_dtlogin = new TDataGridColumn('data_login_fmt', 'Data/Hora Login', 'center', '150px');
        $col_dtout   = new TDataGridColumn('data_logout_fmt', 'Data/Hora Logout', 'center', '150px');
        $col_ip      = new TDataGridColumn('ip_address', 'Endereço IP', 'center', '130px');
        $col_canal   = new TDataGridColumn('canal_badge', 'Canal', 'center', '110px');
        $col_status  = new TDataGridColumn('status_badge', 'Status', 'center', '150px');

        // Formatação visual do Canal (Bootstrap 5 badges)
        $col_canal->setTransformer(function($value, $object, $row) {
            $badges = [
                'web'        => '<span class="badge bg-light text-dark border"><i class="fas fa-desktop"></i> Web</span>',
                'mobile_app' => '<span class="badge bg-info text-white"><i class="fas fa-mobile-alt"></i> Mobile</span>',
                'api'        => '<span class="badge bg-dark"><i class="fas fa-network-wired"></i> API</span>'
            ];
            return $badges[$object->canal_acesso] ?? $object->canal_acesso;
        });

        // Formatação visual do Status da tentativa
        $col_status->setTransformer(function($value, $object, $row) {
            $badges = [
                'sucesso'               => '<span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle"></i> Sucesso</span>',
                'credenciais_invalidas' => '<span class="badge bg-danger px-2 py-1"><i class="fas fa-times-circle"></i> Inválido</span>',
                'bloqueado'             => '<span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-ban"></i> Bloqueado</span>',
                'expirado'              => '<span class="badge bg-secondary px-2 py-1"><i class="fas fa-clock"></i> Expirado</span>'
            ];
            return $badges[$object->status_acesso] ?? $object->status_acesso;
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_login);
        $this->datagrid->addColumn($col_user);
        $this->datagrid->addColumn($col_dtlogin);
        $this->datagrid->addColumn($col_dtout);
        $this->datagrid->addColumn($col_ip);
        $this->datagrid->addColumn($col_canal);
        $this->datagrid->addColumn($col_status);

        $this->datagrid->createModel();

        // 3. Paginação
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->enableCounters();

        // 4. Montagem estrutural
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

        if (!empty($data->login_attempt)) {
            $filter->add(new TFilter('login_attempt', 'like', "%{$data->login_attempt}%"));
        }
        if (!empty($data->status_acesso)) {
            $filter->add(new TFilter('status_acesso', '=', $data->status_acesso));
        }
        if (!empty($data->canal_acesso)) {
            $filter->add(new TFilter('canal_acesso', '=', $data->canal_acesso));
        }
        if (!empty($data->data_inicio)) {
            $filter->add(new TFilter('data_login', '>=', "{$data->data_inicio} 00:00:00"));
        }
        if (!empty($data->data_fim)) {
            $filter->add(new TFilter('data_login', '<=', "{$data->data_fim} 23:59:59"));
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

            $repository = new TRepository('AdminLogAcesso');
            $limit = 15;

            $criteria = TSession::getValue(__CLASS__ . '_filter');
            if (empty($criteria)) {
                $criteria = new TCriteria;
            }

            // Exclui registros marcados com exclusão lógica
            $criteria->add(new TFilter('delete_date', 'IS', NULL));

            if (empty($param['order'])) {
                $criteria->setProperty('order', 'id');
                $criteria->setProperty('direction', 'desc');
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $limit);

            $logs = $repository->load($criteria, false);
            $this->datagrid->clear();

            if ($logs) {
                foreach ($logs as $log) {
                    $item = new stdClass;
                    $item->id            = $log->id;
                    $item->login_attempt = $log->login_attempt;
                    $item->ip_address    = $log->ip_address;
                    $item->canal_acesso  = $log->canal_acesso;
                    $item->status_acesso = $log->status_acesso;

                    // Formatação de data/hora
                    $item->data_login_fmt  = !empty($log->data_login) ? date('d/m/Y H:i:s', strtotime($log->data_login)) : '-';
                    $item->data_logout_fmt = !empty($log->data_logout) ? date('d/m/Y H:i:s', strtotime($log->data_logout)) : '<span class="text-muted">Ativo / Não registrado</span>';

                    // Resgata o usuário associado
                    $usuario = $log->get_usuario();
                    if ($usuario && !empty($usuario->nome)) {
                        $item->usuario_nome = "<strong>" . htmlspecialchars($usuario->nome) . "</strong>";
                    } else {
                        $item->usuario_nome = '<span class="text-muted">Não identificado</span>';
                    }

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

    public function show()
    {
        if (!$this->loaded) {
            $this->onReload();
        }
        parent::show();
    }
}