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

$type = GETPOST("typeprod", 'int');
if ($typeprod == '' && !$user->rights->produit->lire) $typeprod = '1'; // Force global page on service page only
if ($typeprod == '' && !$user->rights->service->lire) $typeprod = '0'; // Force global page on product page only

// Security check
if ($typeprod == '0') $result = restrictedArea($user, 'produit');
elseif ($typeprod == '1') $result = restrictedArea($user, 'service');
else $result = restrictedArea($user, 'produit|service|expedition');

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks'));

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('productindex'));

$product_static = new Product($db);


/*
 * View
 */

$transAreaType = "Produits / Lots / Services";
$transAreaCreate = $langs->trans("CreateCat");
$transAreaEncours = $langs->trans("programmeEncours");
$transAreaList = $langs->trans("CatList");
$transGesClient = $langs->trans("gestclient");
$transgrillePAjout = $langs->trans("AjoutGrille");
$transgrillePModif = $langs->trans("ModifGrille");
$transgrillePConsult = $langs->trans("ConsultGrille");
$transAreatouteList = $langs->trans("allProgramme");
$suivisouscription=$langs->trans("suiviSousciption");
$openficcli = $langs->trans("Openficcli");
$creerficsouscription = $langs->trans("creerficsouscription");
$creerContrat=$langs->trans("creerContrat");



$helpurl = '';

llxHeader("", $langs->trans("ProductsAndServices"), $helpurl);


//requete recupération liste catégorie
$sql1 = "SELECT DISTINCT COUNT(c.rowid) as nbprog";
$sql1.= " FROM ".MAIN_DB_PREFIX."categorie as c ";
$sql1 .= " WHERE c.entity IN (" . getEntity('category') . ")";
$sql1 .= " AND c.type = " . (int) $typeprod;

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
print load_fiche_titre($transAreaType, $linkback, 'categories');
print '</h3><hr width="100%"> </td></tr>';

print '<div style="width: 100%">';
print '<div style="width: 50%;float: left;" >';

/* print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';*/
/*  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';*/
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

print '<a href="../categories/card.php?action=create&typeprod=0&mainmenu=products&leftmenu=">'. load_fiche_titre($transAreaCreate, $linkback, '').'</a>';
print '</td>';
print '<td style="font-weight: bold;font-size: 20px; color: #8b0000">';
print $nb_programme;
print '</td>';
print '<td>';
print '<a href="../categories/index.php?leftmenu=cat&typeprod=0">'. load_fiche_titre($transAreaEncours, $linkback, '').'</a>';
print '</td>';
print '</tr>';
print '</table>';

/* print '</form>';*/
print '<br>';


print '</div>
<div style="width: 40%;float: left;">';


$max=5;

// STAT SUR LES PROSPECTS PAR COMMERCIALE

// Stat pour les tiers
$sql1 = "SELECT month(`datec`) as  mois_int  ,year(`datec`) as annee,date_format(`datec`,'%M') as mois ";
$sql1.= " FROM ".MAIN_DB_PREFIX."societe ";
$sql1.= " GROUP BY  annee, mois_int ";
$sql1.= " ORDER BY annee, mois_int DESC ";
$ladate=$_POST['mois_com'];

print '<table width="100%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr>';
print '<td nowrap="nowrap" >';
print '<a style="font-weight: bold; font-size: 15px;" href="#">'.$transAreaList.'</a>';
print '</td>';
print'</tr>';
print '<tr><td ><hr width="200%"></td></tr>';

