<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
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
 *	\file       htdocs/compta/index.php
 *	\ingroup    compta
 *	\brief      Main page of accountancy area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->commande->enabled))
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->tax->enabled))
	require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

// L'espace compta/treso doit toujours etre actif car c'est un espace partage
// par de nombreux modules (banque, facture, commande a facturer, etc...) independamment
// de l'utilisation de la compta ou non. C'est au sein de cet espace que chaque sous fonction
// est protegee par le droit qui va bien du module concerne.
//if (!$user->rights->compta->general->lire)
//  accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));
if (! empty($conf->commande->enabled))
	$langs->load("orders");

$action=GETPOST('action', 'alpha');
$bid=GETPOST('bid', 'int');

// Security check
$socid='';
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$max=3;

$hookmanager->initHooks(array('invoiceindex'));

/*
 * Actions
 */


/*
 * View
 */

$now=dol_now();

$facturestatic=new Facture($db);
$facturesupplierstatic=new FactureFournisseur($db);

$form = new Form($db);
$formfile = new FormFile($db);
$thirdpartystatic = new Societe($db);

llxHeader("", $langs->trans("AccountancyTreasuryArea"));

print load_fiche_titre($langs->trans("AccountancyTreasuryArea"), '', 'title_accountancy.png');


print '<div class="fichecenter">

<div class="fichethirdleft">';


if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search customer invoices
    if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
    {
    	$listofsearchfields['search_invoice']=array('text'=>'CustomerInvoice');
    }
    // Search supplier invoices
    if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
    {
    	$listofsearchfields['search_supplier_invoice']=array('text'=>'SupplierInvoice');
    }
    if (! empty($conf->don->enabled) && $user->rights->don->lire)
    {
    	$langs->load("donations");
    	$listofsearchfields['search_donation']=array('text'=>'Donation');
    }

    if (count($listofsearchfields))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<table class="noborder nohover centpercent">';
    	$i=0;
    	foreach($listofsearchfields as $key => $value)
    	{
    		if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    		print '<tr '.$bc[false].'>';
    		print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'"></td>';
    		if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
    		print '</tr>';
    		$i++;
    	}
    	print '</table>';
    	print '</form>';
    	print '<br>';
    }
}


