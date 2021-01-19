<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
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
 *	\file       htdocs/index.php
 *	\brief      Dolibarr home page
 */

define('NOCSRFCHECK', 1);	// This is main home and login page. We must be able to go on it from another web site.

require 'main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
//require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/dynamic_price/class/price_parser.class.php';

// If not defined, we select menu "home"
$_GET['mainmenu']=GETPOST('mainmenu', 'aZ09')?GETPOST('mainmenu', 'aZ09'):'home';
$action=GETPOST('action', 'aZ09');

$hookmanager->initHooks(array('index'));


/*
 * Actions
 */


/**
 *  \file       htdocs/product/index.php
 *  \ingroup    product
 *  \brief      Homepage products and services
 */

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

$transAreaType = $langs->trans("PROGRAMMES");
$transAreaCreate = $langs->trans("CreateCat");
$transAreaEncours = $langs->trans("programmeEncours");
$transAreaList = $langs->trans("CatList");
$transGesClient = $langs->trans("gestclient");
$transgrillePAjout = $langs->trans("AjoutGrille");
$transgrillePModif = $langs->trans("ModifGrille");
$transgrillePConsult = $langs->trans("ConsultGrille");
$transAreatouteList = $langs->trans("allProgramme");
$suivisouscription = $langs->trans("suiviSousciption");
$openficcli = $langs->trans("Openficcli");
$creerficsouscription = $langs->trans("creerficsouscription");
$creerContrat = $langs->trans("creerContrat");


$helpurl = '';

llxHeader("", $langs->trans("ProductsAndServices"), $helpurl);


//requete recupération liste catégorie
$sql1 = "SELECT DISTINCT COUNT(c.rowid) as nbprog";
$sql1 .= " FROM " . MAIN_DB_PREFIX . "categorie as c ";
$sql1 .= " WHERE c.entity IN (" . getEntity('category') . ")";
//$sql1 .= " AND c.type = " . (int)$type;

$resql1 = $db->query($sql1);
$nb_programme = 0;
if ($resql1) {
    $num = $db->num_rows($resql1);
    if ($num) {
        $obj = $db->fetch_object($resql1);
        $var = !$var;
        if ($obj) {
            $nb_programme = $obj->nbprog;
        } else $nb_programme = 0;


    }
}


$linkback = "";
print load_fiche_titre($transAreaType, $linkback, 'categories');
print '</h3><hr width="100%"> </td></tr>';
print '<div class="fichecenter"><div class="fichethirdleft">';
print '<table  width="100%">';
print '<tr >';
print '<td>';

print ' <div id="noun_buildings_1868750">	<svg class="Trac_44" viewBox="10 10 30 34" style="width: 45px;">
				<path fill="#8b0000" id="Trac_44" d="M 43.57500839233398 43.15000534057617 L 39.57734680175781 43.15000534057617 L 39.57734680175781 10.42500114440918 C 39.57734680175781 10.19008922576904 39.38705444335938 10.00000095367432 39.15234756469727 10.00000095367432 L 27.78359603881836 10.00000095367432 C 27.54889106750488 10.00000095367432 27.35859489440918 10.19008922576904 27.35859489440918 10.42500114440918 L 27.35859489440918 14.05659294128418 L 17.49539947509766 14.05659294128418 C 17.26069450378418 14.05659294128418 17.07039833068848 14.24668121337891 17.07039833068848 14.48159313201904 L 17.07039833068848 24.43339920043945 L 14.84765720367432 24.43339920043945 C 14.61295318603516 24.43339920043945 14.42265796661377 24.62348747253418 14.42265796661377 24.85840034484863 L 14.42265796661377 43.15000534057617 L 10.42500114440918 43.15000534057617 C 10.1902961730957 43.15000534057617 10.00000095367432 43.34009170532227 10.00000095367432 43.57500839233398 C 10.00000095367432 43.80991744995117 10.1902961730957 44.00000381469727 10.42500114440918 44.00000381469727 L 43.57500839233398 44.00000381469727 C 43.80971145629883 44.00000381469727 44.00000381469727 43.80991744995117 44.00000381469727 43.57500839233398 C 44.00000381469727 43.34009170532227 43.80971145629883 43.15000534057617 43.57500839233398 43.15000534057617 Z M 38.72735214233398 10.85000133514404 L 38.72735214233398 43.15000534057617 L 28.20859527587891 43.15000534057617 L 28.20859527587891 10.85000133514404 L 38.72735214233398 10.85000133514404 Z M 17.9203987121582 14.90659332275391 L 27.35859489440918 14.90659332275391 L 27.35859489440918 24.43339920043945 L 17.9203987121582 24.43339920043945 L 17.9203987121582 14.90659332275391 Z M 15.2726583480835 25.28339958190918 L 27.35859489440918 25.28339958190918 L 27.35859489440918 43.15000534057617 L 15.2726583480835 43.15000534057617 L 15.2726583480835 25.28339958190918 Z">
				</path>
			</svg>
			</div>
			';
