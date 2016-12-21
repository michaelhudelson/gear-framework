<?php
////////////////////////////////////////////////////////////////////////////////
//
// CSS AND JS
//
// TABLE OF CONTENTS
// -----------------
// public static function css($css_array)
// public static function noCss()
// public static function addCss($name, $path = null)
// 
// public static function js($js_array) 
// public static function noJs()
// public static function addJs($name, $path = null)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait CssAndJs
{
    // set css to be loaded
    public static function css($css_array)
    {
        $GLOBALS['gf_css_array'] = $css_array;
    }

    // clear css to be loaded
    public static function noCss()
    {
        $GLOBALS['gf_css_array'] = array();
    }

    // clear css to be loaded
    public static function addCss($name, $path = null)
    {
        if (is_null($path)) {
            $path = GF_PUBLIC_URL . "/css/$name.css";
        }
        $GLOBALS['gf_css_array'][$name] = $path;
    }
    
////////////////////////////////////////////////////////////////////////////////

    // set js to be loaded
    public static function js($js_array)
    {
        $GLOBALS['gf_js_array'] = $js_array;
    }

    // clear js to be loaded
    public static function noJs()
    {
        $GLOBALS['gf_js_array'] = array();
    }

    // clear js to be loaded
    public static function addJs($name, $path = null)
    {
        if (is_null($path)) {
            $path = GF_PUBLIC_URL . "/js/$name.js";
        }
        $GLOBALS['gf_js_array'][$name] = $path;
    }
}
