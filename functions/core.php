<?php

namespace HM\BackUpWordPress;

/**
 * Handles anything that needs to be
 * done when the plugin is updated
 */
function update() {

	// Remove old plugin version variable
	if ( get_option( 'hmbkp_plugin_version' ) && version_compare( '1.0.2', get_option( 'hmbkp_plugin_version' ), '>' ) ) {
		delete_option( 'hmbkp_plugin_version' );
	}

	// Every update
	if ( get_option( 'obzbrm_plugin_version' ) && version_compare( Plugin::PLUGIN_VERSION, get_option( 'obzbrm_plugin_version' ), '>' ) ) {

		require_once( HMBKP_PLUGIN_PATH . 'classes/class-setup.php' );

		\HMBKP_Setup::deactivate();

		Path::get_instance()->protect_path( 'reset' );

	}

	// Update the stored version
	if ( get_option( 'obzbrm_plugin_version' ) !== Plugin::PLUGIN_VERSION ) {
		update_option( 'obzbrm_plugin_version', Plugin::PLUGIN_VERSION );
	}

}

/**
 * Setup the default backup schedules
 */
function setup_default_schedules() {

	$schedules = Schedules::get_instance();

	if ( $schedules->get_schedules() ) {
		return;
	}

	/**
	 * Schedule a database backup daily and store backups
	 * for the last 2 weeks
	 */
	$database_daily = new Scheduled_Backup( (string) time() );
	$database_daily->set_type( 'database' );
	$database_daily->set_schedule_start_time( determine_start_time( 'daily', array( 'hours' => '23', 'minutes' => '0' ) ) );
	$database_daily->set_reoccurrence( 'daily' );
	$database_daily->set_max_backups( 7 );
	$database_daily->save();

	/**
	 * Schedule a complete backup to run weekly and store backups for
	 * the last 3 months
	 */
	$complete_weekly = new Scheduled_Backup( (string) ( time() + 1 ) );
	$complete_weekly->set_type( 'complete' );
	$complete_weekly->set_schedule_start_time( determine_start_time( 'weekly', array( 'day_of_week' => 'sunday', 'hours' => '3', 'minutes' => '0' ) ) );
	$complete_weekly->set_reoccurrence( 'weekly' );
	$complete_weekly->set_max_backups( 3 );
	$complete_weekly->save();

	$schedules->refresh_schedules();

	add_action( 'admin_notices', function() {
		echo '<div id="hmbkp-warning" class="updated fade"><p><strong>' . __( 'Backup & Restore Manager has set up your default schedules.', 'backup-restore-manager' ) . '</strong> ' . __( 'By default Backup & Restore Manager performs a daily backup of your database and a weekly backup of your database &amp; files. You can modify these schedules.', 'backup-restore-manager' ) . '</p></div>';
	} );

}

add_action( 'admin_init', '\HM\BackUpWordPress\setup_default_schedules', 11 );

/**
 * Return an array of cron schedules
 *
 * @param $schedules
 * @return array $reccurrences
 */
function cron_schedules( $schedules = array() ) {

	$schedules += array(
		'hourly'      => array( 'interval' => HOUR_IN_SECONDS, 'display' => __( 'Once Hourly', 'backup-restore-manager' ) ),
		'twicedaily'  => array( 'interval' => 12 * HOUR_IN_SECONDS, 'display' => __( 'Twice Daily', 'backup-restore-manager' ) ),
		'daily'       => array( 'interval' => DAY_IN_SECONDS, 'display' => __( 'Once Daily', 'backup-restore-manager' ) ),
		'weekly'      => array( 'interval' => WEEK_IN_SECONDS, 'display' => __( 'Once Weekly', 'backup-restore-manager' ) ),
		'fortnightly' => array( 'interval' => 2 * WEEK_IN_SECONDS, 'display' => __( 'Once Every Two Weeks', 'backup-restore-manager' ) ),
		'monthly'     => array( 'interval' => 30 * DAY_IN_SECONDS, 'display' => __( 'Once Monthly', 'backup-restore-manager' ) ),
	);

	return $schedules;
}

add_filter( 'cron_schedules', '\HM\BackUpWordPress\cron_schedules' );

/**
 * Recursively delete a directory including
 * all the files and sub-directories.
 *
 * @param string $dir
 * @return bool
 * @return bool|WP_Error
 */
