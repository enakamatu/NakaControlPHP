<?php

use Adianti\Control\TPage;
use Adianti\Core\TSession;

abstract class AdminPage extends TPage
{
    public function __construct()
    {
        parent::__construct();

        $controllerName = get_class($this);
        $method = $_REQUEST['method'] ?? null;

        // Auto-validação de integridade na construção do objeto
        if (!AdminAccessControl::checkAccess($controllerName, $method)) {
            throw new Exception("Operação não autorizada para o recurso {$controllerName}.");
        }
    }

    /**
     * Método auxiliar para checar flags granulares diretamente na View
     */
    protected function can($action)
    {
        $class = get_class($this);
        $permissions = TSession::getValue('user_permissions') ?? [];
        return !empty($permissions[$class][$action]);
    }
}