<?php
//////////////////////////////////////////////////////////////////////////////////
//
// MVC
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait MVC
{
    public function mvc($atts)
    {
        // setup app
        $this->setupApp();

        // get page slug
        $post = get_post();
        $page_slug = $post->post_name;

        // check for mvc attribute to set M, V, and C
        // else default all to page slug
        if (isset($atts['mvc'])) {
            $model_name = $view_name = $controller_name = $atts['mvc'];
        } else {
            // covert slug-format to SlugFormat
            $model_name = $view_name = $controller_name = $this->slugToMvc($page_slug);
        }

        // get model attribute from short code
        if (isset($atts['m'])) {
            $model_name = $atts['m'];
        }
 
        $model_path = GF_MODEL_PATH . '/' .$model_name . 'Model.php';
        if (!file_exists($model_path)) {
            if ($model_name == '') {
                // empty model
                $model_path = GF_PLUGIN_PATH . '/assets/models/EmptyModel.php';
            } else {
                // default model
                $model_path = GF_PLUGIN_PATH . '/assets/models/DefaultModel.php';
                $model_name = '';
            }
        }
        include_once $model_path;

        // get view attribute from short code
        if (isset($atts['v'])) {
            $view_name = $atts['v'];
        }
        
        $view_path = GF_VIEW_PATH . '/' . $view_name . 'View.php';
        if (!file_exists($view_path)) {
            if ($view_name == '') {
                // empty view
                $view_path = GF_PLUGIN_PATH . '/assets/views/EmptyView.php';
            } else {
                // default view
                $view_path = GF_PLUGIN_PATH . '/assets/views/DefaultView.php';
                $view_name = '';
            }
        }
        include_once $view_path;

        // get controller attribute from short code
        if (isset($atts['c'])) {
            $controller_name = $atts['c'];
        }
        
        $controller_path = GF_CONTROLLER_PATH . '/' . $controller_name . 'Controller.php';
        if (!file_exists($controller_path)) {
            if ($controller_name == '') {
                $controller_path = GF_PLUGIN_PATH . '/assets/controllers/EmptyController.php';
            } else {
                // default controller
                $controller_path = GF_PLUGIN_PATH . '/assets/controllers/DefaultController.php';
                $controller_name = '';
            }
        }
        include_once $controller_path;

        // build class names
        $model_class = ($model_name == '') ? '\GearFramework\Model' : "\\GearFramework\\$model_name\\Model";
        $controller_class = ($controller_name == '') ? '\GearFramework\Controller' : "\\GearFramework\\$controller_name\\Controller";
        $view_class = ($view_name == '') ? '\GearFramework\View' : "\\GearFramework\\$view_name\\View";

        // run mvc
        if (class_exists($model_class) && class_exists($controller_class) && class_exists($view_class)) {
            $model = new $model_class();
            $controller = new $controller_class($model);
            $view = new $view_class($model);

            // check that the user's role has access to this page slug
            // runs after MVC __constructors
            // @ to suppress php notice about undefined index
            @$user_access_not_allowed = !(\Gear::userIsRole($GLOBALS['gf_roles_allowed_access_array'][$page_slug]) || empty($GLOBALS['gf_roles_allowed_access_array'][$page_slug]));
            @$user_access_denied = \Gear::userIsRole($GLOBALS['gf_roles_denied_access_array'][$page_slug]);
            if (($user_access_denied || $user_access_not_allowed) && \Gear::userNotRole('developer')) {
                return $this->loadOutput($GLOBALS['gf_access_denied_message']);
            }

            // get control request
            // if the controller requested matches this controller
            // or if no controller was specified
            $control = null;
            // @ to suppress php notice about undefined index
            if (@$_POST['gf_controller'] === $controller_name || !isset($_POST['gf_controller'])) {
                // grab requested control
                if (isset($_POST['gf_control'])) {
                    $control = $_POST['gf_control'];
                }
            }

            // run requested control
            if (!is_null($control) && \Gear::tokenValid()) {
                // check for and run control
                if (method_exists($controller, $control)) {
                    $controller->$control();
                    // check for post resubmission
                    if (!$GLOBALS['gf_post_resubmit']) {
                        die("<script>window.location.replace('" . $_SERVER['REQUEST_URI'] . "');</script>");
                    }
                } else {
                    \Gear::debug("\\{$controller_name}\\Controller->{$control}(): Does not exist.");
                }
            }

            // get view output
            $view_output = $view->output();
        } else {
            if (!class_exists($model_class)) {
                \Gear::debug("$model_class: Does not exist.");
            }
            if (!class_exists($controller_class)) {
                \Gear::debug("$controller_class: Does not exist.");
            }
            if (!class_exists($view_class)) {
                \Gear::debug("$view_class: Does not exist.");
            }
        }

        return $this->loadOutput($view_output);
    }
}
