<?php
/**
 * service class for Wpform Google Sheet Connector
 * @since 1.0
 */
if (!defined('ABSPATH')) {
   exit; // Exit if accessed directly
}

/**
 * WPforms_Googlesheet_Services Class
 *
 * @since 1.0
 */
class WPforms_Googlesheet_Services {

   public function __construct() {
      // get with all data and display form
      add_action('wp_ajax_get_wpforms', array($this, 'display_wpforms_data'));

      // get all form data
      add_action('admin_init', array($this, 'execute_post_data'));


      // activation n deactivation ajax call
      add_action('wp_ajax_deactivate_wp_integation', array($this, 'deactivate_wp_integation'));

      // save entry with posted data
      add_action('wpforms_process_entry_save', array($this, 'entry_save'), 20, 4);
      
      //add_action( 'admin_notices', array( $this, 'display_upgrade_notice' ) );
      
      add_action( 'wp_ajax_set_upgrade_notification_interval', array( $this, 'set_upgrade_notification_interval' ) );
      add_action( 'wp_ajax_close_upgrade_notification_interval', array( $this, 'close_upgrade_notification_interval' ) );

   }

   /**
    * AJAX function - get wpforms details with sheet data
    * @since 1.1
    */
   function display_wpforms_data() {

      // nonce check
      check_ajax_referer('wp-ajax-nonce', 'security');

      $form = get_post($_POST['wpformsId']);
      $form_id = $_POST['wpformsId'];

      ob_start();
      $this->wpforms_googlesheet_settings_content($form_id);
      $result = ob_get_contents();
      ob_get_clean();
      wp_send_json_success(htmlentities($result));
   }

