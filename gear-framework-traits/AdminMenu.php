<?php
////////////////////////////////////////////////////////////////////////////////
//
// ADMIN MENU
//
////////////////////////////////////////////////////////////////////////////////

namespace GearFramework;

trait AdminMenu
{
    // add admin menu page
    public function adminMenu()
    {
        // get gf defines
        $this->setupDefines();

        // include gf library
        include_once GF_PLUGIN_PATH . "/gear-library/Gear.php";
        
        // add css
        wp_enqueue_style('gear_framework_css', plugins_url(dirname(dirname(plugin_basename(__FILE__))) . "/assets/public/css/style.css"));

        // main menu ie settings menu
        add_menu_page('Gear Framework Settings', 'Gear Framework', 'manage_options', 'gf', array($this, 'settingsMenu'), plugins_url(dirname(dirname(plugin_basename(__FILE__))) . "/assets/public/images/icon.svg"));

        // change main menu name to settings in submenu
        add_submenu_page('gf', 'Gear Framework Settings', 'Settings', 'manage_options', 'gf');

        // add page menu
        add_submenu_page('gf', 'Gear Framework Add Page', 'Add Page', 'manage_options', 'gf_add_page', array($this, 'addPageMenu'));
    }

    // output settings page
    public function settingsMenu()
    {
        // display forms for setting GF_APP_PATH and GF_PUBLIC_PATH
        $gf_app_path = get_option('gf_app_path');
        $gf_public_path = get_option('gf_public_path');
        $html = "
        <div class='wrap'>
            <h1>Settings</h1>
            <p>
                Define where the application's root folder and the applications' public folder are located.
            </p>
            <form action='options.php' method='post' name='options'>
                ".wp_nonce_field('update-options')."
                <input type='hidden' name='action' value='update'/>
                <input type='hidden' name='page_options' value='gf_app_path, gf_public_path,'/>
                <table class='form-table'>
                    <tbody>
                        <tr>
                            <th scope='row'>
                                <label>GF_APP_PATH: </label>
                            </th>
                            <td>
                                <input class='gf_input' type='text' name='gf_app_path' value='$gf_app_path' placeholder='Leave blank for default'/>
                                <br/>
                                ".GF_APP_PATH."
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>GF_PUBLIC_PATH: </label>
                            </th>
                            <td>
                                <input class='gf_input' type='text' name='gf_public_path' value='$gf_public_path' placeholder='Leave blank for default'/>
                                <br/>
                                ".GF_PUBLIC_PATH."
                            </td>
                        </tr>
                    <tbody>
                </table>
                <input class='button button-primary' type='submit' name='Submit' value='Update'/>
            </form>
        </div>
        ";
        echo $html;
        
        // check that the file structure is installed
        $this->installStructure();
    }

    public function installStructure()
    {
        // check if install form was submitted
        if ($_POST['gf_action'] === 'gf_install_structure') {
            // get download url
            $url = (empty($_POST['zip_url'])) ? "http://www.gearframework.com/gf-app.zip" : $_POST['zip_url'];

            // check url is valid format and a zip file
            if (!filter_var($url, FILTER_VALIDATE_URL) === false && substr($url, -4) == '.zip') {
                // set paths for zip and unzip
                $zip_path = GF_PLUGIN_PATH . "/gf-app.zip";
                $temp_path = GF_PLUGIN_PATH . "/temp";

                // get files
                file_put_contents($zip_path, fopen($url, 'r'));

                // unzip
                exec("unzip $zip_path -d $temp_path 2>&1", $output, $error);
                
                if ($error) {
                    foreach ($output as $line) {
                        echo "<h1 class='gf_error'>$line</h1>";
                    }
                } else {
                    echo "<br/>";
                    foreach ($output as $line) {
                        echo "<div>$line</div>";
                    }
                    echo "<h1>Successfully Installed!</h1>";
                }

                // change permissions
                exec("chmod -R 775 $temp_path");
                exec("find $temp_path -type f -exec chmod -R 664 {} \;");

                // place files
                exec("mv $temp_path/gf-app " . GF_APP_PATH);

                // remove zip and temp
                exec("rm $zip_path");
                exec("rm -rf $temp_path");
            } else {
                $message = "<h3 class='gf_error'>Error - Invalid URL (Leave blank for default)</h3>";
            }
        }

        // if GF_APP_PATH does NOT exists display form
        if (!file_exists(GF_APP_PATH)) {
            echo "
                <br/>
                $message
                <form method='post'>
                    <label>Please install the application file structure at GF_APP_PATH:</label>
                    <input type='hidden' name='gf_action' value='gf_install_structure'/>
                    <!-- FUTURE FEATURE
                    <input type='text' name='zip_url' placeholder='Leave blank for default'/>
                    -->
                    <input class='button button-primary' type='submit' name='Submit' value='Install Now'/>
                </form>
                <br/>
                ";
        }
    }

