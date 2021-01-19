<?php
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include '../main.inc.php';                    // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res) die("Include of main fails");
if (!$res)
    global $db;
$code = strtoupper($_GET['code']);
$sql2 = "SELECT code from llx_type_journal  WHERE UPPER(code)='".strtoupper($code)."'";

$resql2 = $db->query($sql2);
$obj = $db->fetch_object($resql2);
$nb = $db->num_rows($resql2);
//echo $sql2;
echo ($nb > 0) ? "invalide" : "valide" ;