   /**
    * Function - save the setting data of google sheet with sheet name and tab name
    * @since 1.0
    */
   public function wpforms_googlesheet_settings_content($form_id) {

      $get_data = get_post_meta($form_id, 'wpform_gs_settings');

      $saved_sheet_name = isset($get_data[0]['sheet-name']) ? $get_data[0]['sheet-name'] : "";
      $saved_tab_name = isset($get_data[0]['sheet-tab-name']) ? $get_data[0]['sheet-tab-name'] : "";

      echo '<div class="wpforms-panel-content-section-googlesheet-tab">';
      echo '<div class="wpforms-panel-content-section-title">';
      ?>
<div class="wpforms-gs-fields">
    <h3><?php esc_html_e('Google Sheet Settings', 'gsheetconnector-wpforms'); ?>
        <span class="gs-info-wpform">( Fetch your sheets automatically using PRO <a
                href="https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro?gsheetconnector-ref=17"
                target="_blank">Upgrade to PRO</a> )</span>
    </h3>

    <p>
        <label><?php echo esc_html(__('Google Sheet Name', 'gsheetconnector-wpforms')); ?></label>
        <input type="text" name="wpform-gs[sheet-name]" id="wpforms-gs-sheet-name"
            value="<?php echo ( isset($get_data[0]['sheet-name']) ) ? esc_attr($get_data[0]['sheet-name']) : ''; ?>" />
        <a href=""
            class=" gs-name help-link"><?php //echo esc_html(__('Where do i get Google Sheet Name?', 'gsheetconnector-wpforms')); ?><img
                src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/help.png" class="help-icon"><span
                class='hover-data'><?php echo esc_html(__('Go to your google account and click on"Google apps" icon and than click "Sheets". Select the name of the appropriate sheet you want to link your contact form or create new sheet.', 'gsheetconnector-wpforms')); ?>
            </span></a>
    </p>
    <p>
        <label><?php echo esc_html(__('Google Sheet Id', 'gsheetconnector-wpforms')); ?></label>
        <input type="text" name="wpform-gs[sheet-id]" id="wpforms-gs-sheet-id"
            value="<?php echo ( isset($get_data[0]['sheet-id']) ) ? esc_attr($get_data[0]['sheet-id']) : ''; ?>" />
        <a href="" class=" gs-name help-link"><img src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/help.png"
                class="help-icon"><?php //echo esc_html(__('Google Sheet Id?', 'gsheetconnector-wpforms')); ?><span
                class='hover-data'><?php echo esc_html(__('you can get sheet id from your sheet URL', 'gsheetconnector-wpforms')); ?></span></a>
    </p>
    <p>
        <label><?php echo esc_html(__('Google Sheet Tab Name', 'gsheetconnector-wpforms')); ?></label>
        <input type="text" name="wpform-gs[sheet-tab-name]" id="wpforms-sheet-tab-name"
            value="<?php echo ( isset($get_data[0]['sheet-tab-name']) ) ? esc_attr($get_data[0]['sheet-tab-name']) : ''; ?>" />
        <a href="" class=" gs-name help-link"><img src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/help.png"
                class="help-icon"><?php //echo esc_html(__('Where do i get Sheet Tab Name?', 'gsheetconnector-wpforms')); ?><span
                class='hover-data'><?php echo esc_html(__('Open your Google Sheet with which you want to link your contact form . You will notice a tab names at bottom of the screen. Copy the tab name where you want to have an entry of contact form.', 'gsheetconnector-wpforms')); ?></span></a>
    </p>
    <p>
        <label><?php echo esc_html(__('Google Tab Id', 'gsheetconnector-wpforms')); ?></label>
        <input type="text" name="wpform-gs[tab-id]" id="wpforms-gs-tab-id"
            value="<?php echo ( isset($get_data[0]['tab-id']) ) ? esc_attr($get_data[0]['tab-id']) : ''; ?>" />
        <a href="" class=" gs-name help-link"><img src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/help.png"
                class="help-icon"><?php //echo esc_html(__('Google Tab Id?', 'gsheetconnector-wpforms')); ?><span
                class='hover-data'><?php echo esc_html(__('you can get tab id from your sheet URL', 'gsheetconnector-wpforms')); ?></span></a>
    </p>
    <?php if(((isset($get_data[0]['sheet-name'])) || $get_data[0]['sheet-name']!="") && ((isset($get_data[0]['sheet-id'])) || $get_data[0]['sheet-id']!="") &&  ((isset($get_data[0]['sheet-tab-name'])) || $get_data[0]['sheet-tab-name']!="") && ((isset($get_data[0]['tab-id'])) || $get_data[0]['tab-id']!="")) {
          $sheet_url = "https://docs.google.com/spreadsheets/d/".$get_data[0]['sheet-id']."/edit#gid=".$get_data[0]['tab-id'];
          ?>
    <p>
        <a href="<?php echo $sheet_url; ?>" target="_blank" class="cf7_gs_link_wpfrom">Google Sheet Link</a>
    </p>
    <?php } ?>
</div>
<input type="submit" align="middle" value="Submit Data" id="wp-save-btn" class="wp-save-btn" name="wp-save-btn">
<input type="hidden" name="form-id" id="form-id" value="<?php echo $form_id; ?>">
</div>
<!-- Upgrade to PRO -->
<br />
<hr class="divide">
<div class="upgrade_pro_wpform">
    <div class="wpform_pro_demo">
        <div class="cd-faq-content" style="display: block;">
            <div class="gs-demo-fields gs-second-block">

                <h2 class="upgradetoprotitlewpform">Upgrade to WPForms Google sheet Connector PRO</h2>
                <hr class="divide">
                <p>
                    <a class="wpform_pro_link" target="_blank"
                        href="https://wpformsdemo.gsheetconnector.com"><label><?php echo esc_html( __( 'Click Here Demo', 'gsheetconnector-wpforms' ) ); ?></label></a>
                </p>
                <p>
                    <a class="wpform_pro_link"
                        href="https://docs.google.com/spreadsheets/d/1ooBdX0cgtk155ww9MmdMTw8kDavIy5J1m76VwSrcTSs/edit#gid=1289172471"
                        target="_blank"
                        rel="noopener"><label><?php echo esc_html( __( 'Sheet URL (Click Here to view Sheet with submitted data.)', 'gsheetconnector-wpforms' ) ); ?></label></a>
                </p>

                <a href="https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro?gsheetconnector-ref=17"
                    target="_blank">
                    <h3>WPForms Google Sheet Connector PRO Features </h3>
                </a>
                <div class="gsh_wpform_pro_fatur_int1">
                    <ul style="list-style: square;margin-left:30px">
                        <li>Google Sheets API (Up-to date)</li>
                        <li>One Click Authentication</li>
                        <li>Click & Fetch Sheet Automated</li>
                        <li>Automated Sheet Name & Tab Name</li>
                        <li>Manually Adding Sheet Name & Tab Name</li>
                        <li>Supported WPForms Lite/Pro</li>
                        <li>Latest WordPress & PHP Support</li>
                        <li>Support WordPress Multisite</li>
                    </ul>
                </div>
                <div class="gsh_wpform_pro_img_int">
                    <img width="250" height="200" alt="wpform-GSheetConnector"
                        src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/WPForms-GSheetConnector-desktop-img.png"
                        class="">
                </div>
                <div class="gsh_wpform_pro_fatur_int2">
                    <ul style="list-style: square;margin-left:68px">
                        <li>Multiple Forms to Sheet</li>
                        <li>Roles Management</li>
                        <li>Creating New Sheet Option</li>
                        <li>Authenticated Email Display</li>
                        <li>Automatic Updates</li>
                        <li>Using Smart Tags</li>
                        <li>Custom Ordering</li>
                        <li>Image / PDF Attachment Link</li>
                        <li>Sheet Headers Settings</li>
                        <li>Click to Sync</li>
                        <li>Sheet Sorting</li>
                        <li>Excellent Priority Support</li>
                    </ul>
                </div>
                <p>
                    <a class="wpform_pro_link_buy"
                        href="https://www.gsheetconnector.com/wpforms-google-sheet-connector-pro?gsheetconnector-ref=17"
                        target="_blank"
                        rel="noopener"><label><?php echo esc_html( __( 'Buy Now - $29.00', 'gsheetconnector-wpforms' ) ); ?></label></a>
                </p>
            </div>
        </div>
    </div>
</div>
<!-- Upgrade to PRO -->
<?php
   }