    // output add page menu page
    public function addPageMenu()
    {
        // do not allow page create until structure installs
        if (!file_exists(GF_APP_PATH)) {
            echo "
                <p>
                    You must install the application file structure before adding pages.
                </p>
                ";
            
            // check that the file structure is installed
            $this->installStructure();
            
            return;
        }

        // get wordpress database global
        global $wpdb;

        ////////////////////////////////////////////////////////////////////////////
        // START: PROCESS ADD PAGE FORM SUBMISSION
        ////////////////////////////////////////////////////////////////////////////
        if ($_POST['gf_action'] === 'gf_add_page') {
            // set error flag
            $error = false;

            // TITLE
            $title = $_POST['title'];
            if (empty($title)) {
                $error = true;
                $title_error = "Title must not be blank.";
            }

            // SLUG
            $slug = $_POST['slug'];
            if (empty($slug)) {
                $slug = $this->stringToSlug($title);
                $mvc_slug = $this->slugToMvc($slug);
            } else {
                $slug = $this->stringToSlug($slug);
                $mvc_slug = $this->slugToMvc($slug);
            }

            // check if slug already exists
            $slug_count = $wpdb->get_var("SELECT count(post_title) FROM $wpdb->posts WHERE post_name like '$slug'");
            if ($slug_count > 0) {
                $error = true;
                $slug_error = "Slug already exists.";
            }

            // PARENT
            $parent = $_POST['parent'];

            // CATEGORY
            $category = $_POST['category'];

            // start building mvc shortcode
            $content .= "[gf-mvc";

            // MODEL
            $model_as_defined = $_POST['model_as_defined'];
            if ($_POST['model_name_select'] === 'as_defined') {
                $model_name = $this->slugToMvc($model_as_defined);
                $content .= " m='$model_name'";
                $model_name_select_as_defined = 'checked';
            } else {
                $model_name = $mvc_slug;
                $model_name_select_use_slug = 'checked';
            }

            // VIEW
            $view_as_defined = $_POST['view_as_defined'];
            if ($_POST['view_name_select'] === 'as_defined') {
                $view_name = $this->slugToMvc($view_as_defined);
                $content .= " v='$view_name'";
                $view_name_select_as_defined = 'checked';
            } else {
                $view_name = $mvc_slug;
                $view_name_select_use_slug = 'checked';
            }

            // CONTROLLER
            $controller_as_defined = $_POST['controller_as_defined'];
            if ($_POST['controller_name_select'] === 'as_defined') {
                $controller_name = $this->slugToMvc($controller_as_defined);
                $content .= " c='$controller_name'";
                $controller_name_select_as_defined = 'checked';
            } else {
                $controller_name = $mvc_slug;
                $controller_name_select_use_slug = 'checked';
            }

            // close shortcode
            $content .= "]";

            // CREATE MODEL
            if ($_POST['model_create_file'] === 'yes') {
                $model_create_file_yes = 'checked';
                // check if model exists
                $model_path = GF_MODEL_PATH . '/' .$model_name . 'Model.php';
                if (file_exists($model_path) && $model_name != '') {
                    $error = true;
                    $model_error = "Model already exists.";
                }
            } else {
                $model_create_file_no = 'checked';
            }

            // CREATE VIEW
            if ($_POST['view_create_file'] === 'yes') {
                $view_create_file_yes = 'checked';
                // check if view exists
                $view_path = GF_VIEW_PATH . '/' . $view_name . 'View.php';
                if (file_exists($view_path) && $view_name != '') {
                    $error = true;
                    $view_error = "View already exists.";
                }
            } else {
                $view_create_file_no = 'checked';
            }

            // CREATE CONTROLLER
            if ($_POST['controller_create_file'] === 'yes') {
                $controller_create_file_yes = 'checked';
                // check if controller exists
                $controller_path = GF_CONTROLLER_PATH . '/' . $controller_name . 'Controller.php';
                if (file_exists($controller_path) && $controller_name != '') {
                    $error = true;
                    $controller_error = "Controller already exists";
                }
            } else {
                $controller_create_file_no = 'checked';
            }

            // create files and page
            if ($error === false) {
                $message .= '<h3>Success</h3>';

                // create post
                $insert_id = wp_insert_post(array(
                    'post_author' => get_current_user_id(),
                    'post_content' => $content,
                    'post_name' =>  $slug,
                    'post_status' => 'publish',
                    'post_title' => $title,
                    'post_type' => 'page',
                    'post_parent' => $parent,
                    'post_category' => array($category),
                    'menu_order' => 0,
                    'to_ping' =>  '',
                    'pinged' => ''
                    ));

                // check if files are supposed to created before creating

                if ($_POST['model_create_file'] === 'yes') {
                    $this->createModelFile($model_name, $model_path);
                }
                if ($_POST['view_create_file'] === 'yes') {
                    $this->createViewFile($view_name, $view_path);
                }
                if ($_POST['controller_create_file'] === 'yes') {
                    $this->createControllerFile($controller_name, $controller_path);
                }
            } else {
                $message .= "<h3  class='gf_error'>Error - Please see below</h3>";
            }
        }
        ////////////////////////////////////////////////////////////////////////////
        // END: PROCESS ADD PAGE FORM SUBMISSION
        ////////////////////////////////////////////////////////////////////////////

        // set default values
        if ($error !== true) {
            $title = $slug = $model_as_defined = $view_as_defined = $controller_as_defined = '';
            $model_create_file_yes = $view_create_file_yes = $controller_create_file_yes = $model_name_select_use_slug = $view_name_select_use_slug = $controller_name_select_use_slug = 'checked';
            $model_create_file_no = $view_create_file_no = $controller_create_file_no = $model_name_select_as_defined = $view_name_select_as_defined = $controller_name_select_as_defined = '';
        }

        // HTML OUTPUT
        $html = "
        <div class='wrap'>
            <h1>Add Page</h1>
            $message
            <p>
                Add a new page and create the associated MVC files.
            </p>
            <form method='post'>
                <input type='hidden' name='gf_action' value='gf_add_page'/>
                <table class='form-table'>
                    <tbody>
                        <tr>
                            <th scope='row'>
                                <label>Title:</label>
                            </th>
                            <td>
                                <input class='gf_input' type='text' name='title' placeholder='Enter title here' value='$title'/>
                                <span  class='gf_error'>$title_error</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>Slug:</label>
                            </th>
                            <td>
                                <input class='gf_input' type='text' name='slug' placeholder='Leave blank to use default' value='$slug'/>
                                <span  class='gf_error'>$slug_error</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>Parent:</label>
                            </th>
                            <td>
                                " .
                                \Gear::outputSelect(array(
                                    'name'=>'parent',
                                    'class'=>'gf_input',
                                    'default_text'=>false,
                                    'selected_option'=>$parent,
                                    'options'=>array('0'=>'No Parent') + \Gear::referenceTable($wpdb->prefix.'posts', 'ID', 'post_title', array('where'=>"post_type LIKE 'page'", 'order_by'=>'post_title ASC'))
                                ))
                                . "
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>Category:</label>
                            </th>
                            <td>
                                " .
                                wp_dropdown_categories(array(
                                    'name'=>'category',
                                    'class'=>'gf_input',
                                    'selected'=>$category,
                                    'hide_empty'=>0,
                                    'orderby'=>'name',
                                    'show_option_none'=>__('No Category'),
                                    'echo'=>false
                                ))
                                . "
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                            </th>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>Model:</label>
                            </th>
                            <td>
                                <label>Create File:&nbsp;&nbsp;</label>
                                <input type='radio' name='model_create_file' value='yes' $model_create_file_yes>Yes&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='model_create_file' value='no' $model_create_file_no>No
                                <br/>
                                <br/>
                                <input type='radio' name='model_name_select' value='use_slug' $model_name_select_use_slug>Use Slug&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='model_name_select' value='as_defined' $model_name_select_as_defined>As Defined:<br/>
                                <input class='gf_input' type='text' name='model_as_defined' placeholder='Leave blank if not needed' value='$model_as_defined'/>
                                <span  class='gf_error'>$model_error</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>View:</label>
                            </th>
                            <td>
                                <label>Create File:&nbsp;&nbsp;</label>
                                <input type='radio' name='view_create_file' value='yes' $view_create_file_yes>Yes&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='view_create_file' value='no' $view_create_file_no>No
                                <br/>
                                <br/>
                                <input type='radio' name='view_name_select' value='use_slug' $view_name_select_use_slug>Use Slug&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='view_name_select' value='as_defined' $view_name_select_as_defined>As Defined:<br/>
                                <input class='gf_input' type='text' name='view_as_defined' placeholder='Leave blank if not needed' value='$view_as_defined'/>
                                <span  class='gf_error'>$view_error</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>
                                <label>Controller:</label>
                            </th>
                            <td>
                                <label>Create File:&nbsp;&nbsp;</label>
                                <input type='radio' name='controller_create_file' value='yes' $controller_create_file_yes>Yes&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='controller_create_file' value='no' $controller_create_file_no>No
                                <br/>
                                <br/>
                                <input type='radio' name='controller_name_select' value='use_slug' $controller_name_select_use_slug>Use Slug&nbsp;&nbsp;&nbsp;
                                <input type='radio' name='controller_name_select' value='as_defined' $controller_name_select_as_defined>As Defined:<br/>
                                <input class='gf_input' type='text' name='controller_as_defined' placeholder='Leave blank if not needed' value='$controller_as_defined'/>
                                <span  class='gf_error'>$controller_error</span>
                            </td>
                        </tr>
                    <tbody>
                </table>
                <input class='button button-primary' type='submit' name='Submit' value='Add Page'/>
            </form>
        </div>
        ";
        echo $html;
    }

