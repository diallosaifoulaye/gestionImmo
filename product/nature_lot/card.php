<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2013		Juanjo Menent			<jmenent@2byte.es>
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
 *       \file       htdocs/product/nature_lot/card.php
 *       \ingroup    product
 *       \brief      Page of product list nature_lot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';



require_once(DOL_DOCUMENT_ROOT .'/product/class/nature_lot.class.php');

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width', 380);
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height', 160);

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'stocks', 'bills', 'other'));

$id		= GETPOST('rowid', 'int');         // For this page, id can also be 'all'


$error	= 0;
$mesg	= '';
$graphfiles=array();
$action = GETPOST('action', 'alpha');
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;

$form = new Form($db);
$htmlother = new FormOther($db);
$object = new Product($db);
    llxHeader("", $langs->trans("nature_lot"));
    $typeprod = GETPOST('typeprod', 'int');
    $helpurl='';
    $title=$langs->trans("Nature_lot");


if ($result && (! empty($id) || ! empty($ref)))
{
    $head=product_prepare_head($object);
    $titre=$langs->trans("CardProduct".$object->type);
    $picto=($object->type==Product::TYPE_SERVICE?'service':'product');
    dol_fiche_end();
}




if (! $id)
{
    dol_fiche_end();
}


function GetnatureParRowid($rowid)
{
    global $db;
    $sql = "SELECT * FROM " .MAIN_DB_PREFIX."nature_lot  WHERE rowid = ".$rowid;
    $resql = $db->query($sql);

    $resql = $resql->fetch_object();
    return $resql;
}


if ($action == 'add')
{

    $object = new Type_lot($db);
    $object->label = GETPOST('label', 'alpha');
    $object->type = GETPOST('typeprod', 'numeric');
    $object->statut = GETPOST('statut', 'numeric');
    $result = $object->create($user);
    if ($result > 0)
    {
        $action = 'list';
    } else setEventMessage($object->error, 'errors');

    header("location:" . $_SERVER['PHP_SELF'] . "?id=all&leftmenu=product&action=list&type=0");

    exit();
}



if ($action == 'update') {
    $rowid		= GETPOST('rowid', 'int');
    $label		= GETPOST('label', 'text');
    $typeprod		= GETPOST('typeprod', 'int');
    //$journal = GetnatureParRowid($rowid);
    $sql = "UPDATE " . MAIN_DB_PREFIX . "nature_lot SET label = '".$label."',  type = '".$typeprod."' WHERE rowid = '$rowid'";
    //var_dump($sql);die();
    $resql = $db->query($sql);
    header("location:" . $_SERVER['PHP_SELF'] . "?leftmenu=product&type=0");
    exit();
}

if ($action == 'delete') {
    $ligne = GetnatureParRowid($id);

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "nature_lot  WHERE rowid = '$id'";
        $resql = $db->query($sql);

    header("location:" . $_SERVER['PHP_SELF'] . "?leftmenu=product&type=0");
    exit();
}

if ($action == 'updatestatut') {
    $ligne = GetnatureParRowid($id);
    if ($ligne->statut==0){
        $sql = "UPDATE " . MAIN_DB_PREFIX . "nature_lot SET statut = 1 WHERE rowid = '$id'";
        $resql = $db->query($sql);
    }else{
        $sql = "UPDATE " . MAIN_DB_PREFIX . "nature_lot SET statut = 0 WHERE rowid = '$id'";
        $resql = $db->query($sql);

    }
    header("location:" . $_SERVER['PHP_SELF'] . "?leftmenu=product&type=0");
    exit();
}







// gestion de la vue


