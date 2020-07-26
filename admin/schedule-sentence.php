<?php

namespace HM\BackUpWordPress;

$filesize = get_site_size_text( $schedule );

// Backup Type
$type = strtolower( human_get_type( $schedule->get_type() ) );

// Backup Time
$day = date_i18n( 'l', $schedule->get_next_occurrence( false ) );

// Next Backup
$next_backup = 'title="' . esc_attr( sprintf( __( 'The next backup will be on %1$s at %2$s %3$s', 'backup-restore-manager' ), date_i18n( get_option( 'date_format' ), $schedule->get_next_occurrence( false ) ), date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ), date_i18n( 'T', $schedule->get_next_occurrence( false ) ) ) ) . '"';

// Backup status
$status = new Backup_Status( $schedule->get_id() );

// Backup Re-occurrence
switch ( $schedule->get_reoccurrence() ) :

	case 'hourly' :

		$reoccurrence = date_i18n( 'i', $schedule->get_next_occurrence( false ) ) === '00' ? '<span ' . $next_backup . '>' . __( 'hourly on the hour', 'backup-restore-manager' ) . '</span>' : sprintf( __( 'hourly at %s minutes past the hour', 'backup-restore-manager' ), '<span ' . $next_backup . '>' . intval( date_i18n( 'i', $schedule->get_next_occurrence( false ) ) ) ) . '</span>';

	break;

	case 'daily' :

		$reoccurrence = sprintf( __( 'daily at %s', 'backup-restore-manager' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'twicedaily' :

		$times[] = date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) );
		$times[] = date_i18n( get_option( 'time_format' ), strtotime( '+ 12 hours', $schedule->get_next_occurrence( false ) ) );

		sort( $times );

		$reoccurrence = sprintf( __( 'every 12 hours at %1$s &amp; %2$s', 'backup-restore-manager' ), '<span ' . $next_backup . '>' . esc_html( reset( $times ) ) . '</span>', '<span>' . esc_html( end( $times ) ) ) . '</span>';

	break;

	case 'weekly' :

		$reoccurrence = sprintf( __( 'weekly on %1$s at %2$s', 'backup-restore-manager' ), '<span ' . $next_backup . '>' .esc_html( $day ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'fortnightly' :

		$reoccurrence = sprintf( __( 'every two weeks on %1$s at %2$s', 'backup-restore-manager' ), '<span ' . $next_backup . '>' . $day . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'monthly' :

		$reoccurrence = sprintf( __( 'on the %1$s of each month at %2$s', 'backup-restore-manager' ), '<span ' . $next_backup . '>' . esc_html( date_i18n( 'jS', $schedule->get_next_occurrence( false ) ) ) . '</span>', '<span>' . esc_html( date_i18n( get_option( 'time_format' ), $schedule->get_next_occurrence( false ) ) ) . '</span>' );

	break;

	case 'manually' :

		$reoccurrence = __( 'manually', 'backup-restore-manager' );

	break;

	default :

		$reoccurrence = __( 'manually', 'backup-restore-manager' );
		$schedule->set_reoccurrence( 'manually' );

endswitch;

$server = '<code title="' . __( 'Check the help tab to learn how to change where your backups are stored.', 'backup-restore-manager' ) . '">' . esc_attr( str_replace( Path::get_home_path(), '', Path::get_path() ) ) . '</code>';

// Backup to keep
switch ( $schedule->get_max_backups() ) :

	case 1 :

		$backup_to_keep = sprintf( __( 'store the most recent backup in %s', 'backup-restore-manager' ), $server );

	break;

	case 0 :

		$backup_to_keep = sprintf( __( 'don\'t store any backups in on this server', 'backup-restore-manager' ), Path::get_path() );

	break;

	default :

		$backup_to_keep = sprintf( __( 'store the last %1$s backups in %2$s', 'backup-restore-manager' ), esc_html( $schedule->get_max_backups() ), $server );

endswitch;

$email_msg = '';
$services = array();

foreach ( Services::get_services( $schedule ) as $file => $service ) {

	if ( is_wp_error( $service ) ) {
		$email_msg = $service->get_error_message();
	} elseif ( 'Email' === $service->name ) {
		$email_msg = wp_kses_post( $service->display() );
	} elseif ( $service->is_service_active() && $service->display() ) {
		$services[] = esc_html( $service->display() );
	}
}

if ( ! empty( $services ) && count( $services ) > 1 ) {
	$services[ count( $services ) -2 ] .= ' & ' . $services[ count( $services ) -1 ];
	array_pop( $services );
} ?>

<div class="hmbkp-schedule-sentence<?php if ( $status->get_status() ) { ?> hmbkp-running<?php } ?>">

	<?php $sentence = sprintf( _x( 'Backup my %1$s %2$s %3$s, %4$s.', '1: Backup Type 2: Total size of backup 3: Schedule 4: Number of backups to store', 'backup-restore-manager' ), '<span>' . esc_html( $type ) . '</span>', $filesize, $reoccurrence, $backup_to_keep );

	if ( $email_msg ) {
		$sentence .= ' ' . $email_msg;
	}

	if ( ! empty( $services ) ) {
		$sentence .= ' ' . sprintf( __( 'Send a copy of each backup to %s.', 'backup-restore-manager' ), implode( ', ', $services ) );
	}

	echo $sentence; ?>

	<?php if ( Schedules::get_instance()->get_schedule( $schedule->get_id() ) ) :
		schedule_status( $schedule );
	endif; ?>

	<?php require( HMBKP_PLUGIN_PATH . 'admin/schedule-settings.php' ); ?>

</div>

<?php

/**
 * Returns a formatted string containing the calculated total site size or a message
 * to indicate it is being calculated.
 *
 * @param HM\BackUpWordPress\Scheduled_Backup $schedule
 *
 * @return string
 */
function get_site_size_text( Scheduled_Backup $schedule ) {

	if ( isset( $_GET['hmbkp_add_schedule'] ) ) {
		return '';
	}

	$site_size = new Site_Size( $schedule->get_type(), $schedule->get_excludes() );

	if ( 'database' === $schedule->get_type() || $site_size->is_site_size_cached() ) {
		return sprintf(
			'(<code title="' . __( 'Backups will be compressed and should be smaller than this.', 'backup-restore-manager' ) . '">%s</code>)',
			esc_html( $site_size->get_formatted_site_size() )
		);
	}

	return '';
}


/* OBZMOD */

if ( is_readable( Path::get_path() . '/backup-restore-manager-cron' ) ) {
	$hmbkp_cron_lastrun = time()-filemtime( Path::get_path() . '/backup-restore-manager-cron' );
	$hmbkp_cronmode_tmp = trim( file_get_contents( Path::get_path() . '/backup-restore-manager-cron' ) );
	if ( ( $hmbkp_cronmode_tmp == 'yes' ) AND ($hmbkp_cron_lastrun<300) ) $hmbkp_cronmode = true; else $hmbkp_cronmode = false;
}
else {
	if ( is_writable( Path::get_path() ) ) {
		file_put_contents( Path::get_path() . '/backup-restore-manager-cron', 'no' );
	}
	$hmbkp_cronmode = false;
}

if ( is_multisite() ) $hmbkp_phpself = 'settings.php'; else $hmbkp_phpself = 'tools.php';

if ( isset( $_REQUEST['_wpnonce'] ) ) $hmbkp_nonce = sanitize_text_field( $_REQUEST['_wpnonce'] ); else $hmbkp_nonce = null;
if ( isset( $_REQUEST['hmbkp_restore'] ) ) $hmbkp_restore =  sanitize_file_name( $_REQUEST['hmbkp_restore'] ); else $hmbkp_restore = null;
if ( isset( $_POST['hmbkp_restore_confirm'] ) ) $hmbkp_restore_confirm =  1; else $hmbkp_restore_confirm = null;
if ( isset( $_POST['hmbkp_restore_abort'] ) ) $hmbkp_restore_abort =  1; else $hmbkp_restore_abort = null;
if ( isset( $_GET['hmbkp_schedule_id'] ) ) $hmbkp_schedule_id = intval( $_GET['hmbkp_schedule_id'] ); else $hmbkp_schedule_id = null;


if ( ( isset( $hmbkp_restore ) ) AND ( wp_verify_nonce( $hmbkp_nonce, 'hmbkp_nonce' ) ) AND ( $hmbkp_restore_confirm == null ) AND ( $hmbkp_restore_abort == null ) ) {
	if ( isset( $hmbkp_schedule_id ) ) $hmbkp_schedule_id_str = '&hmbkp_schedule_id=' . ( intval( sanitize_text_field( $hmbkp_schedule_id ) ) ); else $hmbkp_schedule_id_str = null;

	if ( file_exists( Path::get_path() . '/restore.txt' ) ) unlink( Path::get_path() . '/restore.txt' );

	echo '
	<div class="hmbkp-schedule-sentence" style="text-align: center;">
		<form method="post" name="hmbkp_restore_confirm" action="' . $hmbkp_phpself . '?page=backup-restore-manager' . $hmbkp_schedule_id_str . '">
		<input type="hidden" name="hmbkp_restore" value="' . esc_attr( $hmbkp_restore ) . '">
		<input type="hidden" name="_wpnonce" value="' . wp_create_nonce( 'hmbkp_nonce' ) . '">
		
		<b>' . esc_html__( 'Restore backup?', 'backup-restore-manager' ) . '</b><br><br>
		' . esc_html( $hmbkp_restore ) . '<br><br>
		<input type="submit" name="hmbkp_restore_confirm" class="button button-primary" value="' . esc_attr__( 'Yes', 'backup-restore-manager' ) . '"> &nbsp; <input type="submit" name="hmbkp_restore_abort" class="button button-primary" value="' . esc_attr__( 'Abort', 'backup-restore-manager' ) . '">
		</form>
		<br>
		<span style="font-size: 11px;"><b>' . esc_html__( 'just in case:', 'backup-restore-manager' ) . '</b> ' . esc_html__( 'download all important backup zip files before proceeding', 'backup-restore-manager' ) . '</span>
	</div>';
}

if ( ( isset( $hmbkp_restore_abort ) ) AND ( file_exists( Path::get_path() . '/restore_complete.txt' ) == false ) ) {
	if ( file_exists( Path::get_path() . '/restore.txt') ) unlink( Path::get_path() . '/restore.txt' );

	echo '
	<div class="hmbkp-schedule-sentence" style="text-align: center;">
		<b>' . esc_html__( 'Restore aborted', 'backup-restore-manager' ) . '</b><br>
		<p style="margin-top: 16px; margin-bottom: 0px;">' . esc_html__( 'no changes made', 'backup-restore-manager' ) . '</p>
	</div>';
}

if ( ( isset( $_POST['hmbkp_manual_restore'] ) ) AND ( file_exists( Path::get_path() . '/restore_complete.txt' ) == false ) ) {
	if ( isset( $hmbkp_schedule_id ) ) $hmbkp_schedule_id_str = '&hmbkp_schedule_id=' . $hmbkp_schedule_id; else $hmbkp_schedule_id_str = null;

	if ( file_exists( Path::get_path() . '/restore.txt') ) unlink( Path::get_path() . '/restore.txt' );

	$hmbkp_restoreinfo = null;
	if ( strpos( $hmbkp_restore, 'complete' ) ) $hmbkp_restoreinfo = sprintf( esc_html__( '1. Move all files and folders within %s into a temporary subfolder, e.g. %s', 'backup-restore-manager' ), '<b>' . get_home_path() . '</b>', '<b>' . get_home_path() . 'temp/</b>' ) . '<br>
	' . sprintf( esc_html__( '2. Extract the backup file into %s', 'backup-restore-manager' ), '<b>' . get_home_path() . '</b> (use unzip with the -X flag to restore permissions and ownership)' ) . '
	<br>
	' . sprintf( esc_html__( '3. Open your SQL Manager (usually phpmyadmin), delete + recreate your database (or create a new one) and import the sql-file from the backup (now located in %s). Delete the sql-file afterwards.', 'backup-restore-manager' ), '<b>' . get_home_path() . '</b>' ) . '<br>
	' . sprintf( esc_html__( '4. If your SQL database or credentials have changed, update %s accordingly', 'backup-restore-manager' ), '<b>' . get_home_path() . 'wp-config.php</b>' ) . '<br>
	' . esc_html__( '5. Delete the temporary subfolder from step 1 if the restore was successful', 'backup-restore-manager' );
	elseif ( strpos( $hmbkp_restore, 'database' ) ) $hmbkp_restoreinfo = esc_html__( '1. Open your SQL Manager (usually phpmyadmin) and delete + recreate your database (or create a new one)', 'backup-restore-manager' ) . '<br>
	' . esc_html__( '2. Extract the backup archive and import the sql-file.', 'backup-restore-manager' ) . '<br>
	' . sprintf( esc_html__( '3. Update %s if your database name has changed.', 'backup-restore-manager' ), '<b>' . get_home_path() . 'wp-config.php</b>' ) . '<br>';
	elseif ( strpos( $hmbkp_restore, 'file' ) ) $hmbkp_restoreinfo = sprintf( esc_html__( '1. Move all files and folders within %s into a temporary subfolder, e.g. %s', 'backup-restore-manager' ), '<b>' . get_home_path() . '</b>', '<b>' . get_home_path() . 'temp/</b>' ) . '<br>
	' . sprintf( esc_html__( '2. Extract the backup file into %s', 'backup-restore-manager' ), '<b>' . get_home_path() . '</b> (use unzip with the -X flag to restore permissions and ownership)' ) . '	<br>
	' . sprintf( esc_html__( '3. If your SQL credentials have changed since the backup, update %s accordingly', 'backup-restore-manager' ), '<b>' . get_home_path() . 'wp-config.php</b>' ) . '<br>
	' . esc_html__( '4. Delete the temporary subfolder from step 1 if the restore was successful', 'backup-restore-manager' );

	echo '
	<div class="hmbkp-schedule-sentence" style="text-align: center;">
		<b>' . esc_html__( 'Restoring Backup..', 'backup-restore-manager' ) . '</b><br><br>
		' . esc_html( $hmbkp_restore ) . '<br><br>
		<p style="margin: 10px;"><u>' . esc_html__( 'Follow these steps to restore the backup:', 'backup-restore-manager' ) . '</u></p>
		<div style="margin: 0 auto; width: 100%; line-height: 30px;">' . $hmbkp_restoreinfo . '</div>
		<form method="post" action="' . $hmbkp_phpself . '?page=backup-restore-manager' . $hmbkp_schedule_id_str . '">
		<input type="submit" class="button button-primary" style="margin: 15px;" name="hmbkp_refresh" value="' . esc_attr__( 'Done', 'backup-restore-manager' ) . '">
		</form>
	</div>';
}

if ( ( isset( $hmbkp_restore_confirm ) ) AND ( wp_verify_nonce( $hmbkp_nonce, 'hmbkp_nonce' ) ) ) {
	file_put_contents( Path::get_path() . '/restore.txt',  $hmbkp_restore );
}

if ( file_exists( Path::get_path() . '/restore.txt' ) ) {
	if ( isset( $hmbkp_schedule_id ) ) $hmbkp_schedule_id_str = '&hmbkp_schedule_id=' . $hmbkp_schedule_id; else $hmbkp_schedule_id_str = null;

	$hmbkp_restore_file = esc_attr( sanitize_text_field( file_get_contents( Path::get_path() . '/restore.txt' ) ) );

	if ( $hmbkp_cronmode ) {
		if ( file_exists( Path::get_path() . '/' . $hmbkp_restore_file ) ) $hmbkp_filesize = filesize( Path::get_path() . '/' . $hmbkp_restore_file ); else $hmbkp_filesize = 10000000;
		
		if ($hmbkp_filesize>=10000000000) $hmbkp_duration = 60;
		if ($hmbkp_filesize<10000000000) $hmbkp_duration = 45;
		if ($hmbkp_filesize<5000000000) $hmbkp_duration = 30;
		if ($hmbkp_filesize<1000000000) $hmbkp_duration = 10;
		if ($hmbkp_filesize<100000000) $hmbkp_duration = 5;
		if ($hmbkp_filesize<50000000) $hmbkp_duration = 3;

		echo '
		<div class="hmbkp-schedule-sentence" style="text-align: center;">
			<b>' . esc_html__( 'Restoring Backup..', 'backup-restore-manager' ) . '</b><br><br>
			' . $hmbkp_restore_file . '<br><br>
			<p style="margin: 10px;">' . sprintf( esc_html__( 'Restore in progress. Please wait about %s minutes, then click "Done"', 'backup-restore-manager' ), $hmbkp_duration ) . '</p><br>
			<form method="post" name="hmbkp_restore_confirm" action="' . $hmbkp_phpself . '?page=backup-restore-manager' . $hmbkp_schedule_id_str . '">
			<input type="hidden" name="hmbkp_restore" value="' . $hmbkp_restore_file . '">
			<input type="submit" class="button button-primary" name="hmbkp_refresh" value="' . esc_attr__( 'Done', 'backup-restore-manager' ) . '"> &nbsp; <input type="submit" class="button button-primary" name="hmbkp_restore_abort" value="' . esc_attr__( 'Abort', 'backup-restore-manager' ) . '"> &nbsp; <input type="submit" class="button button-primary" name="hmbkp_manual_restore" value="' . esc_attr__( 'Manually restore instead', 'backup-restore-manager' ) . '">
			</form>
		</div>';
	}
	else 
	{
		echo '
		<div class="hmbkp-schedule-sentence" style="text-align: center;">
			<b>' . esc_html__( 'Restoring Backup..', 'backup-restore-manager' ) . '</b><br><br>
			' . $hmbkp_restore_file . '<br><br>
			<p style="margin: 10px;">' . esc_html__( 'Enter the following command into the terminal to restore this backup:', 'backup-restore-manager' ) . '</p>
			<input type="text" style="width: 700px; margin-bottom: 15px; padding: 3px; text-align: center; border: 1px solid #1d1d1d; background-color: #dcdcdc" value="sudo bash ' . plugin_dir_path(__DIR__) . 'restore.sh"><br>
			<form method="post" name="hmbkp_restore_confirm" action="' . $hmbkp_phpself . '?page=backup-restore-manager' . $hmbkp_schedule_id_str . '">
			<input type="hidden" name="hmbkp_restore" value="' . $hmbkp_restore_file . '">
			<input type="submit" class="button button-primary" name="hmbkp_refresh" value="' . esc_attr__( 'Done', 'backup-restore-manager' ) . '"> &nbsp; <input type="submit" class="button button-primary" name="hmbkp_restore_abort" value="' . esc_attr__( 'Abort', 'backup-restore-manager' ) . '"> &nbsp; <input type="submit" class="button button-primary" name="hmbkp_manual_restore" value="' . esc_attr__( 'Manually restore instead', 'backup-restore-manager' ) . '">
			</form>
		</div>';
	}
}

if ( file_exists( Path::get_path() . '/restore_complete.txt' ) ) {
	if ( isset( $hmbkp_schedule_id ) ) $hmbkp_schedule_id_str = '&hmbkp_schedule_id=' . $hmbkp_schedule_id; else $hmbkp_schedule_id_str = null;

	$hmbkp_restore_complete = esc_html( file_get_contents( Path::get_path() . '/restore_complete.txt' ) );

	unlink( Path::get_path() . '/restore_complete.txt' );

	echo '
	<div class="hmbkp-schedule-sentence" style="text-align: center;">
		<b>' . esc_html__( 'Restoring Backup..', 'backup-restore-manager' ) . '</b><br><br>
		' . $hmbkp_restore_complete . '<br><br>
		<span style="color: green;">' . esc_html__( 'Restore successful!', 'backup-restore-manager' ) . '</span><br><br>
		<form method="post" name="hmbkp_restore_confirm" action="' . $hmbkp_phpself . '?page=backup-restore-manager' . $hmbkp_schedule_id_str . '">
		<input type="submit" class="button button-primary" name="hmbkp_refresh" value="' . esc_attr__( 'ok', 'backup-restore-manager' ) . '">
		</form>
	</div>';
}

/* OBZMOD */
