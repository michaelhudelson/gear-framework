<?php

/*
Plugin Name: Gear Framework
Description: Web Application Development for WordPress
Version: 1.0.006
Author: Michael Hudelson
Author URI: http://www.gearframework.com
GitHub Repository: https://github.com/michaelhudelson/gear-framework.git
License: GPLv3
*/

namespace GearFramework;
    
// include all trait files for GearFramework
foreach (glob(__DIR__ . "/gear-framework-traits/*.php") as $file_name) {
    require_once($file_name);
}

// singleton class
class GearFramework
{
    use MemberVariables;
    use Constructor;
    use Sessions;
    use AddDeveloperRole;
    use SetupApp;
    use OutputApp;
    use AdminMenu;
    use MVC;
}

// initialize plugin
GearFramework::initialize();
