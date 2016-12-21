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

namespace Gear;

trait Constructor
{
    protected function __construct()
    {
        // make defines a reference to constants
        self::$defines = &self::$constants;
        
        // set all GF constants
        $defined_constants = get_defined_constants();
        foreach ($defined_constants as $key => $value) {
            if (substr($key, 0, 3) === 'GF_') {
                self::$constants[$key] = $value;
            }
        }
        
        // set all GF globals
        foreach ($GLOBALS as $key => $value) {
            if (substr($key, 0, 3) === 'gf_') {
                self::$globals[$key] = $value;
            }
        }
        
        // set all GF methods
        self::$methods = get_class_methods('Gear');
        
        // set database connection information
        self::$db_host = DB_HOST;
        self::$db_name = DB_NAME;
        self::$db_user = DB_USER;
        self::$db_password = DB_PASSWORD;
    }
    
    public static function initialize()
    {
        if (static::$instance === null) {
            static::$instance = new static();
        }
    }
}
