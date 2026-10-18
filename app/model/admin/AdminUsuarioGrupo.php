<?php
class AdminUsuarioGrupo extends AdminModelBase
{
    const TABLENAME = 'admin_usuario_grupo';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $usuario;
    private $grupo;

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('admin_usuario_id');
        parent::addAttribute('admin_grupo_id');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    public function get_usuario() {
        if (empty($this->usuario)) {
            $this->usuario = new AdminUsuario($this->admin_usuario_id);
        }
        return $this->usuario;
    }

    public function get_grupo() {
        if (empty($this->grupo)) {
            $this->grupo = new AdminGrupo($this->admin_grupo_id);
        }
        return $this->grupo;
    }
}