   /**
    * function to get all the custom posted header fields
    *
    * @since 1.0
    */
   public function execute_post_data() {
      if (isset($_POST ['wp-save-btn'])) {

         $form_id = $_POST['form-id'];

         $get_existing_data = get_post_meta($form_id, 'wpform_gs_settings');


         $gs_sheet_name = isset($_POST['wpform-gs']['sheet-name']) ? $_POST['wpform-gs']['sheet-name'] : "";
         $gs_sheet_id = isset($_POST['wpform-gs']['sheet-id']) ? $_POST['wpform-gs']['sheet-id'] : "";
         $gs_tab_name = isset($_POST['wpform-gs']['sheet-tab-name']) ? $_POST['wpform-gs']['sheet-tab-name'] : "";
         $gs_tab_id = isset($_POST['wpform-gs']['tab-id']) ? $_POST['wpform-gs']['tab-id'] : "";
         // If data exist and user want to disconnect
         if (!empty($get_existing_data) && $gs_sheet_name == "") {
            update_post_meta($form_id, 'wpform_gs_settings', "");
         }

         if (!empty($gs_sheet_name) && (!empty($gs_tab_name) )) {
            update_post_meta($form_id, 'wpform_gs_settings', $_POST['wpform-gs']);
         }
      }
   }

   /**
    * Function - fetch WPform list that is connected with google sheet
    * @since 1.0
    */
   public function get_forms_connected_to_sheet() {
      global $wpdb;
      $query = $wpdb->get_results("SELECT ID,post_title,meta_value from " . $wpdb->prefix . "posts as p JOIN " . $wpdb->prefix . "postmeta as pm on p.ID = pm.post_id where pm.meta_key='wpform_gs_settings' AND p.post_type='wpforms' ORDER BY p.ID");
      return $query;
   }

