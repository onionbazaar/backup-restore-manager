<?php
/*
Plugin Name: Backup & Restore Manager
Plugin URI: https://wordpress.org/plugins/backup-restore-manager/
Description: Simple automated Backup and Restore of your WordPress Website.
Version: 1.0.3
Author: OnionBazaar
Author URI: https://onionbazaar.org
License: GNU General Public License v3.0
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain: backup-restore-manager
Domain Path: /languages
Network: true
*/

// Only load if >= PHP 5.3


if ( ! defined( 'HMBKP_PLUGIN_PATH' ) ) {
	define( 'HMBKP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'HMBKP_BASENAME' ) ) {
	define( 'HMBKP_BASENAME', plugin_basename( __FILE__ ) );
}

require_once( HMBKP_PLUGIN_PATH . 'classes/class-setup.php' );

register_activation_hook( __FILE__, array( 'HMBKP_Setup', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'HMBKP_Setup', 'deactivate' ) );

function get_buwp_notice_message() {
	echo '<div class="notice notice-error is-dismissible"><p><b>'.esc_html__( 'BackUpWordPress is activated -> deactivating Backup & Restore Manager', 'backup-restore-manager' ).'</b></p></div>';
}

function check_backupwordpress_active() {
	if ( is_plugin_active('backupwordpress/backupwordpress.php') ) {
		deactivate_plugins(plugin_basename(__FILE__));
		add_action( 'all_admin_notices', 'get_buwp_notice_message' );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
}
add_action( 'admin_init', 'check_backupwordpress_active' );

if ( HMBKP_Setup::meets_requirements() ) {
	require_once( HMBKP_PLUGIN_PATH . 'classes/class-plugin.php' );
} else {
	add_action( 'admin_init', array( 'HMBKP_Setup', 'self_deactivate' ) );
	add_action( 'all_admin_notices', array( 'HMBKP_Setup', 'display_admin_notices' ) );
}