print '</td>';
print '<td>';

print '<a href="../categories/card.php?action=create&typeprod=0&mainmenu=products&leftmenu=">' . load_fiche_titre($transAreaCreate, $linkback, '') . '</a>';
print '</td>';
print '<td style="font-weight: bold;font-size: 20px; color: #8b0000">';
print $nb_programme;
print '</td>';
print '<td>';
print '<a href="../categories/index.php?leftmenu=cat&type=0">' . load_fiche_titre($transAreaEncours, $linkback, '') . '</a>';
print '</td>';
print '</tr>';
print '</table>';
print '<br/><br/><br/>';
print '<table  width="100%">';
print '<tr>';
print '<td >';
print '<a style="font-weight: bold; font-size: 15px;" href="#">' . $transAreaList . '</a>';
print '</td>';
print'</tr>';
print '<tr><td colspan="10"><hr width="100%"></td></tr>';
print '</table>';

print '<table width="100%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr >';
print '<td ></td>';
print '<td nowrap="nowrap" align="left"   ><a style="font-weight: bold; font-size: 13px;" href="#">' . $langs->trans('PROGRAMMES') . '</a></td>';
print '<td colspan="2" nowrap="nowracolspan="2"p"  align="left"><a style="font-weight: bold; font-size: 13px;" href="#">' . $langs->trans('NumberOflots') . '</a></td>';
print '<td colspan="2" nowrap="nowrap"  align="right"  ><a style="font-weight: bold; font-size: 13px;" href="#">' . $langs->trans('ADRESSE') . '</a></td>';

