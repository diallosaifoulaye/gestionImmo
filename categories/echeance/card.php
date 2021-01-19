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
 *       \file       htdocs/categories/echeance/card.php
 *       \ingroup    product
 *       \brief      Page of categories list echéances
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';



require_once(DOL_DOCUMENT_ROOT .'/categories/class/echeance.class.php');

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width', 380);
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height', 160);

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'stocks', 'bills', 'other'));

$id		= GETPOST('rowid', 'int');         // For this page, id can also be 'all'


$error	= 0;
$mesg	= '';
$graphfiles=array();
$action = GETPOST('action', 'alpha');
$idPropal = GETPOST('originid', 'int');
$idlot = GETPOST('idlot', 'int');
$originid = GETPOST('originid', 'int');
$socid = GETPOST('socid', 'int');
//recupere l'id de propal
$sql = "SELECT ref";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
$sql .= " WHERE p.rowid = ".(int) $idPropal;
$resql = $db->query($sql);

//recupere date actuelle
$date = date("Y-m-d");

$etape = GETPOST('etape', 'alpha');
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;

if ($action == 'add')
{ //var_dump($_POST);die();
    //recupere l'id de propal
    $sql = "SELECT *";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
    $sql .= " WHERE p.rowid = ".(int) $idPropal;
    $resql = $db->query($sql);
    $obj= $db->fetch_object($resql);

    $sqlm = "SELECT SUM(montant) as somech";
    $sqlm .= " FROM ".MAIN_DB_PREFIX."echeance as e";
    $sqlm .= " WHERE e.fk_propal = ".(int) $idPropal;
    //var_dump($sqlm);die();
    $resqlm = $db->query($sqlm);
    $objm= $db->fetch_object($resqlm);

    while ($objm->somech+GETPOST('montant', 'text')<=$obj->total){

        $object = new echeance($db);
        $object->etape = GETPOST('etape', 'alpha');
        $date_debut = dol_mktime(12, 0, 0, GETPOST('date_debmonth'), GETPOST('date_debday'), GETPOST('date_debyear'));
        $date_fin = dol_mktime(12, 0, 0, GETPOST('date_finmonth'), GETPOST('date_finday'), GETPOST('date_finyear'));
        //$object->date_deb = GETPOST('date_deb', 'text');
        $object->date_deb =  date("Y-m-d", $date_debut);
        //$object->date_fin = GETPOST('date_fin', 'date');
        $object->date_fin = date("Y-m-d", $date_fin);
        $object->montant = GETPOST('montant', 'text');
        //$object->date_deb_reelle = GETPOST('date_deb_reelle', 'date');
        // $object->date_fin_reelle = GETPOST('date_fin_reelle', 'date');
        $object->description = GETPOST('description', 'alpha');
        //$object->fk_categorie = GETPOST('fk_categorie', 'numeric');
        $object->fk_propal = GETPOST('fk_propal', 'int');
        $object->statut = GETPOST('statut', 'numeric');
        // var_dump($result);die;
        if (!($date_debut< $date_fin)){
            setEventMessage("La date de début doit être inférieure à la date de fin", 'errors');
            header("location: /categories/echeance/card.php?action=addEcheance&origin=propal&originid=$originid&socid=$socid&etape=$etape");
            die;
        }
        $result = $object->create($user);

        if ($result > 0)
        {

            header("location: /comm/propal/card.php?id=$originid");
            die;
        } else{
            setEventMessage($object->error, 'errors');
        }
//rowid de la nouvelle échéance
        $rowid=$object->getrowid();


        $fact = new facture($db);

        $sql= "SELECT p.*,";
        $sql.="s.nom,";
        $sql.="e.montant";
        $sql.=" FROM " .MAIN_DB_PREFIX."propal as p";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=p.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."echeance as e ON e.fk_propal=p.rowid";
        $sql.=" WHERE p.rowid = ".$originid;
        $result = $db->query($sql);
        $objet = $db->fetch_object($result);
        /* echo '<pre>';
         var_dump($objet);die();*/

        $socid				= $objet->fk_soc;
        $type				= 3;
        $ref			= "ACC_";
        $date				= date("Y-m-d");
        $ref_client			=  $objet->ref_client;
        $modelpdf			= "crabe";
        $cond_reglement_id	= 1;
        $mode_reglement_id	= 4;
        $fk_account         = -1;
        $amount				= $objet->montant;
        $entity				= $objet->entity;
        $statut= 0;
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "facture(";

        $sql.= " ref";
        $sql.= ", entity";
        $sql.= ", type";
        $sql.= ", fk_soc";
        $sql.= ", datec";
        $sql.= ", datef";
        $sql.= ", ref_client";
        $sql.= ", fk_account";
        $sql.= ", fk_cond_reglement, fk_mode_reglement, model_pdf";
        $sql.= ", total";
        $sql.= ", total_ttc";
        $sql.= ", fk_statut";
        $sql.= ")";
        $sql .= "VALUES (";
        $sql .= "" . (!isset($ref) ? 'NULL' : "'" . $db->escape($ref) . "'") . ",";
        $sql .= "" . (!isset($entity) ? 'NULL' : "'" . $db->escape($entity) . "'"). ",";
        $sql .= "" . (!isset($type	) ? 'NULL' : "'" . $db->escape($type) . "'") . ",";
        $sql .= "" . (!isset($socid) ? 'NULL' : "'" . $db->escape($socid) . "'") . ",";
        $sql .= "" . (!isset($date) ? 'NULL' : "'" . $db->escape($date) . "'"). ",";
        $sql .= "" . (!isset($date) ? 'NULL' : "'" . $db->escape($date) . "'") . ",";
        $sql .= "" . (!isset($ref_client) ? 'NULL' : "'" . $db->escape($ref_client) . "'") . ",";
        $sql .= "" . (!isset($fk_account) ? 'NULL' : "'" . $db->escape($fk_account) . "'") . ",";
        $sql .= "" . (!isset($cond_reglement_id) ? 'NULL' : "'" . $db->escape($cond_reglement_id) . "'") . ",";
        $sql .= "" . (!isset($mode_reglement_id) ? 'NULL' : "'" . $db->escape($mode_reglement_id) . "'") . ",";
        $sql .= "" . (!isset($modelpdf) ? 'NULL' : "'" . $db->escape($modelpdf) . "'") . ",";
        $sql .= "" . (!isset($amount) ? 'NULL' : "'" . $db->escape($amount) . "'") . ",";
        $sql .= "" . (!isset($amount) ? 'NULL' : "'" . $db->escape($amount) . "'") . ",";
        $sql .= "" . (!isset($statut) ? 'NULL' : "'" . $db->escape($statut) . "'");
        $sql .= ")";
        //var_dump($sql);die();
        $resql = $db->query($sql);
        if($resql){
            $idf = $db->last_insert_id(MAIN_DB_PREFIX.'facture');
            // Update ref with new one
            $ref='(ACC_'.$idf.')';
            $sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET ref='".$db->escape($ref)."' WHERE rowid=".$id;
            $resql = $db->query($sql);
            $sql = 'UPDATE '.MAIN_DB_PREFIX."echeance SET fk_facture='".$db->escape($idf)."' WHERE rowid=".$rowid;
            $resql= $db->query($sql);

        }   else  {
            setEventMessage("Une erreur à été détécté", 'errors');
            header("location: /categories/echeance/card.php?action=addEcheance&origin=propal&originid=$originid&socid=$socid");
            die;
        }

        header("location: /comm/propal/card.php?id=$originid");

        exit();

    }
    if ($objm->somech+GETPOST('montant', 'text')>$obj->total){
        setEventMessage("Le prix du lot est atteint", 'errors');
        header("location: /comm/propal/card.php?id=$originid");
        die;
    }



}
elseif ($action == 'update') {
    $rowid = GETPOST('rowid', 'int');
    $etape	= GETPOST('etape', 'text');
    $date_deb	= GETPOST('date_deb', 'date');
    $date_fin	= GETPOST('date_fin', 'date');
    $statut=GETPOST('statut', 'numeric');
    $date_deb_reelle	= GETPOST('date_deb_reelle', 'date');
    $date_fin_reelle	= GETPOST('date_fin_reelle', 'date');
    $sql = "UPDATE " . MAIN_DB_PREFIX . "echeance SET etape = '".$etape."',  date_deb = '".$date_deb."',  statut = '".$statut."',  date_fin = '".$date_fin."', date_deb_reelle = '".$date_deb_reelle."',  date_fin_reelle = '".$date_fin_reelle."',  fk_propal = '".$originid."' WHERE rowid = '$rowid'";
    //var_dump($sql);die();
    $resql = $db->query($sql);
    //var_dump('Date debut est '.$date_deb.' et date fin est'.$date_fin.'Date debut reel est '.$date_deb_reelle.' et date fin reelle est'.$date_fin_reelle);die;
    //header("location:" . $_SERVER['PHP_SELF'] . "?leftmenu=categorie&typeprod=0");
    if (!(strtotime($date_deb) < strtotime($date_fin))) {
        setEventMessage("La date de début doit être inférieure à la date de fin", 'errors');
        header("location: /categories/echeance/card.php?leftmenu=categorie&action=updateEcheance&rowid=$rowid&originid=$originid");
        https://sunuerp.numherit-labs.com/categories/echeance/card.php?leftmenu=categorie&action=updateEcheance&rowid=126&originid=203
        die;
    }
    if($date_deb_reelle !== "" && $date_fin_reelle !== "")
    {
        if (!(strtotime($date_deb_reelle) < strtotime($date_fin_reelle))) {
            setEventMessage("La date de début doit être inférieure à la date de fin", 'errors');
            header("location: /categories/echeance/card.php?leftmenu=categorie&action=updateEcheance&rowid=$rowid&originid=$originid");
            die;
        }
    }

    if ($resql > 0)
    {

        header("location: /comm/propal/card.php?id=$originid");



    } else{

        setEventMessage($object->error, 'errors');
    }

    exit();
}
elseif ($action == 'delete') {
    $ligne = GetecheanceParRowid($id);

    $sql = "DELETE FROM " . MAIN_DB_PREFIX . "echeance  WHERE rowid = '$id'";
    $resql = $db->query($sql);
    $sql1 = "DELETE FROM " . MAIN_DB_PREFIX . "categorie_echeance  WHERE fk_echeance = '$id'";
    $resql1 = $db->query($sql1);
    //var_dump($sql." ".$sql1);die();

    header("location:" . $_SERVER['PHP_SELF'] . "?leftmenu=categorie&typeprod=0");
    exit();
}

