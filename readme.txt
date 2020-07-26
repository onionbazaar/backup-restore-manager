=== Backup & Restore Manager ===
Contributors: OnionBazaar
Donate link: https://onionbazaar.org/?p=donation
Tags: backup, restore, recover, backupwordpress, backups, clone, database, zip, db, migrate, file backup, archive
Requires at least: 3.9
Tested up to: 5.4.1
Stable tag: 1.0.2
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Simple automated Backup and Restore of your WordPress Website.

== Description ==

Backup & Restore Manager will back up your entire WordPress website including your database and all your files, manually or on a custom schedule.

Backup & Restore Manager is a fork of the popular backup software [BackUpWordPress](https://wordpress.org/plugins/backupwordpress) by HumanMade, which seems to be discontinued unfortunately. We continue maintenance and have extended the plugin with the ability to upload backup files and an automated restore feature.

If you are already using BackUpWordPress, switching to Backup & Restore Manager will retain all your settings, schedules and backups (and vice versa).

For support, head over to the [WordPress Support Forum](https://wordpress.org/support/plugin/backup-restore-manager) or [https://onionbazaar.org/?p=help](https://onionbazaar.org/?p=help) for direct support.

This plugin requires PHP version 5.3.2 or later

= Features =

* Simple to use, no setup required
* Works in low memory, "shared host" environments
* Manage multiple schedules
* Option to have each backup file emailed to you
* Uses `zip` and `mysqldump` for faster backups if they are available
* Works on Linux & Windows Server
* Exclude files and folders from your backups
* Simple upload and restore of your backups

== Installation ==

1. Upload the entire `/backup-restore-manager` directory to the `/wp-content/plugins/` directory.
2. Activate Backup & Restore Manager through the 'Plugins' menu in WordPress.
3. Open `Tools` -> `Backups` to setup the plugin and mange your backups. For multisite it's `Settings` -> `Backups`

The plugin will try to use the `mysqldump` and `zip` commands via shell if they are available, using these will greatly improve the time it takes to back up your site.

Protip: To enable fully automated restores, edit the file `backup-restore-manager-cron` in your backup directory from `no` to `yes` and set up a cronjob to call the script `restore.sh` (located in the plugin directory) every minute (as root), e.g:
sudo crontab -e
* * * * * bash /var/www/html/wp-content/plugins/backup-restore-manager/restore.sh

== Frequently Asked Questions ==

**Where does Backup & Restore Manager store the backup files?**

Backups are stored on your server in `/wp-content`, you can change the directory.

Important: By default Backup & Restore Manager backs up everything in your site root as well as your database, this includes any non WordPress folders that happen to be in your site root. This does mean that your backup directory can get quite large.

**How do I restore my site from a backup?**

Click the Restore link next to the backup. Linux servers can restore automatically via script, on Windows and shared environments you need to click Manual Restore and follow the instructions.

**Does Backup & Restore Manager back up the backups directory?**

No.

**I'm not receiving my backups by email**

Most servers have a filesize limit on email attachments, it's generally about 10mb. If your backup file is over that limit, it won't be sent attached to the email. Instead, you should receive an email with a link to download the backup. If you aren't even receiving that, then you likely have a mail issue on your server that you'll need to contact your host about.

**How many backups are stored by default?**

Backup & Restore Manager stores the last 10 backups by default.

**How long should a backup take?**

Unless your site is very large (many gigabytes) it should only take a few minutes to perform a backup. If your back up has been running for longer than an hour, it's safe to assume that something has gone wrong. Try de-activating and re-activating the plugin. If it keeps happening, contact support.

**What do I do if I get the wp-cron error message?**

The issue is that your `wp-cron.php` is not returning a `200` response when hit with a HTTP request originating from your own server, it could be several things. In most cases, it's an issue with the server / site.

There are some things you can test to confirm this is the issue.

	 * Are scheduled posts working? (They use wp-cron as well.)

	 * If you click manual backup, does it work?

	 * Try adding `define( 'ALTERNATE_WP_CRON', true );` to your `wp-config.php`. Do automatic backups work?

	 * Is your site private (i.e. is it behind some kind of authentication, maintenance plugin, .htaccess)? If so, wp-cron won't work until you remove it. If you are and you temporarily remove the authentication, do backups start working?

Report the results to our support team for further help.

**My backups seem to be failing?**

If your backups are failing, it's commonly caused by a lack of available resources on your server. To establish this is the case, exclude the complete (or parts of the) uploads folder and run a backup. If that succeeds, you know it's probably a server issue. If it does not succeed, report the results to our support team for further help.

== Screenshots ==

1. Manage multiple schedules
2. Choose your schedule, backup type, number of backups to keep and whether to receive a notification email
3. Easily manage exclude rules and see exactly which files are included and excluded from your backup
4. Automatic backup restore
5. Manual backup restore


== Changelog ==

= 1.0.2 - 2020-07-26 =
* Improved restore script, Bugfixes

= 1.0.1 - 2020-05-23 =
* Minor changes restore script cronmode

= 1.0.0 - 2020-04-28 =
* Fork of BackUpWordPress 3.6.4. Added backup upload and restore functionality. The management and development of the plugin is done by [OnionBazaar](https://onionbazaar.org)
