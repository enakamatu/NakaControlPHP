<?php
/**
 * Script de Inicialização Operacional (Seed) - libSQL Admin
 * Executar via terminal a partir da raiz:
 * php app/database/seed_initial_admin.php
 */

if (php_sapi_name() === 'cli') {
    chdir(__DIR__ . '/../../');
    require_once 'init.php';
}

// Garante o carregamento dos models e helpers para execução via CLI
require_once 'app/service/SecurityHelper.php';
require_once 'app/model/admin/AdminModelBase.php';
require_once 'app/model/admin/AdminEmpresa.php';
require_once 'app/model/admin/AdminGrupo.php';
require_once 'app/model/admin/AdminUsuario.php';
require_once 'app/model/admin/AdminUsuarioGrupo.php';
require_once 'app/model/admin/AdminEmpresaGrupo.php';
require_once 'app/model/admin/AdminRotina.php';
require_once 'app/model/admin/AdminGrupoRotina.php';
require_once 'app/model/admin/AdminLogAcesso.php';

try {
    echo "Iniciando provisionamento da base libSQL...\n";

    TTransaction::open('libsql_admin');

    $now = date('Y-m-d H:i:s');
    $systemUserId = 1;

    // 1. Empresa de Homologação
    echo "1. Configurando Empresa de Homologação...\n";
    $empresa = AdminEmpresa::where('razao_social', '=', 'EMPRESA HOMOLOGACAO SISTEMAS LTDA')
                           ->where('delete_date', 'IS', NULL)
                           ->first();

    if (!$empresa) {
        $empresa = new AdminEmpresa();
        $empresa->razao_social      = 'EMPRESA HOMOLOGACAO SISTEMAS LTDA';
        $empresa->nome_fantasia     = 'Homologação Matriz';
        $empresa->set_cnpj('00.000.000/0001-91');
        $empresa->set_ie('123.456.789.110');
        $empresa->set_im('ISENTO');
        $empresa->regime_tributario = 'simples_nacional';
        $empresa->cnae_principal    = '6201-5/01';
        $empresa->email_contato     = 'homologacao@sistema.local';
        $empresa->telefone          = '(11) 4002-8922';
        $empresa->whatsapp          = '(11) 99999-9999';
        $empresa->logradouro        = 'Avenida Industrial';
        $empresa->numero            = '1000';
        $empresa->complemento       = 'Sala 101 - Bloco B';
        $empresa->bairro            = 'Bairro Campestre';
        $empresa->cep               = '09080-510';
        $empresa->cidade            = 'Santo André';
        $empresa->uf                = 'SP';
        $empresa->codigo_ibge       = '3547809';
        $empresa->status            = 'ativo';
        $empresa->create_date       = $now;
        $empresa->user_create       = $systemUserId;
        $empresa->store();
        echo "   -> Empresa cadastrada com sucesso (ID: {$empresa->id}).\n";
    } else {
        echo "   -> Empresa já existente (ID: {$empresa->id}).\n";
    }

    // 2. Grupo Administrador
    echo "2. Configurando Grupo 'Administradores'...\n";
    $grupoAdmin = AdminGrupo::where('nome', '=', 'Administradores')
                            ->where('delete_date', 'IS', NULL)
                            ->first();

    if (!$grupoAdmin) {
        $grupoAdmin = new AdminGrupo();
        $grupoAdmin->nome        = 'Administradores';
        $grupoAdmin->descricao   = 'Acesso total e irrestrito a todas as rotinas administrativas e de governança';
        $grupoAdmin->status      = 'ativo';
        $grupoAdmin->create_date = $now;
        $grupoAdmin->user_create = $systemUserId;
        $grupoAdmin->store();
        echo "   -> Grupo Administradores criado (ID: {$grupoAdmin->id}).\n";
    } else {
        echo "   -> Grupo Administradores já existente (ID: {$grupoAdmin->id}).\n";
    }

    // 3. Vínculo Empresa x Grupo
    $vinculoEmpresaGrupo = AdminEmpresaGrupo::where('admin_empresa_id', '=', $empresa->id)
                                            ->where('admin_grupo_id', '=', $grupoAdmin->id)
                                            ->where('delete_date', 'IS', NULL)
                                            ->first();
    if (!$vinculoEmpresaGrupo) {
        $vinculoEmpresaGrupo = new AdminEmpresaGrupo();
        $vinculoEmpresaGrupo->admin_empresa_id = $empresa->id;
        $vinculoEmpresaGrupo->admin_grupo_id   = $grupoAdmin->id;
        $vinculoEmpresaGrupo->create_date      = $now;
        $vinculoEmpresaGrupo->user_create      = $systemUserId;
        $vinculoEmpresaGrupo->store();
        echo "   -> Empresa vinculada ao grupo Administradores.\n";
    }

    // 4. Usuário Admin
    echo "3. Configurando Usuário 'admin'...\n";
    $usuarioAdmin = AdminUsuario::where('email', '=', 'admin@sistema.local')
                                ->where('delete_date', 'IS', NULL)
                                ->first();

    if (!$usuarioAdmin) {
        $usuarioAdmin = new AdminUsuario();
        $usuarioAdmin->nome        = 'Administrador do Sistema';
        $usuarioAdmin->email       = 'admin@sistema.local';
        $usuarioAdmin->set_senha('admin');
        $usuarioAdmin->set_cpf('000.000.000-00');
        $usuarioAdmin->celular     = '(11) 98888-7777';
        $usuarioAdmin->theme       = 'dark';
        $usuarioAdmin->status      = 'ativo';
        $usuarioAdmin->create_date = $now;
        $usuarioAdmin->user_create = $systemUserId;
        $usuarioAdmin->store();
        echo "   -> Usuário 'admin' criado com sucesso (ID: {$usuarioAdmin->id}).\n";
    } else {
        echo "   -> Usuário 'admin' já existente (ID: {$usuarioAdmin->id}).\n";
    }

    // 5. Vínculo Usuário x Grupo Admin
    $vinculoUserGrupo = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuarioAdmin->id)
                                        ->where('admin_grupo_id', '=', $grupoAdmin->id)
                                        ->where('delete_date', 'IS', NULL)
                                        ->first();
    if (!$vinculoUserGrupo) {
        $vinculoUserGrupo = new AdminUsuarioGrupo();
        $vinculoUserGrupo->admin_usuario_id = $usuarioAdmin->id;
        $vinculoUserGrupo->admin_grupo_id   = $grupoAdmin->id;
        $vinculoUserGrupo->create_date      = $now;
        $vinculoUserGrupo->user_create      = $systemUserId;
        $vinculoUserGrupo->store();
        echo "   -> Vínculo do usuário admin com o grupo Administradores concluído.\n";
    }

    // 6. Catálogo de Rotinas
    echo "4. Provisionando catálogo de rotinas do sistema...\n";
    $rotinasBase = [
        ['codigo' => 'ROT_EMPRESA_LIST',    'nome' => 'Empresas - Listagem',             'controller' => 'EmpresaList',   'modulo' => 'Cadastros Gerais',          'icone' => 'fas:building', 'visivel_menu' => 1, 'ordem' => 10],
        ['codigo' => 'ROT_EMPRESA_FORM',    'nome' => 'Empresas - Formulário',           'controller' => 'EmpresaForm',   'modulo' => 'Cadastros Gerais',          'icone' => 'fas:edit',     'visivel_menu' => 0, 'ordem' => 11],
        ['codigo' => 'ROT_USUARIO_LIST',    'nome' => 'Usuários - Listagem',             'controller' => 'UsuarioList',   'modulo' => 'Controle de Acesso',        'icone' => 'fas:users',    'visivel_menu' => 1, 'ordem' => 20],
        ['codigo' => 'ROT_USUARIO_FORM',    'nome' => 'Usuários - Formulário',           'controller' => 'UsuarioForm',   'modulo' => 'Controle de Acesso',        'icone' => 'fas:user-plus','visivel_menu' => 0, 'ordem' => 21],
        ['codigo' => 'ROT_GRUPO_LIST',      'nome' => 'Grupos de Usuários - Listagem',   'controller' => 'GrupoList',     'modulo' => 'Controle de Acesso',        'icone' => 'fas:user-tag', 'visivel_menu' => 1, 'ordem' => 30],
        ['codigo' => 'ROT_GRUPO_FORM',      'nome' => 'Grupos de Usuários - Formulário', 'controller' => 'GrupoForm',     'modulo' => 'Controle de Acesso',        'icone' => 'fas:edit',     'visivel_menu' => 0, 'ordem' => 31],
        ['codigo' => 'ROT_ROTINA_LIST',     'nome' => 'Rotinas do Sistema - Listagem',   'controller' => 'RotinaList',    'modulo' => 'Segurança & Configurações', 'icone' => 'fas:cogs',     'visivel_menu' => 1, 'ordem' => 40],
        ['codigo' => 'ROT_ROTINA_FORM',     'nome' => 'Rotinas do Sistema - Formulário', 'controller' => 'RotinaForm',    'modulo' => 'Segurança & Configurações', 'icone' => 'fas:edit',     'visivel_menu' => 0, 'ordem' => 41],
        ['codigo' => 'ROT_LOG_ACESSO_LIST', 'nome' => 'Logs de Acesso - Auditoria',      'controller' => 'LogAcessoList', 'modulo' => 'Auditoria',                 'icone' => 'fas:history',  'visivel_menu' => 1, 'ordem' => 50]
    ];

    foreach ($rotinasBase as $item) {
        $rotina = AdminRotina::where('codigo', '=', $item['codigo'])
                             ->where('delete_date', 'IS', NULL)
                             ->first();

        if (!$rotina) {
            $rotina = new AdminRotina();
            $rotina->codigo       = $item['codigo'];
            $rotina->nome         = $item['nome'];
            $rotina->controller   = $item['controller'];
            $rotina->modulo       = $item['modulo'];
            $rotina->icone        = $item['icone'];
            $rotina->visivel_menu = $item['visivel_menu'];
            $rotina->ordem        = $item['ordem'];
            $rotina->create_date  = $now;
            $rotina->user_create  = $systemUserId;
            $rotina->store();
            echo "   -> Rotina cadastrada: {$item['controller']}\n";
        }

        // Amarra ao Grupo Administradores com permissão total
        $permissaoGrupo = AdminGrupoRotina::where('admin_grupo_id', '=', $grupoAdmin->id)
                                         ->where('admin_rotina_id', '=', $rotina->id)
                                         ->where('delete_date', 'IS', NULL)
                                         ->first();
        if (!$permissaoGrupo) {
            $permissaoGrupo = new AdminGrupoRotina();
            $permissaoGrupo->admin_grupo_id  = $grupoAdmin->id;
            $permissaoGrupo->admin_rotina_id = $rotina->id;
            $permissaoGrupo->pode_ler        = 1;
            $permissaoGrupo->pode_inserir    = 1;
            $permissaoGrupo->pode_editar     = 1;
            $permissaoGrupo->pode_deletar    = 1;
            $permissaoGrupo->create_date     = $now;
            $permissaoGrupo->user_create     = $systemUserId;
            $permissaoGrupo->store();
        }
    }

    echo "   -> Todas as rotinas foram amarradas ao Grupo Administradores.\n";

    TTransaction::close();

    echo "\n=== PROVISIONAMENTO CONCLUÍDO COM SUCESSO! ===\n";
    echo "Login: admin@sistema.local\n";
    echo "Senha: admin\n";

} catch (Exception $e) {
    TTransaction::rollback();
    echo "\n[ERRO NO SEED]: " . $e->getMessage() . "\n";
}