/**
 * Draft customers invoices
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    $sql = "SELECT f.rowid, f.ref, f.datef as date, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.ref_client";
    $sql.= ", f.type";
    $sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid, s.email";
    $sql.= ", s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity IN (".getEntity('invoice').")";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereCustomerDraft', $parameters);
	$sql.=$hookmanager->resPrint;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("CustomersDraftInvoices").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$facturestatic->id=$obj->rowid;
				$facturestatic->ref=$obj->ref;
				$facturestatic->date=$db->jdate($obj->date);
				$facturestatic->type=$obj->type;
				$facturestatic->total_ht=$obj->total_ht;
				$facturestatic->total_tva=$obj->total_tva;
				$facturestatic->total_ttc=$obj->total_ttc;
				$facturestatic->ref_client=$obj->ref_client;

				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->email=$obj->email;
				$companystatic->client = 1;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td class="nowrap">';
				print $facturestatic->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap">';
				print $companystatic->getNomUrl(1, 'customer', 16);
				print '</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/**
 * Draft suppliers invoices
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$sql  = "SELECT f.ref, f.rowid, f.total_ht, f.total_tva, f.total_ttc, f.type, f.ref_supplier";
	$sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid, s.email";
    $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
    $sql.= ", cc.rowid as country_id, cc.code as country_code";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity IN (".getEntity('invoice').')';
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereSupplierDraft', $parameters);
	$sql.=$hookmanager->resPrint;
	$resql = $db->query($sql);

	if ( $resql )
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("SuppliersDraftInvoices").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$facturesupplierstatic->ref=$obj->ref;
				$facturesupplierstatic->id=$obj->rowid;
				$facturesupplierstatic->total_ht=$obj->total_ht;
				$facturesupplierstatic->total_tva=$obj->total_tva;
				$facturesupplierstatic->total_ttc=$obj->total_ttc;
				$facturesupplierstatic->ref_supplier=$obj->ref_supplier;
				$facturesupplierstatic->type=$obj->type;

				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->email=$obj->email;
				$companystatic->country_id=$obj->country_id;
				$companystatic->country_code=$obj->country_code;
				$companystatic->fournisseur = 1;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td class="nowrap">';
				print $facturesupplierstatic->getNomUrl(1, '', 16);
				print '</td>';
				print '<td>';
				print $companystatic->getNomUrl(1, 'supplier', 16);
				print '</td>';
				print '<td class="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


print '</div><div class="fichetwothirdright">
<div class="ficheaddleft">';

/*// CHIFFRE AFFAIRE
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre("Chiffre d'Affaire par Programme et par mois","","","","","","","");
$annee=2020;
$tab= ListeMoisFacture($annee);
$taille=sizeof($tab);
$tot=array();
$i=0;
for($j=0;$j<$taille;$j++)
{
    print_liste_field_titre(" ","","","","",'align="right"',"","");
}
print "</tr>\n";
print '<tr class="liste_titre">';
print '<td ><a href="#">Programme</a></td>';
$tab= ListeMoisFacture($annee);
foreach($tab as $source)
{
    $tot[$i]=0;
    print '<td align="right" ><a href="#" >'.$langs->trans($source[0]).' '.$source[2].'</a></td>';
    $i++;
}
print "</tr>\n";
// requete pour les programme
$sql = " SELECT  DISTINCT cat.label as lib, cat.rowid as id_cat  ";
$sql.= " FROM  ".MAIN_DB_PREFIX."product as p,";
$sql.= " ".MAIN_DB_PREFIX."categorie_product as cat_prod, ".MAIN_DB_PREFIX."categorie as cat ";
$sql.= " WHERE  p.rowid=cat_prod.fk_product ";
$sql.= " AND cat_prod.fk_categorie=cat.rowid AND cat.type=0 AND  p.fk_product_type != 1 ";
///Affichage du resultat
$result = $db->query($sql);
if ($result)
{

    $var=True;
    while ($objp = $db->fetch_object($result))
    {
        $var=!$var;
        print '<tr  '.$bc[$var].'><td >'.$objp->lib.'</td>';
        $i=0;
        $j=0;
        // requete pour le CA du programme et par mois
        foreach($tab as $source)
        {
            $sql2 = " SELECT DISTINCT f.rowid ";
            $sql2.= " FROM ".MAIN_DB_PREFIX."facture as f ";
            $sql2.= " WHERE f.rowid in ( SELECT fac.rowid   FROM ".MAIN_DB_PREFIX."facturedet as detail,".MAIN_DB_PREFIX."product as pr,";
            $sql2.= " ".MAIN_DB_PREFIX."categorie_product as cat_prod, ".MAIN_DB_PREFIX."categorie as cat,".MAIN_DB_PREFIX."facture as fac ";
            $sql2.= " WHERE pr.rowid=cat_prod.fk_product AND fac.rowid=detail.fk_facture ";
            $sql2.= " AND detail.fk_product=pr.rowid   ";
            $sql2.= " AND fac.type in (0,1) AND fac.fk_statut in ( 1,2) ";
            $sql2.= " AND cat_prod.fk_categorie=cat.rowid AND cat.type=0 AND  pr.fk_product_type != 1  ";
            $sql2.= " AND cat.rowid=".$objp->id_cat." AND month(fac.datef)=".$source[1]." AND year(fac.datef) =".$source[2]." )";
            $result2 = $db->query($sql2);
            if ($result2)
            {
                $i = 0;
                $tot1=0;
                $tot2=0;
                $nbr = $db->num_rows($result2);
                if ($nbr > 0)
                {
                    while ($i < $nbr)
                    {
                        $objp2 = $db->fetch_object($result2);
                        //$valeur=$objp2->total_ht + $valeur;
                        $sql3 = "SELECT f.total as total_ht,f.total_ttc";
                        $sql3.= " FROM ".MAIN_DB_PREFIX."societe as s";
                        $sql3.= ",".MAIN_DB_PREFIX."facture as f ";
                        $sql3.= " WHERE f.fk_soc = s.rowid ";
                        $sql3.= " AND s.entity = ".$conf->entity;
                        $sql3.= " AND f.rowid =".$objp2->rowid." ";
                        $result3 = $db->query($sql3);
                        $objp3 = $db->fetch_object($result3);
                        $tot2=$tot2+$objp3->total_ht;
                        $i++;
                    }
                }
            }
            print '<td align="right">'.number_format($tot2,0,',',' ').'</td>';
            $tot[$j]=$tot2+$tot[$j];
            $j++;
        }
        print '</tr>';
    }
}
// le total
print '<tr  '.$bc[!$var].'><td><a href="#" >Total</a></td>';
foreach($tot as $source)
{
    print '<td align="right"><a href="#" >'.price($source).'</a></td>';
}
print '</tr>';
print '</table><br>';

// FIN CHIFFRE AFFAIRE*/

// FIN STAT STATUS