$form = new Form($db);
$htmlother = new FormOther($db);
$object = new Categorie($db);
    llxHeader("", $langs->trans("echeance"));
    $typeprod = GETPOST('typeprod', 'int');
    $helpurl='';
    $title=$langs->trans("Nature_lot");



if (! $id)
{
    dol_fiche_end();
}


function GetecheanceParRowid($rowid)
{
    global $db;
    $sql = "SELECT * FROM " .MAIN_DB_PREFIX."echeance  WHERE rowid = ".$rowid;
    $resql = $db->query($sql);

    $resql = $resql->fetch_object();
    return $resql;
}


// gestion de la vue


// Example 3 : List of data
if ($action == 'list' || $action == '') {
    print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
/*    print '<tr><td colspan="9" align="right"><a class="butAction" href="card.php?id=all&leftmenu=categorie&action=addEcheance&typeprod=0" title="Ajouter ">Ajouter </a></td></tr>';*/
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('etape'), $_SERVER['PHP_SELF'], 't.etape', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('date_deb'), $_SERVER['PHP_SELF'], 't.date_deb', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('date_fin'), $_SERVER['PHP_SELF'], 't.date_fin', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('date_deb_reelle'), $_SERVER['PHP_SELF'], 't.date_deb', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('date_fin_reelle'), $_SERVER['PHP_SELF'], 't.date_fin', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('description'), $_SERVER['PHP_SELF'], 't.description', '', $param, '', '', '');
   /* print_liste_field_titre($langs->trans('programmes'), $_SERVER['PHP_SELF'], 't.programme', '', $param, '', '', '');*/
    print_liste_field_titre($langs->trans('lot'), $_SERVER['PHP_SELF'], 't.lot', '', $param, '', '', '');
    print_liste_field_titre($langs->trans('montant'), $_SERVER['PHP_SELF'], 't.montant', '', $param, '', '', '');

    print_liste_field_titre($langs->trans('statut '), $_SERVER['PHP_SELF'], 't.statut', '', $param, '', '', '');

    print_liste_field_titre($langs->trans(''), $_SERVER['PHP_SELF'], '', '', $param, '', '', '');
    $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "echeance";
    dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
    $resql = $db->query($sql);
    if ($resql) {
        $num = $db->num_rows($resql);
        $i = 0;
        if ($num) {
            while ($i < $num) {
                $obj = $db->fetch_object($resql);
             //   var_dump($obj);die();
                $var = !$var;
                if ($obj) {
                    // You can use here results
                    print "<tr " . $bc[$var] . ">";
                    print '<td>';
                    print   $obj->etape;
                  print '</td>';
                  print '<td>';
                    //print   $obj->date_deb;
                    print dol_print_date($db->jdate( $obj->date_deb), 'day');
                  print '</td>';
                  print '<td>';
                    print dol_print_date($db->jdate( $obj->date_fin), 'day');
                  print '</td>';
                  print '<td>';
                    //print   $obj->date_deb;
                    print dol_print_date($db->jdate( $obj->date_deb_reelle), 'day');
                  print '</td>';
                  print '<td>';
                    print dol_print_date($db->jdate( $obj->date_fin_reelle), 'day');
                  print '</td>';
                  print '<td>';
                    print   $obj->description;
                    print '</td>';

                    if ( $obj->fk_propal){
                        $sql1 = 'SELECT ref';
                        $sql1 .= ' FROM ' . MAIN_DB_PREFIX . 'propal as p';
                        $sql1 .= ' WHERE p.rowid = ' . $obj->fk_propal;
                        $result1 = $db->query($sql1);
                        $objpro = $db->fetch_object($result1);

                        print '<td nowrap="nowrap" class="left">' .$objpro->ref . '</td>';
                    }
                    else	{
                        print '<td align="right">Néant</td>';
                    }
                    print '</td>';
                    print '<td align="right">'.number_format($obj->montant,0,',',' ').'</td>';


                    if ($obj->statut==0){
                        print '<td>';
                        print   "non démarré";
                        print '</td>';
                    }
                    if ($obj->statut==1){
                        print '<td>';
                        print  "en cours";
                        print '</td>';
                    }
                    if ($obj->statut==2){
                        print '<td>';
                        print  "terminé";
                        print '</td>';
                    }


				print '<td align="right">';
/*                    print '<a href="card.php?leftmenu=categorie&action=updateEcheance&rowid='.$obj->rowid.'" title="Modifier"><i class="fas fa-edit" style="margin-right:8px;"></i></a>';*/
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
elseif ($action == 'addEcheance' || $action == 'updateEcheance') {

    if($id !== ''){

        $ligne = GetecheanceParRowid($id);
       // var_dump($ligne->fk_categorie);die();


        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        print "<tr>";
        print'<td>';
        print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
        print '<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="rowid" value="'.$ligne->rowid.'">';
        print '<input type="hidden" name="originid" value="'.$originid.'">';
        print '<input type="hidden" name="fk_propal" value="'.$originid.'">';


        print '<fieldset width="100%"> ';
        print '<legend style="font-weight:bold">Modification d\'une échéance</legend>';
        print '<table width="100%" cellspacing="5" cellpadding="5" align="center"> ';
        //if(!is_null($journal[0]))

        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('etape') . ' :</td><td class="fieldrequired" align="left"><input class="flat" type = "text" readonly size="40" name="etape" required="required" value="'. $ligne->etape.'"></td></tr>';
/*        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('etape') . ' :</td><td width="45%">'. $ligne->etape.'</td></tr>';*/
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('date_deb') . ' :</td>
                <td class="fieldrequired" align="left">
                <input class="flat" type = "date" size="40" name="date_deb"  min="'.$date.'" required="required" value="'. $ligne->date_deb.'"></td></tr>';

        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('date_fin') . ' :</td>
                <td class="fieldrequired" align="left"><input class="flat" type = "date" size="40" name="date_fin" min="'.$date.'"  required="required" value="'. $ligne->date_fin.'"></td></tr>';

        /*        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('montant') . ' :</td><td class="fieldrequired" align="left"><input class="flat" type = "text" size="25" name="montant" required="required" value="'. $ligne->montant.'"></td></tr>';*/
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('montant') . ' :</td><td width="45%">'. $ligne->montant.'</td></tr>';
        print '<tr><td width="45%" class="fieldrequired" align="right">' . $langs->trans('statut') . ' :</td>';
        print '<td><select id="select" name="statut">';
        if ($obj->statut==0){
            print '<option value='.$obj->statut.' selected>';print "non démarré";   print '</option>';
            print '<option value=1>';print "en cours";   print '</option>';
            print '<option value=2>';print "terminé";   print '</option>';
        }
        if ($obj->statut==1){
            print '<option value=0>';print "non démarré";   print '</option>';
            print '<option value='.$obj->statut.' selected>';print "en cours";   print '</option>';
            print '<option value=2>';print "terminé";   print '</option>';
        }
        if ($obj->statut==2){
            print '<option value=0>';print "non démarré";   print '</option>';
            print '<option value=1>';print "en cours";   print '</option>';
            print '<option value='.$obj->statut.' selected>';print "terminé";   print '</option>';
        }

        print "</select></td>";
        if($ligne->date_deb_reelle || $ligne->date_fin_reelle){
            print '<tr><td width="45%"  align="right">' . $langs->trans('date_deb_reelle') . ' :</td>
                <td  align="left">'. $form->selectDate('', 'date_fin', '', '', '', "addprop", 1, 1).'</td></tr>';

            print '<tr><td width="45%"  align="right">' . $langs->trans('date_fin_reelle') . ' :</td><td  align="left"><input class="flat" type = "date" size="40" name="date_fin_reelle" min="'.$date.'" value="'. $ligne->date_fin_reelle.'"></td></tr>';
        }else{
            print '<tr><td width="45%"  align="right">' . $langs->trans('date_deb_reelle') . ' :</td><td  align="left"><input class="flat" type = "date" size="40" name="date_deb_reelle"  min="'.$date.'" value=""></td></tr>';
            print '<tr><td width="45%"  align="right">' . $langs->trans('date_fin_reelle') . ' :</td><td  align="left"><input class="flat" type = "date" size="40" name="date_fin_reelle" min="'.$date.'" value=""></td></tr>';
        }

/*         print '<tr><td width="45%" align="right">' . $langs->trans('Description') . ' :</td><td  align="left"><input class="flat" type = "text" size="40" name="description"  value="'. $ligne->description.'"></td></tr>';*/
        print '<tr><td width="45%" align="right">' . $langs->trans('lot') . ' :</td>';
        $sql = "SELECT ref";
        $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
        $sql .= " WHERE p.rowid = ". $ligne->fk_propal;
        //var_dump($sql);die;
        $resql = $db->query($sql);
        //var_dump($resql);die;
        if ($resql) {
            $objet = $db->fetch_object($resql);
            print '<td>'. $objet->ref.'</td>';
            print '<input type="hidden" name="fk_propal" id="fk_propal" value="' . $idPropal . '">';
            print '</tr>';

        }



        print '</td></tr>';
        print '<tr><td colspan="2" align="center" valign="middle"><input type="submit" class="button" name="bouton" value="' . $langs->trans('Modifier') . '"></td></tr>';
        print '</table>';
        print '</fieldset>';
        print "</form>\n";
        print'</td>';
        print '</tr>';
        print '</table>';
    }
    else{



        $sql = "SELECT label FROM ".MAIN_DB_PREFIX."etape_const WHERE statut = 1";
        //var_dump($sql);die;
        $resql = $db->query($sql);
        //var_dump($resql);die;
        $option = '';
        if ($resql) {
            $num = $db->num_rows($resql);
            if ($num > 0) {
                $i = 0;
                while ($i < $num) {
                    $objet = $db->fetch_object($resql);
                    $option .= '<option '.(($objet->label == $etape) ? ' selected="selected"':'') .'value="'.$objet->label.'">'.$objet->label.'
                    </option>';
                    $i++;
                    //var_dump($etape);

                }
            }
        }
        ?>
        <div class="fiche">
            <table class="centpercent notopnoleftnoright" style="margin-bottom: 6px;"><tbody><tr><td class="nobordernopadding widthpictotitle opacityhigh valignmiddle"><img src="/theme/eldy/img/title_generic.png" alt="" class="valignmiddle widthpictotitle pictotitle"></td><td class="nobordernopadding valignmiddle"><div class="titre inline-block">Nouvelle echéance</div></td></tr></tbody></table>
            <form action="<?= $_SERVER["PHP_SELF"] ?>" method="POST">
                <input type="hidden" name="token" value="<?= $_SESSION ['newtoken'] ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="originid" value="<?= $originid ?>">
                <input type="hidden" name="socid" value="<?= $socid ?>">
                <div class="tabs" data-role="controlgroup" data-type="horizontal">
                </div>

                <div class="tabBar tabBarWithBottom">
                    <table class="border" width="100%">
                        <tbody>
                        <tr>
                            <td class="titlefieldcreate fieldrequired"><?= $langs->trans('etape') ?></td>
                            <td><select name="etape" required><?= $option ?></select></td>
                        </tr>

                        <tr>

                            <td class="titlefieldcreate fieldrequired"><?= $langs->trans('date_deb') ?></td>
                            <td>
<!--                             <input class="flat" type = "date" size="40" name="date_deb" min="<?/*= $date;*/?>" required="required" >
-->                               <?php
                                   print $form->selectDate('', 'date_deb', '', '', '', "addprop", 1, 1);
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="titlefieldcreate fieldrequired"><?= $langs->trans('date_fin') ?></td>
<!--                            <td><input class="flat" type = "date" size="40" name="date_fin" min="<?/*= $date;*/?>" required="required" ></td>
-->                            <td>
                                <?php
                                    print $form->selectDate('', 'date_fin', '', '', '', "addprop", 1, 1);

                                ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="titlefieldcreate fieldrequired"><?= $langs->trans('montant') ?></td>
                            <td><input class="flat" type = "number" min="0" size="40" name="montant" required="required" ></td>
                        </tr>
                        <tr>
                            <td class="titlefieldcreate"><?= $langs->trans('Description') ?></td>
                            <td><input class="flat" type = "text" size="40" name="Description"></td>
                        </tr>
                        <tr>
                            <td class="titlefieldcreate"><?= $langs->trans('Souscription') ?></td>

                            <?php
                            $sql = "SELECT ref";
                            $sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
                            $sql .= " WHERE p.rowid = ". $idPropal;
                            //var_dump($sql);die;
                            $resql = $db->query($sql);
                            if ($resql) {
                                       $objet = $db->fetch_object($resql);
                                       print '<td>'. $objet->ref.'</td>';
                                        print '<input type="hidden" name="fk_propal" id="fk_propal" value="' . $idPropal . '">';
                                      print '</tr>';
                               }?>
                        </tbody>
                    </table>
                    <input type="hidden" name="createmode" value="empty">
                </div>
                <div class="center"><input type="submit" class="button" value="Creer échéance">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button" value="Annuler" onclick="javascript:history.go(-1)"></div></form>

        </div>
        <?php
    }



}

// End of page
llxFooter();
$db->close();
