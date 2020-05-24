<?php

namespace HM\BackUpWordPress;

?>

<h3><?php esc_html_e( 'Upload Backup', 'backup-restore-manager' ); ?></h3>

<?php 

include_once( 'hmbkp_functions.php' );

if ( isset( $_FILES['hmbkp_upload_file'] ) ) {

	$hmbkp_filename = sanitize_file_name( $_FILES['hmbkp_upload_file']['name'] );

	$hmbkp_idendpos = null;
	if ( strpos( $hmbkp_filename, 'complete' ) ) $hmbkp_idendpos = strpos( $hmbkp_filename, 'complete' );
	elseif ( strpos( $hmbkp_filename, 'database' ) ) $hmbkp_idendpos = strpos( $hmbkp_filename, 'database' );
	elseif ( strpos( $hmbkp_filename, 'file' ) ) $hmbkp_idendpos = strpos( $hmbkp_filename, 'file' );

	if ( is_numeric( substr( $hmbkp_filename, $hmbkp_idendpos-11, 10 ) ) ) {
		$hmbkp_scheduleid = substr( $hmbkp_filename, $hmbkp_idendpos-11, 10 );

		if ( $hmbkp_scheduleid<>$schedule->get_id() ) {
			$hmbkp_filename2 = str_replace($hmbkp_scheduleid, $schedule->get_id(), $hmbkp_filename);
			$hmbkp_filename = $hmbkp_filename2;
		}
	}
	else $hmbkp_scheduleid = null;

	$hmbkp_uploadmessage_type = 'error';
	if ( substr( $hmbkp_filename, -4) != '.zip' ) {
		$hmbkp_uploadmessage = esc_html__('File upload error! Invalid filetype (must be the unaltered .zip file of the backup)', 'backup-restore-manager');
	}
	elseif ( hmbkp_validateDate( substr( substr( $hmbkp_filename, -23), 0, 19 ) ) == false ) {
		$hmbkp_uploadmessage = esc_html__('File upload error! Invalid filename (could not detect date)', 'backup-restore-manager');
	}
	elseif ( $hmbkp_idendpos == null ) {
		$hmbkp_uploadmessage = esc_html__('File upload error! Invalid filename (could not detect backup type)', 'backup-restore-manager');
	}	
	elseif ( $hmbkp_scheduleid == null ) {
		$hmbkp_uploadmessage = esc_html__('File upload error! Invalid filename (could not detect schedule id)', 'backup-restore-manager');
	}	
	elseif ( file_exists( Path::get_path() . '/' . $hmbkp_filename ) ) {
		$hmbkp_uploadmessage = esc_html__('File upload error! A backup with the same filename already exists', 'backup-restore-manager');
	}	
	else
	{
		if ( move_uploaded_file( $_FILES['hmbkp_upload_file']['tmp_name'], Path::get_path() . '/' . $hmbkp_filename ) ) {
			$hmbkp_timestamp = strtotime( hmbkp_returntimestamp( substr( substr( $hmbkp_filename, -23), 0, 19 ) ) );
			touch( Path::get_path() . '/' . $hmbkp_filename, $hmbkp_timestamp );
			$hmbkp_uploadmessage = sprintf( esc_html__( 'Backup upload successful - %s', 'backup-restore-manager' ), $hmbkp_filename );
			$hmbkp_uploadmessage_type = 'success';
		} 
		else {
			$hmbkp_uploadmessage = esc_html__('File upload error!', 'backup-restore-manager');
		}
	}
}
else $hmbkp_uploadmessage = null;



if ( isset( $hmbkp_uploadmessage ) ) {
	echo '<div id="hmbkp-warning" class="notice notice-' . $hmbkp_uploadmessage_type . '"><p><strong>' . $hmbkp_uploadmessage . '</strong></p></div>';
 }

$hmbkp_upload_max_filesize = ini_get( "upload_max_filesize" );
$hmbkp_post_max_size = ini_get( "post_max_size" );

if ( substr( $hmbkp_upload_max_filesize, 0, strlen( $hmbkp_upload_max_filesize ) - 1 ) > substr( $hmbkp_post_max_size, 0, strlen( $hmbkp_post_max_size ) - 1 ) ) $hmbkp_max_filesize = $hmbkp_post_max_size; else $hmbkp_max_filesize = $hmbkp_upload_max_filesize;

?>

<form method="post" name="hmbkp_upload" enctype="multipart/form-data" action="#">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />
	<input type="hidden" name="action" value="hmbkp_edit_schedule_submit" />

	<?php wp_nonce_field( 'hmbkp-edit-schedule', 'hmbkp-edit-schedule-nonce' ); ?>

	<table class="form-table">

		<tbody>

			<tr valign="top">

				<th scope="row">
					<label for="hmbkp_schedule_type"><?php esc_html_e( 'Backup', 'backup-restore-manager' ); ?></label>
				</th>

				<td>
					<input type="file" name="hmbkp_upload_file" accept=".zip"> &nbsp; <input type="submit" name="hmbkp_upload" class="button button-primary" value="<?php esc_attr_e( 'Upload', 'backup-restore-manager' ); ?>"><br><br><?php echo sprintf( esc_html__( 'Max upload size set in php.ini: %s', 'backup-restore-manager' ), $hmbkp_max_filesize ).'<br>'.esc_html__( 'Only zip-files with the original filename. If your archive is renamed, use the filename of a new backup for proper format.', 'backup-restore-manager' ); ?>
				</td>

			</tr>

		</tbody>

	</table>
</form>
<?php
if ( isset( $_GET['hmbkp_schedule_id'] ) ) $hmbkp_schedule_id_str = '&hmbkp_schedule_id=' . ( intval( $_GET['hmbkp_schedule_id'] ) ); else $hmbkp_schedule_id_str = null; ?>
<a href="?page=backup-restore-manager<?php echo $hmbkp_schedule_id_str; ?>"><button class="button button-primary"><?php esc_html_e( 'Done', 'backup-restore-manager' ); ?></button></a>
