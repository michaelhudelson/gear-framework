<?php
////////////////////////////////////////////////////////////////////////////////
//
// OUTPUT APP
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait OutputApp
{
    // builds the output string:
    // css + js + messages + gf_before_output + $output + gf_after_output
    public function loadOutput($output)
    {
        $return = '';
        // check if js is required and wrap content accordingly
        if ($GLOBALS['gf_require_js']) {
            $return .="<div class='gf-container' style='display:none;'>";
        } else {
            $return .= "<div class='gf-container'>";
        }
        
        // initialize js
        $return .= $this->initializeJs();

        // get css and js into return
        $return .= $this->loadCssJs();

        // load error, debug, and custom messages into return
        $return .= $this->loadMessages();

        // add before_output, output, and after_output to return string
        $return .= $GLOBALS['gf_before_output'] . $output . $GLOBALS['gf_after_output'];

        // close div.gf-container
        $return .= "</div><!-- END .gf-container -->";

        return $return;
    }

    // include js initializing output one time - GF_PUBLIC_URL and <noscript>
    public function initializeJs()
    {
        $return = '';
        // check if this function has already been called
        if ($GLOBALS['gf_js_initialized']) {
            return;
        }

        // set that this function was called
        $GLOBALS['gf_js_initialized'] = true;

        // make public url available to js
        $return .= "
            <script>
                window.GF_PUBLIC_URL = '" . GF_PUBLIC_URL . "';
            </script>
            ";

        // provide noscript if js is required
        if ($GLOBALS['gf_require_js']) {
            $return .="
            <noscript>
                <h1>You must enable JavaScript for this application to function properly.</h1>
            </noscript>
                ";
        }

        return $return;
    }

    // includes all css and returns the js to be included
    public function loadCssJs()
    {
        $return = '';
        // load css files via wordpress
        foreach ($GLOBALS['gf_css_array'] as $name => $file) {
            if (!in_array($name, $GLOBALS['gf_css_included'])) {
                wp_enqueue_style($name, $file);
                array_push($GLOBALS['gf_css_included'], $name);
            }
        }

        // load js files directly
        foreach ($GLOBALS['gf_js_array'] as $name => $file) {
            if (!in_array($name, $GLOBALS['gf_js_included'])) {
                $return .= "<script src='$file'></script>";
                array_push($GLOBALS['gf_js_included'], $name);
            }
        }
        
        // display gf-container
        $return .= "
            <script>
                var gf_containers = document.getElementsByClassName('gf-container');
                var i = gf_containers.length;
                
                while(i--){
                    gf_containers[i].style.display = 'block';
                }
            </script>
            ";

        // return display js
        return $return;
    }

    // orgainzes messages: error, success, debug, other
    public function loadMessages()
    {
        $return = '';
        // load debug messages
        if ($GLOBALS['gf_debug'] || \Gear::userIsRole('developer')) {
            $return .= $this->displayMessage('Debug', 'panel-warning', $GLOBALS['gf_debug_array']);
        }

        // load error messages
        $return .= $this->displayMessage('Error', 'panel-danger', $GLOBALS['gf_error_array']);

        // load success messages
        $return .= $this->displayMessage('Success', 'panel-success', $GLOBALS['gf_success_array']);

        // load other messages
        foreach ($GLOBALS['gf_message_array'] as $message) {
            $return .= $this->displayMessage($message['title'], $message['class'], $message['messages']);
        }

        // return display messages
        return $return;
    }

    // builds messages into html
    public function displayMessage($title, $class, $messages)
    {
        $return = '';
        if (!empty($messages)) {
            $return .= "
                <div class='panel $class'>
                    <div class='panel-heading'>$title</div>
                    <div class='panel-body'>
                ";

            foreach ($messages as $message) {
                $return .= "$message<br/>";
            }

            $return .= '
                    </div>
                </div>';
        }
        return $return;
    }
}
