<?php
////////////////////////////////////////////////////////////////////////////////
//
// VALIDATION
//
// TABLE OF CONTENTS
// -----------------
// public static function valueValid($value, $rules)
// public static function tableValuesValid($table, $values, $parms = array()) 
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Validation
{
    // validate data
    public static function valueValid($value, $rules)
    {
        $label = $rules['label'];
        $type_width = $rules['type'];
        $unsigned = $rules['unsigned'];
        $not_null = $rules['not_null'];
        
        // set up flag and split type
        $valid = true;
        $pieces = explode('(', $type_width);
        $type = $pieces[0];
        $width = explode(')', $pieces[1])[0];
        //////////////////////
        // validate not null
        //////////////////////
        if ($not_null && is_null($value)) {
            self::error($label . " may not be left blank.");
            return false;
        }
        if (is_null($value)) {
            return true;
        }
        //////////////////////
        // validate string
        //////////////////////
        if (in_array($type, array('char', 'varchar'))) {
            if (strlen($value) > $width) {
                self::error($label . " may not exceed $width characters.");
                $valid = false;
            }
        } elseif (in_array($type, array('boolean', 'tinyint', 'smallint', 'mediumint', 'int', 'bigint'))) {
            //////////////////////
            // validate integer
            //////////////////////
            $min = 0;
            $max = 0;
            
            // which int
            switch ($type) {
                case 'boolean':
                case 'tinyint':
                    $max = 127;
                    break;
                case 'smallint':
                    $max = 32767;
                    break;
                case 'mediumint':
                    $max = 8388607;
                    break;
                case 'int':
                    $max = 2147483647;
                    break;
                case 'bigint':
                    $max = 9223372036854775807;
                    break;
            }
            
            // adjust range
            if ($unsigned) {
                $max = $max * 2 + 1;
            } else {
                $min = $max * -1 -1;
            }
            
            if ($value > $max || $value < $min || !is_numeric($value)) {
                self::error($label . " must be between $min and $max.");
                $valid = false;
            }
        } elseif (in_array($type, array('float', 'double', 'decimal'))) {
            //////////////////////
            // validate decimal
            //////////////////////
            // get left and right width allowed
            $left_right_width = explode(',', $width);
            $right_width = $left_right_width[1];
            $left_width = $left_right_width[0] - $right_width;
            
            // get max left digits
            $max = str_repeat('9', $left_width);
            // get max right digits
            if ($right_width > 0) {
                $max .= '.' . str_repeat('9', $right_width);
            }
            
            // get min
            $min = $unsigned ? '0' : "-$max";
            
            if ($value > $max || $value < $min || !is_numeric($value)) {
                self::error($label . " must be between $min and $max.");
                $valid = false;
            }
        } elseif ($type == 'date') {
            //////////////////////
            // validate date
            //////////////////////
            $slash_mdy = \DateTime::createFromFormat('m/d/Y', $value);
            $slash_ymd = \DateTime::createFromFormat('Y/m/d', $value);
            $dash_mdy = \DateTime::createFromFormat('m-d-Y', $value);
            $dash_ymd = \DateTime::createFromFormat('Y-m-d', $value);
            
            // check all possible formats
            if ($slash_mdy === false && $slash_ymd === false  && $dash_mdy === false  && $dash_ymd === false) {
                self::error($label . " must be in proper format (YYYY-MM-DD or MM-DD-YYYY).");
                $valid = false;
            }
        } elseif ($type == 'datetime') {
            $slash_mdy = \DateTime::createFromFormat('m/d/Y H:i:s', $value);
            $slash_ymd = \DateTime::createFromFormat('Y/m/d H:i:s', $value);
            $dash_mdy = \DateTime::createFromFormat('m-d-Y H:i:s', $value);
            $dash_ymd = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            
            // check all possible formats
            if ($slash_mdy === false && $slash_ymd === false  && $dash_mdy === false  && $dash_ymd === false) {
                self::error($label . " must be in proper format (YYYY-MM-DD HH:MM:SS or MM-DD-YYYY HH:MM:SS).");
                $valid = false;
            }
        } elseif ($type == 'timestamp') {
            $slash_mdy = \DateTime::createFromFormat('m/d/Y H:i:s', $value);
            $slash_ymd = \DateTime::createFromFormat('Y/m/d H:i:s', $value);
            $dash_mdy = \DateTime::createFromFormat('m-d-Y H:i:s', $value);
            $dash_ymd = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            
            $value_year = self::date('Y', $value);
            
            // check all possible formats and year range
            if (($slash_mdy === false && $slash_ymd === false  && $dash_mdy === false  && $dash_ymd === false) || $value_year < 1970 || $value_year > 2037) {
                self::error($label . " must be in proper format (YYYY-MM-DD HH:MM:SS or MM-DD-YYYY HH:MM:SS) and between 01-01-1970 and 12-31-2037.");
                $valid = false;
            }
        } elseif ($type == 'time') {
            // check all possible formats
            $his = \DateTime::createFromFormat('H:i:s', $value);
            $hi = \DateTime::createFromFormat('H:i', $value);
            if ($his === false && $hi === false) {
                self::error($label . " must be in proper format (HH:MM:SS).");
                $valid = false;
            }
        } elseif ($type == 'year') {
            // two digit year
            if ($width == 2) {
                if ($value < 0 || $value > 99) {
                    self::error($label . " must be a two digit year between 1970 and 2069.");
                    $valid = false;
                }
            } else {
                // four digit year
                if ($value < 1901 || $value > 2155) {
                    self::error($label . " must be a year between 1901 and 2155.");
                    $valid = false;
                }
            }
        } elseif (in_array($type, array('tinyblob', 'tinytext', 'blob', 'text',  'mediumblog', 'mediumtext', 'longblob', 'longtext'))) {
            //////////////////////
            // validate blob/text
            //////////////////////
            // which blob/text
            switch ($type) {
                case 'tinyblob':
                case 'tinytext':
                    $max = 255;
                    break;
                case 'blob':
                case 'text':
                    $max = 65535;
                    break;
                case 'mediumblog':
                case 'mediumtext':
                    $max = 16777215;
                    break;
                case 'longblob':
                case 'longtext':
                    $max = 4294967295;
                    break;
            }
            
            if (strlen($value) > $max) {
                self::error($label . " must be $max characters or less.");
                $valid = false;
            }
        } elseif ($type == 'enum') {
            //////////////////////
            // validate enum
            //////////////////////
            // get allowed enum values
            $enum_vals = explode(',', $width);
            foreach ($enum_vals as $key => $val) {
                $enum_vals[$key] = trim($val, "'");
            }
            
            if (!in_array($value, $enum_vals)) {
                self::error($label . " must be one of the following values: " . implode(', ', $enum_vals));
                $valid = false;
            }
        } else {
            //////////////////////
            // unrecongnized type
            //////////////////////
            $valid = false;
            $message = array();
            $message[] = "Validation type ($type) is unrecognized.";
            $message[] = $rules;
            self::systemError($message);
        }
        
        return $valid;
    }

    // validate data against table
    public static function tableValuesValid($table, $values, $parms = array())
    {
        // $parms['labels'][$column] - contains new label name
        // $parms['true_callback'] - function to call on true
        // $parms['false_callback'] - function to call on false
        
        // false if no values are provided
        if (empty($values)) {
            $message = array();
            $message[] = "No values received for table ($table).";
            $message[] = $values;
            self::systemError($message);
            return false;
        }
        
        // set a valid flag and prepare an array to hold all table rules
        $valid = true;
        $table_rules = array();
        
        // set all table rules by column
        $rows = self::query("SHOW FIELDS FROM $table");
        foreach ($rows as $row) {
            $column = $row['Field'];
            $label = "'".ucwords(str_replace('_', ' ', $row['Field']))."'";
            $pieces = explode(' ', $row['Type']);
            $type = $pieces[0];
            $unsigned = ($pieces[1] == 'unsigned') ? true : false;
            $not_null = ($row['Null'] == 'NO') ? true : false;
            
            // overwrite label name if one was provided
            if (is_array($parms['labels'])) {
                if (array_key_exists($column, $parms['labels'])) {
                    $label = $parms['labels'][$column];
                }
            }
            
            $rules = array(
                'column' => $column,
                'label' => $label,
                'type' => $type,
                'unsigned' => $unsigned,
                'not_null' => $not_null
                );
            
            $table_rules[$column] = $rules;
        }
        
        // compare all values against table rules
        foreach ($values as $column => $value) {
            // check that column exists
            if (array_key_exists($column, $table_rules)) {
                if (!self::valueValid($value, $table_rules[$column])) {
                    $valid = false;
                }
            } else {
                $message = array();
                $message[] = "Column ($column) does not exist in table ($table).";
                self::systemError($message);
                $valid = false;
            }
        }
        
        // true call back
        if ($valid && isset($parms['on_true_callback'])) {
            call_user_func($parms['on_true_callback']);
        }
        
        // false call back
        if (!$valid && isset($parms['on_false_callback'])) {
            call_user_func($parms['on_false_callback']);
        }
        
        return $valid;
    }
}