function rmdirtree( $dir ) {

	if ( false !== strpos( Path::get_home_path(), $dir ) ) {
		return new WP_Error( 'hmbkp_invalid_action_error', sprintf( __( 'You can only delete directories inside your WordPress installation', 'backup-restore-manager' ) ) );
	}

	if ( is_file( $dir ) ) {
		@unlink( $dir );
	}

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) ) {
		return false;
	}

	$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir, \RecursiveDirectoryIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST, \RecursiveIteratorIterator::CATCH_GET_CHILD );

	foreach ( $files as $file ) {

		if ( $file->isDir() ) {
			@rmdir( $file->getPathname() );
		} else {
			@unlink( $file->getPathname() );
		}
	}

	@rmdir( $dir );

	return true;

}

/**
 * Check if we have read and write permission on the server
 *
 * @return bool
 */
function has_server_permissions() {

	if ( ! wp_is_writable( Path::get_path() ) ) {
		return false;
	}

	if ( ! is_readable( Path::get_root() ) ) {
		return false;
	}

	return true;
}

/**
 * Check if a backup is possible with regards to file
 * permissions etc.
 *
 * @return bool
 */
function is_backup_possible() {

	if ( ! has_server_permissions() || ! is_dir( Path::get_path() ) ) {
		return false;
	}

	if ( ! Requirement_Mysqldump_Command_Path::test() && ! Requirement_PDO::test() ) {
		return false;
	}

	if ( ! Requirement_Zip_Command_Path::test() && ! Requirement_Zip_Archive::test() ) {
		return false;
	}

	return true;
}

/**
 * Get the max email attachment filesize
 *
 * Can be overridden by defining HMBKP_ATTACHMENT_MAX_FILESIZE
 *
 * return int the filesize
 */
function get_max_attachment_size() {

	$max_size = '10mb';

	if ( defined( 'HMBKP_ATTACHMENT_MAX_FILESIZE' ) && wp_convert_hr_to_bytes( HMBKP_ATTACHMENT_MAX_FILESIZE ) ) {
		$max_size = HMBKP_ATTACHMENT_MAX_FILESIZE;
	}

	return wp_convert_hr_to_bytes( $max_size );

}

function is_path_accessible( $dir ) {

	// Path is inaccessible
	if ( strpos( $dir, Path::get_home_path() ) === false ) {
		return false;
	}

	return true;
}

/**
 * List of schedules
 *
 * @return array
 */
function get_cron_schedules() {
	return cron_schedules();
}

/**
 * @param string $type the type of the schedule
 * @param array $times {
 *     An array of time arguments. Optional.
 *
 *     @type int $minutes          The minute to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'i' )` output.
 *     @type int $hours            The hour to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'G' )` output.
 *     @type string $day_of_week   The day of the week to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'l' )` output.
 *     @type int $day_of_month     The day of the month to start the schedule on. Defaults to current time + 10 minutes. Accepts
 *                                 any valid `date( 'j' )` output.
 *     @type int $now              The current time. Defaults to `time()`. Accepts any valid timestamp.
 *
 * }
 * @return int $timestamp Returns the resulting timestamp on success and Int 0 on failure
 */