//requete recupération liste catégorie
$sql = "SELECT DISTINCT c.rowid, c.label, ce.adresse, COUNT(cp.fk_product) as nblot";
$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c, " . MAIN_DB_PREFIX . "categories_extrafields as ce, " . MAIN_DB_PREFIX . "categorie_product as cp ";
$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
//$sql .= " AND c.type = " . (int)$type;
$sql .= " AND c.rowid = ce.fk_object";
$sql .= " AND c.rowid = cp.fk_categorie GROUP BY cp.fk_categorie";

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
                print '<a  href="categories/viewcat.php?id='.$obj->rowid.'">
             <div id="noun_buildings_1868750">	<svg class="Trac_44" viewBox="10 10 30 34" style="width: 15px;">
				<path fill="#8b0000" id="Trac_44" d="M 43.57500839233398 43.15000534057617 L 39.57734680175781 43.15000534057617 L 39.57734680175781 10.42500114440918 C 39.57734680175781 10.19008922576904 39.38705444335938 10.00000095367432 39.15234756469727 10.00000095367432 L 27.78359603881836 10.00000095367432 C 27.54889106750488 10.00000095367432 27.35859489440918 10.19008922576904 27.35859489440918 10.42500114440918 L 27.35859489440918 14.05659294128418 L 17.49539947509766 14.05659294128418 C 17.26069450378418 14.05659294128418 17.07039833068848 14.24668121337891 17.07039833068848 14.48159313201904 L 17.07039833068848 24.43339920043945 L 14.84765720367432 24.43339920043945 C 14.61295318603516 24.43339920043945 14.42265796661377 24.62348747253418 14.42265796661377 24.85840034484863 L 14.42265796661377 43.15000534057617 L 10.42500114440918 43.15000534057617 C 10.1902961730957 43.15000534057617 10.00000095367432 43.34009170532227 10.00000095367432 43.57500839233398 C 10.00000095367432 43.80991744995117 10.1902961730957 44.00000381469727 10.42500114440918 44.00000381469727 L 43.57500839233398 44.00000381469727 C 43.80971145629883 44.00000381469727 44.00000381469727 43.80991744995117 44.00000381469727 43.57500839233398 C 44.00000381469727 43.34009170532227 43.80971145629883 43.15000534057617 43.57500839233398 43.15000534057617 Z M 38.72735214233398 10.85000133514404 L 38.72735214233398 43.15000534057617 L 28.20859527587891 43.15000534057617 L 28.20859527587891 10.85000133514404 L 38.72735214233398 10.85000133514404 Z M 17.9203987121582 14.90659332275391 L 27.35859489440918 14.90659332275391 L 27.35859489440918 24.43339920043945 L 17.9203987121582 24.43339920043945 L 17.9203987121582 14.90659332275391 Z M 15.2726583480835 25.28339958190918 L 27.35859489440918 25.28339958190918 L 27.35859489440918 43.15000534057617 L 15.2726583480835 43.15000534057617 L 15.2726583480835 25.28339958190918 Z">
				</path>
			</svg>
			</div>

        ';
                print '</td>';
                print '<td nowrap="nowrap" align="left">';
               print '<a style="font-weight: bold; font-size: 13px;" href="categories/viewcat.php?id='.$obj->rowid.'">' . $obj->label . '</a>';
                //print   $obj->label;
                print '</td>';

                print '<td colspan="2" nowrap="nowrap" align="left">';
                print   $obj->nblot . ' lots';
                print '</td>';

                print '<td colspan="2" nowrap="nowrap" align="right">';
                print   $obj->adresse;
                print '</td>';
//nowrap="nowrap" pour avoir sur la meme ligne
                print '<td nowrap="nowrap">';

                print  '<a style="font-size: 7px; font-weight: bold; color:darkred;!important; border-radius: 10px; " href="../product/list_parProgramme.php?leftmenu=product&type=0&rowid=' . $obj->rowid . '" class="button" >' . $transgrillePAjout . '</a>';
                // print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0"  class="button >'.$transgrillePAjout.'</a>';
                print '</td>';

                /* print '<td nowrap="nowrap">';
                /* print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0">'.$transgrillePModif.'</a>';
                 print  '<a style="font-size: 7px; color:darkred;!important; border-radius: 10px; href=" href="list_parProgramme.php?leftmenu=product&type=0&action=getGrille&rowid='.$obj->rowid.'" class="button">'.$transgrillePModif.'</a>';
                 print '</td>';*/

                print '<td nowrap="nowrap" >';
                print  '<a style="font-size: 7px; color:darkred;!important; border-radius: 10px; href=" href="../product/list_parProgramme.php?leftmenu=product&type=0&rowid=' . $obj->rowid . '" class="button">' . $transgrillePConsult . '</a>';
                /* print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0">'.$transgrillePConsult.'</a>';*/
                print '</td>';

                print '</tr>';
            }
            $i++;

        }
    }
}
print'</td>';
print '</tr>';
print '<tr ></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>';
print '<br/><br/>';




