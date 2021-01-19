<?php
/* Copyright (C) 2001-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2014-2016  Charlie BENKE           <charlie@patas-monkey.com>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019       Pierre Ardoin           <mapiolca@me.com>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

$type = GETPOST("type", 'int');
if ($type == '' && !$user->rights->produit->lire) $type = '1'; // Force global page on service page only
if ($type == '' && !$user->rights->service->lire) $type = '0'; // Force global page on product page only

// Security check
if ($type == '0') $result = restrictedArea($user, 'produit');
elseif ($type == '1') $result = restrictedArea($user, 'service');
else $result = restrictedArea($user, 'produit|service|expedition');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('productindex'));

$product_static = new Product($db);


/*
 * View
 */

$transAreaType = "Espace commercial";
$transAreaCreate = $langs->trans("CreateCat");
$transAreaEncours = $langs->trans("programmeEncours");
$transAreaList = $langs->trans("CatList");
$transGesClient = $langs->trans("gestclient");
$transgrillePAjout = $langs->trans("AjoutGrille");
$transgrillePModif = $langs->trans("ModifGrille");
$transgrillePConsult = $langs->trans("ConsultGrille");
$transAreatouteList = $langs->trans("allProgramme");

$openficcli = $langs->trans("Openficcli");
$creerficsouscription = $langs->trans("creerficsouscription");
$creerContrat=$langs->trans("creerContrat");

//$page
//////////////////////////////////////TTEST
///
///

$page=GETPOST("page", 'int');
if (empty($page) || $page == -1 || !empty($search_btn) || !empty($search_remove_btn) || (empty($toselect) && $massaction === '0')) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$sql = " SELECT  p.ref,p.rowid,p.tms,p.total,";
$sql.= ' s.rowid as socid,s.nom,s.phone';
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p,".MAIN_DB_PREFIX."societe as s ";
$sql.= " WHERE s.rowid=p.fk_soc AND p.fk_statut=0";
$sql.= " ORDER BY p.ref ASC ";
$resql = $db->query($sql);
/////Count total nb of records

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
    if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
    {
        $page = 0;
        $offset = 0;
    }
}

$num1 = $db->num_rows($resql);










////////////////////////////////////////////TEST




$helpurl = '';

llxHeader("", $langs->trans("ProductsAndServices"), $helpurl);


//requete recupération liste catégorie
$sql1 = "SELECT DISTINCT COUNT(c.rowid) as nbprog";
$sql1.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
$sql1 .= " WHERE c.entity IN (" . getEntity('category') . ")";
$sql1 .= " AND c.type = " . (int) $type;

$resql1 = $db->query($sql1);
$nb_programme=0;
if ($resql1) {
    $num = $db->num_rows($resql1);
    if ($num) {
        $obj = $db->fetch_object($resql1);
        $var = !$var;
        if ($obj) {
            $nb_programme= $obj->nbprog;
        }
        else $nb_programme=0;


    }
}


$linkback = "";
/*print  load_fiche_titre($transAreaType, $linkback, 'categories');*/
$title = $transAreaType;
print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield='', $sortorder, $massactionbutton, $num1, $nbtotalofrecords,'title_companies', 0, $newcardbutton='', '', $limit);
var_dump($_SERVER["PHP_SELF"].' 1-'.$title.' 2-'.$page.' 3-'.$param.' 4-'.$sortfield.' 5-'.$sortorder.' 6-'.$massactionbutton.' 7-'.$num1.' 8-'.$nbtotalofrecords.' 9-'.$newcardbutton.' 10-'.$limit);

print '<br/><br/>' ;



print '<table width="100%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr>';
print '<td style="color: gray">';
print "DOSSIER CLIENTS";
print '</td>';
print'</tr>';
print '</table>';
print '</h3><hr width="100%"> </td></tr>';
print '<div class="fichecenter"><div class="fichethirdleft">';

print '<div style="width: 200%">';
print '<div style="width: 20%;float: left;" >';

/* print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';*/
/*  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';*/
print '<table>';
print '<br>';print '<br>';
print '<tr >';

