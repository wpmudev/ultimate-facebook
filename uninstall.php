<?php
/**
 * Remove plugin settings data
 *
 * @since 2.7.9
 *
 */

//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}
global $wpdb;
//Network_options
$wdfb_option_keys = array(
	'wdfb_api',
	'wdfb_autopost',
	'wdfb_widget_pack',
	'wdfb_opengraph',
	'wdfb_comments',
	'wdfb_grant',
	'wdfb_connect',
	'widget_wdfb_widgetactivityfeed',
	'widget_wdfb_widgetalbums',
	'widget_wdfb_widgetconnect',
	'widget_wdfb_widgetevents',
	'widget_wdfb_widgetfacepile',
	'widget_wdfb_widgetlikebox',
	'widget_wdfb_widgetrecentcomments',
	'widget_wdfb_widgetrecommendations',
	'wdfb_button',
	'wdfb_groups',
	'wdfb_error_log',
	'wdfb_notice_log'
);
//Delete options
foreach ( $wdfb_option_keys as $key ) {
	if ( is_multisite() ) {
		global $wpdb;
		$blogs = $wpdb->get_results( "SELECT blog_id FROM {$wpdb->blogs}", ARRAY_A );
		if ( $blogs ) {
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog['blog_id'] );
				delete_site_option( $key );
			}
			restore_current_blog();
		}
	} else {
		delete_option( $key );
	}
}
//delete from site meta

//Remove Cron Job
wp_clear_scheduled_hook( 'wdfb_import_comments' );
?>