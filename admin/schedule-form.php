<?php

namespace HM\BackUpWordPress;

?>

<h3><?php esc_html_e( 'Settings', 'backup-restore-manager' ); ?></h3>

<?php $hmbkp_form_errors = get_settings_errors(); ?>

<?php if ( ! empty( $hmbkp_form_errors ) ) { ?>

	<div id="hmbkp-warning" class="error settings-error">

		<?php foreach ( $hmbkp_form_errors as $error ) { ?>
			<p><strong><?php echo wp_kses_data( $error ); ?></strong></p>
		<?php } ?>

	</div>

<?php }

// We can clear them now we've displayed them
clear_settings_errors();

?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">

	<input type="hidden" name="hmbkp_schedule_id" value="<?php echo esc_attr( $schedule->get_id() ); ?>" />
	<input type="hidden" name="action" value="hmbkp_edit_schedule_submit" />

	<?php wp_nonce_field( 'hmbkp-edit-schedule', 'hmbkp-edit-schedule-nonce' ); ?>

	<table class="form-table">

		<tbody>

			<tr valign="top">

				<th scope="row">
					<label for="hmbkp_schedule_type"><?php _e( 'Backup', 'backup-restore-manager' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_type" id="hmbkp_schedule_type">

						<option<?php selected( $schedule->get_type(), 'complete' ); ?> value="complete"><?php _e( 'Both Database &amp; files', 'backup-restore-manager' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'file' ); ?> value="file"><?php _e( 'Files only', 'backup-restore-manager' ); ?></option>

						<option<?php selected( $schedule->get_type(), 'database' ); ?> value="database"><?php _e( 'Database only', 'backup-restore-manager' ); ?></option>

					</select>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_recurrence_type"><?php _e( 'Schedule', 'backup-restore-manager' ); ?></label>
				</th>

				<td>

					<select name="hmbkp_schedule_recurrence[hmbkp_type]" id="hmbkp_schedule_recurrence_type">

						<option value="manually"><?php _e( 'Manual Only', 'backup-restore-manager' ); ?></option>

						<?php foreach ( get_cron_schedules() as $cron_schedule => $cron_details ) : ?>

								<option <?php selected( $schedule->get_reoccurrence(), $cron_schedule ); ?> value="<?php echo esc_attr( $cron_schedule ); ?>">

									<?php esc_html_e( $cron_details['display'], 'backup-restore-manager' ); ?>

								</option>

						<?php endforeach; ?>

					</select>

				</td>

			</tr>

			<?php if ( ! $start_time = $schedule->get_schedule_start_time( false ) ) :
				$start_time = time();
			endif; ?>

			<?php $start_date_array = date_parse( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $start_time ) ); ?>

			<tr id="start-day" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_week"><?php _e( 'Start Day', 'backup-restore-manager' ); ?></label>
				</th>

				<td>

					<select id="hmbkp_schedule_start_day_of_week" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_week]">

						<?php $weekdays = array(
							'monday'    => __( 'Monday',    'backup-restore-manager' ),
							'tuesday'   => __( 'Tuesday',   'backup-restore-manager' ),
							'wednesday' => __( 'Wednesday', 'backup-restore-manager' ),
							'thursday'  => __( 'Thursday',  'backup-restore-manager' ),
							'friday'    => __( 'Friday',    'backup-restore-manager' ),
							'saturday'  => __( 'Saturday',  'backup-restore-manager' ),
							'sunday'    => __( 'Sunday',    'backup-restore-manager' ),
						);

						foreach ( $weekdays as $key => $day ) : ?>

							<option value="<?php echo esc_attr( $key ) ?>" <?php selected( strtolower( date_i18n( 'l', $start_time ) ), $key ); ?>><?php echo esc_html( $day ); ?></option>

						<?php endforeach; ?>

					</select>

				</td>

			</tr>

			<tr id="start-date" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_day_of_month"><?php _e( 'Start Day of Month', 'backup-restore-manager' ); ?></label>
				</th>

				<td>
					<input type="number" min="0" max="31" step="1" id="hmbkp_schedule_start_day_of_month" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_day_of_month]" value="<?php echo esc_attr( $start_date_array['day'] ); ?>">
				</td>

			</tr>

			<tr id="schedule-start" class="recurring-setting">

				<th scope="row">
					<label for="hmbkp_schedule_start_hours"><?php _e( 'Start Time', 'backup-restore-manager' ); ?></label>
				</th>

				<td>

					<span class="field-group">

						<label for="hmbkp_schedule_start_hours"><input type="number" min="0" max="23" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_hours]" id="hmbkp_schedule_start_hours" value="<?php echo esc_attr( $start_date_array['hour'] ); ?>">

						<?php _e( 'Hours', 'backup-restore-manager' ); ?></label>

						<label for="hmbkp_schedule_start_minutes"><input type="number" min="0" max="59" step="1" name="hmbkp_schedule_recurrence[hmbkp_schedule_start_minutes]" id="hmbkp_schedule_start_minutes" value="<?php echo esc_attr( $start_date_array['minute'] ); ?>">

						<?php _e( 'Minutes', 'backup-restore-manager' ); ?></label>

					</span>

					<p class="description">
						<?php esc_html_e( '24-hour format.', 'backup-restore-manager' ); ?>
						<span class="twice-js <?php if ( $schedule->get_reoccurrence() !== 'fortnightly' ) { ?> hidden<?php } ?>"><?php _e( 'The second backup will run 12 hours after the first.', 'backup-restore-manager' ); ?><span>
					</p>

				</td>

			</tr>

			<tr>

				<th scope="row">
					<label for="hmbkp_schedule_max_backups"><?php _e( 'Number of backups to store on this server', 'backup-restore-manager' ); ?></label>
				</th>

				<td>

					<input type="number" id="hmbkp_schedule_max_backups" name="hmbkp_schedule_max_backups" min="1" step="1" value="<?php echo esc_attr( $schedule->get_max_backups() ); ?>" />

					<p class="description">

						<?php printf( __( 'Past this limit older backups will be deleted automatically.', 'backup-restore-manager' ) ); ?>

						<?php

						$site_size = new Site_Size( $schedule->get_type(), $schedule->get_excludes() );

						if ( $site_size->is_site_size_cached() ) :
							printf( __( 'This schedule will store a maximum of %s of backups.', 'backup-restore-manager' ), '<code>' . esc_html( size_format( $site_size->get_site_size() * $schedule->get_max_backups() ) ) . '</code>' );
						endif; ?>

					</p>

				</td>

			</tr>

			<?php foreach ( Services::get_services( $schedule ) as $service ) :
				$service->field();
			endforeach; ?>

		</tbody>

	</table>

	<?php submit_button( __( 'Done', 'backup-restore-manager' ) ); ?>

</form>