   /**
    * function to save the setting data of google sheet
    *
    * @since 1.0
    */
   public function add_integration() {

      $wpforms_manual_setting = get_option('wpforms_manual_setting');
    //   if (isset($_GET['code']) && ($wpforms_manual_setting == 0)) {
    //       update_option('is_new_client_secret_wpformsgsc', 1);
    //       $Code = sanitize_text_field($_GET["code"]);
    //       //WPforms_Gsheet_Connector_Init::verify_wpform_gs_integation_new($Code);
    //       //header("Location: " . admin_url('admin.php?page=wpform-google-sheet-config'));
    //       $header = admin_url('admin.php?page=wpform-google-sheet-config');
    //   }else {
    //       $Code = "";
    //       $header = "";
    //   }

    $Code = "";
    $header = "";
    if (isset($_GET['code']) && ($wpforms_manual_setting == 0)) {
        if (is_string($_GET['code'])) {
            $Code = sanitize_text_field($_GET["code"]);
        }
        update_option('is_new_client_secret_wpformsgsc', 1);
        $header = esc_url_raw(admin_url('admin.php?page=wpform-google-sheet-config'));
    }

      ?>
<div class="gs-parts-wpform">
    <div class="card-wp">
        <input type="hidden" name="redirect_auth_wpforms" id="redirect_auth_wpforms"
            value="<?php echo (isset($header)) ?$header:''; ?>">
        <span class="wpforms-setting-field log-setting">

            <h2 class="title"><?php echo __('WPForms - Google Sheet Integration'); ?></h2>
            <hr>
            <p class="wpform-gs-alert-kk">
                <?php echo __('Click "Sign in with Google" button to retrieve your code from Google Drive to allow us to access your spreadsheets. And then Click on Save & Authenticate button. ', 'gsheetconnector-wpforms'); ?>
            </p>
            <p>
                <label><?php echo __('Google Access Code', 'gsheetconnector-wpforms'); ?></label>

                <?php if (!empty(get_option('wpform_gs_token')) && get_option('wpform_gs_token') !== "") { ?>
                <input type="text" name="google-access-code" id="wpforms-setting-google-access-code" value="" disabled
                    placeholder="<?php echo __('Currently Active', 'gsheetconnector-wpforms'); ?>" />
                <input type="button" name="wp-deactivate-log" id="wp-deactivate-log"
                    value="<?php echo __('Deactivate', 'gsheetconnector-wpforms'); ?>" class="button button-primary" />
                <span class="tooltip"> <img src="<?php echo WPFORMS_GOOGLESHEET_URL; ?>assets/img/help.png"
                        class="help-icon"> <span
                        class="tooltiptext tooltip-right"><?php _e('On deactivation, all your data saved with authentication will be removed and you need to reauthenticate with your google account and configure sheet name and tab.', 'gsheetconnector-wpforms'); ?></span></span>
                <?php } else { 
            $redirct_uri = admin_url( 'admin.php?page=wpform-google-sheet-config' );
         ?>
                <input type="text" name="google-access-code" id="wpforms-setting-google-access-code"
                    value="<?php echo esc_attr($Code); ?>"
                    placeholder="<?php echo __('Enter Code', 'gsheetconnector-wpforms'); ?>" />
                <!-- <a href="https://accounts.google.com/o/oauth2/auth?access_type=offline&approval_prompt=force&client_id=1075324102277-drjc21uouvq2d0l7hlgv3bmm67er90mc.apps.googleusercontent.com&redirect_uri=urn:ietf:wg:oauth:2.0:oob&response_type=code&scope=https%3A%2F%2Fspreadsheets.google.com%2Ffeeds%2F+https://www.googleapis.com/auth/userinfo.email+https://www.googleapis.com/auth/drive.metadata.readonly" target="_blank" class="wpforms-btn wpforms-btn-md wpforms-btn-light-grey"><?php //echo __('Get Code', 'gsheetconnector-wpforms'); ?></a> -->

                <a href="https://oauth.gsheetconnector.com/index.php?client_admin_url=<?php echo $redirct_uri;  ?>&plugin=wpformgsheetconnector"
                    class="button_wpformgsc"><img
                        src="<?php echo WPFORMS_GOOGLESHEET_URL ?>/assets/img/btn_google_signin_dark_pressed_web.png"></a>
                <?php } ?>

                <?php 
         if (!empty(get_option('wpform_gs_token')) && get_option('wpform_gs_token') !== "") {
         $google_sheet = new wpfgsc_googlesheet();
         $email_account = $google_sheet->gsheet_print_google_account_email(); 
         if( $email_account ) { ?>
            <p class="connected-account-wpform">
                <?php printf( __( 'Connected email account: %s', 'gsheetconnector-gravityforms' ), $email_account ); ?>
            <p>
                <?php }else{?>
            <p style="color:red">
                <?php echo esc_html(__('Something went wrong ! Your Auth Code may be wrong or expired. Please Deactivate AUTH and Re-Authenticate again. ', 'gsconnector')); ?>
            </p>
            <?php 
                  } 
         }         ?>

            <!-- set nonce -->
            <input type="hidden" name="gs-ajax-nonce" id="gs-ajax-nonce"
                value="<?php echo wp_create_nonce('gs-ajax-nonce'); ?>" />
            <?php if (empty(get_option('wpform_gs_token'))) { ?>
            <input type="submit" name="save-gs" class="wpforms-btn wpforms-btn-md wpforms-btn-orange"
                id="save-wpform-gs-code" value="Save & Authenticate">
            <?php } ?>

            <span class="wpforms-setting-field">
                <label><?php echo __('Debug Log ->', 'gsheetconnector-wpforms'); ?></label>
                <label><a href="<?php echo plugins_url('logs/log.txt', __FILE__); ?>" target="_blank"
                        class="wpform-debug-view"><?php echo __('View', 'gsheetconnector-wpforms'); ?></a></label>
                <label><a class="debug-clear-kk"><?php echo __('Clear', 'gsheetconnector-wpforms'); ?></a></label>
                <span class="clear-loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <p id="gs-validation-message"></p>
                <span class="loading-sign-deactive">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
                <span id="deactivate-message"></span>
            </span>
            <span class="loading-sign">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
            </p>
            <!-- <p>permission displayed here </p> -->
            <?php
              //include_once( WPFORMS_GOOGLESHEET_ROOT . "/lib/google-sheets.php" );
              //$doc = new wpfgsc_googlesheet();
              //$doc->auth();
              //$doc->print_permission($sheet_id);
              //$doc->setSpreadsheetId($sheet_id);
              //$doc->setWorkTabId($tab_id);
             ?>

    </div>
    <div class="gs-sidebar-block-wpform">
        <h2 class="title-wpfrom">Rate us</h2>
        <a target="_blank" href="https://wordpress.org/support/plugin/cf7-google-sheets-connector/reviews/#new-post">
            <div class="gs-stars-wpform"></div>
        </a>
        <p><?php echo __( "Did WPForms GSheetConnector help you out ? Please leave a 5-star review. Thank you!", "gsconnector" ); ?>
        </p><br />
        <a target="_blank" href="https://wordpress.org/support/plugin/gsheetconnector-wpforms/reviews/"
            class="button button-primary gs-review-button" rel="noopener noreferrer">Write a review</a>
    </div>
    <div class="gs-support-wpform">
        <h2 class="title"><?php echo esc_html( __( 'Need a helping hand ?', 'gsconnector' ) ); ?></h2>
        <p>
        <h4><?php echo __( "Please ask for help on ", "gsconnector" ); ?><a
                href="https://wordpress.org/plugins/gsheetconnector-wpforms/"
                target="_blank"><?php echo __( "Support Forum", "gsconnector" ); ?></a><?php echo __( " and ", "gsconnector" ); ?><a
                href="mailto:helpdesk@gsheetconnector.com"><?php echo __( "Email. ", "gsconnector" ); ?></a><br /><br /><?php echo __( "Do provide us detailed information about the issue along with wordpress version. ", "gsconnector" ); ?>
        </h4>
        </p>
    </div>
</div>
<?php
   }

