<?php

class AdminGrupoRotina extends AdminModelBase
{
    const TABLENAME = 'admin_grupo_rotina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    private $grupo;
    private $rotina;

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('admin_grupo_id');
        parent::addAttribute('admin_rotina_id');
        parent::addAttribute('pode_ler');
        parent::addAttribute('pode_inserir');
        parent::addAttribute('pode_editar');
        parent::addAttribute('pode_deletar');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    public function get_grupo()
    {
        if (empty($this->grupo) && !empty($this->admin_grupo_id)) {
            $this->grupo = new AdminGrupo($this->admin_grupo_id);
        }
        return $this->grupo;
    }

    public function get_rotina()
    {
        if (empty($this->rotina) && !empty($this->admin_rotina_id)) {
            $this->rotina = new AdminRotina($this->admin_rotina_id);
        }
        return $this->rotina;
    }
}