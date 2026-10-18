<?php

class AdminAuthService
{
    public static function authenticate($login, $password, $channel = 'web')
    {
        $login = trim($login);
        $ipAddress = self::getClientIp();
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Terminal', 0, 255);

        TTransaction::open('libsql_admin');

        try {
            $usuario = AdminUsuario::where('email', '=', $login)
                                   ->where('delete_date', 'IS', NULL)
                                   ->first();

            if (!$usuario) {
                self::logAccess(null, $login, $ipAddress, $userAgent, $channel, 'credenciais_invalidas');
                TTransaction::close();
                throw new Exception('Usuário ou senha inválidos.');
            }

            if ($usuario->status !== 'ativo') {
                self::logAccess($usuario->id, $login, $ipAddress, $userAgent, $channel, 'bloqueado');
                TTransaction::close();
                throw new Exception('Usuário inativo ou bloqueado no sistema.');
            }

            if (!password_verify($password, $usuario->senha_hash)) {
                self::logAccess($usuario->id, $login, $ipAddress, $userAgent, $channel, 'credenciais_invalidas');
                TTransaction::close();
                throw new Exception('Usuário ou senha inválidos.');
            }

            $logId = self::logAccess($usuario->id, $login, $ipAddress, $userAgent, $channel, 'sucesso');
            $permissions = self::loadUserPermissions($usuario->id);
            $empresasIds = self::loadUserEmpresas($usuario->id);

            TSession::regenerate();
            TSession::setValue('logged', true);
            TSession::setValue('userid', $usuario->id);
            TSession::setValue('username', $usuario->nome);
            TSession::setValue('usermail', $usuario->email);
            TSession::setValue('theme', $usuario->theme ?? 'light');
            TSession::setValue('admin_log_id', $logId);
            TSession::setValue('user_programs', $permissions['programs']);
            TSession::setValue('user_permissions', $permissions['details']);
            TSession::setValue('user_empresas', $empresasIds);

            TTransaction::close();

            return $usuario;

        } catch (Exception $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    public static function logout()
    {
        $logId = TSession::getValue('admin_log_id');

        if (!empty($logId)) {
            try {
                TTransaction::open('libsql_admin');
                $log = new AdminLogAcesso($logId);
                if (!empty($log->id)) {
                    $log->data_logout = date('Y-m-d H:i:s');
                    $log->update_date = date('Y-m-d H:i:s');
                    $log->user_update = TSession::getValue('userid');
                    $log->store();
                }
                TTransaction::close();
            } catch (Exception $e) {
                TTransaction::rollback();
            }
        }

        TSession::freeSession();
    }

    private static function logAccess($usuarioId, $login, $ip, $userAgent, $canal, $status)
    {
        $log = new AdminLogAcesso();
        $log->admin_usuario_id = $usuarioId;
        $log->login_attempt    = $login;
        $log->data_login       = date('Y-m-d H:i:s');
        $log->ip_address       = $ip;
        $log->user_agent       = $userAgent;
        $log->canal_acesso     = $canal;
        $log->status_acesso    = $status;
        $log->create_date      = date('Y-m-d H:i:s');
        $log->user_create      = $usuarioId ?? 0;
        $log->store();

        return $log->id;
    }

    private static function loadUserPermissions($usuarioId)
    {
        $programs = [];
        $details = [];

        $vinculosGrupos = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuarioId)
                                            ->where('delete_date', 'IS', NULL)
                                            ->load();

        if ($vinculosGrupos) {
            $gruposIds = array_map(fn($v) => $v->admin_grupo_id, $vinculosGrupos);

            $permissoes = AdminGrupoRotina::where('admin_grupo_id', 'IN', $gruposIds)
                                          ->where('delete_date', 'IS', NULL)
                                          ->load();

            if ($permissoes) {
                foreach ($permissoes as $perm) {
                    $rotina = new AdminRotina($perm->admin_rotina_id);
                    if (!empty($rotina->controller) && empty($rotina->delete_date)) {
                        $controller = trim($rotina->controller);
                        $programs[$controller] = true;

                        if (!isset($details[$controller])) {
                            $details[$controller] = [
                                'ler'      => 0,
                                'inserir'  => 0,
                                'editar'   => 0,
                                'deletar'  => 0
                            ];
                        }

                        $details[$controller]['ler']     = max($details[$controller]['ler'], (int)$perm->pode_ler);
                        $details[$controller]['inserir'] = max($details[$controller]['inserir'], (int)$perm->pode_inserir);
                        $details[$controller]['editar']  = max($details[$controller]['editar'], (int)$perm->pode_editar);
                        $details[$controller]['deletar'] = max($details[$controller]['deletar'], (int)$perm->pode_deletar);
                    }
                }
            }
        }

        return ['programs' => $programs, 'details' => $details];
    }

    private static function loadUserEmpresas($usuarioId)
    {
        $empresasIds = [];

        $vinculosGrupos = AdminUsuarioGrupo::where('admin_usuario_id', '=', $usuarioId)
                                            ->where('delete_date', 'IS', NULL)
                                            ->load();

        if ($vinculosGrupos) {
            $gruposIds = array_map(fn($v) => $v->admin_grupo_id, $vinculosGrupos);

            $vinculosEmpresas = AdminEmpresaGrupo::where('admin_grupo_id', 'IN', $gruposIds)
                                                 ->where('delete_date', 'IS', NULL)
                                                 ->load();

            if ($vinculosEmpresas) {
                foreach ($vinculosEmpresas as $ve) {
                    $empresasIds[$ve->admin_empresa_id] = true;
                }
            }
        }

        return array_keys($empresasIds);
    }

    private static function getClientIp()
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ipList[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}