print '<td style="size: 200px; color: darkred">';
print '<i class="fa fa-folder-open-o" aria-hidden="true"></i>';
/*print ' <div id="noun_buildings_1868750">
 	<svg class="Trac_101_cr" viewBox="10 15 43.239 50" style="width: 25px;">
				<path fill="#8b0000"  d="M 48.1857795715332 25.64388656616211 C 47.62515258789062 24.99551010131836 46.82077789306641 24.6250114440918 45.96765518188477 24.6250114440918 L 45.79702758789062 24.6250114440918 L 45.79702758789062 17.3125057220459 C 45.79702758789062 15.69887924194336 44.48564910888672 14.3875036239624 42.87202453613281 14.3875036239624 L 25.27326202392578 14.3875036239624 L 21.66088485717773 10.32662582397461 C 21.48050689697266 10.11700057983398 21.21238136291504 10 20.93450736999512 10 L 11.67200088500977 10 C 10.05837440490723 10 8.746997833251953 11.31137657165527 8.746997833251953 12.9250020980835 L 8.746997833251953 24.6250114440918 L 8.576373100280762 24.6250114440918 C 7.72324800491333 24.6250114440918 6.918872356414795 24.99551010131836 6.358246803283691 25.64388656616211 C 5.802496433258057 26.29226303100586 5.558746337890625 27.14538955688477 5.68549633026123 27.98876190185547 L 8.508122444152832 46.51377868652344 C 8.727498054504395 47.95678329467773 9.941373825073242 49.00003051757812 11.39900016784668 49.00003051757812 L 43.14502334594727 49.00003051757812 C 44.6026496887207 49.00003051757812 45.8165283203125 47.95678329467773 46.04077911376953 46.51377868652344 L 48.8585319519043 27.98876190185547 C 48.98528289794922 27.14538955688477 48.74153137207031 26.29226303100586 48.1857795715332 25.64388656616211 Z M 10.69699954986572 12.9250020980835 C 10.69699954986572 12.38875102996826 11.13574981689453 11.95000171661377 11.67200088500977 11.95000171661377 L 20.49575614929199 11.95000171661377 L 24.10813522338867 16.01087951660156 C 24.28851127624512 16.22050476074219 24.55663681030273 16.33750534057617 24.83451080322266 16.33750534057617 L 42.87202453613281 16.33750534057617 C 43.40827560424805 16.33750534057617 43.84702682495117 16.7762565612793 43.84702682495117 17.3125057220459 L 43.84702682495117 24.6250114440918 L 41.89702606201172 24.6250114440918 L 41.89702606201172 21.70000839233398 C 41.89702606201172 21.16375732421875 41.45827484130859 20.72500991821289 40.92202377319336 20.72500991821289 L 13.62200164794922 20.72500991821289 C 13.0857515335083 20.72500991821289 12.64700126647949 21.16375732421875 12.64700126647949 21.70000839233398 L 12.64700126647949 24.6250114440918 L 10.69699954986572 24.6250114440918 L 10.69699954986572 12.9250020980835 Z M 39.947021484375 22.67501068115234 L 39.947021484375 24.6250114440918 L 14.59700202941895 24.6250114440918 L 14.59700202941895 22.67501068115234 L 39.947021484375 22.67501068115234 Z M 46.92803192138672 27.69626235961914 L 44.11027526855469 46.22127914428711 C 44.03714752197266 46.70390319824219 43.63252258300781 47.05002975463867 43.14502334594727 47.05002975463867 L 11.39900016784668 47.05002975463867 C 10.91149997711182 47.05002975463867 10.50687503814697 46.70390319824219 10.43375015258789 46.22127914428711 L 7.615998268127441 27.69626617431641 C 7.572123050689697 27.41351318359375 7.650123119354248 27.13563919067383 7.840248584747314 26.91626358032227 C 8.025498390197754 26.69689178466797 8.288748741149902 26.57501602172852 8.576373100280762 26.57501602172852 L 45.96765518188477 26.57501602172852 C 46.25527572631836 26.57501602172852 46.51852416992188 26.69689178466797 46.70378112792969 26.91626358032227 C 46.89390182495117 27.13563919067383 46.97190856933594 27.41351318359375 46.92803192138672 27.69626235961914 Z">
				</path>
			</svg>
			</div>
			';*/
print '</td>';

print '<td nowrap="nowrap">';

print '<a href="../comm/propal/card.php?action=create&leftmenu=propals"> '. load_fiche_titre("Créer une souscription", $linkback, '').'</a>';
print '</td>';

print '</table>';
/* print '</form>';*/
print '<br>';


print '</div>
<div style="width: 80%;float: right;">';


$max=5;

// STAT SUR LES PROSPECTS PAR COMMERCIALE