// Example 3 : List of data
if ($action == 'list' || $action == '') {
    print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
    print '<tr><td colspan="4" align="right"><a class="butAction" href="card.php?id=all&leftmenu=product&action=addNature&type=0" title="Ajouter ">Ajouter </a></td></tr>';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Label'), $_SERVER['PHP_SELF'], 't.label', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('type'), $_SERVER['PHP_SELF'], 't.type', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('statut '), $_SERVER['PHP_SELF'], 't.statut', '', $param, '', '', '');

    print_liste_field_titre($langs->trans(''), $_SERVER['PHP_SELF'], '', '', $param, '', '', '');
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "nature_lot";
    dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
                $var = !$var;
                if ($obj) {
                    // You can use here results
                    print "<tr " . $bc[$var] . ">";
                    print '<td>';
                    print   $obj->label;
                    if($obj->type==0){
                        print '<td>';
                        print "principal";
                        print '</td>';
                    }else{
                        print '<td>';
                        print "secondaire";
                        print '</td>';
                    }


                    if($obj->statut==1){
                        print '<td>'; ?>
                        <a  href="<?= $_SERVER["PHP_SELF"] ?>?action=updatestatut&rowid=<?= $obj->rowid ?>" title="Désactiver"><i class="fa fa-toggle-on btn-danger"></i></a>
                        <?php
                        print '</td>';
                    }  else{
                        print '<td>'; ?>
                        <a  href="<?= $_SERVER["PHP_SELF"] ?>?action=updatestatut&rowid=<?= $obj->rowid ?>" title="Activer"><i class="fa fa-toggle-off btn-danger"></i></a>
                        <?php
                        print '</td>';
                    }



				print '<td align="right">';
                    print '<a href="card.php?leftmenu=product&action=updateNature&rowid='.$obj->rowid.'" title="Modifier"><i class="fas fa-edit" style="margin-right:8px;"></i></a>';
                    ?>





                    <a  href="<?= $_SERVER["PHP_SELF"] ?>?action=delete&rowid=<?= $obj->rowid ?>" onclick="return confirm('Etes vous sur de vouloir supprimer ce nature lot ?');" title="Supprimer"><i class="fas fa-trash"></i></a>
                    <?php
                    print '</td></tr>';
                }
                $i++;
            }
        }
    } else {
        $error++;
        dol_print_error($db);
    }

   // print '</table>' . "\n";
    print'</td>';
    print '</tr>';
    print '</table>';
}
elseif ($action == 'addNature' || $action == 'updateNature') {

    if($id !== ''){
        $ligne = GetnatureParRowid($id);


        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        print "<tr>";
        print'<td>';
        print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="rowid" value="'.$ligne->rowid.'">';
        print '<fieldset width="100%"> ';
        print '<legend style="font-weight:bold">Modification d\'une nature de lot</legend>';
        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        //if(!is_null($journal[0]))
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('label : ') . '</td><td class="fieldrequired" align="left"><input class="flat" type = "text" size="40" name="label" required="required" value="'. $ligne->label.'"></td></tr>';
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('type : ') . '</td><td class="fieldrequired" align="left">';
        if($ligne->type== 0){
            print '<input type="radio" name="type" value="0" required="required" checked> Principal';
            print '<input type="radio" name="type" value="1" required="required"> Secondaire';
        }

        if($ligne->type== 1) {
            print '<input type="radio" name="type" value="0" required="required"> Principal';
            print '<input type="radio" name="type" value="1" required="required" checked> Secondaire';
        }
               
        print '</td></tr>';
        print '<tr><td colspan="2" align="center" valign="middle"><input type="submit" class="button" name="bouton" value="' . $langs->trans('Valider') . '"></td></tr>';
        print '</table>';
        print '</fieldset>';
        print "</form>\n";
        print'</td>';
        print '</tr>';
        print '</table>';
    }
    else{
        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        print "<tr>";
        print'<td>';
        print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="add">';
        print '<fieldset width="100%"> ';
        print '<legend style="font-weight:bold">Nouvelle nature de lot</legend>';
        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('label : ') . '</td><td class="fieldrequired" align="left"><input class="flat" type = "text" size="40" name="label" required="required" ></td></tr>';
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('type : ') . '</td><td class="fieldrequired" align="left">
           <input type="radio" name="type" value="0" required="required"> Principal
            <input type="radio" name="type" value="1" required="required"> Secoddaire
               
            </td></tr>';
        print '<tr><td colspan="2" align="center" valign="middle"><input type="submit" class="button" name="bouton" value="' . $langs->trans('Valider') . '"></td></tr>';
        print '</table>';
        print '</fieldset>';
        print "</form>\n";
        print'</td>';
        print '</tr>';
        print '</table>';
    }



}


// End of page
llxFooter();
$db->close();