/*print '<tr aria-rowspan="10">';
print '<td align="right" colspan="6">';
print '<a href="../categories/index.php?leftmenu=cat&type=0">'.$transAreatouteList.'</a>';
print '</td>';
print '</tr>';*/
print '</table>';
print '<br/><br/>';
print '<div align="right" style="width: auto; margin-right: 0px;" >';
print '<a style="font-size: 15px;" href="../categories/index.php?leftmenu=cat&type=0">' . $transAreatouteList . '</a>';
print '</div>';
/*
print '<table align width="50%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr>';
print '<td align="right" colspan="2">';
print '<a href="../categories/index.php?leftmenu=cat&type=0">'.$transAreatouteList.'</a>';
print '</td>';
print '</tr>';
print '</table>';*/
print '<br/><br/>';
print '<table width="100%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr>';
print '<td  style="color: gray">';
print $transGesClient;
print '</td>';
print'</tr>';
print '</table>';
print '<tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>';
print '</h3><hr width="200%"> </td></tr>';

print '<div style="width: 200%">';
print '<div style="width: 25%;float: left;" >';

/* print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';*/
/*  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';*/
print '<table>';
print '<br>';
print '<br>';
print '<tr >';

print '<td style="size: B4; color: darkred">';
print '<i class="fa fa-folder-open-o" aria-hidden="true"></i>';
/*print ' <div id="noun_buildings_1868750">
 	<svg class="Trac_101_cr" viewBox="10 15 43.239 50" style="width: 25px;">
				<path fill="#8b0000"  d="M 48.1857795715332 25.64388656616211 C 47.62515258789062 24.99551010131836 46.82077789306641 24.6250114440918 45.96765518188477 24.6250114440918 L 45.79702758789062 24.6250114440918 L 45.79702758789062 17.3125057220459 C 45.79702758789062 15.69887924194336 44.48564910888672 14.3875036239624 42.87202453613281 14.3875036239624 L 25.27326202392578 14.3875036239624 L 21.66088485717773 10.32662582397461 C 21.48050689697266 10.11700057983398 21.21238136291504 10 20.93450736999512 10 L 11.67200088500977 10 C 10.05837440490723 10 8.746997833251953 11.31137657165527 8.746997833251953 12.9250020980835 L 8.746997833251953 24.6250114440918 L 8.576373100280762 24.6250114440918 C 7.72324800491333 24.6250114440918 6.918872356414795 24.99551010131836 6.358246803283691 25.64388656616211 C 5.802496433258057 26.29226303100586 5.558746337890625 27.14538955688477 5.68549633026123 27.98876190185547 L 8.508122444152832 46.51377868652344 C 8.727498054504395 47.95678329467773 9.941373825073242 49.00003051757812 11.39900016784668 49.00003051757812 L 43.14502334594727 49.00003051757812 C 44.6026496887207 49.00003051757812 45.8165283203125 47.95678329467773 46.04077911376953 46.51377868652344 L 48.8585319519043 27.98876190185547 C 48.98528289794922 27.14538955688477 48.74153137207031 26.29226303100586 48.1857795715332 25.64388656616211 Z M 10.69699954986572 12.9250020980835 C 10.69699954986572 12.38875102996826 11.13574981689453 11.95000171661377 11.67200088500977 11.95000171661377 L 20.49575614929199 11.95000171661377 L 24.10813522338867 16.01087951660156 C 24.28851127624512 16.22050476074219 24.55663681030273 16.33750534057617 24.83451080322266 16.33750534057617 L 42.87202453613281 16.33750534057617 C 43.40827560424805 16.33750534057617 43.84702682495117 16.7762565612793 43.84702682495117 17.3125057220459 L 43.84702682495117 24.6250114440918 L 41.89702606201172 24.6250114440918 L 41.89702606201172 21.70000839233398 C 41.89702606201172 21.16375732421875 41.45827484130859 20.72500991821289 40.92202377319336 20.72500991821289 L 13.62200164794922 20.72500991821289 C 13.0857515335083 20.72500991821289 12.64700126647949 21.16375732421875 12.64700126647949 21.70000839233398 L 12.64700126647949 24.6250114440918 L 10.69699954986572 24.6250114440918 L 10.69699954986572 12.9250020980835 Z M 39.947021484375 22.67501068115234 L 39.947021484375 24.6250114440918 L 14.59700202941895 24.6250114440918 L 14.59700202941895 22.67501068115234 L 39.947021484375 22.67501068115234 Z M 46.92803192138672 27.69626235961914 L 44.11027526855469 46.22127914428711 C 44.03714752197266 46.70390319824219 43.63252258300781 47.05002975463867 43.14502334594727 47.05002975463867 L 11.39900016784668 47.05002975463867 C 10.91149997711182 47.05002975463867 10.50687503814697 46.70390319824219 10.43375015258789 46.22127914428711 L 7.615998268127441 27.69626617431641 C 7.572123050689697 27.41351318359375 7.650123119354248 27.13563919067383 7.840248584747314 26.91626358032227 C 8.025498390197754 26.69689178466797 8.288748741149902 26.57501602172852 8.576373100280762 26.57501602172852 L 45.96765518188477 26.57501602172852 C 46.25527572631836 26.57501602172852 46.51852416992188 26.69689178466797 46.70378112792969 26.91626358032227 C 46.89390182495117 27.13563919067383 46.97190856933594 27.41351318359375 46.92803192138672 27.69626235961914 Z">
				</path>
			</svg>
			</div>
			';*/