// Stat pour les tiers
$sql1 = "SELECT month(`datec`) as  mois_int  ,year(`datec`) as annee,date_format(`datec`,'%M') as mois ";
$sql1.= " FROM ".MAIN_DB_PREFIX."societe ";
$sql1.= " GROUP BY  annee, mois_int ";
$sql1.= " ORDER BY annee, mois_int DESC ";
$ladate=$_POST['mois_com'];
print '<table class="noborder" width="50%">';
print '<tr><td style="font-size: 15px; font-weight: bold; color: darkred " colspan="4">Souscriptions en cours de validation par le client</td>';print '</tr >';

print '<tr >';
print '<th nowrap="nowrap"  align="left" ><a href="#">Souscription </a></th>';
print '<th nowrap="nowrap"  align="left" ><a href="#">CLIENTS</a></th>';
print '<th nowrap="nowrap" align="center"><a href="#">TELEPHONE</a></th>';
print '<th nowrap="nowrap" align="center"><a href="#">DATE</a></th>';
print '<th nowrap="nowrap" align="right" ><a href="#">MONTANT</a></th>';

print '</tr >';


//requete recupération liste proposition commerciale
/*$sql = " SELECT  p.ref,p.rowid,p.tms,p.total,";
$sql.= ' s.rowid as socid,s.nom,s.phone';
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p,".MAIN_DB_PREFIX."societe as s ";
$sql.= " WHERE s.rowid=p.fk_soc AND p.fk_statut=0";
$sql.= " ORDER BY p.ref ASC ";
*/
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
                print '<td nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../comm/propal/card.php?id='.$obj->rowid.'&save_lastsearch_values=1">' . $obj->ref . '</a>';

                print '</td>';

                print '<td  nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../societe/card.php?socid='.$obj->socid.'&save_lastsearch_values=1">' . $obj->nom . '</a>';
                print '</td>';

                print '<td  nowrap="nowrap" align="right">';
                print   $obj->phone;
                print '</td>';
                print '<td nowrap="nowrap" align="center">';
                print   $obj->tms;
                print '</td>';

               /* print '<td  nowrap="nowrap" align="center">';
                print   $obj->total;
                print '</td>';*/
                //Séparateur de millier
                print '<td align="right">'.number_format($obj->total,0,',',' ').'</td>';

                print '</tr>';
            }
            $i++;

        }
    }
}

print "</table>";




print '</div>
<div style="width: 80%;float: right;">';


$max=5;

// STAT SUR LES PROSPECTS PAR COMMERCIALE

// Stat pour les tiers


print '<table class="noborder" width="100%">';
print '<tr><td style="font-size: 15px; font-weight: bold; color: darkred " colspan="4">Souscriptions validées</td>';
print '</tr>';

print '<tr >';
print '<th nowrap="nowrap"  align="left" ><a href="#">Souscription</a></th>';
print '<th nowrap="nowrap"  align="left" ><a href="#">CLIENTS</a></th>';
print '<th nowrap="nowrap" align="center"><a href="#">TELEPHONE</a></th>';
print '<th nowrap="nowrap" align="center"><a href="#">DATE</a></th>';
print '</tr>';



//requete recupération liste proposition commerciale
$sql = " SELECT  p.ref,p.rowid,p.tms,";
$sql.= ' s.rowid as socid,s.nom,s.phone';
$sql.= " FROM ".MAIN_DB_PREFIX."propal as p,".MAIN_DB_PREFIX."societe as s ";
$sql.= " WHERE s.rowid=p.fk_soc AND p.fk_statut IN (1,2,3)";
$sql.= " ORDER BY p.ref ASC ";
//var_dump($sql);die();
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
                print '<td nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../comm/propal/card.php?id='.$obj->rowid.'&save_lastsearch_values=1">' . $obj->ref . '</a>';

                print '</td>';

                print '<td  nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../societe/card.php?socid='.$obj->socid.'&save_lastsearch_values=1">' . $obj->nom . '</a>';

                print '</td>';

                print '<td  nowrap="nowrap" align="right">';
                print   $obj->phone;
                print '</td>';
                print '<td nowrap="nowrap" align="center">';
                print   $obj->tms;
                print '</td>';


                print '<td nowrap="nowrap">';
                print  '<a style="font-size: 10px; font-weight: bold; color:darkred;!important; border-radius: 10px; " href="../compta/facture/card.php?action=create&leftmenu=" class="button" >Créer la facture</a>';
                print '</td>';

                print '</tr>';
            }
            $i++;

        }
    }
}


print "</table>";

print '</div>';





$parameters = array('type' => $type, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();


