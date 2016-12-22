<?php
////////////////////////////////////////////////////////////////////////////////
//
// MESSAGES
//
// TABLE OF CONTENTS
// -----------------
// public static function debug($message = null)
// public static function error($message = null)
// public static function success($message = null)
// public static function systemError($message)
// 
// public static function createMessage($title, $class = '', $messages = null)
// public static function message($index, $message)
// 
// protected static function messageFormat($message, $format)
// protected static function messageCleanse($value)
// protected static function messageTab($tab, $indent)
// 
// public static function log($file, $message)
// public static function errorLog($message)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Messages
{
    // push debug messages - to be handled by gear framework
    public static function debug($message = null)
    {
        array_push($GLOBALS['gf_debug_array'], self::messageFormat($message));
    }

    // push error messages - to be handled by gear framework
    public static function error($message = null)
    {
        array_push($GLOBALS['gf_error_array'], self::messageFormat($message));
    }

    // push success messages - to be handled by gear framework
    public static function success($message = null)
    {
        array_push($GLOBALS['gf_success_array'], self::messageFormat($message));
    }
    
    public static function systemError($messages)
    {
        // add labels to the messages
        array_unshift($messages, '========================================');
        array_unshift($messages, 'MESSAGES');
        array_unshift($messages, '========================================');
        array_unshift($messages, PHP_EOL);
        
        $messages[] = PHP_EOL;
        $messages[] = '========================================';
        $messages[] = 'DEBUG BACKTRACE';
        $messages[] = '========================================';
        
        // build debug backtrace
        $debug_backtrace = debug_backtrace();
        
        for ($i = 3; $i >= 0; $i--) {
            $debug_backtrace[$i]['function'] = $debug_backtrace[$i]['class'].$debug_backtrace[$i]['type'].$debug_backtrace[$i]['function'];
            unset($debug_backtrace[$i]['class']);
            unset($debug_backtrace[$i]['type']);
            unset($debug_backtrace[$i]['object']);
            $messages[] = $debug_backtrace[$i];
        }
        
        // output debug and log the error
        self::debug($messages);
        self::errorLog($messages);
        self::error('There was an internal system error. Please contact an administrator.');
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // creates new message groups like error and debug on the fly
    public static function createMessage($title, $class = '', $messages = null)
    {
        if (!is_array($messages)) {
            $messages = (array)$messages;
        }
        foreach ($messages as $key => $message) {
            $messages[$key] = self::messageFormat($message);
        }
        array_push($GLOBALS['gf_message_array'], array('title' => $title, 'class' => $class, 'messages' => (array)$messages));
        return sizeof($GLOBALS['gf_message_array']) - 1;
    }

    // push messages - to be handled by gear framework
    public static function message($index, $message = null)
    {
        array_push($GLOBALS['gf_message_array'][$index]['messages'], self::messageFormat($message));
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    // convert array into string output
    protected static function messageFormat($message, $format = array('new_line' => '<br/>', 'tab' => '|&nbsp; &nbsp; &nbsp; &nbsp;', 'indent' => 0))
    {
        // output objects
        if (is_object($message)) {
            $vars = get_object_vars($message);
            if (empty($vars)) {
                return "Object ()";
            }
            
            $return .= "Object ({$format['new_line']}";
            $format['indent']++;
            
            foreach ($vars as $key => $value) {
                $return .= self::messageTab($format['tab'], $format['indent']) . "->$key - " . self::messageFormat($value, $format, $format['indent']) . $format['new_line'];
            }
            
            $return .= self::messageTab($format['tab'], --$format['indent']) . ")";
            return $return;
        }
        
        // output arrays
        if (is_array($message)) {
            if (empty($message)) {
                return "Array ()";
            }
            
            $return .= "Array ({$format['new_line']}";
            $format['indent']++;
            
            foreach ($message as $key => $value) {
                $return .= self::messageTab($format['tab'], $format['indent']) . "[$key] - " . self::messageFormat($value, $format, $format['indent']) . $format['new_line'];
            }
            
            $return .= self::messageTab($format['tab'], --$format['indent']) . ")";
            return $return;
        }
        
        // output simple variables
        return self::messageCleanse($message);
    }
    
    // set null, empty string, space, true, and false
    protected static function messageCleanse($value)
    {
        if (is_null($value)) {
            $return = 'null';
        } elseif ($value === true) {
            $return = 'true';
        } elseif ($value === false) {
            $return = 'false';
        } elseif ($value == '') {
            $return = "''";
        } elseif ($value == ' ') {
            $return = "' '";
        } else {
            $return = $value;
        }
        
        return $return;
    }
    
    // adds a tab to the message
    protected static function messageTab($tab, $indent = 1)
    {
        $return = "";
        for ($i = 0; $i < $indent; $i++) {
            $return .= $tab;
        }
        return $return;
    }
    
    ////////////////////////////////////////////////////////////////////////////////

    // add message to $file
    public static function log($log_file, $messages)
    {
        // expect messages to be array
        if (!is_array($messages)) {
            $messages = array($messages);
        }
        
        $log_message .=
            "================================================================================".PHP_EOL.
            self::dateTime().PHP_EOL.
            "================================================================================".PHP_EOL
            ;
              
        foreach ($messages as $message) {
            $log_message .= self::messageFormat($message, array('new_line' => PHP_EOL, 'tab' => '|   ')).PHP_EOL;
        }

        $log_message .= PHP_EOL.PHP_EOL.PHP_EOL;

        error_log($log_message, 3, $log_file);
    }
    
    // add message to GF_ERROR_LOG file
    public static function errorLog($messages)
    {
        self::log(GF_ERROR_LOG, $messages);
    }
}
