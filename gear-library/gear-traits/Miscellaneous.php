<?php
////////////////////////////////////////////////////////////////////////////////
//
// MISCELLANEOUS
//
// TABLE OF CONTENTS
// -----------------
// public static function redirect($url)
//
////////////////////////////////////////////////////////////////////////////////

namespace Gear;

trait Miscellaneous
{
    // use javascript to forward
    public static function redirect($url)
    {
        die("<script>location.replace('$url');</script>");
    }
}
