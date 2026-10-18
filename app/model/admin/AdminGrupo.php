<?php
class AdminGrupo extends AdminModelBase
{
    const TABLENAME = 'admin_grupo';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('descricao');
        parent::addAttribute('status');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }
}