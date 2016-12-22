<?php
////////////////////////////////////////////////////////////////////////////////
//
// VIEW HELPERS
//
// TABLE OF CONTENTS
// -----------------
// public static function beforeOutput($output)
// public static function afterOutput($output)
// 
// public static function outputSelect($parms)
// public static function outputDatalist($parms)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait ViewHelpers
{
    // prepend output
    public static function beforeOutput($output)
    {
        $GLOBALS['gf_before_output'] .= $output;
    }

    // append output
    public static function afterOutput($output)
    {
        $GLOBALS['gf_after_output'] .= $output;
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    public static function outputSelect($parms)
    {
        // id: <select> id
        // class: <select> class
        // name: <select> name
        // default_text: <option>default_text</option>
        // options: $key => $value array
        // selected_option: key
        // disabled: true or false
        // style
        if ($parms['disabled']) {
            $disabled = 'disabled';
        }
        
        $return = "
            <select id='{$parms['id']}' class='{$parms['class']}' name='{$parms['name']}' style='{$parms['style']}' $disabled>
            ";
        
        if ($parms['default_text'] !== false) {
            $return .= "
                <option value=''>{$parms['default_text']}</option>
                ";
        }

        foreach ($parms['options'] as $key => $value) {
            $selected = '';
            if ($parms['selected_option'] == $key && !is_null($parms['selected_option'])) {
                $selected = 'selected';
            }
            $return .= "<option value='$key' $selected>$value</option>";
        }

        $return .= "</select>";
        return $return;
    }

    public static function outputDatalist($parms)
    {
        if ($parms['disabled']) {
            $disabled = 'disabled';
        }
        
        $return .= "
            <input type='text' id='{$parms['id']}' class='{$parms['class']}' name='{$parms['name']}' style='{$parms['style']}' list='{$parms['name']}_list' placeholder='{$parms['default_text']}' value='{$parms['selected_option']}' $disabled/>
            <datalist id='{$parms['name']}_list'>";

        foreach ($parms['options'] as $key => $value) {
            $return .= "<option value='$key'>$value</option>";
        }

        $return .= "</datalist>";
        return $return;
    }
}
