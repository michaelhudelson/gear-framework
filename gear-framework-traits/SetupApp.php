<?php
////////////////////////////////////////////////////////////////////////////////
//
// SETUP APP
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait SetupApp
{
    // setup app with defines globals and includes
    public function setupApp()
    {
        $this->setupSession();
        $this->setupDefines();
        $this->setupGlobals();
        $this->setupIncludes();
    }

    // create all session variables
    public function setupSession()
    {
        if (!isset($_SESSION['gf_token'])) {
            $_SESSION['gf_token'] = sha1(mt_rand());
        }
    }

    // create all defines
    public function setupDefines()
    {
        // define plugin path
        define('GF_PLUGIN_PATH', dirname(__DIR__));

        // check if gf_app_path is set in the plugin
        // default location is /wp-content/gf_app
        $gf_app_path = get_option('gf_app_path');
        $GF_APP_PATH = ($gf_app_path == '') ? dirname(dirname(GF_PLUGIN_PATH)) . '/gf-app' : $gf_app_path;
        define('GF_APP_PATH', $GF_APP_PATH);

        // check if gf_public_path is set in the plugin
        // default location is GF_APP_PATH/public
        $gf_public_path = get_option('gf_public_path');
        $GF_PUBLIC_PATH = ($gf_public_path == '') ? GF_APP_PATH . '/public' : $gf_public_path;
        define('GF_PUBLIC_PATH', $GF_PUBLIC_PATH);

        // Set all other defines for framework
        define('GF_CONTROLLER_PATH', GF_APP_PATH . '/controllers');
        define('GF_DATA_PATH', GF_APP_PATH . '/data');
        define('GF_INCLUDE_PATH', GF_APP_PATH . '/includes');
        define('GF_MODEL_PATH', GF_APP_PATH . '/models');
        define('GF_SCRIPT_PATH', GF_APP_PATH . '/scripts');
        define('GF_TEMP_PATH', GF_APP_PATH . '/temp');
        define('GF_VIEW_PATH', GF_APP_PATH . '/views');
        define('GF_ERROR_LOG', GF_APP_PATH . '/error_log');
        define('GF_SITE_PATH', getcwd());
        define('GF_SITE_URL', get_site_url());
        define('GF_PUBLIC_URL', GF_SITE_URL . explode(getcwd(), GF_PUBLIC_PATH)[1]);
    }

    // create all global variables
    public function setupGlobals()
    {
        // set up global variables
        $GLOBALS['gf_debug'] = false;
        $GLOBALS['gf_query_debug'] = false;
        $GLOBALS['gf_post_resubmit'] = false;
        $GLOBALS['gf_before_output'] = '';
        $GLOBALS['gf_after_output'] = '';

        // set up global message handlers
        $GLOBALS['gf_error_array'] = array();
        $GLOBALS['gf_success_array'] = array();
        $GLOBALS['gf_debug_array'] = array();
        $GLOBALS['gf_message_array'] = array();

        // set up global css and js handlers
        $GLOBALS['gf_css_array'] = array();
        $GLOBALS['gf_js_array'] = array();

        // set up allowed roles handler - indexed by page_slug
        $GLOBALS['gf_roles_allowed_access_array'] =  array();
        $GLOBALS['gf_roles_denied_access_array'] =  array();
        $GLOBALS['gf_access_denied_message'] =  'Access Denied';

        // set up global to require js
        $GLOBALS['gf_require_js'] = true;

        // set up global to track if initializeJs function has been called
        if (!isset($GLOBALS['gf_js_initialized'])) {
            $GLOBALS['gf_js_initialized'] = false;
        }

        // maintain css and js loaded across components
        if (!isset($GLOBALS['gf_css_included'])) {
            $GLOBALS['gf_css_included'] = array();
        }
        if (!isset($GLOBALS['gf_js_included'])) {
            $GLOBALS['gf_js_included'] = array();
        }
    }

    // include all relevant files
    public function setupIncludes()
    {
        // initialize error handling before processing the gear library or any user files
        $this->errorHandling(true);

        // include the gf_library
        include_once GF_PLUGIN_PATH . "/gear-library/Gear.php";

        // include all files inside the automatic_includes folder
        foreach (glob(GF_INCLUDE_PATH . "/automatic-includes/*.php") as $file_name) {
            include_once $file_name;
        }

        // include all files inside the automatic_includes subfolders
        foreach (glob(GF_INCLUDE_PATH . "/automatic-includes/*/*.php") as $file_name) {
            include_once $file_name;
        }

        // include config file every time to reset config
        include GF_APP_PATH . '/config.php';

        // set error handling based on config
        $this->errorHandling();
    }

    // configure php error handling
    public function errorHandling($display_errors = null)
    {
        if (is_null($display_errors)) {
            $display_errors = (is_bool($GLOBALS['gf_debug'])) ? $GLOBALS['gf_debug'] : true;
        }
        ini_set('display_errors', $display_errors);
        error_reporting(E_ALL ^ E_NOTICE);
    }
}