   /**
    * get form data on ajax fire inside div
    * @since 1.1
    */
   public function add_settings_page() {
      $forms = get_posts(array(
         'post_type' => 'wpforms',
         'numberposts' => -1
      ));
      ?>
<div class="wp-formSelect">
    <h3><?php echo __('Select Form', 'gsheetconnector-wpforms'); ?></h3>
</div>
<div class="wp-select">
    <select id="wpforms_select" name="wpforms">
        <option value=""><?php echo __('Select Form', 'gsheetconnector-wpforms'); ?></option>
        <?php foreach ($forms as $form) { ?>
        <option value="<?php echo $form->ID; ?>"><?php echo $form->post_title; ?></option>
        <?php } ?>
    </select>
    <span class="loading-sign-select">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
    <input type="hidden" name="wp-ajax-nonce" id="wp-ajax-nonce"
        value="<?php echo wp_create_nonce('wp-ajax-nonce'); ?>" />
</div>
<div class="wrap gs-form">
    <div class="wp-parts">

        <div class="card" id="wpform-gs">
            <form method="post">
                <h2 class="title"><?php echo __('WPForms - Google Sheet Settings', 'gsheetconnector-wpforms'); ?></h2>
                <hr class="divide">
                <br class="clear">

                <div id="inside">

                </div>
            </form>
        </div>

    </div>
</div>
<?php
   }

