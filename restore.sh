#!/bin/bash

echo "### WordPress Restore ###"

# detect wordpress directory
PLUGINDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
WPDIR=$(echo "$PLUGINDIR" | rev | cut -d'/' -f4- | rev)

if [ -d "$WPDIR/wp-content" ]; then
	echo "# Wordpress Directory: $WPDIR"
	echo "# Plugin Directory: $PLUGINDIR"
else
	echo "# Error: Wordpress directory not found. Edit this script if you are using a non-default directory structure."
	exit
fi

# detect backup directory
hits=0
for BACKUPDIRPATH in $WPDIR/wp-content/*; do
	[ -e "$BACKUPDIRPATH" ] || continue
	if [[ $BACKUPDIRPATH == *"backupwordpress"* ]]; then
		((hits++))
		BACKUPDIR="$BACKUPDIRPATH"
		echo "# Backup Directory: $BACKUPDIR"
	fi
done

if (( $hits == 0)); then
	for BACKUPDIRPATH in $WPDIR/wp-content/uploads/*; do
		[ -e "$BACKUPDIRPATH" ] || continue
		if [[ $BACKUPDIRPATH == *"backupwordpress"* ]]; then
			((hits++))
			BACKUPDIR="$BACKUPDIRPATH"
			BACKUPPRE="/uploads"
			echo "# Backup Directory: $BACKUPDIR"
		fi
	done
	if (( $hits == 0)); then
		echo "# Error: Backup directory not found. edit this script if you are using a non-default directory structure."
		exit
	fi
fi

#setting cronmode to yes if run as cron
if [ ! -t 1 ] ; then
	echo "yes" > "$BACKUPDIR/backup-restore-manager-cron"
fi

#detect restore job
if [ -f "$BACKUPDIR/restore.txt" ]; then
	BACKUPFILE=`cat $BACKUPDIR/restore.txt`
	echo "# Backup File: $BACKUPFILE"
	
	#detect backup type
	if [ "${BACKUPFILE/file}" != "$BACKUPFILE" ] ; then
		BACKUPTYPE=3
		echo "# Backup Type: Files only"
	elif [ "${BACKUPFILE/database}" != "$BACKUPFILE" ] ; then
		BACKUPTYPE=2
		echo "# Backup Type: Database only"
	elif [ "${BACKUPFILE/complete}" != "$BACKUPFILE" ] ; then
		BACKUPTYPE=1
		echo "# Backup Type: Complete (Database and Files)"
	else
		echo "# Error: Backuptype not detected. Make sure backup files are not renamed."
		exit
	fi
else
	echo "# No Restore Job found."
	exit
fi

#start restore process
#get pre-backup (currently active) sql credentials and sitename
echo "# reading SQL credentials from current wp-config.php ..."
if [ -r "$WPDIR/wp-config.php" ]; then
	SQLHOSTR=$( awk '/DB_HOST/ {print $3}' $WPDIR/wp-config.php )
	SQLHOST=${SQLHOSTR#"'"}
	SQLHOST=${SQLHOST%"'"}
	echo "# Current SQL Host: $SQLHOST"
	SQLDBR=$( awk '/DB_NAME/ {print $3}' $WPDIR/wp-config.php )
	SQLDB=${SQLDBR#"'"}
	SQLDB=${SQLDB%"'"}
	echo "# Current SQL Database: $SQLDB"
	SQLUSERR=$( awk '/DB_USER/ {print $3}' $WPDIR/wp-config.php )
	SQLUSER=${SQLUSERR#"'"}
	SQLUSER=${SQLUSER%"'"}
	echo "# Current SQL Username: $SQLUSER"
	SQLPASSR=$( awk '/DB_PASSWORD/ {print $3}' $WPDIR/wp-config.php )
	SQLPASS=${SQLPASSR#"'"}
	SQLPASS=${SQLPASS%"'"}
	echo "# Current SQL Password: $SQLPASS"
	
	if (($BACKUPTYPE < 3)); then
		WPSITEURL=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'")
		echo "# Current Site URL: $WPSITEURL"
		WPHOME=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'home'")
		echo "# Current Home: $WPHOME"
	fi
else
	#wp-config.php not readable, ask for credentials if not cronmode
	if [ ! -t 1 ] ; then
		echo "# Could not retrieve SQL credentials from wp-config.php. Exiting."
		exit
	else
		if [ -f "$WPDIR/wp-config.php" ]; then
			echo "#-> wp-config.php not readable. Enter your SQL credentials to restore the database:"
		else
			echo "#-> wp-config.php not found. Enter your SQL credentials to restore the database:"
		fi
		echo -n "Host: "
		read SQLHOST
		echo -n "Database: "
		read SQLDB
		echo -n "Username: "
		read SQLUSER
		echo -n "Password: "
		read SQLPASS
		WPSITEURL=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'")
		if [ -z "$WPSITEURL" ]; then
			echo "# Error: Could not get Website URL from Database, probably incorrect credentials. Try again."
			exit
		else
			echo "# Current Site URL: $WPSITEURL"
			WPHOME=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'home'")
			echo "# Current Home: $WPHOME"
		fi
	fi
fi

#install unzip
if [ ! -n "$(command -v unzip)" ]; then
	if [ -n "$(command -v yum)" ]; then
		PMGR="yum"
	else
		PMGR="apt-get"
	fi
	echo "# installing unzip ..."
	$PMGR -y install unzip
	if [ ! -n "$(command -v unzip)" ]; then
		echo "# Error: could not install unzip. Try again."
		exit
	fi
fi

#delete restore job file
rm -f "$BACKUPDIR/restore.txt"

echo "# restoring Backup $BACKUPFILE ..."
SITENAME=${BACKUPFILE::-44}

#restore database and files
if (($BACKUPTYPE == 1)); then

	#create temp directory for old files
	echo "# moving files to subfolder ..."
	if [ -d $WPDIR/backup-restore-manager-old ]; then
		rm -r -f $WPDIR/backup-restore-manager-old
	fi
	mkdir $WPDIR/backup-restore-manager-old
	shopt -s dotglob
	mv -f $WPDIR/* $WPDIR/backup-restore-manager-old 2> /dev/null

	#extract backupfile
	BACKUPDIROLD="$WPDIR/backup-restore-manager-old/wp-content$BACKUPPRE/$(basename $BACKUPDIR)"
	echo "# extracting $BACKUPFILE to $WPDIR ..."
	sudo unzip -X -o -q "$BACKUPDIROLD/$BACKUPFILE" -d "$WPDIR"
	
	#move backup directory
	mv -f $BACKUPDIROLD $BACKUPDIR
	
	#delete temp directory
	rm -r -f $WPDIR/backup-restore-manager-old

	#get sql credentials from backup
	echo "# reading SQL credentials from backup wp-config.php ..."
	SQLHOSTRBCK=$( awk '/DB_HOST/ {print $3}' $WPDIR/wp-config.php )
	SQLHOSTBCK=${SQLHOSTRBCK#"'"}
	SQLHOSTBCK=${SQLHOSTBCK%"'"}
	SQLDBRBCK=$( awk '/DB_NAME/ {print $3}' $WPDIR/wp-config.php )
	SQLDBBCK=${SQLDBRBCK#"'"}
	SQLDBBCK=${SQLDBBCK%"'"}
	SQLUSERRBCK=$( awk '/DB_USER/ {print $3}' $WPDIR/wp-config.php )
	SQLUSERBCK=${SQLUSERRBCK#"'"}
	SQLUSERBCK=${SQLUSERBCK%"'"}
	SQLPASSRBCK=$( awk '/DB_PASSWORD/ {print $3}' $WPDIR/wp-config.php )
	SQLPASSBCK=${SQLPASSRBCK#"'"}
	SQLPASSBCK=${SQLPASSBCK%"'"}

	if [ "$SQLHOST" == "$SQLHOSTBCK" ] && [ "$SQLDB" == "$SQLDBBCK" ] && [ "$SQLUSER" == "$SQLUSERBCK" ] && [ "$SQLPASS" == "$SQLPASSBCK" ]; then
		echo "# SQL credentials unchanged"
	else
		echo "# SQL credentials changed, write them to wp-config"
		sed -i -e "s/$SQLHOSTBCK/$SQLHOST/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLDBBCK/$SQLDB/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLUSERBCK/$SQLUSER/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLPASSBCK/$SQLPASS/g" $WPDIR/wp-config.php
	fi
	
	#restore database
	for SQLFILENAMEPATH in $WPDIR/database-$SITENAME*.sql; do
		[ -e "$SQLFILENAMEPATH" ] || continue
		SQLFILENAME="${SQLFILENAMEPATH##*/}"
		echo "# importing $WPDIR/$SQLFILENAME ..."
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "DROP Database $SQLDB;"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "CREATE DATABASE $SQLDB;"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" "$SQLDB" < "$SQLFILENAMEPATH"
		echo "# removing $WPDIR/$SQLFILENAME ..."
		rm -f "$WPDIR/$SQLFILENAME"
		break
	done
	
	#get siteurl and home from backup
	echo "# reading Site/Home URL from backup database ..."
	WPSITEURLBCK=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'")
	WPHOMEBCK=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'home'")

	if [ "$WPSITEURL" == "$WPSITEURLBCK" ] && [ "$WPHOME" == "$WPHOMEBCK" ]; then
		echo "# Site/Home URL unchanged"
	else
		if [ "$WPSITEURL" == "$WPHOME" ]; then
			echo "# Site/Home URL changed, replacing all occurences of $WPSITEURLBCK with $WPSITEURL in the database ..."
		else
			echo "# Site/Home URL changed, replacing all occurences of $WPSITEURLBCK with $WPSITEURL and $WPHOMEBCK with $WPHOME in the database ..."
		fi
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "UPDATE $SQLDB.wp_options SET option_value = '$WPSITEURL' WHERE option_name ='siteurl'"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "UPDATE $SQLDB.wp_options SET option_value = '$WPHOME' WHERE option_name ='home'"
		if [ -f "$PLUGINDIR/sar/srdb.cli.php" ]; then
			php $PLUGINDIR/sar/srdb.cli.php -h "$SQLHOST" -u "$SQLUSER" -p "$SQLPASS" -n "$SQLDB" -s "$WPSITEURLBCK" -r "$WPSITEURL" -v false
			php $PLUGINDIR/sar/srdb.cli.php -h "$SQLHOST" -u "$SQLUSER" -p "$SQLPASS" -n "$SQLDB" -s "$WPHOMEBCK" -r "$WPHOME" -v false
		else
			echo "$PLUGINDIR/sar/srdb.cli.php not found (introduced in version 1.0.2). The restore was successful, but there are most likely still occurences of the old URL in the database."
		fi
	fi
	echo "# Database and File Restore Complete!"
	echo "$BACKUPFILE" > "$BACKUPDIR/restore_complete.txt"

