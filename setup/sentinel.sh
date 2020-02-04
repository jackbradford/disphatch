#!/bin/bash

printf "Thank you for using Disphatch!\n\n";
printf "Running this script will add Sentinel's tables to your database.\n";
printf "Sentinel is an authorization/authentication package in PHP by Cartalyst.\n\n"
printf "The following tables will be added:\n"
printf "\tactivations\n\tpersistences\n\treminders\n\trole_users\n\troles\n\tthrottle\n\tusers\n\n"
printf "Enter a MySQL username. This user should have the CREATE permission on your database.\nUsername: ";
read un;

printf "Enter the user's  password.\nPassword: ";
read pw;

printf "Enter the name of the database.\nDatabase: ";
read db;

curl -L --silent "https://raw.githubusercontent.com/cartalyst/sentinel/3.0/schema/mysql-5.6%2B.sql" > sentinel.sql;
mysql -u $un -p$pw $db < sentinel.sql;
rm sentinel.sql;
printf "Done.\n"