function determine_start_time( $type, $times = array() ) {

	// Default to in 10 minutes
	if ( ! empty( $times['now'] ) ) {
		$default_timestamp = $times['now'] + 600;

	} else {
		$default_timestamp = time() + 600;
	}

	$default_times = array(
		'minutes'      => date( 'i', $default_timestamp ),
		'hours'        => date( 'G', $default_timestamp ),
		'day_of_week'  => date( 'l', $default_timestamp ),
		'day_of_month' => date( 'j', $default_timestamp ),
		'now'          => time(),
	);

	$args = wp_parse_args( $times, $default_times );

	$intervals = get_cron_schedules();

	// Allow the hours and minutes to be overwritten by a constant
	if ( defined( 'HMBKP_SCHEDULE_TIME' ) && HMBKP_SCHEDULE_TIME ) {
		$hm = HMBKP_SCHEDULE_TIME;
	} else { // The hour and minute that the schedule should start on
		$hm = $args['hours'] . ':' . $args['minutes'] . ':00';
	}

	switch ( $type ) {

		case 'hourly' :
		case 'daily' :
		case 'twicedaily':

			// The next occurance of the specified time
			$schedule_start = $hm;
			break;

		case 'weekly' :
		case 'fortnightly' :

			// The next day of the week at the specified time
			$schedule_start = $args['day_of_week'] . ' ' . $hm;
			break;

		case 'monthly' :

			// The occurance of the time on the specified day of the month
			$schedule_start = date( 'F', $args['now'] ) . ' ' . $args['day_of_month'] . ' ' . $hm;

			// If we've already gone past that day this month then we'll need to start next month
			if ( strtotime( $schedule_start, $args['now'] ) <= $args['now'] ) {
				$schedule_start = date( 'F', strtotime( '+ 1 month', $args['now'] ) )  . ' ' . $args['day_of_month'] . ' ' . $hm;
			}

			// If that's still in the past then we'll need to jump to next year
			if ( strtotime( $schedule_start, $args['now'] ) <= $args['now'] ) {
				$schedule_start = date( 'F', strtotime( '+ 1 month', $args['now'] ) )  . ' ' . $args['day_of_month'] . ' ' . date( 'Y', strtotime( '+ 1 year', $args['now'] ) ) . ' ' . $hm;
			}

			break;

		default :

			return 0;

	}

	$timestamp = strtotime( $schedule_start, $args['now'] );

	// Convert to UTC
	$timestamp -= get_option( 'gmt_offset' ) * 3600;

	// If the scheduled time already passed then keep adding the interval until we get to a future date
	while ( $timestamp <= $args['now'] ) {
		$timestamp += $intervals[ $type ]['interval'];
	}

	return $timestamp;

}

/**
 * Helper function for creating safe action URLs.
 *
 * @param string $action Callback function name.
 * @param array $query_args Additional GET params.
 *
 * @return string
 */
function admin_action_url( $action, array $query_args = array() ) {

	$query_args = array_merge( $query_args, array( 'action' => 'hmbkp_' . $action ) );

	return esc_url( wp_nonce_url( add_query_arg( $query_args, admin_url( 'admin-post.php' ) ), 'hmbkp_' . $action, 'hmbkp-' . $action . '_nonce' ) );
}

/**
 * OS dependant way to pipe stderr to null
 *
 * @return string The exec argument to pipe stderr to null
 */
function ignore_stderr() {

	// If we're on Windows
	if ( DIRECTORY_SEPARATOR == '\\' ) {
		return '2>nul';
	}

	// Or Unix
	return '2>/dev/null';

}

/**
 * Return the contents of `$directory` as a single depth list ordered by total filesize.
 *
 * Will schedule background threads to recursively calculate the filesize of subdirectories.
 * The total filesize of each directory and subdirectory is cached in a transient for 1 week.
 *
 * @param string $directory The directory to list
 *
 * @todo doesn't really belong in this class, should just be a function
 * @return array            returns an array of files ordered by filesize
 */
function list_directory_by_total_filesize( $directory, Excludes $excludes ) {

	$files = $files_with_no_size = $empty_files = $files_with_size = $unreadable_files = array();

	if ( ! is_dir( $directory ) ) {
		return $files;
	}

	$finder = new \Symfony\Component\Finder\Finder();
	$finder->followLinks();
	$finder->ignoreDotFiles( false );
	$finder->ignoreUnreadableDirs();
	$finder->depth( '== 0' );

	$site_size = new Site_Size( 'file', $excludes );

	$files = $finder->in( $directory );

	foreach ( $files as $entry ) {

		// Get the total filesize for each file and directory
		$filesize = $site_size->filesize( $entry );

		if ( $filesize ) {

			// If there is already a file with exactly the same filesize then let's keep increasing the filesize of this one until we don't have a clash
			while ( array_key_exists( $filesize, $files_with_size ) ) {
				$filesize ++;
			}

			$files_with_size[ $filesize ] = $entry;

		} elseif ( 0 === $filesize ) {
			$empty_files[] = $entry;
		} else {
			$files_with_no_size[] = $entry;
		}
	}

	// Sort files by filesize, largest first
	krsort( $files_with_size );

	// Add 0 byte files / directories to the bottom
	$files = $files_with_size + array_merge( $empty_files, $unreadable_files );

	// Add directories that are still calculating to the top
	if ( $files_with_no_size ) {

		// We have to loop as merging or concatenating the array would re-flow the keys which we don't want because the filesize is stored in the key
		foreach ( $files_with_no_size as $entry ) {
			array_unshift( $files, $entry );
		}
	}

	return $files;

}
