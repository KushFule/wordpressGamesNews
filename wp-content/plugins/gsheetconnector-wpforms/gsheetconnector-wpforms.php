<?php

/**
 * Plugin Name:			WPForms GSheetConnector
 * Plugin URI:			   https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro
 * Description:			Send your WPForms data to your Google Sheets spreadsheet.
 * Requires at least: 	5.2
 * Requires PHP: 		   5.6
 * Author:       	   	GSheetConnector
 * Author URI:   		   https://www.gsheetconnector.com/
 * Version:      		   3.4.7
 * Text Domain:  		   gsheetconnector-wpforms
 * Domain Path:  		   languages
 */
// Exit if accessed directly.
if (!defined('ABSPATH')) {
   exit;
}

define('WPFORMS_GOOGLESHEET_VERSION', '3.4.7');
define('WPFORMS_GOOGLESHEET_DB_VERSION', '3.4.7');
define('WPFORMS_GOOGLESHEET_ROOT', dirname(__FILE__));
define('WPFORMS_GOOGLESHEET_URL', plugins_url('/', __FILE__));
define('WPFORMS_GOOGLESHEET_BASE_FILE', basename(dirname(__FILE__)) . '/gsheetconnector-wpforms.php');
define('WPFORMS_GOOGLESHEET_BASE_NAME', plugin_basename(__FILE__));
define('WPFORMS_GOOGLESHEET_PATH', plugin_dir_path(__FILE__)); //use for include files to other files
define('WPFORMS_GOOGLESHEET_PRODUCT_NAME', 'Wpforms Google Sheet Connector');
define('WPFORMS_GOOGLESHEET_CURRENT_THEME', get_stylesheet_directory());
load_plugin_textdomain('gsheetconnector-wpforms', false, basename(dirname(__FILE__)) . '/languages');

/*
 * include utility classes
 */
if (!class_exists('Wpform_gs_Connector_Utility')) {
   include( WPFORMS_GOOGLESHEET_ROOT . '/includes/class-wpform-utility.php' );
}