   /**
    * AJAX function - deactivate activation
    * @since 1.0
    */
   public function deactivate_wp_integation() {
      // nonce check
      check_ajax_referer('gs-ajax-nonce', 'security');

      if (get_option('wpform_gs_token') != '') {

         $accesstoken = get_option( 'wpform_gs_token' );
         $client = new wpfgsc_googlesheet();
         $client->revokeToken_auto($accesstoken);
         
         delete_option('wpform_gs_token');
         delete_option('wpform_gs_access_code');
         delete_option('wpform_gs_verify');
         wp_send_json_success();
      } else {
         wp_send_json_error();
      }
   }

   /**
    * Function - To send wpform data to google spreadsheet
    * @since 1.0
    */
   public function entry_save($fields, $entry, $form_id, $form_data = '') { 
      
      $data = array();
      
      // Get Entry Id
      $entry_id = wpforms()->process->entry_id;
      
      // Get smart tag list
      $smart_tags = $this->smart_tags_list();
      
      /*
       * Get smart tag values
       * To get query vars and user meta user need to implement hook 
       * apply_filters( 'wpforms_smart_tags', $tags );
       * and modify the tags
       * wpforms()->get( 'smart_tags' )->process( '{user_meta key="last_name"}', $form_data );
       */
      if(!empty($smart_tags)){
        foreach( $smart_tags as $key ) {
            // For free version displaying error as entry id is zero.
            if( preg_match_all( '/entry_date format=\"(.+?)\"/', $key ) && $entry_id == 0 ) {
                continue;
            } else {
                $data[ $key ] = wpforms()->get( 'smart_tags' )->process('{' . $key . '}', $form_data, $fields, $entry_id);
            }
        }
       }
      
      // get form data
      $form_data_get = get_post_meta($form_id, 'wpform_gs_settings');      
      $sheet_name = isset( $form_data_get[0]['sheet-name'] ) ? $form_data_get[0]['sheet-name'] : "";

      $sheet_id = isset( $form_data_get[0]['sheet-id'] ) ? $form_data_get[0]['sheet-id'] : "";

      $sheet_tab_name = isset( $form_data_get[0]['sheet-tab-name'] ) ? $form_data_get[0]['sheet-tab-name'] : "";

      $tab_id = isset( $form_data_get[0]['tab-id'] ) ? $form_data_get[0]['tab-id'] : "";
      
      $payment_type = array( "payment-single", "payment-multiple", "payment-select", "payment-total" );

      if ((!empty($sheet_name) ) && (!empty($sheet_tab_name) )) {
         try {
            include_once( WPFORMS_GOOGLESHEET_ROOT . "/lib/google-sheets.php" );
            $doc = new wpfgsc_googlesheet();
            $doc->auth();
            $doc->setSpreadsheetId($sheet_id);
            $doc->setWorkTabId($tab_id);

            //$timestamp = strtotime(date("Y-m-d H:i:s"));
            // Fetched local date and time instaed of unix date and time
            $data['date'] = date_i18n(get_option('date_format'));
            $data['time'] = date_i18n(get_option('time_format'));
            
            foreach ($fields as $k => $v) {
               $get_field = $fields[$k];
               $key = $get_field['name'];
               $value = $get_field['value'];
               if( in_array( $get_field['type'], $payment_type ) ) {
                  $value =  html_entity_decode( $get_field['value'] );
               }
               $data[$key] = $value;
            }             
            $doc->add_row($data);
         } catch (Exception $e) {
            $data['ERROR_MSG'] = $e->getMessage();
            $data['TRACE_STK'] = $e->getTraceAsString();
            Wpform_gs_Connector_Utility::gs_debug_log($data);
         }
      }
   }
   
