<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();

// delete options created by the plugin
delete_option('eightdigits_active');
delete_option('eightdigits_tracking_code');
delete_option('eightdigits_access_token');
delete_option('eightdigits_installation_notified');

?>