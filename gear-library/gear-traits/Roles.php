<?php
////////////////////////////////////////////////////////////////////////////////
//
// ROLES
//
// TABLE OF CONTENTS
// -----------------
// public static function userRoles($user_id = null)
// 
// public static function userIsRole([$user_id], $roles)
// public static function userNotRole([$user_id], $roles)
// 
// public static function allowedRoles($roles = null)
// public static function addAllowedRoles($roles = null)
// public static function addAllowedUser()
// public static function allowRolesAccess($roles = null)
// public static function allowUserAccess()
// 
// public static function deniedRoles($roles = null)
// public static function addDeniedRoles($roles = null)
// public static function addDeniedUser()
// public static function denyRolesAccess($roles = null)
// public static function denyUserAccess()
// 
// public static function setAccessDeniedMessage($message)
// 
// private static function developerAsAdministrator(&$roles)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Roles
{
    public static function userRoles($user_id = null)
    {
        // default user_id to current user if null
        if (is_null($user_id)) {
            $user_id = get_current_user_id();
        }
        
        // guest users
        if ($user_id == '') {
            return array('');
        }
        
        // get users role
        $user = new \WP_User($user_id);
        return $user->roles;
    }
    
////////////////////////////////////////////////////////////////////////////////
    
    public static function userIsRole() // [$user_id], $roles
    {
        $args = func_get_args();
        
        // check for first parameter to be user_id
        if (is_numeric($args[0])) {
            $user_id = (int)$args[0];
            $roles = $args[1];
        } else {
        // if not get current user id
            $user_id = get_current_user_id();
            $roles = $args[0];
        }

        // if roles is not array, turn it into an array with all non user id args
        if (!is_array($roles)) {
            $roles = array();
            
            foreach ($args as $arg) {
                if (is_int($arg)) {
                    continue;
                }
                array_push($roles, $arg);
            }
        }
        
        // if administrator is present add developer
        self::developerAsAdministrator($roles);
        
        // get users role
        $user = new \WP_User($user_id);
        $user_roles = $user->roles;
        
        // guest user
        if (empty($user_roles)) {
            $user_roles = array('');
        }

        // check if users role is in requested roles
        foreach ($user_roles as $user_role) {
            foreach ($roles as $role) {
                if ($user_role === $role) {
                    return true;
                }
            }
        }
        
        // no role was found
        return false;
    }
    
    public static function userNotRole() // [$user_id], $roles
    {
        // inverse of user_is_role()
        $args = func_get_args();
        if (call_user_func_array("self::userIsRole", $args)) {
            return false;
        } else {
            return true;
        }
    }
    
////////////////////////////////////////////////////////////////////////////////
    
    // sets allowed roles for current page slug
    public static function allowedRoles($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        // get page slug
        $post = get_post();
        $page_slug = $post->post_name;
        
        // clear out allowed roles for this page slug
        $GLOBALS['gf_roles_allowed_access_array'][$page_slug] =  array();
        
        // return list of allowed roles
        return self::addAllowedRoles($roles);
    }
    
    // adds allowed roles for current page slug
    public static function addAllowedRoles($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        // get page slug
        $post = get_post();
        $page_slug = $post->post_name;
        
        // merge existing allowed roles with received allowed roles
        $allowed_roles = (is_array($GLOBALS['gf_roles_allowed_access_array'][$page_slug])) ? $GLOBALS['gf_roles_allowed_access_array'][$page_slug] : array();
        $GLOBALS['gf_roles_allowed_access_array'][$page_slug] =  array_unique(array_merge($roles, $allowed_roles));

        // return list of allowed roles
        return $GLOBALS['gf_roles_allowed_access_array'][$page_slug];
    }
    
    // adds allowed role for current user
    public static function addAllowedUser()
    {
        return self::addAllowedRoles(self::userRoles());
    }
    
    // rename for add_allowed_roles
    public static function allowRolesAccess($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        return self::addAllowedRoles($roles);
    }
    
    // rename for add_allowed_user
    public static function allowUserAccess()
    {
        return self::addAllowedUser();
    }
    
////////////////////////////////////////////////////////////////////////////////
    
    // sets denied roles for current page slug
    public static function deniedRoles($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        // get page slug
        $post = get_post();
        $page_slug = $post->post_name;
        
        // clear out denied roles for this page slug
        $GLOBALS['gf_roles_denied_access_array'][$page_slug] =  array();
        
        // return list of denied roles
        return self::addDeniedRoles($roles);
    }
    
    // adds denied roles for current page slug
    public static function addDeniedRoles($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        // get page slug
        $post = get_post();
        $page_slug = $post->post_name;
        
        // merge existing denied roles with received denied roles
        $denied_roles = (is_array($GLOBALS['gf_roles_denied_access_array'][$page_slug])) ? $GLOBALS['gf_roles_denied_access_array'][$page_slug] : array();
        $GLOBALS['gf_roles_denied_access_array'][$page_slug] =  array_unique(array_merge($roles, $denied_roles));

        // return list of denied roles
        return $GLOBALS['gf_roles_denied_access_array'][$page_slug];
    }
    
    // adds denied role for current user
    public static function addDeniedUser()
    {
        return self::addDeniedRoles(self::userRoles());
    }
    
    // rename for add_denied_roles
    public static function denyRolesAccess($roles = null)
    {
        // get roles as array
        if (!is_array($roles)) {
            $roles = func_get_args();
        }
        
        return self::addDeniedRoles($roles);
    }
    
    // rename for add_denied_user
    public static function denyUserAccess()
    {
        return self::addDeniedUser();
    }
    
////////////////////////////////////////////////////////////////////////////////
    
    // change access denied message
    public static function setAccessDeniedMessage($message)
    {
        $GLOBALS['gf_access_denied_message'] =  $message;
    }
    
////////////////////////////////////////////////////////////////////////////////
    
    // if administrator is in list of roles, add developer
    private static function developerAsAdministrator(&$roles)
    {
        if (in_array('administrator', $roles)) {
            array_push($roles, 'developer');
        }
    }
}
