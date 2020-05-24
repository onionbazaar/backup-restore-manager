<?php
echo '<p><strong>' . __( 'Where does Backup & Restore Manager store the backup files?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'Backups are stored on your server in <code>/wp-content</code>, you can change the directory.', 'backup-restore-manager' ). '</p>' .

	 '<p>' . __( 'Important: By default Backup & Restore Manager backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does mean that your backup directory can get quite large.', 'backup-restore-manager' ) . '</p>';

	 /* OBZMOD
	 '<p><strong>' . __( 'What if I want to back up my site to another destination?', 'backupwordpress' ) . '</strong></p>' .

	 '<p>' . __( 'BackUpWordPress Pro supports Dropbox, Google Drive, Amazon S3, Rackspace, Azure, DreamObjects and FTP/SFTP. Check it out here: <a href="http://bwp.hmn.md/?utm_source=wordpress-org&utm_medium=plugin-page&utm_campaign=freeplugin" title="BackUpWordPress Homepage" target="_blank">https://bwp.hmn.md</a>', 'backupwordpress' ) . '</p>' .

	 '<p><strong>' . __( 'How do I restore my site from a backup?', 'backupwordpress' ) . '</strong></p>' .

	 '<p>' . __( 'You need to download the latest backup file either by clicking download on the backups page or via <code>FTP</code>. <code>Unzip</code> the files and upload all the files to your server overwriting your site. You can then import the database using your hosts database management tool (likely <code>phpMyAdmin</code>).', 'backupwordpress' ) . '</p>' .
	
	 '<p>' . __( 'See this guide for more details - <a href="https://bwp.hmn.md/support-center/restore-backup/" title="Go to support center" target="_blank">How to restore from backup</a>.', 'backupwordpress' ) . '</p>' .
	 */
	 
echo '<p><strong>' . __( 'How do I restore my site from a backup?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'Click the Restore link next to the backup. Linux servers can restore automatically via script, on Windows and shared environments you need to click Manual Restore and follow the instructions.', 'backup-restore-manager' ). '</p>';

	 /* OBZMOD */
	 
echo '<p><strong>' . __( 'Does Backup & Restore Manager back up the backups directory?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'No.', 'backup-restore-manager' ) . '</p>' .

	 '<p><strong>' . __( 'I\'m not receiving my backups by email', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'Most servers have a filesize limit on email attachments, it\'s generally about 10mb. If your backup file is over that limit, it won\'t be sent attached to the email. Instead, you should receive an email with a link to download the backup. If you aren\'t even receiving that, then you likely have a mail issue on your server that you\'ll need to contact your host about.', 'backup-restore-manager' ) . '</p>' .

	 '<p><strong>' . __( 'How many backups are stored by default?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'Backup & Restore Manager stores the last 10 backups by default.', 'backup-restore-manager' ) . '</p>' .

	 '<p><strong>' . __( 'How long should a backup take?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'Unless your site is very large (many gigabytes) it should only take a few minutes to perform a backup. If your back up has been running for longer than an hour, it\'s safe to assume that something has gone wrong. Try de-activating and re-activating the plugin. If it keeps happening, contact support.', 'backup-restore-manager' ) . '</p>' .

	 '<p><strong>' . __( 'What do I do if I get the wp-cron error message?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'The issue is that your <code>wp-cron.php</code> is not returning a <code>200</code> response when hit with a HTTP request originating from your own server, it could be several things. In most cases, it\'s an issue with the server / site.', 'backup-restore-manager' ) . '</p>' .

	 '<p>' . __( 'There are some things you can test to confirm this is the issue.', 'backup-restore-manager' ) . '</p>' .

	 '<ul><li>' . __( 'Are scheduled posts working? (They use wp-cron as well.)', 'backup-restore-manager' ) . '</li>' .

	 '<li>' . __( 'If you click manual backup, does it work?', 'backup-restore-manager' ) . '</li>' .

	 '<li>' . __( 'Try adding <code>define( \'ALTERNATE_WP_CRON\', true );</code> to your <code>wp-config.php</code>. Do automatic backups work?', 'backup-restore-manager' ) . '</li>' .

	 '<li>' . __( 'Is your site private (i.e. is it behind some kind of authentication, maintenance plugin, .htaccess)? If so, wp-cron won\'t work until you remove it. If you are and you temporarily remove the authentication, do backups start working?', 'backup-restore-manager' ) . '</li></ul>';

	 /* OBZMOD
	 '<p>' . __( 'Report the results to our support team for further help. To do this, either enable support from your Admin Dashboard (recommended), or email backupwordpress@hmn.md', 'backupwordpress' ) . '</p>';
	 '<p><strong>' . __( 'How to get BackUpWordPress working in Heart Internet', 'backupwordpress' ) . '</strong></p>' .
	 '<p>' . __( 'The script to be entered into the Heart Internet cPanel is: <code>/usr/bin/php5 /home/sites/yourdomain.com/public_html/wp-cron.php</code> (note the space between php5 and the location of the file). The file <code>wp-cron.php</code> <code>chmod</code> must be set to <code>711</code>.', 'backupwordpress' ) . '</p>' .

	 '<p><strong>' . __( 'My backups seem to be failing?', 'backupwordpress' ) . '</strong></p>' .
	 '<p>' . __( 'If your backups are failing, it\'s commonly caused by a lack of available resources on your server. To establish this is the case, exclude the complete (or parts of the) uploads folder and run a backup. If that succeeds, you know it\'s probably a server issue. If it does not succeed, report the results to our support team for further help. You can contact support by enabling support from your Admin Dashboard (recommended), or emailing backupwordpress@hmn.md', 'backupwordpress' ) . '</p>';
	 */

echo '<p>' . __( 'Report the results to our support team for further help.', 'backup-restore-manager' ) . '</p>' .

	 '<p><strong>' . __( 'My backups seem to be failing?', 'backup-restore-manager' ) . '</strong></p>' .

	 '<p>' . __( 'If your backups are failing, it\'s commonly caused by a lack of available resources on your server. To establish this is the case, exclude the complete (or parts of the) uploads folder and run a backup. If that succeeds, you know it\'s probably a server issue. If it does not succeed, report the results to our support team for further help.', 'backup-restore-manager' ) . '</p>';
	 /* OBZMOD */


