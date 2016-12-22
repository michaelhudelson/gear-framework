<?php
////////////////////////////////////////////////////////////////////////////////
//
// DATES
//
// TABLE OF CONTENTS
// -----------------
// public static function date($format = 'Y-m-d', $date_time = 'now', $adjust = '-0 days')
// public static function dateTime($format = 'Y-m-d H:i:s', $date_time = 'now', $adjust = '-0 days')
// public static function time($format = 'H:i:s', $date_time = 'now', $adjust = '-0 days')
// 
// public static function formatDate($format = 'Y-m-d', $date_time = 'now')
// public static function formatDateTime($format = 'Y-m-d H:i:s', $date_time = 'now')
// public static function formatTime($format = 'H:i:s', $date_time = 'now')
// 
// public static function adjustDate($adjust = '-0 days', $date_time = 'now')
// public static function adjustDateTime($adjust = '-0 days', $date_time = 'now')
// public static function adjustTime($adjust = '-0 days', $date_time = 'now')
// 
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Dates
{
    // returns date_time in format
    public static function date($format = 'Y-m-d', $date_time = 'now', $adjust = '-0 days')
    {
        // if no date is received, return current in format
        if ($date_time == 'now') {
            $date_time = date('Y-m-d H:i:s');
        }
        
        // replace / with -
        $date_time = str_replace('/', '-', $date_time);
        
        // break date_time into date and time
        $date_time_pieces = explode(' ', $date_time);
        $date = $date_time_pieces[0];
        $time = $date_time_pieces[1];
        
        // get date pieces
        $date_pieces = explode('-', $date);
        
        // reformat date
        if (strlen($date_pieces[0]) == 4) {
            // YYYY-MM-DD to YYYY-MM-DD
            $ymd = "$date_pieces[0]-$date_pieces[1]-$date_pieces[2]";
        } else {
            // MM-DD-YYYY to YYYY-MM-DD
            $ymd = "$date_pieces[2]-$date_pieces[0]-$date_pieces[1]";
        }
        
        // check if time string is malformed
        if (strpos($time, ':') === false) {
            $his = '00:00:00';
        } else {
            $his = $time;
        }
        
        // add tme to return_date
        $ymd_his = "$ymd $his";
        
        // atempt to adjust and format with DateTime
        $datetime_object = \DateTime::createFromFormat('Y-m-d H:i:s', $ymd_his);
        if ($datetime_object !== false) {
            $datetime_object->modify($adjust);
            return $datetime_object->format($format);
        } else {
            // return current date when DateTime fails
            return date($format, strtotime($adjust, $ymd_his));
        }
    }
    
    public static function dateTime($format = 'Y-m-d H:i:s', $date_time = 'now', $adjust = '-0 days')
    {
        return self::date($format, $date_time, $adjust);
    }
    
    public static function time($format = 'H:i:s', $date_time = 'now', $adjust = '-0 days')
    {
        return self::date($format, $date_time, $adjust);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    public static function formatDate($format = 'Y-m-d', $date_time = 'now')
    {
        return self::date($format, $date_time);
    }
    
    public static function formatDateTime($format = 'Y-m-d H:i:s', $date_time = 'now')
    {
        return self::dateTime($format, $date_time);
    }
    
    public static function formatTime($format = 'H:i:s', $date_time = 'now')
    {
        return self::time($format, $date_time);
    }
    
    ////////////////////////////////////////////////////////////////////////////////
    
    public static function adjustDate($adjust = '-0 days', $date_time = 'now')
    {
        return self::date('Y-m-d', $date_time, $adjust);
    }
    
    public static function adjustDateTime($adjust = '-0 days', $date_time = 'now')
    {
        return self::dateTime('Y-m-d H:i:s', $date_time, $adjust);
    }
    
    public static function adjustTime($adjust = '-0 days', $date_time = 'now')
    {
        return self::time('H:i:s', $date_time, $adjust);
    }
}
