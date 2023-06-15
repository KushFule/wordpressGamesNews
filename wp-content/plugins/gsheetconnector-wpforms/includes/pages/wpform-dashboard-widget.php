<?php
/*
 * Wpform GS Dashboard Widget
 * @since 1.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
   exit();
}
?>
<div class="dashboard-content">
   <?php
   $gs_connector_service = new WPforms_Googlesheet_Services();

   $forms_list = $gs_connector_service->get_forms_connected_to_sheet();
   ?>
   <div class="main-content">
      <div>
         <h3><?php echo __("Wpforms connected with Google Sheet", "gsheetconnector-wpforms"); ?></h3>
         <ul class="wp-form-list">
            <?php
            if (!empty($forms_list)) {
               foreach ($forms_list as $key => $value) {
                  $meta_value = unserialize($value->meta_value);
                  $sheet_name = $meta_value['sheet-name'];
                  if ($sheet_name !== "") {
                     ?>
                     <a href="<?php echo admin_url('admin.php?page=wpforms-builder&view=fields&form_id=' . $value->ID . ''); ?>" target="_blank">
                        <li style= "list-style:none;"><?php echo $value->post_title; ?></li>
                     </a>
                     <?php } else {
                     ?>
                     <li><span><?php echo __("No Wpforms are connected with google sheet.", "gsheetconnector-wpforms"); ?></span></li>
                     <?php
                  }
               }
            } else {
               ?>
               <li><span><?php echo __("No Wpforms are connected with google sheet.", "gsheetconnector-wpforms"); ?></span></li>
               <?php
            }
            ?>
         </ul>
      </div>
   </div> <!-- main-content end -->
</div> <!-- dashboard-content end -->