<?php

class AdminEmpresa extends AdminModelBase
{
    const TABLENAME = 'admin_empresa';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('razao_social');
        parent::addAttribute('nome_fantasia');
        parent::addAttribute('cnpj_encrypted');
        parent::addAttribute('ie_encrypted');
        parent::addAttribute('im_encrypted');
        parent::addAttribute('regime_tributario');
        parent::addAttribute('cnae_principal');
        parent::addAttribute('email_contato');
        parent::addAttribute('telefone');
        parent::addAttribute('whatsapp');
        parent::addAttribute('logradouro');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('bairro');
        parent::addAttribute('cep');
        parent::addAttribute('cidade');
        parent::addAttribute('uf');
        parent::addAttribute('codigo_ibge');
        parent::addAttribute('status');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    // Acessores virtuais com encriptação/decriptação automática
    public function set_cnpj($value)
    {
        $this->cnpj_encrypted = SecurityHelper::encrypt($value);
    }

    public function get_cnpj()
    {
        return SecurityHelper::decrypt($this->cnpj_encrypted);
    }

    public function set_ie($value)
    {
        $this->ie_encrypted = SecurityHelper::encrypt($value);
    }

    public function get_ie()
    {
        return SecurityHelper::decrypt($this->ie_encrypted);
    }

    public function set_im($value)
    {
        $this->im_encrypted = SecurityHelper::encrypt($value);
    }

    public function get_im()
    {
        return SecurityHelper::decrypt($this->im_encrypted);
    }
}