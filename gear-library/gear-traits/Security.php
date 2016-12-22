<?php
////////////////////////////////////////////////////////////////////////////////
//
// SECURITY
//
// TABLE OF CONTENTS
// -----------------
// public static function bindController($controller, $control)
// public static function bindControl($control)
// 
// public static function token()
// public static function tokenValid()
// 
// public static function post($keys = null)
// public static function get($keys = null)
// protected static function postGet($array, $keys)
// protected static function postGetCleanse($string)
// 
// public static function encrypt($string, $encryption_key = null)
// public static function decrypt($url_encryption, $encryption_key = null)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Security
{
    // bind a form to a controller and control
    public static function bindController($controller, $control)
    {
        return " 
            <input type='hidden' name='gf_controller' value='$controller'>
            <input type='hidden' name='gf_control' value='$control'>
            <input type='hidden' name='gf_token' value='" . self::token() . "'>
            ";
    }
    
    // bind a form to a control
    public static function bindControl($control)
    {
        return " 
            <input type='hidden' name='gf_control' value='$control'>
            <input type='hidden' name='gf_token' value='" . self::token() . "'>
            ";
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // set if not set and return token
    public static function token()
    {
        if (!isset($_SESSION['gf_token'])) {
            $_SESSION['gf_token'] = sha1(mt_rand());
        }
        return $_SESSION['gf_token'];
    }

    // verify token and same server request origin
    public static function tokenValid()
    {
        $url = explode('://', $_SERVER['HTTP_REFERER'])[1];
        $host = explode('/', $url)[0];
        
        if (self::token() === $_POST['gf_token'] && $host === $_SERVER['HTTP_HOST']) {
            return true;
        } else {
            self::debug("HTTP_REFERER: " . $_SERVER['HTTP_REFERER']);
            self::debug("Session Token: " . self::token());
            self::debug("Request Token: " . $_POST['gf_token']);
            self::error('Token invalid');
            return false;
        }
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // get cleansed $_POST data
    public static function post($keys = null)
    {
        if (!is_array($keys) && !is_null($keys) && func_num_args() > 1) {
            $keys = func_get_args();
        }
        return self::postGet($_POST, $keys);
    }

    // get cleansed $_GET data
    public static function get($keys = null)
    {
        if (!is_array($keys) && !is_null($keys) && func_num_args() > 1) {
            $keys = func_get_args();
        }
        return self::postGet($_GET, $keys);
    }

    // get cleansed $_POST or $_GET data
    protected static function postGet($array, $keys)
    {
        // if index is null, return all array keys
        if (is_null($keys)) {
            $keys = array_keys($array);
        }
        if (is_array($keys)) {
            $return = array();
            foreach ($array as $key => $value) {
                if (in_array($key, $keys)) {
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            $value[$k] = ($v == '') ? null : self::postGetCleanse($v);
                        }
                    } else {
                        $value = ($value == '') ? null : self::postGetCleanse($value);
                    }
                    $return[$key] = $value;
                }
            }
        } else {
            $value = $array[$keys];
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = ($v == '') ? null : self::postGetCleanse($v);
                }
            } else {
                $value = ($value == '') ? null : self::postGetCleanse($value);
            }
            $return = $value;
        }
        return $return;
    }
    
    // remove potentially harmful user data
    protected static function postGetCleanse($string)
    {
        return htmlentities(str_replace('\"', '"', str_replace("\'", "'", trim($string))), ENT_QUOTES);
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // encrypt and url encode a string using an encryption key
    public static function encrypt($string, $encryption_key = null)
    {
        if (is_null($encryption_key)) {
            $encryption_key  = AUTH_KEY;
        }
        $encryption = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($encryption_key), $string, MCRYPT_MODE_CBC, md5(md5($encryption_key)));
        $url_encryption = rtrim(strtr(base64_encode($encryption), '+/', '-_'), '=');
        return $url_encryption;
    }

    // decrypt a url encoding string using an encryption key
    public static function decrypt($url_encryption, $encryption_key = null)
    {
        if (is_null($encryption_key)) {
            $encryption_key  = AUTH_KEY;
        }
        $encryption = base64_decode(str_pad(strtr($url_encryption, '-_', '+/'), strlen($url_encryption) % 4, '=', STR_PAD_RIGHT));
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($encryption_key), $encryption, MCRYPT_MODE_CBC, md5(md5($encryption_key))), "\0");
    }
}