print '</td>';

print '<td>';

print '<a href="../societe/card.php?action=create&leftmenu="> ' . load_fiche_titre($openficcli, $linkback, '') . '</a>';
print '</td>';

print '</tr>';
print '<tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>';
print '<tr >';
print '<td style="size: B4; color: darkred">';
print '<i class="fa fa-folder-open-o" aria-hidden="true"></i>';

print '<td>';
print '<a href="../compta/facture/card.php?action=create&leftmenu=">' . load_fiche_titre($creerficsouscription, $linkback, '') . '</a>';
print '</td>';
print '</tr>';
print '<tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr><tr></tr>';
print '<tr >';


print '<td style="size:100px ; color: darkred">';
print '<i class="fa fa-folder-open-o" aria-hidden="true"></i>';
print '<td>';
print '<a href="../compta/facture/list_facture_valide.php?leftmenu=customers_bills">' . load_fiche_titre($creerContrat, $linkback, '') . '</a>';
print '</td>';
print '</tr>';
print '</table>';
/* print '</form>';*/
print '<br>';


print '</div>
<div style="width: 75%;float: right;">';


$max = 5;

// STAT SUR LES PROSPECTS PAR COMMERCIALE
//
//
//print '<table class="noborder" width="50%">';
//print '<tr><td style="font-size: 15px; font-weight: bold; " colspan="4"><a href="#">' . $suivisouscription . '</a></td>';
//print '</tr>';
//
//print '<tr >';
//print '<th nowrap="nowrap"  align="left" ><a href="#">CLIENTS</a></th>';
//print '<th nowrap="nowrap" align="left"><a href="#">LOT CHOISI</a></th>';
//print '<th nowrap="nowrap" align="center"><a href="#">SOUSCRIPTION</a></th>';
//print '<th nowrap="nowrap" align="center" ><a href="#">RESERVATION</a></th>';
///*print '<th nowrap="nowrap" align="center"><a href="#">FRAIS DE DOSSIER</a></th>';
//print '<th nowrap="nowrap" align="left"><a href="#">DEPOT DE GARANTIE</a></th>';*/
//
//print '</tr>';
//
//
////requete recupération liste proposition commerciale
///*$sql = " SELECT  p.ref,p.tms,s.nom";
//$sql.= " FROM ".MAIN_DB_PREFIX."propal as p,".MAIN_DB_PREFIX."societe as s ";
//$sql.= " WHERE s.rowid=p.fk_soc AND p.fk_statut=1";
//$sql.= " ORDER BY p.ref ASC ";*/
//
//$sql = 'SELECT DISTINCT';
//$sql.= ' p.ref,p.rowid as proid, ';
//$sql.= ' s.rowid as socid, s.nom as name,';
//$sql.= ' pr.label,pr.rowid ,';
//$sql.= ' pd.fk_propal,pd.fk_product, ';
//$sql.= ' pe.statut_lot ';
//
//$parameters=array();
//$reshook=$hookmanager->executeHooks('printFieldListSelect', $parameters);    // Note that $action and $object may have been modified by hook
//$sql.=$hookmanager->resPrint;
//$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pd';
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as p ON pd.fk_propal=p.rowid";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=p.fk_soc";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as pr ON pr.rowid=pd.fk_product ";
//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON pr.rowid=pe.fk_object ";
//$sql.='  WHERE pe.statut_lot = 6';
//$sql.='  AND pr.fk_product_type = 0';
//$sql.= ' AND p.entity IN ('.getEntity('invoice').')';
//$sql.= 'GROUP BY label';
////var_dump($sql);die();
//
//
//
//
//$resql = $db->query($sql);
//if ($resql) {
//    $num = $db->num_rows($resql);
//    $i = 0;
//    if ($num) {
//        while ($i < $num) {
//            $obj = $db->fetch_object($resql);
//            $var = !$var;
//            if ($obj) {
//                // You can use here results
//                print "<tr " . $bc[$var] . ">";
//                print '<td nowrap="nowrap" align="left">';
//                print '<a style="font-weight: bold; font-size: 13px;" href=" societe/card.php?socid='.$obj->socid.'&save_lastsearch_values=1">' . $obj->name . '</a>';
//                print '</td>';
//
//                print '<td  nowrap="nowrap" align="left">';
//               // print   $obj->label;
//                print '<a style="font-weight: bold; font-size: 13px;" href=" product/card.php?id='.$obj->rowid.'&save_lastsearch_values=1">' . $obj->label . '</a>';
//                print '</td>';
//                print '<td nowrap="nowrap" align="center">';
//                print  '<a style="font-size: 10px; font-weight: bold; color:darkred;!important; border-radius: 10px; " href="../admin/facture.php?action=specimen&module=souscription&ref_facture='.$obj->ref.'" class="button" ><i class="fa fa-file-pdf" aria-hidden="true"></i> fichier de souscription</a>';
//                // print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0"  class="button >'.$transgrillePAjout.'</a>';
//                print '</td>';
//                print '<td nowrap="nowrap" align="center">';
//                print  '<a style="font-size: 10px; font-weight: bold;border-radius: 10px; " href="../admin/facture.php?action=specimen&module=contrat&ref_facture='.$obj->ref.'" class="button" ><i  class="fa fa-file-pdf" aria-hidden="true"></i> fichier de réservation</a>';
//                // print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0"  class="button >'.$transgrillePAjout.'</a>';
//                print '</td>';
//
//
//               /* print '<td nowrap="nowrap">';
//                print  '<a style="font-size: 10px; font-weight: bold; color:darkred;!important; border-radius: 10px; " href="../compta/facture/card.php?action=create&leftmenu=" class="button" >Créer la facture</a>';
//                // print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0"  class="button >'.$transgrillePAjout.'</a>';
//                print '</td>';
//
//                print '<td nowrap="nowrap">';
//
//                print  '<a style="font-size: 10px; font-weight: bold; color:darkred;!important; border-radius: 10px; href=" href="list_parProgramme.php?leftmenu=product&type=0&action=getGrille&rowid='.$obj->rowid.'" class="button">Créer sa fiche de souscription</a>';
//                print '</td>';
//
//                print '<td nowrap="nowrap" >';
//                print  '<a style="font-size: 10px; font-weight: bold; color:darkred;!important; border-radius: 10px; href=" href="list_parProgramme.php?leftmenu=product&type=0&rowid='.$obj->rowid.'" class="button">Créer le dossier de réservation</a>';
//
//                print '</td>';*/
//                print '</tr>';
//            }
//            $i++;
//
//        }
//    }
//}


