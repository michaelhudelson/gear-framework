<?php
////////////////////////////////////////////////////////////////////////////////
//
// CONSTRUCTOR
//
// TABLE OF CONTENTS
// -----------------
// protected function __construct()
// public static function initialize()
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait Constructor
{
    protected function __construct()
    {
        // add developer role on activation
        $plugin_file = dirname(dirname(__FILE__))."/GearFramework.php";
        register_activation_hook($plugin_file, array($this, 'addDeveloperRole'));
        
        // initialize session handling
        add_action('init', array($this, 'startSession'), 1);
        add_action('wp_logout', array($this, 'endSession'));
        add_action('wp_login', array($this, 'endSession'));
        
        // add admin menues
        add_action('admin_menu', array($this, 'adminMenu'));
        
        // add mvc shortcode
        add_shortcode('gf-mvc', array($this, 'mvc'));
    }
    
    public static function initialize()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
    }
}