// STAT SUR LES MONTANTS  DES FACTURES PAR PROGRAMME
/*if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
// Stat pour les tiers
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td >Montant des factures par programme</td>';
    print '<td>&nbsp;</td>';
    print '<td align="right">&nbsp;</td>';
    print '</tr>';
// requete pour les nombre de produits par programme
    $sql = " SELECT  cat.label as lib, cat.rowid as id_cat  ";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie as cat ";
    $sql.= " WHERE cat.type=0 ";
///Affichage du resultat
    $result = $db->query($sql);
    if ($result)
    {
        print '<tr class="liste_titre"><td ><a href="#">Programme</a></td>';
        print '<td align="right"><a href="#">Encaissé</a></td>';
        print '<td align="right"><a href="#">Total</a></td>';
        print '</tr>';
        $tot1=0;
        $tot2=0;
        $var=True;
        while ($objp = $db->fetch_object($result))
        {
            $var=!$var;
            // requete pour le montant total du programmme
            $val1=0;
            $val2=0;
            $sql2 = " SELECT DISTINCT f.rowid ";
            $sql2.= " FROM ".MAIN_DB_PREFIX."facture as f ";
            $sql2.= " WHERE f.rowid in ( SELECT fac.rowid   FROM ".MAIN_DB_PREFIX."facturedet as detail,".MAIN_DB_PREFIX."product as pr,";
            $sql2.= " ".MAIN_DB_PREFIX."categorie_product as cat_prod, ".MAIN_DB_PREFIX."categorie as cat,".MAIN_DB_PREFIX."facture as fac ";
            $sql2.= " WHERE pr.rowid=cat_prod.fk_product AND fac.rowid=detail.fk_facture ";
            $sql2.= " AND detail.fk_product=pr.rowid   ";
            $sql2.= " AND f.type in (0,1) AND f.fk_statut = 1";
            $sql2.= " AND cat_prod.fk_categorie=cat.rowid AND cat.type=0 AND  pr.fk_product_type != 1  ";
            $sql2.= " AND cat.rowid=".$objp->id_cat." )";
            $result2 = $db->query($sql2);
            if ($result2)
            {
                $i = 0;
                $nbr = $db->num_rows($result2);
                if ($nbr > 0)
                {
                    while ($i < $nbr)
                    {
                        $objp2 = $db->fetch_object($result2);
                        //$valeur=$objp2->total_ht + $valeur;
                        $sql3 = "SELECT f.total as total_ht,f.total_ttc";
                        $sql3.= ", sum(pf.amount) as am";
                        $sql3.= " FROM ".MAIN_DB_PREFIX."societe as s";
                        $sql3.= ",".MAIN_DB_PREFIX."facture as f ";
                        $sql3.= " , ".MAIN_DB_PREFIX."paiement_facture as pf  ";
                        $sql3.= " WHERE f.rowid=pf.fk_facture ";
                        $sql3.= " AND f.fk_soc = s.rowid ";
                        $sql3.= " AND s.entity = ".$conf->entity;
                        $sql3.= " AND f.type in (0,1) AND f.fk_statut = 1";
                        $sql3.= " AND f.rowid =".$objp2->rowid." ";
                        $result3 = $db->query($sql3);
                        $objp3 = $db->fetch_object($result3);
                        $tot1=$tot1+$objp3->am;
                        $tot2=$tot2+$objp3->total_ht;
                        $val1=$val1+$objp3->am;
                        $val2=$val2+$objp3->total_ht;
                        $i++;
                    }


                }
                $db->free();
            }

            print '<tr  '.$bc[$var].'><td >'.$objp->lib.'</td>';
            print '<td align="right"><a href="http://www.sabluxgroup.com/gescom/htdocs/compta/facture/ca_programme.php?id_cat='.$objp->id_cat.'" target="_blank">'.number_format($val1,0,',',' ').'</a></td>';
            print '<td align="right"><a href="http://www.sabluxgroup.com/gescom/htdocs/compta/facture/ca_programme.php?id_cat='.$objp->id_cat.'" target="_blank">'.number_format($val2,0,',',' ').'</a></td>';
            print '</tr>';
            //$tot1=$tot1+$objp3->tot;
            //$tot2=$tot2+$objp2->tot;
        }
        print '<tr  '.$bc[!$var].'><td ><a href="#">Total</a></td>';
        print '<td align="right"><a href="#">'.number_format($tot1,0,',',' ').'</a></td>';
        print '<td align="right"><a href="#">'.number_format($tot2,0,',',' ').'</a></td>';
        print '</tr>';
    }

    print "</table>";
}*/
// FIN STAT STATUS



// Last trips and expenses
/*if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
    include_once(DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php');

    $langs->load("boxes");

    $sql = "SELECT u.rowid as uid, u.firstname, d.rowid, d.dated as date, d.tms as dm, d.km";
    $sql.= " FROM ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE u.rowid = d.fk_user";
    $sql.= " AND d.entity = ".$conf->entity;
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND d.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)	$sql.= " AND d.fk_soc = ".$socid;
    $sql.= $db->order("d.tms","DESC");
    $sql.= $db->plimit($max, 0);

    $result = $db->query($sql);

    if ($result)
    {
        $var=false;
        $num = $db->num_rows($result);

        $i = 0;

        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",$max).'</td>';
        print '<td align="right">'.$langs->trans("FeesKilometersOrAmout").'</td>';
        print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
        print '<td width="16">&nbsp;</td>';
        print '</tr>';
        if ($num)
        {
            $total_ttc = $totalam = $total = 0;

            $deplacementstatic=new Deplacement($db);
            $userstatic=new User($db);
            while ($i < $num && $i < $max)
            {
                $objp = $db->fetch_object($result);
                $deplacementstatic->ref=$objp->rowid;
                $deplacementstatic->id=$objp->rowid;
                $userstatic->id=$objp->uid;
                $userstatic->nom=$objp->name;
                $userstatic->prenom=$objp->firstname;
                print '<tr '.$bc[$var].'>';
                print '<td>'.$deplacementstatic->getNomUrl(1).'</td>';
                print '<td>'.$userstatic->getNomUrl(1).'</td>';
                print '<td align="right">'.$objp->km.'</td>';
                print '<td align="right">'.dol_print_date($db->jdate($objp->dm),'day').'</td>';
                print '<td>'.$deplacementstatic->LibStatut($objp->fk_statut,3).'</td>';
                print '</tr>';
                $var=!$var;
                $i++;
            }

        }
        else
        {
            print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
        }
        print '</table><br>';
    }
    else dol_print_error($db);
}
*/