//requete recupération liste catégorie
$sql = "SELECT DISTINCT c.rowid, c.label, ce.adresse, COUNT(cp.fk_product) as nblot";
$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categories_extrafields as ce, ".MAIN_DB_PREFIX."categorie_product as cp ";
$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
$sql .= " AND c.type = " . (int) $typeprod;
$sql .= " AND c.rowid = ce.fk_object" ;
$sql .= " AND c.rowid = cp.fk_categorie GROUP BY cp.fk_categorie" ;

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

          /*      print '<td nowrap="nowrap" align="left">';
                print '<a  href="categories/viewcat.php?id='.$obj->rowid.'">
             <div id="noun_buildings_1868750">	<svg class="Trac_44" viewBox="10 10 30 34" style="width: 15px;">
				<path fill="#8b0000" id="Trac_44" d="M 43.57500839233398 43.15000534057617 L 39.57734680175781 43.15000534057617 L 39.57734680175781 10.42500114440918 C 39.57734680175781 10.19008922576904 39.38705444335938 10.00000095367432 39.15234756469727 10.00000095367432 L 27.78359603881836 10.00000095367432 C 27.54889106750488 10.00000095367432 27.35859489440918 10.19008922576904 27.35859489440918 10.42500114440918 L 27.35859489440918 14.05659294128418 L 17.49539947509766 14.05659294128418 C 17.26069450378418 14.05659294128418 17.07039833068848 14.24668121337891 17.07039833068848 14.48159313201904 L 17.07039833068848 24.43339920043945 L 14.84765720367432 24.43339920043945 C 14.61295318603516 24.43339920043945 14.42265796661377 24.62348747253418 14.42265796661377 24.85840034484863 L 14.42265796661377 43.15000534057617 L 10.42500114440918 43.15000534057617 C 10.1902961730957 43.15000534057617 10.00000095367432 43.34009170532227 10.00000095367432 43.57500839233398 C 10.00000095367432 43.80991744995117 10.1902961730957 44.00000381469727 10.42500114440918 44.00000381469727 L 43.57500839233398 44.00000381469727 C 43.80971145629883 44.00000381469727 44.00000381469727 43.80991744995117 44.00000381469727 43.57500839233398 C 44.00000381469727 43.34009170532227 43.80971145629883 43.15000534057617 43.57500839233398 43.15000534057617 Z M 38.72735214233398 10.85000133514404 L 38.72735214233398 43.15000534057617 L 28.20859527587891 43.15000534057617 L 28.20859527587891 10.85000133514404 L 38.72735214233398 10.85000133514404 Z M 17.9203987121582 14.90659332275391 L 27.35859489440918 14.90659332275391 L 27.35859489440918 24.43339920043945 L 17.9203987121582 24.43339920043945 L 17.9203987121582 14.90659332275391 Z M 15.2726583480835 25.28339958190918 L 27.35859489440918 25.28339958190918 L 27.35859489440918 43.15000534057617 L 15.2726583480835 43.15000534057617 L 15.2726583480835 25.28339958190918 Z">
				</path>
			</svg>
			</div>

        ';
                print '</td>';*/
                print '<td nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../categories/viewcat.php?id='.$obj->rowid.'">' . $obj->label . '</a>';
                //print   $obj->label;
                print '</td>';

               /* print '<td nowrap="nowrap" align="left">';
                print   $obj->label;
                print '</td>';*/

                print '<td nowrap="nowrap" align="left">';
                print   $obj->nblot.' lot(s)';
                print '</td>';

                print '<td nowrap="nowrap" align="right">';
                print   $obj->adresse;
                print '</td>';

                print '</tr>';
            }
            $i++;

        }
    }
}

print '</tr>';


/*print '<tr aria-rowspan="10">';
print '<td align="right" colspan="6">';
print '<a href="../categories/index.php?leftmenu=cat&type=0">'.$transAreatouteList.'</a>';
print '</td>';
print '</tr>';*/
print '</table>';

print '</div>';
print '</div>';


print '<div class="fichecenter"><div class="fichethirdleft">';
print '<br/><br/><br/>';
print '<table  width="100%">';
print '<tr>';
print '<td >';
print '<a style="font-weight: bold; font-size: 15px;" href="#">'.$transAreaList.'</a>';
print '</td>';
print'</tr>';
print '<tr><td ><hr width="200%"></td></tr>';
print '</table>';
print '<br/><br/>';

print '<table width="100%" cellpadding="0" cellspacing="0" border="0"> ';
print '<tr >';
print '<td ></td>';
print '<td nowrap="nowrap" align="left"   ><a style="font-weight: bold; font-size: 13px;" href="#">'.$langs->trans('PROGRAMMES').'</a></td>';
print '<td colspan="2" nowrap="nowracolspan="2"p"  align="left"><a style="font-weight: bold; font-size: 13px;" href="#">'.$langs->trans('NumberOflots').'</a></td>';
print '<td colspan="2" nowrap="nowrap"  align="right"  ><a style="font-weight: bold; font-size: 13px;" href="#">'.$langs->trans('ADRESSE').'</a></td>';

