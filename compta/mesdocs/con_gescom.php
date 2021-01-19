<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_con_gescom = "fhbs.myd.infomaniak.com";
$database_con_gescom = "fhbs_numheritlabscom21";
$username_con_gescom = "fhbs_sablux";
$password_con_gescom = "KQG9kdfHSZGf@";
$con_gescom = @mysql_pconnect($hostname_con_gescom, $username_con_gescom, $password_con_gescom) or trigger_error(mysql_error(),E_USER_ERROR); 
?>