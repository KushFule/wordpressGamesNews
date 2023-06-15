<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
   exit();
}
$WpForms_gs_tools_service = new WPforms_Gsheet_Connector_Init();
?>
<div class="card">
   <textarea readonly="readonly" onclick="this.focus();this.select()" id="wpforms-gs-system-info" name="wpforms-gs-system-info" title="<?php echo __( 'To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac).', 'googlesheet' ); ?>">
<?php echo $WpForms_gs_tools_service->get_wpforms_system_info(); ?>
   </textarea>
</div>    