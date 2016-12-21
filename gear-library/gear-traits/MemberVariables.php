<?php
////////////////////////////////////////////////////////////////////////////////
//
// MEMBER VARIABLES
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait MemberVariables
{
    public static $constants = array();
    public static $defines = array();
    public static $globals = array();
    public static $methods = array();
    
    protected static $db_host;
    protected static $db_name;
    protected static $db_user;
    protected static $db_password;
    
    private static $instance;
}