/**
 * Social contributions to pay
 */
/*if ($conf->tax->enabled && $user->rights->tax->charges->lire)
{
    if (!$socid)
    {
        $chargestatic=new ChargeSociales($db);

        $sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
        $sql.= " cc.libelle,";
        $sql.= " SUM(pc.amount) as sumpaid";
        $sql.= " FROM (".MAIN_DB_PREFIX."c_chargesociales as cc, ".MAIN_DB_PREFIX."chargesociales as c)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = c.rowid";
        $sql.= " WHERE c.fk_type = cc.id";
        $sql.= " AND c.entity = ".$conf->entity;
        $sql.= " AND c.paye = 0";
        $sql.= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

        $resql = $db->query($sql);
        if ( $resql )
        {
            $var = false;
            $num = $db->num_rows($resql);

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans("ContributionsToPay").($num?' ('.$num.')':'').'</td>';
            print '<td align="center">'.$langs->trans("DateDue").'</td>';
            print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
            print '<td align="right">'.$langs->trans("Paid").'</td>';
            print '<td>&nbsp;</td>';
            print '</tr>';
            if ($num)
            {
                $i = 0;
                $tot_ttc=0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    print "<tr $bc[$var]>";
                    $chargestatic->id=$obj->rowid;
                    $chargestatic->ref=$obj->libelle;
                    $chargestatic->lib=$obj->libelle;
                    $chargestatic->paye=$obj->paye;
                    print '<td>'.$chargestatic->getNomUrl(1).'</td>';
                    print '<td align="center">'.dol_print_date($obj->date_ech,'day').'</td>';
                    print '<td align="right">'.price($obj->amount).'</td>';
                    print '<td align="right">'.price($obj->sumpaid).'</td>';
                    print '<td align="center">'.$chargestatic->getLibStatut(3).'</td>';
                    print '</tr>';
                    $tot_ttc+=$obj->amount;
                    $var = !$var;
                    $i++;
                }

                print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Total").'</td>';
                print '<td align="right">'.price($tot_ttc).'</td>';
                print '<td align="right"></td>';
                print '<td align="right">&nbsp</td>';
                print '</tr>';
            }
            else
            {
                print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
            }
            print "</table><br>";
            $db->free($resql);
        }
        else
        {
            dol_print_error($db);
        }
    }
}*/

