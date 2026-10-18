<?php
require_once 'init.php';

class TApplication extends AdiantiCoreApplication
{
    public static function run($debug = null)
    {
        new TSession;
        
        if ($_REQUEST)
        {
            // -------------------------------------------------------------
            // CAMADA DE SEGURANÇA E CONTROLE DE ACESSO (INTERCEPTOR)
            // -------------------------------------------------------------
            $class  = $_REQUEST['class']  ?? null;
            $method = $_REQUEST['method'] ?? null;

            if ($class)
            {
                // Se a verificação falhar, aborta a execução do Adianti imediatamente
                if (!AdminAccessControl::checkAccess($class, $method))
                {
                    return;
                }
            }
            // -------------------------------------------------------------

            $ini   = AdiantiApplicationConfig::get();
            $debug = is_null($debug)? $ini['general']['debug'] : $debug;
            
            parent::run($debug);
        }
    }
}

TApplication::run();

/** ORIGINAL  8.7
<?php
require_once 'init.php';

class TApplication extends AdiantiCoreApplication
{
    public static function run($debug = null)
    {
        new TSession;
        
        if ($_REQUEST)
        {
            $ini    = AdiantiApplicationConfig::get();
            $debug  = is_null($debug)? $ini['general']['debug'] : $debug;
            
            parent::run($debug);
        }
    }
}

TApplication::run();
*/