<?php
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_connexion = "fhbs.myd.infomaniak.com";
$database_connexion = "fhbs_numheritlabscom212";
$username_connexion = "fhbs_sablux";
$password_connexion = "KQG9kdfHSZGf@";
$connexion = @mysql_pconnect($hostname_connexion, $username_connexion, $password_connexion) or trigger_error(mysql_error(),E_USER_ERROR);

?>