/*
 * Customers orders to be billed
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{

    $commandestatic=new Commande($db);
    $langs->load("orders");

    $sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc,";
    $sql.= " s.nom, s.rowid as socid,";
    $sql.= " c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.total_ttc";
    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";

    if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";

    $sql.= ", ".MAIN_DB_PREFIX."commande as c";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
    $sql.= " WHERE c.fk_soc = s.rowid";
    $sql.= " AND c.entity = ".$conf->entity;
    if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)	$sql.= " AND c.fk_soc = ".$socid;
    $sql.= " AND c.fk_statut = 3";
    $sql.= " AND c.facture = 0";
    $sql.= " GROUP BY s.nom, s.rowid, c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.total_ttc";
    //var_dump($sql);die;
    $resql = $db->query($sql);
    if ( $resql )
    {
        $var=false;
        $num = $db->num_rows($resql);

        if ($num)
        {
            $i = 0;
            print '<table class="noborder" width="100%">';
            print "<tr class=\"liste_titre\">";
            print '<td colspan="2">'.$langs->trans("OrdersToBill").' <a href="'.DOL_URL_ROOT.'/commande/liste.php?status=3&afacturer=1">('.$num.')</a></td>';
            if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
            print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
            print '<td align="right">'.$langs->trans("ToBill").'</td>';
            print '<td align="center" width="16">&nbsp;</td>';
            print '</tr>';
            $tot_ht=$tot_ttc=$tot_tobill=0;
            $societestatic = new Societe($db) ;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);

                print "<tr $bc[$var]>";
                print '<td nowrap="nowrap">';

                $commandestatic->id=$obj->rowid;
                $commandestatic->ref=$obj->ref;

                print '<table class="nobordernopadding"><tr class="nocellnopadd">';
                print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
                print $commandestatic->getNomUrl(1);
                print '</td>';
                print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
                print '&nbsp;';
                print '</td>';
                print '<td width="16" align="right" class="nobordernopadding">';
                $filename=dol_sanitizeFileName($obj->ref);
                $filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
                $formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
                print '</td></tr></table>';

                print '</td>';

                print '<td align="left">';
                $societestatic->id=$obj->socid;
                $societestatic->nom=$obj->nom;
                $societestatic->client=1;
                print $societestatic->getNomUrl(1,'customer',44);
                print '</a></td>';
                if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($obj->total_ht).'</td>';
                print '<td align="right">'.price($obj->total_ttc).'</td>';
                print '<td align="right">'.price($obj->total_ttc-$obj->tot_fttc).'</td>';
                print '<td>'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,3).'</td>';
                print '</tr>';
                $tot_ht += $obj->total_ht;
                $tot_ttc += $obj->total_ttc;
                //print "x".$tot_ttc."z".$obj->tot_fttc;
                $tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
                $i++;
                $var=!$var;
            }

            print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
            if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($tot_ht).'</td>';
            print '<td align="right">'.price($tot_ttc).'</td>';
            print '<td align="right">'.price($tot_tobill).'</td>';
            print '<td>&nbsp;</td>';
            print '</tr>';
            print '</table><br>';
        }
        $db->free($resql);
    }
    else
    {
        dol_print_error($db);
    }
}


// FIN STAT STATUS


// Latest modified customer invoices
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$langs->load("boxes");
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.fk_statut, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
	$sql.= ", f.date_lim_reglement as datelimite";
	$sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid";
    $sql.= ", s.code_client, s.code_compta, s.email";
    $sql.= ", cc.rowid as country_id, cc.code as country_code";
    $sql.= ", sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays, ".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc";
	$sql.= " AND f.entity IN (".getEntity('invoice').")";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereCustomerLastModified', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= " GROUP BY f.rowid, f.ref, f.fk_statut, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
	$sql.= " s.nom, s.rowid, s.code_client, s.code_compta, s.email,";
	$sql.= " cc.rowid, cc.code";
	$sql.= " ORDER BY f.tms DESC ";
	$sql.= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

        print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastCustomerBills", $max).'</th>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				$facturestatic->ref=$obj->ref;
				$facturestatic->id=$obj->rowid;
				$facturestatic->total_ht=$obj->total_ht;
				$facturestatic->total_tva=$obj->total_tva;
				$facturestatic->total_ttc=$obj->total_ttc;
				$facturestatic->statut = $obj->fk_statut;
				$facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);
				$facturestatic->type=$obj->type;

				$thirdpartystatic->id=$obj->socid;
				$thirdpartystatic->name=$obj->name;
				$thirdpartystatic->email=$obj->email;
				$thirdpartystatic->country_id=$obj->country_id;
				$thirdpartystatic->country_code=$obj->country_code;
				$thirdpartystatic->email=$obj->email;
				$thirdpartystatic->client=1;
				$thirdpartystatic->code_client = $obj->code_client;
				//$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
				$thirdpartystatic->code_compta = $obj->code_compta;
				//$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $facturestatic->getNomUrl(1, '');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($facturestatic->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td class="left">';
                print $thirdpartystatic->getNomUrl(1, 'customer', 44);
				print '</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="nowrap right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye, $obj->fk_statut, 3, $obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total_ht;
				$totalam +=  $obj->am;

				$i++;
			}
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}



// Last modified supplier invoices
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$langs->load("boxes");
	$facstatic=new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.tms, ff.paye";
	$sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid";
    $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
	$sql.= ", SUM(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	$sql.= " AND ff.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereSupplierLastModified', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.tms, ff.paye,";
	$sql.= " s.nom, s.rowid, s.code_fournisseur, s.code_compta_fournisseur";
	$sql.= " ORDER BY ff.tms DESC ";
	$sql.= $db->plimit($max, 0);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

        print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastSupplierBills", $max).'</th>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print "</tr>\n";
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$facstatic->ref=$obj->ref;
				$facstatic->id = $obj->rowid;
				$facstatic->total_ht = $obj->total_ht;
				$facstatic->total_tva = $obj->total_tva;
				$facstatic->total_ttc = $obj->total_ttc;

				$thirdpartystatic->id=$obj->socid;
				$thirdpartystatic->name=$obj->name;
				$thirdpartystatic->fournisseur=1;
				//$thirdpartystatic->code_client = $obj->code_client;
				$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
				//$thirdpartystatic->code_compta = $obj->code_compta;
				$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td>';
				print $facstatic->getNomUrl(1, '');
				print '</td>';
				print '<td>';
				print $thirdpartystatic->getNomUrl(1, 'supplier', 44);
				print '</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye, $obj->fk_statut, 3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc +=  $obj->total_ttc;
				$totalam +=  $obj->am;
				$i++;
			}
		}
		else
		{
			$colspan=5;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
	}
	else
	{
		dol_print_error($db);
	}
}
// Last trips and expenses
if ($conf->deplacement->enabled && $user->rights->deplacement->lire)
{
    include_once(DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php');

    $langs->load("boxes");

    $sql = "SELECT u.rowid as uid, u.name, u.firstname, d.rowid, d.dated as date, d.tms as dm, d.km";
    $sql.= " FROM ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
    $sql.= " WHERE u.rowid = d.fk_user";
    $sql.= " AND d.entity = ".$conf->entity;
    if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND d.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    if ($socid)	$sql.= " AND d.fk_soc = ".$socid;
    $sql.= $db->order("d.tms","DESC");
    $sql.= $db->plimit($max, 0);

    $result = $db->query($sql);
    if ($result)
    {
        $var=false;
        $num = $db->num_rows($result);

        $i = 0;

        print '<table class="noborder" width="100%">';
        print '<tr class="liste_titre">';
        print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",$max).'</td>';
        print '<td align="right">'.$langs->trans("FeesKilometersOrAmout").'</td>';
        print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
        print '<td width="16">&nbsp;</td>';
        print '</tr>';
        if ($num)
        {
            $total_ttc = $totalam = $total = 0;

            $deplacementstatic=new Deplacement($db);
            $userstatic=new User($db);
            while ($i < $num && $i < $max)
            {
                $objp = $db->fetch_object($result);
                $deplacementstatic->ref=$objp->rowid;
                $deplacementstatic->id=$objp->rowid;
                $userstatic->id=$objp->uid;
                $userstatic->nom=$objp->name;
                $userstatic->prenom=$objp->firstname;
                print '<tr '.$bc[$var].'>';
                print '<td>'.$deplacementstatic->getNomUrl(1).'</td>';
                print '<td>'.$userstatic->getNomUrl(1).'</td>';
                print '<td align="right">'.$objp->km.'</td>';
                print '<td align="right">'.dol_print_date($db->jdate($objp->dm),'day').'</td>';
                print '<td>'.$deplacementstatic->LibStatut($objp->fk_statut,3).'</td>';
                print '</tr>';
                $var=!$var;
                $i++;
            }

        }
        else
        {
            print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
        }
        print '</table><br>';
    }
    else dol_print_error($db);
}



// Last donations
if (! empty($conf->don->enabled) && $user->rights->societe->lire)
{
	include_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

	$langs->load("boxes");
    $donationstatic=new Don($db);

	$sql = "SELECT d.rowid, d.lastname, d.firstname, d.societe, d.datedon as date, d.tms as dm, d.amount, d.fk_statut";
	$sql.= " FROM ".MAIN_DB_PREFIX."don as d";
	$sql.= " WHERE d.entity IN (".getEntity('donation').")";
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereLastDonations', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= $db->order("d.tms", "DESC");
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

        print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("BoxTitleLastModifiedDonations", $max).'</th>';
        print '<th></th>';
        print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
        print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
        print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;

			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);

				$donationstatic->id=$objp->rowid;
				$donationstatic->ref=$objp->rowid;
				$donationstatic->lastname=$objp->lastname;
				$donationstatic->firstname=$objp->firstname;

				$label=$donationstatic->getFullName($langs);
				if ($objp->societe) $label.=($label?' - ':'').$objp->societe;

				print '<tr class="oddeven">';
				print '<td>'.$donationstatic->getNomUrl(1).'</td>';
				print '<td>'.$label.'</td>';
				print '<td class="nowrap right">'.price($objp->amount).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($objp->dm), 'day').'</td>';
                print '<td>'.$donationstatic->LibStatut($objp->fk_statut, 3).'</td>';
				print '</tr>';

				$i++;
			}
		}
		else
		{
			print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print '</table></div><br>';
	}
	else dol_print_error($db);
}

/**
 * Social contributions to pay
 */
