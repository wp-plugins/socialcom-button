<?php
/*
Plugin Name: Social.com Button
Plugin URI: http://www.social.com/main/wordpress-plugin/ 
Description: Plugin to add the Social.com button into your Wordpress posts.
Version: 1.0
Author: Scott Carter
Author URI: http://www.social.com
*/

// define('SOCIAL_PATH', dirname(__FILE__));

// Hook to add to the <head> section
function social_admin_head(){
} 

// Hook to add to the end of the <body> section 
function social_admin_footer(){
} 


// Called when Social.com Button is deactivated.  Delete Social.com options.
function social_deactivate(){
    delete_option('bigt_options_initialized');
    delete_option('bigt_show_on_page');
    delete_option('bigt_show_on_home');
    delete_option('bigt_prefix');
    delete_option('bigt_button_div_style');
    delete_option('bigt_button_location');
    delete_option('bigt_button_label');
    delete_option('bigt_button_form_factor');
}


// Filter the content prior to rendering to allow us to conditionally add the
// Social.com button.
function social_filter_content($content) {
    // References:
    // http://www.php.net/manual/en/language.types.boolean.php
    //
    // Notes:
    // get_option() returns type "boolean" (false) if no option is found otherwise "string"
    // is_page(), is_home() return type "boolean"
    // 
    // NULL == 0 == false == ""
      
    // If we can't retrieve the following options or they are turned off
    // for the relevant context, return unfiltered content.
    $bigt_show_on_page = get_option('bigt_show_on_page');
    $bigt_show_on_home = get_option('bigt_show_on_home');
    
    
    if( is_page() && ($bigt_show_on_page == NULL) ) {
        return $content;
    } 

    if( is_home() && ($bigt_show_on_home == NULL) ) {
        return $content;
    }
    
    // We don't currently process an rss feed.
    if (is_feed()) {
		return $content;
    } 
    
    // Get the button code.
	$button_code = social_get_button_code();
	
    // If we encounter a shortcode, replace it with our button and return.
    if (get_option('bigt_button_location') == 'shortcode') {
		return str_replace('[social_button]', $button_code, $content);
	} 
    
    // Place the button before or after the post?   
    if (get_option('bigt_button_location') == 'before_content') {
        return $button_code . $content;
    } 
    else {
       return $content . $button_code;
    }
	
}


// Form the code necessary to instance the button.
function social_get_button_code() {
    global $post;
    
    $button_code = "";
    
    $title = "";
    $url = "";
    
    
    // If published, get the permanent url and title. 
    if (get_post_status($post->ID) == 'publish') {
        $title = $post->post_title; 
        $url = get_permalink();   
    }
    
    $prefix = get_option('bigt_prefix');
    
    $button_code .= <<<EOM
<script type="text/javascript">
    social_prefix = '$prefix';
    social_title = '$title'; 
    social_url = '$url';   
</script>
EOM;

    $label = get_option('bigt_button_label');
    $formFactor = get_option('bigt_button_form_factor');

    // Our button code wrapped in a div with class "social_button" for admin styling.
    // We use &mode=01 to indicate Wordpress plugin.
    $host = "widget.social.com";

    
    $button_code .= '<div class="social_button" style="' . get_option('bigt_button_div_style') . '">';
	$button_code .= '<script type="text/javascript" src="http://' . $host . '/widgets/button1.js?wid=4a9f1689e574f059&pid=4aabdca58f512b36&bl=' . urlencode($label) . '&bsnc=&bsnbac=&bshc=&bshbac=&bsnboc=&ff=' . $formFactor . '&mode=01&url=' . urlencode($url) . '"></script></div>';

    return $button_code;
}


// Prior to rendering admin page, register some settings (For WP 2.7 and above this is required)
function social_initialize_options(){
    // Reference:
    // http://codex.wordpress.org/Function_Reference/register_setting
    //
    // register_setting( $option_group, $option_name, $sanitize_callback );
    
    if(function_exists('register_setting')){
        // Only register options which we will be submitting with form. Otherwise
        // options without a form element will be set to ""
        //
        // Leave out 'bigt_options_initialized'
        register_setting('bigt_options_group', 'bigt_show_on_page');
        register_setting('bigt_options_group', 'bigt_show_on_home');
        register_setting('bigt_options_group', 'bigt_prefix');
        register_setting('bigt_options_group', 'bigt_button_div_style');
        register_setting('bigt_options_group', 'bigt_button_location');
        register_setting('bigt_options_group', 'bigt_button_label');
        register_setting('bigt_options_group', 'bigt_button_form_factor');

    }
}


