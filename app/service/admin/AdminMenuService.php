<?php

class AdminMenuService
{
    /**
     * Gera o HTML da barra lateral buscando as rotinas no libSQL
     * Compatível com Bootstrap 5 (adminbs5) e escopo global do Adianti
     *
     * @param string $theme Nome do tema ativo
     * @return string
     */
    public static function buildMenu($theme = 'adminbs5')
    {
        $userId = TSession::getValue('userid');
        
        // Se não houver usuário logado, retorna vazio
        if (empty($userId)) {
            return '';
        }

        $rotinasLiberadas = self::getUserRoutines($userId);

        if (empty($rotinasLiberadas)) {
            return '<ul class="nav nav-pills flex-column mb-auto">' .
                   '<li class="nav-item"><a href="index.php?class=HomeView" class="nav-link"><i class="fas fa-home me-2"></i><span>Início</span></a></li>' .
                   '</ul>';
        }

        // Agrupa as rotinas pelo nome do módulo
        $modulos = [];
        foreach ($rotinasLiberadas as $rotina) {
            $moduloNome = !empty($rotina['modulo']) ? $rotina['modulo'] : 'Geral';
            $modulos[$moduloNome][] = $rotina;
        }

        $currentClass = $_REQUEST['class'] ?? 'HomeView';

        // Container de menu compatível com adminbs5 / Bootstrap 5
        $html  = '<ul class="nav nav-pills flex-column mb-auto sidebar-menu">';
        
        // Item fixo: Início / Home
        $homeActive = ($currentClass === 'HomeView') ? 'active' : '';
        $html .= '<li class="nav-item">';
        $html .= "  <a href=\"index.php?class=HomeView\" class=\"nav-link {$homeActive}\">";
        $html .= '    <i class="fas fa-home me-2"></i>';
        $html .= '    <span>Início</span>';
        $html .= '  </a>';
        $html .= '</li>';

        $moduleIndex = 0;
        foreach ($modulos as $moduloNome => $itens) {
            $moduleIndex++;
            $collapseId = 'menu_module_' . $moduleIndex;

            // Checa se o item ativo pertence a este módulo
            $hasActiveChild = false;
            foreach ($itens as $item) {
                if ($item['controller'] === $currentClass) {
                    $hasActiveChild = true;
                    break;
                }
            }

            $showClass      = $hasActiveChild ? 'show' : '';
            $collapsedClass = $hasActiveChild ? '' : 'collapsed';
            $ariaExpanded   = $hasActiveChild ? 'true' : 'false';

            $html .= '<li class="nav-item">';
            $html .= "  <a href=\"#{$collapseId}\" data-bs-toggle=\"collapse\" class=\"nav-link {$collapsedClass} d-flex align-items-center justify-content-between\" aria-expanded=\"{$ariaExpanded}\">";
            $html .= "    <span><i class=\"fas fa-folder me-2\"></i>{$moduloNome}</span>";
            $html .= '    <i class="fas fa-chevron-down arrow-icon small"></i>';
            $html .= '  </a>';
            $html .= "  <div class=\"collapse {$showClass}\" id=\"{$collapseId}\">";
            $html .= '    <ul class="nav flex-column ps-3 py-1">';

            foreach ($itens as $item) {
                $itemActive = ($item['controller'] === $currentClass) ? 'active' : '';
                $icone = !empty($item['icone']) ? str_replace(':', ' ', $item['icone']) : 'fas fa-circle';

                $html .= '      <li class="nav-item">';
                $html .= "        <a href=\"index.php?class={$item['controller']}\" class=\"nav-link py-1 {$itemActive}\">";
                $html .= "          <i class=\"{$icone} me-2 small\"></i>";
                $html .= "          <span>{$item['nome']}</span>";
                $html .= '        </a>';
                $html .= '      </li>';
            }

            $html .= '    </ul>';
            $html .= '  </div>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Consulta as rotinas acessíveis e visíveis no menu via libSQL
     */
    private static function getUserRoutines($userId)
    {
        $rotinas = [];

        try {
            TTransaction::open('libsql_admin');

            // 1. Resgata os IDs dos grupos do usuário logado
            $vinculosGrupos = AdminUsuarioGrupo::where('admin_usuario_id', '=', $userId)
                                                ->where('delete_date', 'IS', NULL)
                                                ->load();

            if (!$vinculosGrupos) {
                TTransaction::close();
                return [];
            }

            $gruposIds = array_map(fn($v) => $v->admin_grupo_id, $vinculosGrupos);

            // 2. Busca permissões ativas de leitura atreladas a estes grupos
            $permissoes = AdminGrupoRotina::where('admin_grupo_id', 'IN', $gruposIds)
                                          ->where('pode_ler', '=', 1)
                                          ->where('delete_date', 'IS', NULL)
                                          ->load();

            if (!$permissoes) {
                TTransaction::close();
                return [];
            }

            $rotinasIds = array_unique(array_map(fn($p) => $p->admin_rotina_id, $permissoes));

            // 3. Carrega apenas rotinas visíveis no menu ordenadas por módulo e ordem
            $rotinasList = AdminRotina::where('id', 'IN', $rotinasIds)
                                      ->where('visivel_menu', '=', 1)
                                      ->where('delete_date', 'IS', NULL)
                                      ->orderBy('modulo')
                                      ->orderBy('ordem')
                                      ->load();

            if ($rotinasList) {
                foreach ($rotinasList as $r) {
                    $rotinas[] = [
                        'id'         => $r->id,
                        'nome'       => $r->nome,
                        'controller' => $r->controller,
                        'modulo'     => $r->modulo,
                        'icone'      => $r->icone,
                        'ordem'      => $r->ordem
                    ];
                }
            }

            TTransaction::close();
        } catch (Exception $e) {
            TTransaction::rollback();
        }

        return $rotinas;
    }
}