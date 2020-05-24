<?php

namespace HM\BackUpWordPress;

?>

<table class="widefat">

	<thead>

		<tr>

			<th scope="col"><?php backups_number( $schedule ); ?></th>
			<th scope="col"><?php _e( 'Size', 'backup-restore-manager' ); ?></th>
			<th scope="col"><?php _e( 'Type', 'backup-restore-manager' ); ?></th>
			<th scope="col"><?php _e( 'Actions', 'backup-restore-manager' ); 
			
			/* OBZMOD */
			echo ' &nbsp;' . esc_html__( '(do not rename downloads)', 'backup-restore-manager' );
			/* OBZMOD */
			
			
			?></th>

		</tr>

	</thead>

	<tbody>

		<?php if ( $schedule->get_backups() ) :

			$schedule->delete_old_backups();

			foreach ( $schedule->get_backups() as $file ) :

				if ( ! file_exists( $file ) ) {
					continue;
				}

				get_backup_row( $file, $schedule );

			endforeach;

		else : ?>

			<tr>
				<td class="hmbkp-no-backups" colspan="4"><?php _e( 'This is where your backups will appear once you have some.', 'backup-restore-manager' ); ?></td>
			</tr>

		<?php endif; ?>

	</tbody>

</table>
