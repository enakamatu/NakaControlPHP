<?php

class AdminAccessControl
{
    private static $publicRoutes = [
	    'ControlPublic',
        'LoginForm',
        'AdiantiCoreApplication',
        'SearchInputService',
        'EmptyPage'
    ];

    public static function checkAccess($class, $method = null)
    {
        if (in_array($class, self::$publicRoutes)) {
            return true;
        }

        if (!TSession::getValue('logged')) {
            self::deny('Sessão expirada ou usuário não autenticado.', 'LoginForm');
            return false;
        }

        if (in_array($class, ['HomeView', 'WelcomeView', 'SystemProfileView'])) {
            return true;
        }

        $programs = TSession::getValue('user_programs') ?? [];
        $permissions = TSession::getValue('user_permissions') ?? [];

        if (!isset($programs[$class])) {
            self::deny("Acesso Negado: Você não possui permissão para acessar a rotina [{$class}].");
            return false;
        }

        if (!empty($method)) {
            $perm = $permissions[$class] ?? ['ler' => 0, 'inserir' => 0, 'editar' => 0, 'deletar' => 0];

            if (in_array(strtolower($method), ['ondelete', 'delete']) && empty($perm['deletar'])) {
                self::deny("Acesso Negado: Seu perfil não possui permissão para excluir registros nesta rotina.");
                return false;
            }

            if (in_array(strtolower($method), ['oncreate', 'onnew']) && empty($perm['inserir'])) {
                self::deny("Acesso Negado: Seu perfil não possui permissão para cadastrar nesta rotina.");
                return false;
            }

            if (in_array(strtolower($method), ['onedit', 'onsave']) && empty($perm['editar']) && empty($perm['inserir'])) {
                self::deny("Acesso Negado: Seu perfil não possui permissão para salvar alterações nesta rotina.");
                return false;
            }

            if (in_array(strtolower($method), ['onreload', 'onsearch', 'onload']) && empty($perm['ler'])) {
                self::deny("Acesso Negado: Seu perfil não possui permissão de leitura para esta rotina.");
                return false;
            }
        }

        return true;
    }

    private static function deny($message, $targetPage = null)
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_REQUEST['static']);

        if ($targetPage) {
            AdiantiCoreApplication::gotoPage($targetPage);
            return;
        }

        if ($isAjax) {
            new TMessage('error', $message);
        } else {
            echo "<div style='display:flex; justify-content:center; align-items:center; height:100vh; font-family:sans-serif; background:#f4f6f9;'>";
            echo "  <div style='background:#fff; padding:30px; border-radius:8px; box-shadow:0 4px 6px rgba(0,0,0,0.1); text-align:center; max-width:480px;'>";
            echo "      <h3 style='color:#dc3545; margin-bottom:12px;'><i class='fa fa-ban'></i> Acesso Restrito</h3>";
            echo "      <p style='color:#6c757d; font-size:14px; margin-bottom:20px;'>{$message}</p>";
            echo "      <a href='index.php?class=HomeView' class='btn btn-primary' style='display:inline-block; padding:8px 16px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;'>Voltar ao Início</a>";
            echo "  </div>";
            echo "</div>";
        }
    }
}