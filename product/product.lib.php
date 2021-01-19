<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/product.lib.php
 *	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	product
 *	\version    $Id: product.lib.php,v 1.44 2011/07/31 23:25:32 eldy Exp $
 *
 * 	Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function product_prepare_head($object, $user)
{
	var_dump(1111);die();
	global $langs, $conf;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$object->id;
	$head[$h][1] = $langs->trans("CustomerPrices");
	$head[$h][2] = 'price';
	$h++;

	if ($conf->fournisseur->enabled && $user->rights->fournisseur->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$object->id;
		$head[$h][1] = $langs->trans("SuppliersPrices");
		$head[$h][2] = 'suppliers';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;

	// Show category tab
	if ($conf->categorie->enabled && $user->rights->categorie->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$object->id.'&type=0';
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

	// Show barcode tab
	if ($conf->global->MAIN_MODULE_BARCODE && $user->rights->barcode->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$object->id;
		$head[$h][1] = $langs->trans("BarCode");
		$head[$h][2] = 'barcode';
		$h++;
	}

	// Multilangs
	if($conf->global->MAIN_MULTILANGS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$object->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	// Sub products
	if($conf->global->PRODUIT_SOUSPRODUITS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/composition/fiche.php?id=".$object->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');
		$head[$h][2] = 'subproduct';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

    if($object->isproduct())    // Si produit stockable
    {
        if ($conf->stock->enabled && $user->rights->stock->lire)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$object->id;
            $head[$h][1] = $langs->trans("Stock");
            $head[$h][2] = 'stock';
            $h++;
        }
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'product');

    $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;


	// More tabs from canvas
	if (is_array($object->onglets))
	{
		foreach ($object->onglets as $onglet)
		{
			$head[$h] = $onglet;
			$h++;
		}
	}
//var_dump($head);die();
	return $head;
}


/*function terrain_prepare_head($object, $user)
{
	global $langs, $conf;
	$langs->load("terrains");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$object->id;
	$head[$h][1] = $langs->trans("CustomerPrices");
	$head[$h][2] = 'price';
	$h++;

	if ($conf->fournisseur->enabled && $user->rights->fournisseur->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$object->id;
		$head[$h][1] = $langs->trans("SuppliersPrices");
		$head[$h][2] = 'suppliers';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;

	// Show category tab
	if ($conf->categorie->enabled && $user->rights->categorie->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$object->id.'&type=0';
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

	// Show barcode tab
	if ($conf->global->MAIN_MODULE_BARCODE && $user->rights->barcode->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$object->id;
		$head[$h][1] = $langs->trans("BarCode");
		$head[$h][2] = 'barcode';
		$h++;
	}

	// Multilangs
	if($conf->global->MAIN_MULTILANGS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$object->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	// Sub products
	if($conf->global->PRODUIT_SOUSPRODUITS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/composition/fiche.php?id=".$object->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');
		$head[$h][2] = 'subproduct';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

    if($object->isproduct())    // Si produit stockable
    {
        if ($conf->stock->enabled && $user->rights->stock->lire)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$object->id;
            $head[$h][1] = $langs->trans("Stock");
            $head[$h][2] = 'stock';
            $h++;
        }
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'product');

    $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;


	// More tabs from canvas
	if (is_array($object->onglets))
	{
		foreach ($object->onglets as $onglet)
		{
			$head[$h] = $onglet;
			$h++;
		}
	}

	return $head;
}
*/

function show_stats_for_company($product,$socid)
{
	global $conf,$langs,$user,$db;

	print '<tr>';
	print '<td align="left" width="25%" valign="top">'.$langs->trans("Referers").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("NbOfReferers").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	// Propals
	if ($conf->propal->enabled && $user->rights->propale->lire)
	{
		$ret=$product->load_stats_propale($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("propal");
		print '<tr><td>';
		print '<a href="propal.php?id='.$product->id.'">'.img_object('','propal').' '.$langs->trans("Proposals").'</a>';
		print '</td><td align="right">';
		print $product->stats_propale['customers'];
		print '</td><td align="right">';
		print $product->stats_propale['nb'];
		print '</td><td align="right">';
		print $product->stats_propale['qty'];
		print '</td>';
		print '</tr>';
	}
	// Commandes clients
	if ($conf->commande->enabled && $user->rights->commande->lire)
	{
		$ret=$product->load_stats_commande($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande.php?id='.$product->id.'">'.img_object('','order').' '.$langs->trans("CustomersOrders").'</a>';
		print '</td><td align="right">';
		print $product->stats_commande['customers'];
		print '</td><td align="right">';
		print $product->stats_commande['nb'];
		print '</td><td align="right">';
		print $product->stats_commande['qty'];
		print '</td>';
		print '</tr>';
	}
	// Commandes fournisseurs
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
	{
		$ret=$product->load_stats_commande_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande_fournisseur.php?id='.$product->id.'">'.img_object('','order').' '.$langs->trans("SuppliersOrders").'</a>';
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['suppliers'];
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['nb'];
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}
	// Contrats
	if ($conf->contrat->enabled && $user->rights->contrat->lire)
	{
		$ret=$product->load_stats_contrat($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("contracts");
		print '<tr><td>';
		print '<a href="contrat.php?id='.$product->id.'">'.img_object('','contract').' '.$langs->trans("Contracts").'</a>';
		print '</td><td align="right">';
		print $product->stats_contrat['customers'];
		print '</td><td align="right">';
		print $product->stats_contrat['nb'];
		print '</td><td align="right">';
		print $product->stats_contrat['qty'];
		print '</td>';
		print '</tr>';
	}
	// Factures clients
	if ($conf->facture->enabled && $user->rights->facture->lire)
	{
		$ret=$product->load_stats_facture($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture.php?id='.$product->id.'">'.img_object('','bill').' '.$langs->trans("CustomersInvoices").'</a>';
		print '</td><td align="right">';
		print $product->stats_facture['customers'];
		print '</td><td align="right">';
		print $product->stats_facture['nb'];
		print '</td><td align="right">';
		print $product->stats_facture['qty'];
		print '</td>';
		print '</tr>';
	}
	// Factures fournisseurs
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire)
	{
		$ret=$product->load_stats_facture_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture_fournisseur.php?id='.$product->id.'">'.img_object('','bill').' '.$langs->trans("SuppliersInvoices").'</a>';
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['suppliers'];
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['nb'];
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}

	return 0;
}


/**
 *	Return translation label of a unit key
 *	@param  	unit                Unit key (-3,0,3,98,99...)
 *	@param  	measuring_style     Style of unit: weight, volume,...
 *	@return		string	            Unit string
 * 	@see	load_measuring_units
 */
function measuring_units_string($unit,$measuring_style='')
{
	global $langs;

	if ($measuring_style == 'weight')
	{
		$measuring_units[3] = $langs->trans("WeightUnitton");
		$measuring_units[0] = $langs->trans("WeightUnitkg");
		$measuring_units[-3] = $langs->trans("WeightUnitg");
		$measuring_units[-6] = $langs->trans("WeightUnitmg");
        $measuring_units[99] = $langs->trans("WeightUnitpound");
	}
	else if ($measuring_style == 'size')
	{
		$measuring_units[0] = $langs->trans("SizeUnitm");
		$measuring_units[-1] = $langs->trans("SizeUnitdm");
		$measuring_units[-2] = $langs->trans("SizeUnitcm");
		$measuring_units[-3] = $langs->trans("SizeUnitmm");
        $measuring_units[99] = $langs->trans("SizeUnitinch");
        $measuring_units[98] = $langs->trans("SizeUnitfoot");
	}
	else if ($measuring_style == 'surface')
	{
		$measuring_units[0] = $langs->trans("d");
		$measuring_units[-2] = $langs->trans("SurfaceUnitdm2");
		$measuring_units[-4] = $langs->trans("SurfaceUnitcm2");
		$measuring_units[-6] = $langs->trans("SurfaceUnitmm2");
	}
	//by ndickou
/*	else if ($measuring_style == 'volume')
	{
		$measuring_units[0] = $langs->trans("VolumeUnitm3");
		$measuring_units[-3] = $langs->trans("VolumeUnitdm3");
		$measuring_units[-6] = $langs->trans("VolumeUnitcm3");
		$measuring_units[-9] = $langs->trans("VolumeUnitmm3");
        $measuring_units[97] = $langs->trans("VolumeUnitounce");
		$measuring_units[98] = $langs->trans("VolumeUnitlitre");
        $measuring_units[99] = $langs->trans("VolumeUnitgallon");
	}*/

	return $measuring_units[$unit];
}

function get_TypeProduct($type='')
{
	$return ='inconnu';
	switch ($type)
	{
		case 0:
			$return = 'Parking';
			break;
		case 1:
			$return = 'Studio';
			break;
		case 2:
			$return = 'Appartement F2';
			break;
		case 3:
			$return = 'Appartement  F3';
			break;
		case 4:
			$return = 'Appartement F4';
			break;
		case 5:
			$return = 'Appartement F5';
			break;
		case 6:
			$return = 'Bureaux';
			break;
	}
	return $return ;
}
function get_TypeProduct2($type='')
{
	$return ='inconnu';
	switch ($type)
	{
		case 0:
			$return = 'Parking';
			break;
		case 1:
			$return = 'Studio';
			break;
		case 2:
			$return = 'Appartement';
			break;
		case 3:
			$return = 'Appartement';
			break;
		case 4:
			$return = 'Appartement';
			break;
		case 5:
			$return = 'Appartement';
			break;
		case 6:
			$return = 'Bureaux';
			break;
	}
	return $return ;
}
function get_Position($type='')
{
	$return ='inconnu';
	switch ($type)
	{
		case 0:
			$return = 'Aucun';
			break;
		case 1:
			$return = 'Façade';
			break;
		case 2:
			$return = 'Arrière Gauche';
			break;
		case 3:
			$return = 'Arrière Droite';
			break;
		case 4:
			$return = 'Façade a droite';
			break;
		case 5:
			$return = 'Façade a Gauche';
			break;
		case 6:
			$return = 'Arrière';
			break;
	}
	return $return ;
}
function get_NiveauProduct($type='')
{
	$return ='inconnu';
	switch ($type)
	{
		case -1:
			$return = 'Sous sol';
			break;
		case 0:
			$return = 'RDC';
			break;
		case 1:
			$return = '1er Etage';
			break;
		case 2:
			$return = '2ième Etage';
			break;
		case 3:
			$return = '3ième Etage';
			break;
		case 4:
			$return = '4ième Etage';
			break;
		case 5:
			$return = '5ième Etage';
			break;
		case 6:
			$return = '6ième Etage';
			break;
		case 7:
			$return = '7ième Etage';
			break;
		case 8:
			$return = '8ième Etage';
			break;
		case 9:
			$return = '9ième Etage';
			break;
		case 10:
			$return = '10ième Etage';
			break;
	}
	return $return ;
}
function Select_NiveauProduct($name='select',$selected='')
{
	print '<select class="flat" name="'.$name.'">';
	print '<option value="-1"';
	if($selected==-1) print 'selected="selected"';
	print '>Sous sol</option>';
	print '<option value="0"';
	if($selected==0) print 'selected="selected"';
	print '>RDC</option>';
	print '<option value="1"';
	if($selected==1) print 'selected="selected"';
	print '>1er Etage</option>';
	print '<option value="2"';
	if($selected==2) print 'selected="selected"';
	print '>2iéme Etage</option>';
	print '<option value="3"';
	if($selected==3) print 'selected="selected"';
	print '>3iéme Etage</option>';
	print '<option value="4"';
	if($selected==4) print 'selected="selected"';
	print '>4iéme Etage</option>';
	print '<option value="5"';
	if($selected==5) print 'selected="selected"';
	print '>5iéme Etage</option>';
	print '<option value="6"';
	if($selected==6) print 'selected="selected"';
	print '>6iéme Etage</option>';
	print '<option value="7"';
	if($selected==7) print 'selected="selected"';
	print '>7iéme Etage</option>';
	print '<option value="8"';
	if($selected==8) print 'selected="selected"';
	print '>8iéme Etage</option>';
	print '</select>';
}
function Select_TypeProduct($name='select',$selected='')
{
	print '<select class="flat" name="'.$name.'">';
	print '<option value="0" ';
	if($selected==0) print 'selected="selected"';
	print '>Parking</option>';
	print '<option value="1"';
	if($selected==1) print 'selected="selected"';
	print '>Studio</option>';
	print '<option value="2"';
	if($selected==2) print 'selected="selected"';
	print '>Appartement F2</option>';
	print '<option value="3"';
	if($selected==3) print 'selected="selected"';
	print '>Appartement F3</option>';
	print '<option value="4"';
	if($selected==4) print 'selected="selected"';
	print '>Appartement F4</option>';
	print '<option value="5"';
	if($selected==5) print 'selected="selected"';
	print '>Appartement F5</option>';
	print '<option value="6"';
	if($selected==6) print 'selected="selected"';
	print '>Bureaux</option>';
	print '</select>';
}
function Select_Position($name='volume',$selected='')
{
	print '<select class="flat" name="'.$name.'">';
	print '<option value="0" ';
	if($selected==0) print 'selected="selected"';
	print '>Aucun</option>';
	print '<option value="1"';
	if($selected==1) print 'selected="selected"';
	print '>Façade</option>';
	print '<option value="2"';
	if($selected==2) print 'selected="selected"';
	print '>Arrière Gauche</option>';
	print '<option value="3"';
	if($selected==3) print 'selected="selected"';
	print '>Arrière Droite</option>';
	print '<option value="4"';
	if($selected==4) print 'selected="selected"';
	print '>Façade a droite</option>';
	print '<option value="5"';
	if($selected==5) print 'selected="selected"';
	print '> Façade à gauche</option>';
	print '<option value="6"';
	if($selected==6) print 'selected="selected"';
	print '>Arrière</option>';
	print '</select>';
}
?>
