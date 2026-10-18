<?php
class AdminUsuario extends AdminModelBase
{
    const TABLENAME = 'admin_usuario';
    const PRIMARYKEY = 'id';
    const IDPOLICY = 'serial';

    public function __construct($id = null, $callObjectLoad = true)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('email');
        parent::addAttribute('senha_hash');
        parent::addAttribute('cpf_encrypted');
        parent::addAttribute('celular');
        parent::addAttribute('theme');
        parent::addAttribute('avatar_url');
        parent::addAttribute('status');
        parent::addAttribute('create_date');
        parent::addAttribute('update_date');
        parent::addAttribute('delete_date');
        parent::addAttribute('user_create');
        parent::addAttribute('user_update');
        parent::addAttribute('user_delete');
    }

    public function set_senha($plainPassword) {
        if (!empty($plainPassword)) {
            $this->senha_hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        }
    }

    public function set_cpf($value) {
        $this->cpf_encrypted = SecurityHelper::encrypt($value);
    }

    public function get_cpf() {
        return SecurityHelper::decrypt($this->cpf_encrypted);
    }

    public function getGrupos()
    {
        $grupos = [];
        $vinculos = AdminUsuarioGrupo::where('admin_usuario_id', '=', $this->id)
            ->where('delete_date', 'IS', NULL)
            ->load();

        if ($vinculos) {
            foreach ($vinculos as $vinculo) {
                $grupos[] = new AdminGrupo($vinculo->admin_grupo_id);
            }
        }
        return $grupos;
    }
}