#restore database
elif (($BACKUPTYPE == 2)); then

	#extract backupfile
	echo "# extracting $BACKUPFILE to $WPDIR ..."
	sudo unzip -X -o -q "$BACKUPDIR/$BACKUPFILE" -d "$WPDIR"
	
	for SQLFILENAMEPATH in $WPDIR/database-$SITENAME*.sql; do
		[ -e "$SQLFILENAMEPATH" ] || continue
		SQLFILENAME="${SQLFILENAMEPATH##*/}"
		echo "# importing $WPDIR/$SQLFILENAME"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "DROP Database $SQLDB;"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "CREATE DATABASE $SQLDB;"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" "$SQLDB" < "$SQLFILENAMEPATH"
		echo "# removing $WPDIR/$SQLFILENAME"
		rm -f "$WPDIR/$SQLFILENAME"
		break
	done
	
	#get siteurl and home from backup
	echo "# reading Site/Home URL from backup database ..."
	WPSITEURLBCK=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'siteurl'")
	WPHOMEBCK=$(mysql -h$SQLHOST -D$SQLDB -u$SQLUSER -p$SQLPASS -se "SELECT option_value FROM wp_options WHERE option_name = 'home'")

	if [ "$WPSITEURL" == "$WPSITEURLBCK" ] && [ "$WPHOME" == "$WPHOMEBCK" ]; then
		echo "# Site/Home URL unchanged"
	else
		if [ "$WPSITEURL" == "$WPHOME" ]; then
			echo "# Site/Home URL changed, replacing all occurences of $WPSITEURLBCK with $WPSITEURL in the database ..."
		else
			echo "# Site/Home URL changed, replacing all occurences of $WPSITEURLBCK with $WPSITEURL and $WPHOMEBCK with $WPHOME in the database ..."
		fi
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "UPDATE $SQLDB.wp_options SET option_value = '$WPSITEURL' WHERE option_name ='siteurl'"
		mysql -h "$SQLHOST" -u "$SQLUSER" -p"$SQLPASS" -e "UPDATE $SQLDB.wp_options SET option_value = '$WPHOME' WHERE option_name ='home'"
		if [ -f "$PLUGINDIR/sar/srdb.cli.php" ]; then
			php $PLUGINDIR/sar/srdb.cli.php -h "$SQLHOST" -u "$SQLUSER" -p "$SQLPASS" -n "$SQLDB" -s "$WPSITEURLBCK" -r "$WPSITEURL" -v false
			php $PLUGINDIR/sar/srdb.cli.php -h "$SQLHOST" -u "$SQLUSER" -p "$SQLPASS" -n "$SQLDB" -s "$WPHOMEBCK" -r "$WPHOME" -v false
		else
			echo "$PLUGINDIR/sar/srdb.cli.php not found (introduced in version 1.0.2). The restore was successful, but there are most likely still occurences of the old URL in the database."
		fi
	fi
	echo "# Database Restore Complete!"
	echo "$BACKUPFILE" > "$BACKUPDIR/restore_complete.txt"