// Main and sub menus
function social_menus() {
    // References:
    // http://codex.wordpress.org/Adding_Administration_Menus
    // http://codex.wordpress.org/User_Levels#User_Level_8
    // http://codex.wordpress.org/Plugin_API/Action_Reference
    
    // add_menu_page(page_title, menu_title, access_level/capability, file, [function], [icon_url]);
    //
    // User Level 8 is allowed to activate Themes, use the Theme Editor to edit Templates, 
    // activate or deactive Plugins, use the Plugin Editor to edit Plugin files, and can change a Post's Author. 
    //
    // Get a return value from add_menu_page() which we can use for registering the actions
    // that follow ($social_menu_page = "toplevel_page_social")
	$social_menu_page = add_menu_page('Social.com', 'Social.com', 8, basename(__FILE__), 'social_settings');
	
	// Hooks to add content to the <head> and end of <body> specific to our plugin admin page.
	add_action( "admin_print_scripts-$social_menu_page", 'social_admin_head' );
	add_action( "admin_footer-$social_menu_page", 'social_admin_footer' );
	
	// add_submenu_page(parent, page_title, menu_title, access_level/capability, file, [function]); 
	add_submenu_page(basename(__FILE__), 'Settings', 'Settings', 8, basename(__FILE__), 'social_settings');
}


// Remove the filter for 'the_content'
function social_remove_filter($content) {
	if (!is_feed()) {
    	remove_action('the_content', 'social_filter_content');
	}
    return $content;
}


// Render the settings page.
function social_settings() {
?>
    <div class="wrap">
    <div class="icon32" id="icon-options-general">&nbsp;</div><h2>Settings for Social.com Button</h2>
    <p>This plugin will add the Social.com Button into each of your blog posts.
    <p>When your visitors click on the button, a Social.com window will appear in the middle of the web page allowing them to tweet without leaving your site.
    <form method="post" action="options.php">
    <?php
        // Reference: http://codex.wordpress.org/Migrating_Plugins_and_Themes_to_2.7
        // Using 'settings_fields' outputs all of the hidden fields that options.php will check, 
        // including the nonce. You no longer need to setup the page_options hidden field if 
        // you use the new API.  
        if(function_exists('settings_fields')){
            settings_fields('bigt_options_group');
        } else {
            wp_nonce_field('update-options');
            ?>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="bigt_show_on_page,bigt_show_on_home,bigt_prefix,bigt_button_div_style,bigt_button_location,bigt_button_label,bigt_button_form_factor" />
            <?php
        }
        
        
    ?>
        <table class="form-table">
            <tr>
                <th scope="row">Show Button</th>
                <td>
                    <p> 
                        <input type="checkbox" value="1" <?php if (get_option('bigt_show_on_page') == '1') echo 'checked'; ?> name="bigt_show_on_page" id="bigt_show_on_page">
                        <label for="bigt_show_on_page">Show the Social.com Button on Pages (About Me, Contact Me, etc.)</label>
                    </p>
                    <p>
                        <input type="checkbox" value="1" <?php if (get_option('bigt_show_on_home') == '1') echo 'checked'; ?> name="bigt_show_on_home" id="bigt_show_on_home">
                        <label for="bigt_show_on_home">Show the Social.com Button on the main blog page.</label>
                    </p>
                </td>
            </tr>
	        <tr>
                <th scope="row">Location</th>
                <td>
                    <p>
                		<select name="bigt_button_location">
                			<option <?php if (get_option('bigt_button_location') == 'before_content') echo 'selected'; ?> value="before_content">Before content</option>
                			<option <?php if (get_option('bigt_button_location') == 'after_content') echo 'selected'; ?> value="after_content">After content</option>
                			<option <?php if (get_option('bigt_button_location') == 'shortcode') echo 'selected'; ?> value="shortcode">Shortcode [social_button]</option>
                		</select>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="bigt_button_label">Button Label</label></th>
                <td>
                    <p>
                        <input type="text" value="<?php echo htmlspecialchars(get_option('bigt_button_label')); ?>" name="bigt_button_label" id="bigt_button_label" size=20 maxlength=20><br/>
                        <span class="setting-description">Control the text of the button label, i.e. &quot;tweet&quot;</span>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">Button Type</th>
                <td>
                    <p>
                        <input type="radio" value="0" <?php if (get_option('bigt_button_form_factor') == '0') echo 'checked'; ?> name="bigt_button_form_factor" id="bigt_button_form_factor" group="bigt_button_form_factor">
                        <label for="bigt_button_form_factor">Full</label>
                    </p>
                    <p>
                        <input type="radio" value="1" <?php if (get_option('bigt_button_form_factor') == '1') echo 'checked'; ?> name="bigt_button_form_factor" id="bigt_button_form_factor" group="bigt_button_form_factor">
                        <label for="bigt_button_form_factor">Compact</label>
                    </p>
                    <p>
                        <input type="radio" value="2" <?php if (get_option('bigt_button_form_factor') == '2') echo 'checked'; ?> name="bigt_button_form_factor" id="bigt_button_form_factor" group="bigt_button_form_factor">
                        <label for="bigt_button_form_factor">Button only (no count)</label>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="bigt_prefix">Message Prefix</label></th>
                <td>
                    <p>
                        <input type="text" value="<?php echo htmlspecialchars(get_option('bigt_prefix')); ?>" name="bigt_prefix" id="bigt_prefix" size=40 maxlength=40 /><br/>
                        <span class="setting-description">Add a custom prefix to tweets, i.e. RT @my_twitter_username</span>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><label for="bigt_button_div_style">Styling</label></th>
                <td>
                    <p>
                        <input type="text" value="<?php echo htmlspecialchars(get_option('bigt_button_div_style')); ?>" name="bigt_button_div_style" id="bigt_button_div_style" size=40 maxlength=80 /><br/>
                        <span class="setting-description">Add an element style to the div that surrounds the button</span><br/>
                        <span class="setting-description"> Ex: <code>float: right; margin-left: 10px;</code></span><br/>
                        <span class="setting-description">Alternatively you can use the class &quot;social_button&quot; that is assigned to this div.</span>
                    </p>
                </td>
            </tr>
            
        </table>
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    </div>
<?php
}


