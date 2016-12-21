<?php
    
// include all trait files for Gear
foreach (glob(__DIR__ . "/gear-traits/*.php") as $file_name) {
    require_once($file_name);
}

// static class
class Gear
{
    use \Gear\MemberVariables;
    use \Gear\Constructor;
    use \Gear\DatabaseAccess;
    use \Gear\Messages;
    use \Gear\GlobalSettings;
    use \Gear\ViewHelpers;
    use \Gear\Security;
    use \Gear\Validation;
    use \Gear\CssAndJs;
    use \Gear\Roles;
    use \Gear\Dates;
    use \Gear\Miscellaneous;
}

// initialize library
\Gear::initialize();