   public function display_upgrade_notice() {
      $get_notification_display_interval = get_option( 'wpforms_gs_upgrade_notice_interval' );
      $close_notification_interval = get_option( 'wpforms_gs_close_upgrade_notice' );
      
      if( $close_notification_interval === "off" ) {
         return;
      }
      
      if ( ! empty( $get_notification_display_interval ) ) {
         $adds_interval_date_object = DateTime::createFromFormat( "Y-m-d", $get_notification_display_interval );
         $notice_interval_timestamp = $adds_interval_date_object->getTimestamp();
      }
      
      if ( empty( $get_notification_display_interval ) || current_time( 'timestamp' ) > $notice_interval_timestamp ) {
         $ajax_nonce   = wp_create_nonce( "wpforms_gs_upgrade_ajax_nonce" );
         $upgrade_text = '<div class="gs-adds-notice">';
         $upgrade_text .= '<span><b>GSheetConnector WPForms </b> ';
         $upgrade_text .= 'version 2.0 would required you to <a href="'.  admin_url("admin.php?page=wpcf7-google-sheet-config") . '">reauthenticate</a> with your Google Account again due to update of Google API V3 to V4.<br/><br/>';
         $upgrade_text .= 'To avoid any loss of data redo the <a href="'.  admin_url("admin.php?page=wpform-google-sheet-config&tab=settings") . '">Google Sheet Form Settings</a> of each WPForms again with required sheet and tab details.<br/><br/>';
         $upgrade_text .= 'Also set header names again with the same name as specified for each WPForms field label.<br/><br/>';
         $upgrade_text .= 'Example: "Comment or Message" label must be added similarly for Google Sheet header.</span>';
         $upgrade_text .= '<ul class="review-rating-list">';
         $upgrade_text .= '<li><a href="javascript:void(0);" class="wpforms_gs_upgrade" title="Done">Yes, I have done.</a></li>';
         $upgrade_text .= '<li><a href="javascript:void(0);" class="wpforms_gs_upgrade_later" title="Remind me later">Remind me later.</a></li>';      
         $upgrade_text .= '</ul>';
         $upgrade_text .= '<input type="hidden" name="wpforms_gs_upgrade_ajax_nonce" id="wpforms_gs_upgrade_ajax_nonce" value="' . $ajax_nonce . '" />';
         $upgrade_text .= '</div>';

         $upgrade_block = Wpform_gs_Connector_Utility::instance()->admin_notice( array(
            'type'    => 'upgrade',
            'message' => $upgrade_text
         ) );
         echo $upgrade_block;
      }
   }
   
   public function set_upgrade_notification_interval() {
      // check nonce
      check_ajax_referer( 'wpforms_gs_upgrade_ajax_nonce', 'security' );
      $time_interval = date( 'Y-m-d', strtotime( '+10 day' ) );
      update_option( 'wpforms_gs_upgrade_notice_interval', $time_interval );
      wp_send_json_success();
   }
   
   public function close_upgrade_notification_interval() {
      // check nonce
      check_ajax_referer( 'wpforms_gs_upgrade_ajax_nonce', 'security' );
      update_option( 'wpforms_gs_close_upgrade_notice', 'off' );
      wp_send_json_success();
   }
   
    protected function smart_tags_list() {
        return [
            'admin_email',
            'field_id',
            'field_html_id',
            'field_value_id',
            'form_id',
            'form_name',
            'page_title',
            'page_url',
            'page_id',
            'date format="m/d/Y"',
            'query_var key=""',
            'user_ip',
            'user_id',
            'user_display',
            'user_full_name',
            'user_first_name',
            'user_last_name',
            'user_email',
            'user_meta key=""',
            'author_id',
            'author_display',
            'author_email',
            'url_referer',
            'url_login',
            'url_logout',
            'url_register',
            'url_lost_password',
            'entry_id',
            'entry_date format="d/m/Y"',
        ];
    }

}

$wpforms_service = new WPforms_Googlesheet_Services();