function wpforms_Googlesheet_integration() {
  require_once plugin_dir_path(__FILE__) . 'includes/class-wpforms-integration.php';
   //Include Library Files
  require_once WPFORMS_GOOGLESHEET_ROOT . '/lib/vendor/autoload.php';

  include_once( WPFORMS_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
}





add_action('wpforms_loaded', 'wpforms_Googlesheet_integration');

class WPforms_Gsheet_Connector_Init {

   public function __construct() {

      //run on activation of plugin
      register_activation_hook(__FILE__, array($this, 'wpform_gs_connector_activate'));

      //run on deactivation of plugin
      register_deactivation_hook(__FILE__, array($this, 'wpform_gs_connector_deactivate'));

      //run on uninstall
      register_uninstall_hook(__FILE__, array('WPforms_Gsheet_Connector_Init', 'wpform_gs_connector_uninstall'));

      // validate is wpforms plugin exist
      add_action('admin_init', array($this, 'validate_parent_plugin_exists'));

      // register admin menu under "Google Sheet" > "Integration"
      add_action('admin_menu', array($this, 'register_wpform_menu_pages'));

      // Display widget to dashboard
      add_action('wp_dashboard_setup', array($this, 'add_wpform_gs_connector_summary_widget'));

      // clear debug log data
      add_action('wp_ajax_gs_clear_log', array($this, 'gs_clear_logs'));

      // verify the spreadsheet connection
      add_action('wp_ajax_verify_wpform_gs_integation', array($this, 'verify_wpform_gs_integation'));

      // load the js and css files
      add_action('init', array($this, 'load_css_and_js_files'));

      // Add custom link for our plugin
      add_filter('plugin_action_links_' . WPFORMS_GOOGLESHEET_BASE_NAME, array($this, 'wpform_gs_connector_plugin_action_links'));
      
      add_action( 'admin_init', array( $this, 'run_on_upgrade' ) );
      
      // redirect to integration page after update
      add_action('admin_init', array( $this, 'redirect_after_upgrade' ), 999 );
   }

   /**
    * Do things on plugin activation
    * @since 1.0
    */
   public function wpform_gs_connector_activate($network_wide) {
      global $wpdb;
      $this->run_on_activation();
      if (function_exists('is_multisite') && is_multisite()) {
         // check if it is a network activation - if so, run the activation function for each blog id
         if ($network_wide) {
            // Get all blog ids
            $blogids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
            foreach ($blogids as $blog_id) {
               switch_to_blog($blog_id);
               $this->run_for_site();
               restore_current_blog();
            }
            return;
         }
      }
      // for non-network sites only
      $this->run_for_site();
   }

   /**
    * deactivate the plugin
    * @since 1.0
    */
   public function wpform_gs_connector_deactivate() {
      
   }

   /**
    *  Runs on plugin uninstall.
    *  a static class method or function can be used in an uninstall hook
    *
    *  @since 1.0
    */
   public static function wpform_gs_connector_uninstall() {
      global $wpdb;
      WPforms_Gsheet_Connector_Init::run_on_uninstall();
      if (function_exists('is_multisite') && is_multisite()) {
         //Get all blog ids; foreach of them call the uninstall procedure
         $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");

         //Get all blog ids; foreach them and call the install procedure on each of them if the plugin table is found
         foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            WPforms_Gsheet_Connector_Init::delete_for_site();
            restore_current_blog();
         }
         return;
      }
      WPforms_Gsheet_Connector_Init::delete_for_site();
   }

   /**
    * Validate parent Plugin wpform exist and activated
    * @access public
    * @since 1.0
    */
   public function validate_parent_plugin_exists() {
      $plugin = plugin_basename(__FILE__);
      //if ((!is_plugin_active('wpforms-lite/wpforms.php') ) && (!is_plugin_active('wpforms/wpforms.php') )) {
      if (!class_exists('WPForms', true)) {
         add_action('admin_notices', array($this, 'wpforms_missing_notice'));
         add_action('network_admin_notices', array($this, 'wpforms_missing_notice'));
         deactivate_plugins($plugin);
         if (isset($_GET['activate'])) {
            // Do not sanitize it because we are destroying the variables from URL
            unset($_GET['activate']);
         }
      }
   }

   /**
    * If Contact Form 7 plugin is not installed or activated then throw the error
    *
    * @access public
    * @return mixed error_message, an array containing the error message
    *
    * @since 1.0 initial version
    */
   public function wpforms_missing_notice() {
      $plugin_error = Wpform_gs_Connector_Utility::instance()->admin_notice(array(
         'type' => 'error',
         'message' => 'WPForms Google Sheet Connector Add-on requires WPForms <a href="https://wordpress.org/plugins/wpforms-lite/" target="_blank">(Lite or PRO)</a> plugin to be installed and activated.'
      ));
      echo $plugin_error;
   }

   /**
    * Create/Register menu items for the plugin.
    * @since 1.0
    */
   public function register_wpform_menu_pages() {
      $current_role = Wpform_gs_Connector_Utility::instance()->get_current_user_role();
      add_submenu_page('wpforms-overview', __('Google Sheet', 'gsheetconnector-wpforms'), __('Google Sheet', 'gsheetconnector-wpforms'), $current_role, 'wpform-google-sheet-config', array($this, 'wpforms_google_sheet_config'));
   }

   /**
    * Google Sheets page action.
    * This method is called when the menu item "Google Sheets" is clicked.
    * @since 1.0
    */
   public function wpforms_google_sheet_config() {
      include( WPFORMS_GOOGLESHEET_PATH . "includes/pages/wpforms-gs-settings.php" );
   }

   /**
    * Add widget to the dashboard
    * @since 1.0
    */
   public function add_wpform_gs_connector_summary_widget() {
      wp_add_dashboard_widget('wpform_gs_dashboard', __('GSheetConnector WPForms', 'gsheetconnector-wpforms')."<img style='width:60px' src='".WPFORMS_GOOGLESHEET_URL."assets/img/WPFormGSheet-Connector-logo.png'>", array($this, 'wpform_gs_connector_summary_dashboard'));
   }

   /**
    * Display widget conetents
    * @since 1.0
    */
   public function wpform_gs_connector_summary_dashboard() {
      include_once( WPFORMS_GOOGLESHEET_ROOT . '/includes/pages/wpform-dashboard-widget.php' );
   }

   /**
    * AJAX function - clear log file
    * @since 1.0
    */
   public function gs_clear_logs() {
      // nonce check
      check_ajax_referer('gs-ajax-nonce', 'security');

      $handle = fopen(WPFORMS_GOOGLESHEET_PATH . 'includes/logs/log.txt', 'w');
      fclose($handle);

      wp_send_json_success();
   }

   /**
    * AJAX function - verifies the token
    *
    * @since 1.0
    */
   public function verify_wpform_gs_integation() {
      // nonce checksave_gs_settings
      check_ajax_referer('gs-ajax-nonce', 'security');

      /* sanitize incoming data */
      $Code = sanitize_text_field($_POST["code"]);

      if (!empty($Code)) {
         update_option('wpform_gs_access_code', $Code);
      } else {
         return;
      }

      if (get_option('wpform_gs_access_code') != '') {
         include_once( WPFORMS_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
         wpfgsc_googlesheet::preauth(get_option('wpform_gs_access_code'));
         update_option('wpform_gs_verify', 'valid');
         wp_send_json_success();
      } else {
         update_option('wpform_gs_verify', 'invalid');
         wp_send_json_error();
      }
   }


   /**
    * AJAX function - verifies the token
    *
    * @since 1.0
    */
   public function verify_wpform_gs_integation_new($Code ="") {
      

      /* sanitize incoming data */
      
      if (!empty($Code)) {
         update_option('wpform_gs_access_code', $Code);
      } else {
         return;
      }

      if (get_option('wpform_gs_access_code') != '') {
         include_once( WPFORMS_GOOGLESHEET_ROOT . '/lib/google-sheets.php');
         wpfgsc_googlesheet::preauth(get_option('wpform_gs_access_code'));
         update_option('wpform_gs_verify', 'valid');
      } else {
         update_option('wpform_gs_verify', 'invalid');
      }
   }


   public function load_css_and_js_files() {
      add_action('admin_print_styles', array($this, 'add_css_files'));
      add_action('admin_print_scripts', array($this, 'add_js_files'));
   }

   /**
    * enqueue CSS files
    * @since 1.0
    */
   public function add_css_files() {
      if (is_admin() && ( isset($_GET['page']) && ( $_GET['page'] == 'wpform-google-sheet-config' ) )) {
         wp_enqueue_style('wpform-gs-connector-css', WPFORMS_GOOGLESHEET_URL . 'assets/css/wpform-gs-connector.css', WPFORMS_GOOGLESHEET_VERSION, true);
         wp_enqueue_style('wpform-gs-connector-font', WPFORMS_GOOGLESHEET_URL . 'assets/css/font-awesome.min.css', WPFORMS_GOOGLESHEET_VERSION, true);
      }              
   }

   /**
    * enqueue JS files
    * @since 1.0
    */
   public function add_js_files() {
      if (is_admin() && ( isset($_GET['page']) && ( $_GET['page'] == 'wpform-google-sheet-config' ) )) {
         wp_enqueue_script('wpform-gs-connector-js', WPFORMS_GOOGLESHEET_URL . 'assets/js/wpform-gs-connector.js', WPFORMS_GOOGLESHEET_VERSION, true);
      }
      
      if ( is_admin() ) {
         wp_enqueue_script('wpform-gs-connector-notice-css', WPFORMS_GOOGLESHEET_URL . 'assets/js/wpforms-gs-connector-notice.js', WPFORMS_GOOGLESHEET_VERSION, true);
      }
   }

   /**
    * Add custom link for the plugin beside activate/deactivate links
    * @param array $links Array of links to display below our plugin listing.
    * @return array Amended array of links.    * 
    * @since 1.0
    */
   public function wpform_gs_connector_plugin_action_links($links) {
      // We shouldn't encourage editing our plugin directly.
      unset($links['edit']);
      // Add our custom links to the returned array value.[16102021]
      return array_merge(array(
         '<a href="' . admin_url('admin.php?page=wpform-google-sheet-config&tab=integration') . '">' . __('Settings', 'gsheetconnector-wpforms') . '</a>',
         '<a href="https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro?gsheetconnector-ref=17" style="color: red;font-style: italic;font-weight: 500;" target="_blank">' . __('Upgrade to Pro', 'gsheetconnector-wpforms') . '</a>',
              ), $links);
   }

   /**
    * called on upgrade. 
    * checks the current version and applies the necessary upgrades from that version onwards
    * @since 1.0
    */
   public function run_on_upgrade() {
      $plugin_options = get_site_option('wpform_GS_info');
      
      if ($plugin_options['version'] <= "1.3") {
         $this->upgrade_database_20();
      }

      // update the version value
      $wpform_GS_info = array(
         'version' => WPFORMS_GOOGLESHEET_VERSION,
         'db_version' => WPFORMS_GOOGLESHEET_DB_VERSION
      );
      update_site_option('wpform_GS_info', $wpform_GS_info);
   }
   
   public function upgrade_database_20() {
      global $wpdb;

      // look through each of the blogs and upgrade the DB
      if (function_exists('is_multisite') && is_multisite()) {
         //Get all blog ids;
         $blog_ids = $wpdb->get_col("SELECT blog_id FROM {$wpdb->base_prefix}blogs");
         foreach ($blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $this->upgrade_helper_20();
            restore_current_blog();
         }
         return;
      }
      $this->upgrade_helper_20();
   }
   
   public function upgrade_helper_20() {
      // Add the transient to redirect.
      set_transient('wpform_gs_upgrade_redirect', true, 30);
   }
   
   public function redirect_after_upgrade() {
      if ( ! get_transient('wpform_gs_upgrade_redirect') ) {
         return;
      }
      $plugin_options = get_site_option( 'wpform_GS_info' );
      if( $plugin_options['version'] == "2.0") {
         delete_transient('wpform_gs_upgrade_redirect');
         wp_safe_redirect('admin.php?page=wpform-google-sheet-config&tab=integration');
      }
   }

   /**
    * Called on activation.
    * Creates the site_options (required for all the sites in a multi-site setup)
    * If the current version doesn't match the new version, runs the upgrade
    * @since 1.0
    */
   private function run_on_activation() {
      $plugin_options = get_site_option('wpform_GS_info');
      if (false === $plugin_options) {
         $wpform_GS_info = array(
            'version' => WPFORMS_GOOGLESHEET_VERSION,
            'db_version' => WPFORMS_GOOGLESHEET_DB_VERSION
         );
         update_site_option('wpform_GS_info', $wpform_GS_info);
      } else if (WPFORMS_GOOGLESHEET_DB_VERSION != $plugin_options['version']) {
         $this->run_on_upgrade();
      }
   }

   /**
    * Called on activation.
    * Creates the options and DB (required by per site)
    * @since 1.0
    */
   private function run_for_site() {
      if (!get_option('wpform_gs_access_code')) {
         update_option('wpform_gs_access_code', '');
      }
      if (!get_option('wpform_gs_verify')) {
         update_option('wpform_gs_verify', 'invalid');
      }
      if (!get_option('wpform_gs_token')) {
         update_option('wpform_gs_token', '');
      }
      if (!get_option('wpform_uninstall')) {
         update_option('wpform_uninstall', 'false');
      }
   }

   /**
    * Called on uninstall - deletes site specific options
    *
    * @since 1.0
    */
   private static function delete_for_site() {

      delete_option('wpform_gs_access_code');
      delete_option('wpform_gs_verify');
      delete_option('wpform_gs_token');
      delete_post_meta_by_key('wpform_gs_settings');
   }

   /**
    * Called on uninstall - deletes site_options
    *
    * @since 1.0
    */
   private static function run_on_uninstall() {
      if (!defined('ABSPATH') && !defined('WP_UNINSTALL_PLUGIN'))
         exit();

      delete_site_option('wpform_GS_info');
   }

   /**
    * Build System Information String
    * @global object $wpdb
    * @return string
    * @since 1.2
    */
   public function get_wpforms_system_info() {
      global $wpdb;
      // Get theme info
      $theme_data = wp_get_theme();
      $theme = $theme_data->Name . ' ' . $theme_data->Version;
      $parent_theme = $theme_data->Template;
      if (!empty($parent_theme)) {
         $parent_theme_data = wp_get_theme($parent_theme);
         $parent_theme = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
      }

      $host = 'DBH: ' . DB_HOST . ', SRV: ' . $_SERVER['SERVER_NAME'];

      $return = '### Begin System Info ###' . "\n\n";

      // Start with the basics...
      $return .= '-- Site Info' . "\n\n";
      $return .= 'Site URL:                 ' . site_url() . "\n";
      $return .= 'Home URL:             ' . home_url() . "\n";
      $return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

      // Can we determine the site's host?
      if ($host) {
         $return .= "\n" . '-- Hosting Provider' . "\n\n";
         $return .= 'Host:                     ' . $host . "\n";
      }

      $locale = get_locale();

      // WordPress configuration
      $return .= "\n" . '-- WordPress Configuration' . "\n\n";
      $return .= 'Version:                          ' . get_bloginfo('version') . "\n";
      $return .= 'Language:                      ' . (!empty($locale) ? $locale : 'en_US' ) . "\n";
      $return .= 'Permalink Structure:      ' . ( get_option('permalink_structure') ? get_option('permalink_structure') : 'Default' ) . "\n";
      $return .= 'Active Theme:               ' . $theme . "\n";
      if ($parent_theme !== $theme) {
         $return .= 'Parent Theme:           ' . $parent_theme . "\n";
      }
      $return .= 'Show On Front:             ' . get_option('show_on_front') . "\n";

      // Only show page specs if frontpage is set to 'page'
      if (get_option('show_on_front') == 'page') {
         $front_page_id = get_option('page_on_front');
         $blog_page_id = get_option('page_for_posts');

         $return .= 'Page On Front:              ' . ( $front_page_id != 0 ? get_the_title($front_page_id) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
         $return .= 'Page For Posts:             ' . ( $blog_page_id != 0 ? get_the_title($blog_page_id) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
      }

      $return .= 'ABSPATH:                      ' . ABSPATH . "\n";
      $return .= 'WP_DEBUG:                   ' . ( defined('WP_DEBUG') ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
      $return .= 'Memory Limit:               ' . WP_MEMORY_LIMIT . "\n";
      $return .= 'Registered Post Stati:    ' . implode(', ', get_post_stati()) . "\n";

      // Get plugins that have an update
      $updates = get_plugin_updates();

      // Must-use plugins
      // NOTE: MU plugins can't show updates!
      $muplugins = get_mu_plugins();
      if (count($muplugins) > 0) {
         $return .= "\n" . '-- Must-Use Plugins' . "\n\n";

         foreach ($muplugins as $plugin => $plugin_data) {
            $return .= $plugin_data['Name'] . ': ' . $plugin_data['Version'] . "\n";
         }
      }

      // WordPress active plugins
      $return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

      $plugins = get_plugins();
      $active_plugins = get_option('active_plugins', array());

      foreach ($plugins as $plugin_path => $plugin) {
         if (!in_array($plugin_path, $active_plugins))
            continue;

         $update = ( array_key_exists($plugin_path, $updates) ) ? ' ( needs update - ' . $updates[$plugin_path]->update->new_version . ' )' : '';
         $return .= $plugin['Name'] . '  :  ' . $plugin['Version'] . $update . "\n";
      }

      // WordPress inactive plugins
      $return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

      foreach ($plugins as $plugin_path => $plugin) {
         if (in_array($plugin_path, $active_plugins))
            continue;

         $update = ( array_key_exists($plugin_path, $updates) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
         $return .= $plugin['Name'] . '  :  ' . $plugin['Version'] . $update . "\n";
      }

      if (is_multisite()) {
         // WordPress Multisite active plugins
         $return .= "\n" . '-- Network Active Plugins' . "\n\n";

         $plugins = wp_get_active_network_plugins();
         $active_plugins = get_site_option('active_sitewide_plugins', array());

         foreach ($plugins as $plugin_path) {
            $plugin_base = plugin_basename($plugin_path);

            if (!array_key_exists($plugin_base, $active_plugins))
               continue;

            $update = ( array_key_exists($plugin_path, $updates) ) ? ' (needs update - ' . $updates[$plugin_path]->update->new_version . ')' : '';
            $plugin = get_plugin_data($plugin_path);
            $return .= $plugin['Name'] . ': ' . $plugin['Version'] . $update . "\n";
         }
      }

      // Server configuration (really just versioning)
      $return .= "\n" . '-- Webserver Configuration' . "\n\n";
      $return .= 'PHP Version:                 ' . PHP_VERSION . "\n";
      $return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
      $return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

      // PHP configs... now we're getting to the important stuff
      $return .= "\n" . '-- PHP Configuration' . "\n\n";
      $return .= 'Memory Limit:               ' . ini_get('memory_limit') . "\n";
      $return .= 'Upload Max Size:           ' . ini_get('upload_max_filesize') . "\n";
      $return .= 'Post Max Size:                ' . ini_get('post_max_size') . "\n";
      $return .= 'Upload Max Filesize:      ' . ini_get('upload_max_filesize') . "\n";
      $return .= 'Time Limit:                     ' . ini_get('max_execution_time') . "\n";
      $return .= 'Max Input Vars:            ' . ini_get('max_input_vars') . "\n";
      $return .= 'Display Errors:               ' . ( ini_get('display_errors') ? 'On (' . ini_get('display_errors') . ')' : 'N/A' ) . "\n";

      // PHP extensions and such
      $return .= "\n" . '-- PHP Extensions' . "\n\n";
      $return .= 'cURL:                     ' . ( function_exists('curl_init') ? 'Supported' : 'Not Supported' ) . "\n";
      $return .= 'fsockopen:            ' . ( function_exists('fsockopen') ? 'Supported' : 'Not Supported' ) . "\n";
      $return .= 'SOAP Client:          ' . ( class_exists('SoapClient') ? 'Installed' : 'Not Installed' ) . "\n";
      $return .= 'Suhosin:                ' . ( extension_loaded('suhosin') ? 'Installed' : 'Not Installed' ) . "\n";

      // Session stuff
      $return .= "\n" . '-- Session Configuration' . "\n\n";
      $return .= 'Session:                  ' . ( isset($_SESSION) ? 'Enabled' : 'Disabled' ) . "\n";
      // The rest of this is only relevant is session is enabled
      if (isset($_SESSION)) {
         $return .= 'Session Name:             ' . esc_html(ini_get('session.name')) . "\n";
         $return .= 'Cookie Path:              ' . esc_html(ini_get('session.cookie_path')) . "\n";
         $return .= 'Save Path:                ' . esc_html(ini_get('session.save_path')) . "\n";
         $return .= 'Use Cookies:              ' . ( ini_get('session.use_cookies') ? 'On' : 'Off' ) . "\n";
         $return .= 'Use Only Cookies:         ' . ( ini_get('session.use_only_cookies') ? 'On' : 'Off' ) . "\n";
      }

      $return .= "\n" . '### End System Info ###';

      return $return;
   }

}

// Initialize the wpform google sheet connector class
$init = new WPforms_Gsheet_Connector_Init();