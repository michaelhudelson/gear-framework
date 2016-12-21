<?php
////////////////////////////////////////////////////////////////////////////////
//
// ADD DEVELOPER ROLE
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait AddDeveloperRole
{
    public function addDeveloperRole()
    {
        // get roles object
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }

        // get administrator role
        $administrator = $wp_roles->get_role('administrator');

        // crete developer role with administrator capabilities
        $wp_roles->add_role('developer', 'Developer', $administrator->capabilities);
    }
}