if (! empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	if (!$socid)
	{
		$chargestatic=new ChargeSociales($db);

		$sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
		$sql.= " cc.libelle,";
		$sql.= " SUM(pc.amount) as sumpaid";
		$sql.= " FROM (".MAIN_DB_PREFIX."c_chargesociales as cc, ".MAIN_DB_PREFIX."chargesociales as c)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = c.rowid";
		$sql.= " WHERE c.fk_type = cc.id";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND c.paye = 0";
		// Add where from hooks
		$parameters=array();
		$reshook=$hookmanager->executeHooks('printFieldListWhereSocialContributions', $parameters);
		$sql.=$hookmanager->resPrint;

		$sql.= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

		$resql = $db->query($sql);
		if ( $resql )
		{
			$num = $db->num_rows($resql);

            print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<th>'.$langs->trans("ContributionsToPay").($num?' <a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?status=0"><span class="badge">'.$num.'</span></a>':'').'</th>';
			print '<th align="center">'.$langs->trans("DateDue").'</th>';
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("Paid").'</th>';
			print '<th align="center" width="16">&nbsp;</th>';
			print '</tr>';
			if ($num)
			{
				$i = 0;
				$tot_ttc=0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					$chargestatic->id=$obj->rowid;
					$chargestatic->ref=$obj->libelle;
					$chargestatic->lib=$obj->libelle;
					$chargestatic->paye=$obj->paye;

					print '<tr class="oddeven">';
					print '<td>'.$chargestatic->getNomUrl(1).'</td>';
					print '<td align="center">'.dol_print_date($db->jdate($obj->date_ech), 'day').'</td>';
					print '<td class="nowrap right">'.price($obj->amount).'</td>';
					print '<td class="nowrap right">'.price($obj->sumpaid).'</td>';
					print '<td align="center">'.$chargestatic->getLibStatut(3).'</td>';
					print '</tr>';
					$tot_ttc+=$obj->amount;
					$i++;
				}

				print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td class="nowrap right">'.price($tot_ttc).'</td>';
				print '<td class="right"></td>';
				print '<td class="right">&nbsp;</td>';
				print '</tr>';
			}
			else
			{
				print '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
			print "</table></div><br>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
}

/*
 * Customers orders to be billed
 */
if (! empty($conf->facture->enabled) && ! empty($conf->commande->enabled) && $user->rights->commande->lire && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER))
{
	$commandestatic=new Commande($db);
	$langs->load("orders");

	$sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc";
	$sql.= ", s.nom as name, s.email";
    $sql.= ", s.rowid as socid";
    $sql.= ", s.code_client, s.code_compta";
	$sql.= ", c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.tva as total_tva, c.total_ttc,";
	$sql.= " cc.rowid as country_id, cc.code as country_code";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ", ".MAIN_DB_PREFIX."commande as c";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND c.fk_soc = ".$socid;
	$sql.= " AND c.fk_statut = 3";
	$sql.= " AND c.facture = 0";
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereCustomerOrderToBill', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= " GROUP BY s.nom, s.email, s.rowid, s.code_client, s.code_compta, c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.tva, c.total_ttc, cc.rowid, cc.code";

	$resql = $db->query($sql);
	if ( $resql )
	{
		$num = $db->num_rows($resql);

		if ($num)
		{
			$i = 0;

            print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder" width="100%">';
			print "<tr class=\"liste_titre\">";
			print '<th colspan="2">'.$langs->trans("OrdersDeliveredToBill").' <a href="'.DOL_URL_ROOT.'/commande/list.php?viewstatut=3&amp;billed=0"><span class="badge">'.$num.'</span></a></th>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("ToBill").'</th>';
			print '<th align="center" width="16">&nbsp;</th>';
			print '</tr>';

			$tot_ht=$tot_ttc=$tot_tobill=0;
			$societestatic = new Societe($db);
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$societestatic->id=$obj->socid;
				$societestatic->name=$obj->name;
				$societestatic->email=$obj->email;
				$societestatic->country_id=$obj->country_id;
				$societestatic->country_code=$obj->country_code;
				$societestatic->client=1;
				$societestatic->code_client = $obj->code_client;
				//$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				//$societestatic->code_fournisseur = $obj->code_fournisseur;

				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td class="left">';
                print $societestatic->getNomUrl(1, 'customer', 44);
				print '</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc-$obj->tot_fttc).'</td>';
				print '<td>'.$commandestatic->LibStatut($obj->fk_statut, $obj->facture, 3).'</td>';
				print '</tr>';
				$tot_ht += $obj->total_ht;
				$tot_ttc += $obj->total_ttc;
				//print "x".$tot_ttc."z".$obj->tot_fttc;
				$tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
				$i++;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($tot_ht).'</td>';
			print '<td class="nowrap right">'.price($tot_ttc).'</td>';
			print '<td class="nowrap right">'.price($tot_tobill).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			print '</table></div><br>';
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Unpaid customers invoices
 */
if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.fk_statut, f.datef, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
	$sql.= ", f.date_lim_reglement as datelimite";
	$sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid, s.email";
    $sql.= ", s.code_client, s.code_compta";
    $sql.= ", cc.rowid as country_id, cc.code as country_code";
    $sql.= ", sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays,".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
	$sql.= " AND f.entity IN (".getEntity('invoice').')';
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereCustomerUnpaid', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= " GROUP BY f.rowid, f.ref, f.fk_statut, f.datef, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
	$sql.= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, cc.rowid, cc.code";
	$sql.= " ORDER BY f.datef ASC, f.ref ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BillsCustomersUnpaid", $num).' <a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status=1"><span class="badge">'.$num.'</span></a></th>';
		print '<th class="right">'.$langs->trans("DateDue").'</th>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("Received").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$societestatic = new Societe($db);
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				$facturestatic->ref=$obj->ref;
				$facturestatic->id=$obj->rowid;
				$facturestatic->total_ht=$obj->total_ht;
				$facturestatic->total_tva=$obj->total_tva;
				$facturestatic->total_ttc=$obj->total_ttc;
				$facturestatic->type=$obj->type;
				$facturestatic->statut = $obj->fk_statut;
				$facturestatic->date_lim_reglement = $db->jdate($obj->datelimite);

				$societestatic->id=$obj->socid;
				$societestatic->name=$obj->name;
				$societestatic->email=$obj->email;
				$societestatic->country_id=$obj->country_id;
				$societestatic->country_code=$obj->country_code;
				$societestatic->client=1;
				$societestatic->code_client = $obj->code_client;
				$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $facturestatic->getNomUrl(1, '');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($facturestatic->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($facturestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td class="left">' ;
				print $societestatic->getNomUrl(1, 'customer', 44);
				print '</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->datelimite), 'day').'</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye, $obj->fk_statut, 3, $obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total_ht;
				$totalam +=  $obj->am;

				$i++;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
			print '<td>&nbsp;</td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($total).'</td>';
			print '<td class="nowrap right">'.price($total_ttc).'</td>';
			print '<td class="nowrap right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=6;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Unpayed supplier invoices
 */
if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
{
	$facstatic=new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.paye";
	$sql.= ", ff.date_lim_reglement";
	$sql.= ", s.nom as name";
    $sql.= ", s.rowid as socid, s.email";
    $sql.= ", s.code_client, s.code_compta";
    $sql.= ", s.code_fournisseur, s.code_compta_fournisseur";
	$sql.= ", sum(pf.amount) as am";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	$sql.= " AND ff.entity = ".$conf->entity;
	$sql.= " AND ff.paye = 0";
	$sql.= " AND ff.fk_statut = 1";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
	// Add where from hooks
	$parameters=array();
	$reshook=$hookmanager->executeHooks('printFieldListWhereSupplierUnpaid', $parameters);
	$sql.=$hookmanager->resPrint;

	$sql.= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.paye, ff.date_lim_reglement,";
	$sql.= " s.nom, s.rowid, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
	$sql.= " ORDER BY ff.date_lim_reglement ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BillsSuppliersUnpaid", $num).' <a href="'.DOL_URL_ROOT.'/fourn/facture/impayees.php"><span class="badge">'.$num.'</span></a></th>';
		print '<th class="right">'.$langs->trans("DateDue").'</th>';
		if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("Paid").'</th>';
		print '<th width="16">&nbsp;</th>';
		print "</tr>\n";
		$societestatic = new Societe($db);
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$facstatic->ref=$obj->ref;
				$facstatic->id = $obj->rowid;
				$facstatic->total_ht = $obj->total_ht;
				$facstatic->total_tva = $obj->total_tva;
				$facstatic->total_ttc = $obj->total_ttc;

				$societestatic->id=$obj->socid;
				$societestatic->name=$obj->name;
				$societestatic->email=$obj->email;
				$societestatic->client=0;
				$societestatic->fournisseur=1;
				$societestatic->code_client = $obj->code_client;
				$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td>';
				print $facstatic->getNomUrl(1, '');
				print '</td>';
				print '<td>'.$societestatic->getNomUrl(1, 'supplier', 44).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->date_lim_reglement), 'day').'</td>';
				if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye, $obj->fk_statut, 3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc +=  $obj->total_ttc;
				$totalam +=  $obj->am;
				$i++;
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</font> </td>';
			print '<td>&nbsp;</td>';
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($total).'</td>';
			print '<td class="nowrap right">'.price($total_ttc).'</td>';
			print '<td class="nowrap right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=6;
			if (! empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
	}
	else
	{
		dol_print_error($db);
	}
}



// TODO Mettre ici recup des actions en rapport avec la compta
$resql = 0;
if ($resql)
{
    print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><thcolspan="2">'.$langs->trans("TasksToDo").'</th>';
	print "</tr>\n";
	$i = 0;
	while ($i < $db->num_rows($resql))
	{
		$obj = $db->fetch_object($resql);


		print '<tr class="oddeven"><td>'.dol_print_date($db->jdate($obj->da), "day").'</td>';
		print '<td><a href="action/card.php">'.$obj->libelle.' '.$obj->label.'</a></td></tr>';
		$i++;
	}
	$db->free($resql);
	print "</table></div><br>";
}


print '</div></div></div>';

// End of page
llxFooter();
$db->close();
function ListeMoisFacture($annee="")
{
    global $db,$langs,$conf,$user,$bc;
    $sql = " SELECT month(`datef`) as  mois_int  ,year(`datef`) as annee,date_format(`datef`,'%M') as mois ";
    $sql.= " FROM ".MAIN_DB_PREFIX."facture ";
    if($annee!="") $sql.=" WHERE year(`datef`)=".$annee;
    else $sql.=" WHERE year(`datef`)=".date("Y");
    $sql.= " GROUP BY  annee, mois_int ";
    $sql.= " ORDER BY annee, mois_int DESC ";
    $resql = $db->query($sql);
    $varialbe=array();
    if ( $resql )
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $mois=str_ireplace("&eacute;","é",$langs->trans($obj->mois));
            $mois=str_ireplace("&ucirc;","u",$mois);
            $tab=array($mois,$obj->mois_int,$obj->annee);
            $varialbe[$i]= $tab;
            $i++;
        }

        $db->free($resql);
    }
    return $varialbe;
}
