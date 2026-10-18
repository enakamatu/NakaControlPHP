<?php
require_once 'init.php';

new TSession;

$ini   = AdiantiApplicationConfig::get();
$theme = $ini['general']['theme'];

// Resgata o tema ativo na sessão ou no cookie gerado pelo switch do Adianti
/*
$userTheme = TSession::getValue('theme') ?? ($_COOKIE['app_theme'] ?? 'light');
$themeMode = ($userTheme === 'dark') ? 'dark-mode' : '';
*/
// -------------------------------------------------------------
// TEMA FIXO DA SESSÃO (Ignora cookies do navegador)
// -------------------------------------------------------------
$sessionTheme = TSession::getValue('theme');
// Caso ainda não exista sessão (ex: utilizador anónimo/público), 
// define o padrão fixo predefinido (ex: 'light')
$themeMode = ($sessionTheme === 'dark') ? 'dark-mode' : '';

if (isset($_REQUEST['template']) && $_REQUEST['template'] == 'iframe')
{
    $content = file_get_contents("app/templates/{$theme}/iframe.html");
}
else
{
    $content = file_get_contents("app/templates/{$theme}/layout.html");
}

/*
$menu_string = AdiantiMenuBuilder::parse('menu.xml', $theme);
*/
$menu_string = AdminMenuService::buildMenu($theme);
$content     = ApplicationTranslator::translateTemplate($content);

// Substituições de Layout e Tema
$content     = str_replace('{theme_mode}', $themeMode, $content);
$content     = str_replace('{LIBRARIES}', file_get_contents("app/templates/{$theme}/libraries.html"), $content);
$content     = str_replace('{class}', isset($_REQUEST['class']) ? $_REQUEST['class'] : '', $content);
$content     = str_replace('{template}', $theme, $content);
$content     = str_replace('{MENU}', $menu_string, $content);
$content     = str_replace('{MENUTOP}', AdiantiMenuBuilder::parseNavBar('menu-top-public.xml', $theme), $content);
$content     = str_replace('{MENUBOTTOM}', AdiantiMenuBuilder::parseNavBar('menu-bottom-public.xml', $theme), $content);
$content     = str_replace('{lang}', $ini['general']['language'], $content);
$content     = str_replace('{title}', $ini['general']['title'] ?? '', $content);
$content     = str_replace('{template_options}', json_encode($ini['template'] ?? []), $content);
$content     = str_replace('{adianti_options}', json_encode($ini['general']), $content);

$css         = TPage::getLoadedCSS();
$js          = TPage::getLoadedJS();
$content     = str_replace('{HEAD}', $css.$js, $content);

echo $content;

if (isset($_REQUEST['class']))
{
    $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : NULL;
    AdiantiCoreApplication::loadPage($_REQUEST['class'], $method, $_REQUEST);
}