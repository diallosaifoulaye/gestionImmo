<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       dev/Quitances/Quitance_page.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *		\brief      This file is an example of a php page
 *					Initialy built by build_class_from_table on 2013-11-01 12:06
 */

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
//if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';
//if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';
//if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
//if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
//if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
// Change this following line to use the correct relative path from htdocs
dol_include_once('/module/class/quitance.class.php');

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->Load("gestimmo");

// Get parameters
$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$myparam	= GETPOST('myparam','alpha');

// Protection if external user
if ($user->societe_id > 0)
{
	//accessforbidden();
}



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if ($action == 'add')
{
	$object=new Quitance($db);
	$object->prop1=$_POST["field1"];
	$object->prop2=$_POST["field2"];
	$result=$object->create($user);
	if ($result > 0)
	{
		// Creation OK
	}
	{
		// Creation KO
		$mesg=$object->error;
	}
}





/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/

//llxHeader('','gestimmo','');
$texte = $langs->trans("Liste des biens ");

    llxHeader("","",$texte);
$form=new Form($db);


// Put here content of your page

// Example 1 : Adding jquery code
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	function init_myfunc()
	{
		jQuery("#myid").removeAttr(\'disabled\');
		jQuery("#myid").attr(\'disabled\',\'disabled\');
	}
	init_myfunc();
	jQuery("#mybutton").click(function() {
		init_needroot();
	});
});
</script>';


// Example 2 : Adding links to objects
// The class must extends CommonObject class to have this method available
//$somethingshown=$object->showLinkedObjectBlock();


// Example 3 : List of data
//if ($action == 'list')
{
    $sql = "SELECT";
    $sql.= " t.rowid,";
    
		$sql.= " t.date,";
		$sql.= " t.date_de,";
		$sql.= " t.date_fin,";
		$sql.= " t.fk_loc,";
		$sql.= " t.fk_bails,";
		$sql.= " t.aquiter,";
		$sql.= " t.total_ht,";
		$sql.= " t.fk_payement";

    
    $sql.= " FROM ".MAIN_DB_PREFIX."quitance as t";
  //  $sql.= " WHERE field3 = 'xxx'";
  //  $sql.= " ORDER BY field1 ASC";

    print '<table class="noborder">'."\n";
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Locataire'),$_SERVER['PHP_SELF'],'t.fk_loc','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Biens'),$_SERVER['PHP_SELF'],'t.fk_bails','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Date'),$_SERVER['PHP_SELF'],'t.date_de','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Payer'),$_SERVER['PHP_SELF'],'t.aquiter','',$param,'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Montant'),$_SERVER['PHP_SELF'],'t.total_ht','',$param,'',$sortfield,$sortorder);
     print_liste_field_titre($langs->trans('Saisie'),$_SERVER['PHP_SELF'],'saisie','',$param,'',$sortfield,$sortorder);
    print '</tr>';

    dol_syslog($script_file." sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num)
        {
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                if ($obj)
                {
                    // You can use here results
                    print '<tr><td>';
                    print $obj->fk_loc;
                    print '</td><td>';
                    print $obj->fk_bails;
                    print '</td><td>';
                    print $obj->date_de;
                    print '</td><td>';
                    print $obj->aquiter;
                    print '</td><td>';
                    print $obj->total_ht;
                //    print '</td></tr>';
                    print '<td>';
                            $namef = 'amount_'.$objp->facid;
                            print '<input type="text" size="8" name="'.$namef.'" value="'.GETPOST($namef).'">';
                            print "</td></tr>\n";
                    
                }
                $i++;
            }
        }
    }
    else
    {
        $error++;
        dol_print_error($db);
    }

    print '</table>'."\n";
}



// End of page
llxFooter();
$db->close();
?>
