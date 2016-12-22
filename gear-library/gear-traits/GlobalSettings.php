<?php
////////////////////////////////////////////////////////////////////////////////
//
// GLOBAL SETTINGS
//
// TABLE OF CONTENTS
// -----------------
// public static function enableDebug()
// public static function disableDebug()
// 
// public static function enableQueryDebug()
// public static function disableQueryDebug()
// 
// public static function enablePostResubmit()
// public static function disablePostResubmit()
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait GlobalSettings
{
    // enabling debugging per page
    public static function enableDebug()
    {
        $GLOBALS['gf_debug'] = true;
    }

    // disable debugging per page
    public static function disableDebug()
    {
        $GLOBALS['gf_debug'] = false;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // enabling debugging per page
    public static function enableQueryDebug()
    {
        $GLOBALS['gf_query_debug'] = true;
    }

    // disable debugging per page
    public static function disableQueryDebug()
    {
        $GLOBALS['gf_query_debug'] = false;
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // enabling post resubmit per page
    public static function enablePostResubmit()
    {
        $GLOBALS['gf_post_resubmit'] = true;
    }

    // disable post resubmit per page
    public static function disablePostResubmit()
    {
        $GLOBALS['gf_post_resubmit'] = false;
    }
}