//requete recupération liste catégorie
$sql = "SELECT DISTINCT c.rowid, c.label, ce.adresse, c.type, COUNT(cp.fk_product) as nblot";
$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c, ".MAIN_DB_PREFIX."categories_extrafields as ce, ".MAIN_DB_PREFIX."categorie_product as cp ";
$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
//$sql .= " AND c.type = " . (int) $typeprod;
$sql .= " AND c.rowid = ce.fk_object" ;
$sql .= " AND c.rowid = cp.fk_categorie GROUP BY cp.fk_categorie" ;
//var_dump($sql);die;
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
              /*  print '<td nowrap="nowrap" align="left">';
                print '
             <div id="noun_buildings_1868750">	<svg class="Trac_44" viewBox="10 10 30 34" style="width: 15px;">
				<path fill="#8b0000" id="Trac_44" d="M 43.57500839233398 43.15000534057617 L 39.57734680175781 43.15000534057617 L 39.57734680175781 10.42500114440918 C 39.57734680175781 10.19008922576904 39.38705444335938 10.00000095367432 39.15234756469727 10.00000095367432 L 27.78359603881836 10.00000095367432 C 27.54889106750488 10.00000095367432 27.35859489440918 10.19008922576904 27.35859489440918 10.42500114440918 L 27.35859489440918 14.05659294128418 L 17.49539947509766 14.05659294128418 C 17.26069450378418 14.05659294128418 17.07039833068848 14.24668121337891 17.07039833068848 14.48159313201904 L 17.07039833068848 24.43339920043945 L 14.84765720367432 24.43339920043945 C 14.61295318603516 24.43339920043945 14.42265796661377 24.62348747253418 14.42265796661377 24.85840034484863 L 14.42265796661377 43.15000534057617 L 10.42500114440918 43.15000534057617 C 10.1902961730957 43.15000534057617 10.00000095367432 43.34009170532227 10.00000095367432 43.57500839233398 C 10.00000095367432 43.80991744995117 10.1902961730957 44.00000381469727 10.42500114440918 44.00000381469727 L 43.57500839233398 44.00000381469727 C 43.80971145629883 44.00000381469727 44.00000381469727 43.80991744995117 44.00000381469727 43.57500839233398 C 44.00000381469727 43.34009170532227 43.80971145629883 43.15000534057617 43.57500839233398 43.15000534057617 Z M 38.72735214233398 10.85000133514404 L 38.72735214233398 43.15000534057617 L 28.20859527587891 43.15000534057617 L 28.20859527587891 10.85000133514404 L 38.72735214233398 10.85000133514404 Z M 17.9203987121582 14.90659332275391 L 27.35859489440918 14.90659332275391 L 27.35859489440918 24.43339920043945 L 17.9203987121582 24.43339920043945 L 17.9203987121582 14.90659332275391 Z M 15.2726583480835 25.28339958190918 L 27.35859489440918 25.28339958190918 L 27.35859489440918 43.15000534057617 L 15.2726583480835 43.15000534057617 L 15.2726583480835 25.28339958190918 Z">
				</path>
			</svg>
			</div>

        ';
                print '</td>';
                print '<td nowrap="nowrap" align="left">';
                print   $obj->label;
                print '</td>';*/
                print '<td nowrap="nowrap" align="left">';
                print '<a  href="../categories/viewcat.php?id='.$obj->rowid.'">
             <div id="noun_buildings_1868750">	<svg class="Trac_44" viewBox="10 10 30 34" style="width: 15px;">
				<path fill="#8b0000" id="Trac_44" d="M 43.57500839233398 43.15000534057617 L 39.57734680175781 43.15000534057617 L 39.57734680175781 10.42500114440918 C 39.57734680175781 10.19008922576904 39.38705444335938 10.00000095367432 39.15234756469727 10.00000095367432 L 27.78359603881836 10.00000095367432 C 27.54889106750488 10.00000095367432 27.35859489440918 10.19008922576904 27.35859489440918 10.42500114440918 L 27.35859489440918 14.05659294128418 L 17.49539947509766 14.05659294128418 C 17.26069450378418 14.05659294128418 17.07039833068848 14.24668121337891 17.07039833068848 14.48159313201904 L 17.07039833068848 24.43339920043945 L 14.84765720367432 24.43339920043945 C 14.61295318603516 24.43339920043945 14.42265796661377 24.62348747253418 14.42265796661377 24.85840034484863 L 14.42265796661377 43.15000534057617 L 10.42500114440918 43.15000534057617 C 10.1902961730957 43.15000534057617 10.00000095367432 43.34009170532227 10.00000095367432 43.57500839233398 C 10.00000095367432 43.80991744995117 10.1902961730957 44.00000381469727 10.42500114440918 44.00000381469727 L 43.57500839233398 44.00000381469727 C 43.80971145629883 44.00000381469727 44.00000381469727 43.80991744995117 44.00000381469727 43.57500839233398 C 44.00000381469727 43.34009170532227 43.80971145629883 43.15000534057617 43.57500839233398 43.15000534057617 Z M 38.72735214233398 10.85000133514404 L 38.72735214233398 43.15000534057617 L 28.20859527587891 43.15000534057617 L 28.20859527587891 10.85000133514404 L 38.72735214233398 10.85000133514404 Z M 17.9203987121582 14.90659332275391 L 27.35859489440918 14.90659332275391 L 27.35859489440918 24.43339920043945 L 17.9203987121582 24.43339920043945 L 17.9203987121582 14.90659332275391 Z M 15.2726583480835 25.28339958190918 L 27.35859489440918 25.28339958190918 L 27.35859489440918 43.15000534057617 L 15.2726583480835 43.15000534057617 L 15.2726583480835 25.28339958190918 Z">
				</path>
			</svg>
			</div>

        ';
                print '</td>';
                print '<td nowrap="nowrap" align="left">';
                print '<a style="font-weight: bold; font-size: 13px;" href="../categories/viewcat.php?id='.$obj->rowid.'&type='.$obj->type.'">' . $obj->label . '</a>';
                //print   $obj->label;
                print '</td>';

                print '<td colspan="2" nowrap="nowrap" align="left">';
                print   $obj->nblot.' lot(s)';
                print '</td>';

                print '<td colspan="2" nowrap="nowrap" align="right">';
                print   $obj->adresse;
                print '</td>';
