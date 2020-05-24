<?php

namespace HM\BackUpWordPress;

/**
 * Add the backups menu item
 * to the tools menu
 */
function admin_menu() {

	if ( is_multisite() ) {
		add_submenu_page( 'settings.php', __( 'Manage Backups | Backup & Restore Manager', 'backup-restore-manager' ), __( 'Backups', 'backup-restore-manager' ), ( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options', HMBKP_PLUGIN_SLUG, 'HM\BackUpWordPress\manage_backups' );
	} else {
		add_management_page( __( 'Manage Backups', 'backup-restore-manager' ), __( 'Backups', 'backup-restore-manager' ), ( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options', HMBKP_PLUGIN_SLUG, 'HM\BackUpWordPress\manage_backups' );
	}

	add_submenu_page( null, __( 'BackUpWordPress Extensions', 'backup-restore-manager' ), __( 'Extensions', 'backup-restore-manager' ), ( defined( 'HMBKP_CAPABILITY' ) && HMBKP_CAPABILITY ) ? HMBKP_CAPABILITY : 'manage_options', HMBKP_PLUGIN_SLUG . '_extensions', 'HM\BackUpWordPress\extensions' );

}
add_action( 'network_admin_menu', 'HM\BackUpWordPress\admin_menu' );
add_action( 'admin_menu', 'HM\BackUpWordPress\admin_menu' );

/**
 * Load the backups admin page
 * when the menu option is clicked
 *
 * @return null
 */
function manage_backups() {
	require_once( HMBKP_PLUGIN_PATH . 'admin/page.php' );
}


/**
 * Load the backups admin page
 * when the menu option is clicked
 *
 * @return null
 */
function extensions() {
	require_once( HMBKP_PLUGIN_PATH . 'admin/extensions.php' );
}

/**
 * Highlights the 'Backups' submenu item when on the Extensions page
 *
 * @param string $submenu_file
 * @return string $submenu_file The slug of the menu item to highlight
 */
function highlight_submenu( $submenu_file ) {

	$screen = get_current_screen();

	if ( 'tools_page_' . HMBKP_PLUGIN_SLUG . '_extensions' === $screen->id ) {

		// Set the main plugin page to be the active submenu page
		$submenu_file = HMBKP_PLUGIN_SLUG;

	}

	return $submenu_file;

}
add_filter( 'submenu_file', 'HM\BackUpWordPress\highlight_submenu' );

/**
 * Add a link to the backups page to the plugin action links.
 *
 * @param array $links
 * @param string $file
 *
 * @return array $links
 */
function plugin_action_link( $links, $file ) {

	if ( false !== strpos( $file, HMBKP_PLUGIN_SLUG ) ) {
		array_push( $links, '<a href="' . esc_url( HMBKP_ADMIN_URL ) . '">' . __( 'Backups', 'backup-restore-manager' ) . '</a>' );
	}

	return $links;

}
add_filter( 'plugin_action_links', 'HM\BackUpWordPress\plugin_action_link', 10, 2 );

/**
 * Add Contextual Help to Backups tools page.
 *
 * Help is pulled from the readme FAQ.
 *
 * @return null
 */
function context_help() {

	// Pre WordPress 3.3 compat
	if ( ! method_exists( get_current_screen(), 'add_help_tab' ) ) {
		return;
	}

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . 'admin/constants.php' );
	$constants = ob_get_clean();

	ob_start();
	include_once( HMBKP_PLUGIN_PATH . 'admin/faq.php' );
	$faq = ob_get_clean();

	get_current_screen()->add_help_tab( array(
		'title'   => __( 'FAQ', 'backup-restore-manager' ),
		'id'      => 'hmbkp_faq',
		'content' => wp_kses_post( $faq ),
	) );

	get_current_screen()->add_help_tab( array(
		'title'   => __( 'Constants', 'backup-restore-manager' ),
		'id'      => 'hmbkp_constants',
		'content' => wp_kses_post( $constants ),
	) );

	require_once( HMBKP_PLUGIN_PATH . 'classes/class-requirements.php' );

	ob_start();
	require_once( HMBKP_PLUGIN_PATH . 'admin/server-info.php' );
	$info = ob_get_clean();

	get_current_screen()->add_help_tab(
		array(
			'title'   => __( 'Server Info', 'backup-restore-manager' ),
			'id'      => 'hmbkp_server',
			'content' => $info,
		)
	);
	
	/* OBZMOD
	get_current_screen()->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For more information:', 'backupwordpress' ) . '</strong></p><p><a href="https://github.com/humanmade/backupwordpress" target="_blank">GitHub</a></p><p><a href="http://wordpress.org/tags/backupwordpress?forum_id=10" target="_blank">' . esc_html__( 'Support Forums', 'backupwordpress' ) . '</a></p><p><a href="https://translate.wordpress.org/projects/wp-plugins/backupwordpress/dev/" target="_blank">' . esc_html__( 'Help with translation', 'backupwordpress' ) . '</a></p>'
	);
	*/
	get_current_screen()->set_help_sidebar(
		'<p><strong>' . esc_html__( 'For Support:', 'backup-restore-manager' ) . '</strong></p><p><a href="https://onionbazaar.org/p=support" target="_blank">OnionBazaar</a></p><p><a href="https://wordpress.org/support/plugin/backup-restore-manager" target="_blank">' . esc_html__( 'Support Forums', 'backup-restore-manager' ) . '</a></p>'
	);
	/* OBZMOD */

}
add_action( 'load-' . HMBKP_ADMIN_PAGE, 'HM\BackUpWordPress\context_help' );
