<?php

class AdminLogAcesso extends AdminModelBase
{
    const TABLENAME = 'admin_log_acesso';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $usuario;

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('admin_usuario_id');
        parent::addAttribute('login_attempt');
        parent::addAttribute('data_login');
        parent::addAttribute('data_logout');
        parent::addAttribute('ip_address');
        parent::addAttribute('user_agent');
        parent::addAttribute('canal_acesso');
        parent::addAttribute('status_acesso');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    public function get_usuario()
    {
        if (empty($this->usuario) && !empty($this->admin_usuario_id)) {
            $this->usuario = new AdminUsuario($this->admin_usuario_id);
        }
        return $this->usuario;
    }
}