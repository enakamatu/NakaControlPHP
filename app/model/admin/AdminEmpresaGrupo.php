<?php

class AdminEmpresaGrupo extends AdminModelBase
{
    const TABLENAME = 'admin_empresa_grupo';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $empresa;
    private $grupo;

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('admin_empresa_id');
        parent::addAttribute('admin_grupo_id');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    public function get_empresa()
    {
        if (empty($this->empresa) && !empty($this->admin_empresa_id)) {
            $this->empresa = new AdminEmpresa($this->admin_empresa_id);
        }
        return $this->empresa;
    }

    public function get_grupo()
    {
        if (empty($this->grupo) && !empty($this->admin_grupo_id)) {
            $this->grupo = new AdminGrupo($this->admin_grupo_id);
        }
        return $this->grupo;
    }
}