// Is the dashboard or admin panel displayed?
// http://codex.wordpress.org/Function_Reference/is_admin
if(is_admin()){
    // References:
    // http://codex.wordpress.org/Plugin_API/Action_Reference
    
    // Create our menus
    // 'admin_menu' runs after the basic admin panel menu structure is in place. 
    add_action('admin_menu', 'social_menus');
    
    // Initialize our options
    // 'admin_init' runs at the beginning of every admin page before the page is rendered. 
    add_action('admin_init', 'social_initialize_options');

}


// add_filter( $tag, $function_to_add, $priority, $accepted_args );
// http://codex.wordpress.org/Function_Reference/add_filter
// http://codex.wordpress.org/Plugin_API/Filter_Reference
// Default priority is 10

// Insert our button.  
// 'the_content' - applied to the post content retrieved from the database, 
// prior to printing on the screen (also used in some other operations, such as trackbacks). 
add_filter('the_content', 'social_filter_content');

// Use the following hook to remove our filter for 'the_content'
// 'get_the_excerpt' - applied to the post's excerpt in the get_the_excerpt function. 
add_filter('get_the_excerpt', 'social_remove_filter', 9);


// Add options if they do not exist.  A workaround to register_activation_hook() bug
// (doesn't work after initial activation).
// http://wordpress.org/support/topic/246702
//
// Note: get_option will return FALSE if option is not in database OR is null. 
//       Therefore we use the common flag bigt_options_initialized which will
//       always contain a value once set (unless we deactivate plugin).
      
if(!get_option('bigt_options_initialized')){
    update_option('bigt_options_initialized', '1');
    update_option('bigt_show_on_page', '1');
    update_option('bigt_show_on_home', '1');
    update_option('bigt_prefix', '');
    update_option('bigt_button_div_style', 'float: right; margin-left: 10px;');
    update_option('bigt_button_location', 'before_content');
    update_option('bigt_button_label', 'tweet');
    update_option('bigt_button_form_factor', '0');
}
   
    
// Register a function to be called upon deactivation.
// Note that this function will not work reliably if a symbolic link
// is used in plugin directory.
register_deactivation_hook(__FILE__,'social_deactivate' );