#restore files
elif (($BACKUPTYPE == 3)); then

	#create temp directory for old files
	echo "# moving files to subfolder ..."
	if [ -d $WPDIR/backup-restore-manager-old ]; then
		rm -r -f $WPDIR/backup-restore-manager-old
	fi
	mkdir $WPDIR/backup-restore-manager-old
	shopt -s dotglob
	mv -f $WPDIR/* $WPDIR/backup-restore-manager-old 2> /dev/null

	#extract backupfile
	BACKUPDIROLD="$WPDIR/backup-restore-manager-old/wp-content$BACKUPPRE/$(basename $BACKUPDIR)"
	echo "# extracting $BACKUPFILE to $WPDIR ..."
	sudo unzip -X -o -q "$BACKUPDIROLD/$BACKUPFILE" -d "$WPDIR"

	#get sql credentials from backup
	echo "# reading SQL credentials from backup wp-config.php ..."
	SQLHOSTRBCK=$( awk '/DB_HOST/ {print $3}' $WPDIR/wp-config.php )
	SQLHOSTBCK=${SQLHOSTRBCK#"'"}
	SQLHOSTBCK=${SQLHOSTBCK%"'"}	
	SQLDBRBCK=$( awk '/DB_NAME/ {print $3}' $WPDIR/wp-config.php )
	SQLDBBCK=${SQLDBRBCK#"'"}
	SQLDBBCK=${SQLDBBCK%"'"}
	SQLUSERRBCK=$( awk '/DB_USER/ {print $3}' $WPDIR/wp-config.php )
	SQLUSERBCK=${SQLUSERRBCK#"'"}
	SQLUSERBCK=${SQLUSERBCK%"'"}
	SQLPASSRBCK=$( awk '/DB_PASSWORD/ {print $3}' $WPDIR/wp-config.php )
	SQLPASSBCK=${SQLPASSRBCK#"'"}
	SQLPASSBCK=${SQLPASSBCK%"'"}

	if [ "$SQLHOST" == "$SQLHOSTBCK" ] && [ "$SQLDB" == "$SQLDBBCK" ] && [ "$SQLUSER" == "$SQLUSERBCK" ] && [ "$SQLPASS" == "$SQLPASSBCK" ]; then
		echo "# SQL credentials unchanged"
	else
		echo "# SQL credentials changed, write them to wp-config ..."
		sed -i -e "s/$SQLHOSTBCK/$SQLHOST/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLDBBCK/$SQLDB/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLUSERBCK/$SQLUSER/g" $WPDIR/wp-config.php
		sed -i -e "s/$SQLPASSBCK/$SQLPASS/g" $WPDIR/wp-config.php
	fi

	#move backup directory
	mv -f $BACKUPDIROLD $BACKUPDIR
	
	#delete temp directory
	rm -r -f $WPDIR/backup-restore-manager-old

	echo "# File Restore Complete!"
	echo "$BACKUPFILE" > "$BACKUPDIR/restore_complete.txt"
fi