//nowrap="nowrap" pour avoir sur la meme ligne
                print '<td nowrap="nowrap">';

              print  '<a style="font-size: 7px; font-weight: bold; color:darkred;!important; border-radius: 10px; " href="list_parProgramme.php?leftmenu=product&type=0&rowid='.$obj->rowid.'" class="button" >'.$transgrillePAjout.'</a>';
               // print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0"  class="button >'.$transgrillePAjout.'</a>';
                print '</td>';

               /* print '<td nowrap="nowrap">';
               /* print '<a href="liste.php?type=0&amp;tosell=0&amp;tobuy=0">'.$transgrillePModif.'</a>';
                print  '<a style="font-size: 7px; color:darkred;!important; border-radius: 10px; href=" href="list_parProgramme.php?leftmenu=product&type=0&action=getGrille&rowid='.$obj->rowid.'" class="button">'.$transgrillePModif.'</a>';
                print '</td>';*/

                print '<td nowrap="nowrap" >';
                print  '<a style="font-size: 7px; color:darkred;!important; border-radius: 10px; href=" href="list_parProgramme.php?leftmenu=product&type=0&rowid='.$obj->rowid.'" class="button">'.$transgrillePConsult.'</a>';
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
print '<br/><br/>' ;
print '<div align="right" style="width: auto; margin-right: 0px;" >';
print '<a style="font-size: 15px;" href="../categories/index.php?leftmenu=cat&typeprod=0">'.$transAreatouteList.'</a>';
print '</div>';


$parameters = array('type' => $typeprod, 'user' => $user);
$reshook = $hookmanager->executeHooks('dashboardProductsServices', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();


