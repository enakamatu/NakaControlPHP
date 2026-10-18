<?php

class AdminRotina extends AdminModelBase
{
    const TABLENAME = 'admin_rotina';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('codigo');
        parent::addAttribute('nome');
        parent::addAttribute('controller');
        parent::addAttribute('modulo');
        parent::addAttribute('icone');
        parent::addAttribute('visivel_menu');
        parent::addAttribute('ordem');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }
}