<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_con_gescom = "fhbs.myd.infomaniak.com";
$database_con_gescom = "fhbs_app_dolibarr";
$username_con_gescom = "fhbs_app_dolibar";
$password_con_gescom = "goG23bkMyj";

/*$dolibarr_main_db_host='fhbs.myd.infomaniak.com';
$dolibarr_main_db_port="";
$dolibarr_main_db_name='fhbs_app_dolibarr';
$dolibarr_main_db_prefix='fdx6_';
$dolibarr_main_db_user='fhbs_app_dolibar';
$dolibarr_main_db_pass='goG23bkMyj';
$dolibarr_main_db_type='mysqli';*/


$con_gescom = mysqli_connect($hostname_con_gescom, $username_con_gescom, $password_con_gescom) or trigger_error(mysql_error(),E_USER_ERROR);
?>