    // allow only alphanumeric and convert spaces to -
    public function stringToSlug($string)
    {
        $lower_string = strtolower($string);
        $preg_string = preg_replace("/[^a-z0-9 -]/", "", $lower_string);
        return str_replace(' ', '-', $preg_string);
    }

    // convert slug to ThisFormat
    public function slugToMvc($slug)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $this->stringToSlug($slug))));
    }

    public function createModelFile($model_name, $model_path)
    {
        if (!file_exists($model_path) && $model_name != '') {
            $file = fopen($model_path, 'w');

            $content =
            "<?php" . PHP_EOL .
            PHP_EOL .
            "namespace GearFramework\\$model_name;" . PHP_EOL .
            PHP_EOL .
            "class Model" . PHP_EOL .
            "{" . PHP_EOL .
            "    public function __construct()" . PHP_EOL .
            "    {" . PHP_EOL .
            "    }" . PHP_EOL .
            "}" . PHP_EOL;

            fwrite($file, $content);
            fclose($file);
            chmod($model_path, 0666);
        }
    }

    public function createViewFile($view_name, $view_path)
    {
        if (!file_exists($view_path) && $view_name != '') {
            $file = fopen($view_path, 'w');

            $content =
            "<?php" . PHP_EOL .
            PHP_EOL .
            "namespace GearFramework\\$view_name;" . PHP_EOL .
            PHP_EOL .
            "class View" . PHP_EOL .
            "{" . PHP_EOL .
            "    protected \$model;" . PHP_EOL .
            PHP_EOL .
            "    public function __construct(\$model)" . PHP_EOL .
            "    {" . PHP_EOL .
            "        \$this->model = \$model;" . PHP_EOL .
            "    }" . PHP_EOL .
            PHP_EOL .
            "    public function output()" . PHP_EOL .
            "    {" . PHP_EOL .
            "        return \$output;" . PHP_EOL .
            "    }" . PHP_EOL .
            "}" . PHP_EOL;

            fwrite($file, $content);
            fclose($file);
            chmod($view_path, 0666);
        }
    }

    public function createControllerFile($controller_name, $controller_path)
    {
        if (!file_exists($controller_path) && $controller_name != '') {
            $file = fopen($controller_path, 'w');

            $content =
            "<?php" . PHP_EOL .
            PHP_EOL .
            "namespace GearFramework\\$controller_name;" . PHP_EOL .
            PHP_EOL .
            "class Controller" . PHP_EOL .
            "{" . PHP_EOL .
            "    protected \$model;" . PHP_EOL .
            PHP_EOL .
            "    public function __construct(\$model)" . PHP_EOL .
            "    {" . PHP_EOL .
            "        \$this->model = \$model;" . PHP_EOL .
            "    }" . PHP_EOL .
            "}" . PHP_EOL;

            fwrite($file, $content);
            fclose($file);
            chmod($controller_path, 0666);
        }
    }
}
