<?php

abstract class AdminModelBase extends TRecord
{
    const DATABASE = 'libsql_admin';

    /**
     * Intercepta a gravação para preencher auditoria de criação/edição
     */
    public function store()
    {
        $now = date('Y-m-d H:i:s');
        $userId = $this->getCurrentUserId();

        if (empty($this->id)) {
            $this->create_date = $this->create_date ?? $now;
            $this->user_create = $this->user_create ?? $userId;
        } else {
            $this->update_date = $now;
            $this->user_update = $userId;
        }

        return parent::store();
    }

    /**
     * Intercepta a exclusão para aplicar Soft Delete com auditoria
     */
    public function delete($id = null)
    {
        $id = $id ? $id : $this->id;

        if ($id) {
            $this->load($id);
            $this->delete_date = date('Y-m-d H:i:s');
            $this->user_delete = $this->getCurrentUserId();

            return parent::store();
        }

        return false;
    }

    /**
     * Captura o ID do usuário logado na sessão com fallback seguro para CLI/Seed
     */
    protected function getCurrentUserId()
    {
        if (class_exists('TSession') && session_status() === PHP_SESSION_ACTIVE) {
            $sessionUserId = TSession::getValue('userid');
            if (!empty($sessionUserId)) {
                return (int) $sessionUserId;
            }
        }

        if (!empty($this->user_create)) {
            return (int) $this->user_create;
        }

        return 1;
    }
}