/*
// requete pour les nombre de produits par programme
$sql = " SELECT  u.lastname,u.firstname,u.rowid  ";
$sql .= " FROM " . MAIN_DB_PREFIX . "user as u," . MAIN_DB_PREFIX . "usergroup_user as gr ";
$sql .= " WHERE u.rowid=gr.fk_user AND gr.fk_usergroup=1 ";
$sql .= " ORDER BY u.lastname ASC ";
// var_dump($sql);die;
///Affichage du resultat
$result = $db->query($sql);
//var_dump($result);die;
if ($result) {


    print '<tr >
        <th></th>';
    print '<th nowrap="nowrap"  align="left" colspan="2"><a href="#">CLIENTS</a></th>';
    print '<th nowrap="nowrap" align="center align="left""><a href="#">LOT CHOISI</a></th>';
    print '<th nowrap="nowrap" align="center"><a href="#">SOUSCRIPTION</a></th>';
    print '<th></th>';
    print '<th nowrap="nowrap" align="left" ><a href="#">RESERVATION</a></th>';
    print '<th></th>';
    print '<th nowrap="nowrap" align="left"><a href="#">FRAIS DE DOSSIER</a></th>';
    print '<th nowrap="nowrap" align="left"><a href="#">DEPOT DE GARANTIE</a></th>';

    print '</tr>';

    $var = True;
    while ($objp = $db->fetch_object($result)) {
        $var = !$var;
        // requete pour le nombre de prospet du commmercial
        $sql2 = " SELECT count(distinct s.rowid) as nb_pros ";
        $sql2 .= " FROM " . MAIN_DB_PREFIX . "societe as s, ";
        $sql2 .= " " . MAIN_DB_PREFIX . "societe_commerciaux as s_c  ";
        $sql2 .= " WHERE  s.rowid=s_c.fk_soc AND s_c.fk_user=" . $objp->rowid;
        $sql2 .= " AND s.client= 2 ";
        if ((isset($_POST['mois_com'])) && ($_POST['mois_com'] != "")) {
            $tab = explode(";", $ladate);
            $sql2 .= " AND month(s.datec)=" . $tab[0] . " AND year(s.datec) =" . $tab[1];
        }
        $result2 = $db->query($sql2);
        $objp2 = $db->fetch_object($result2);
        // requete pour le nombre de client du commmercial
        $sql3 = " SELECT count(distinct s.rowid) as nb_client ";
        $sql3 .= " FROM " . MAIN_DB_PREFIX . "societe as s, ";
        $sql3 .= " " . MAIN_DB_PREFIX . "societe_commerciaux as s_c  ";
        $sql3 .= " WHERE  s.rowid=s_c.fk_soc AND s_c.fk_user=" . $objp->rowid;
        $sql3 .= " AND s.client= 1 ";
        if ((isset($_POST['mois_com'])) && ($_POST['mois_com'] != "")) {
            $tab = explode(";", $ladate);
            $sql3 .= " AND month(s.datec)=" . $tab[0] . " AND year(s.datec) =" . $tab[1];
        }
        $result3 = $db->query($sql3);
        $objp3 = $db->fetch_object($result3);

        print '<tr  ' . $bc[$var] . '>';
        print '<td align="right" style="color: darkred; padding-right: 0px;!important;"><i class="fa fa-user "  aria-hidden="true"></td>';
        print '<td nowrap="nowrap" colspan="2">  ' . $objp->firstname . ' ' . $objp->lastname . '</td>';
        print '<td nowrap="nowrap" align="center">Lot 3</td>';
        print '<td nowrap="nowrap" align="right">souscription du 10/10/20</td>';
        print '<td  align="right" style="color: darkred; padding-right: 0px;!important;"><i class="fa fa-file-pdf" aria-hidden="true"></i></td>';
        print '<td nowrap="nowrap" align="left" > réservation du 10/10/20</td>';
        print '<td  align="right" style="color: darkred;padding-right: 0px;!important;"><i class="fa fa-file-pdf" aria-hidden="true"></td>';
        print '<td  nowrap="nowrap" align="left"> </i> 1000000</td>';
        print '<td  nowrap="nowrap" align="left">clé voiture</td>';
        print '</tr>';
    }
}*/

print "</table><br>";

print '</div>';
print '</div>';


$parameters = array('type' => $type, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook


// End of page
llxFooter();
$db->close();

