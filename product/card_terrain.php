<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016	Laurent Destailleur	 <eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne		     <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015	Regis Houssin		 <regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani	 <acianfa@free.fr>
 * Copyright (C) 2006		Auguria SARL		 <info@auguria.org>
 * Copyright (C) 2010-2015	Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2013-2016	Marcos García		 <marcosgdf@gmail.com>
 * Copyright (C) 2012-2013	Cédric Salvador		 <csalvador@gpcsolutions.fr>
 * Copyright (C) 2011-2020	Alexandre Spangaro	 <aspangaro@open-dsi.fr>
 * Copyright (C) 2014		Cédric Gross		 <c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Ferran Marcet		 <fmarcet@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry	 <jfefe@aternatik.fr>
 * Copyright (C) 2015		Raphaël Doursenaud	 <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016		Charlie Benke		 <charlie@patas-monkey.com>
 * Copyright (C) 2016		Meziane Sof		     <virtualsof@yahoo.fr>
 * Copyright (C) 2017		Josep Lluís Amador	 <joseplluis@lliuretic.cat>
 * Copyright (C) 2019       Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2020  Thibault FOUCART     <support@ptibogxiv.net>
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
 *  \file       htdocs/product/card_terrain.php
 *  \ingroup    product
 *  \brief      Page to show product
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';

if (!empty($conf->propal->enabled))     require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (!empty($conf->facture->enabled))    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (!empty($conf->commande->enabled))   require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'other'));
if (!empty($conf->stock->enabled)) $langs->load("stocks");
if (!empty($conf->facture->enabled)) $langs->load("bills");
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$mesg = ''; $error = 0; $errors = array();

$refalreadyexists = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$typeprod = GETPOST('typeprod', 'int');
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');
if (!empty($user->socid)) $socid = $user->socid;

$object = new Product($db);
//echo '<pre>';
//var_dump($object);die();

$object->type = $typeprod; // so test later to fill $usercancxxx is correct
$extrafields = new ExtraFields($db);
function select_numcompte($selected='',$htmlname='numcompte',$empty=0, $htmloption='')
{

    {
        global $conf,$langs, $db;

        $langs->load("dict");

        $out='';
        $calculArray=array();


        $sql = "SELECT c.numero , c.label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "compte as c ";
        $sql .= " WHERE c.entite_rowid  = " . $conf->entity . " ORDER BY c.numero ASC";

        //dol_syslog(get_class($this)."::select_accounting_codes sql=".$sql);
        $resql=$db->query($sql);
        if ($resql)
        {
            $out.= '<select id="select'.$htmlname.'" class="flat selectmode minwidth200" name="'.$htmlname.'" '.$htmloption.'" >';
            $num = $db->num_rows($resql);
            $i = 0;
            if ($num)
            {
                $foundselected=false;

                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);
                    $calculArray[$i]['numero'] 		= $obj->numero;
                    //$codePostalArray[$i]['code_postal']  = $obj->code_postal;
                    $calculArray[$i]['label']  = $obj->numero." ".$obj->label;

                    $i++;
                }
                $out.= '<option value=""></option>';
                foreach ($calculArray as $row)
                {
                    if ($selected && $selected != '-1' && ($selected == $row['numero'] || $selected == $row['label']) )
                    {
                        $foundselected=true;
                        $out.= '<option value="'.$row['numero'].'" selected="selected">';
                    }
                    else
                    {
                        $out.= '<option value="'.$row['numero'].'">';
                    }
                    $out.= dol_trunc($row['label'],$maxlength,'middle');
                    $out.= '</option>';
                }
            }
            $out.= '</select>';
        }
        else
        {
            dol_print_error($db);
        }
        include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
        $out .= ajax_combobox('select' . $htmlname);
        return $out;
    }
}

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || !empty($ref))
{
    $result = $object->fetch($id, $ref);

    if (!empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref);
    elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'product').dol_sanitizeFileName($object->ref);

    if (!empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
    {
        if (!empty($conf->product->enabled)) $upload_dirold = $conf->product->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
        else $upload_dirold = $conf->service->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
    }
}

$modulepart = 'product';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('product', 'card_terrain', $canvas);
}

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($id) ? 'rowid' : 'ref');
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productcard', 'globalcard'));



/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = (($object->type == Product::TYPE_TERRAIN && $user->rights->produit->lire) );
$usercancreate = (($object->type == Product::TYPE_TERRAIN && $user->rights->produit->creer) );
//var_dump($usercancreate);die();
$usercandelete = (($object->type == Product::TYPE_TERRAIN && $user->rights->produit->supprimer));
$createbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->creer_advance)) $createbarcode = 0;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Type
	if ($action == 'setfk_product_type' && $usercancreate)
    {
    	$result = $object->setValueFrom('fk_product_type', GETPOST('fk_product_type'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Actions to build doc
    $upload_dir = $conf->product->dir_output;
    $permissiontoadd = $usercancreate;
    include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

    include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

    // Barcode type
    if ($action == 'setfk_barcode_type' && $createbarcode)
    {
        $result = $object->setValueFrom('fk_barcode_type', GETPOST('fk_barcode_type'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Barcode value
    if ($action == 'setbarcode' && $createbarcode)
    {
    	$result = $object->check_barcode(GETPOST('barcode'), GETPOST('barcode_type_code'));

		if ($result >= 0)
		{
	    	$result = $object->setValueFrom('barcode', GETPOST('barcode'), '', null, 'text', '', $user, 'PRODUCT_MODIFY');
	    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
	    	exit;
		}
		else
		{
			$langs->load("errors");
        	if ($result == -1) $errors[] = 'ErrorBadBarCodeSyntax';
        	elseif ($result == -2) $errors[] = 'ErrorBarCodeRequired';
        	elseif ($result == -3) $errors[] = 'ErrorBarCodeAlreadyUsed';
        	else $errors[] = 'FailedToValidateBarCode';

			$error++;
			setEventMessages($errors, null, 'errors');
		}
    }

    // Add a product or service
    if ($action == 'add' && $usercancreate)
    {
        var_dump($_POST);die();
        $error = 0;

        if (!GETPOST('label', 'alphanohtml'))
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
            $action = "create";
            $error++;
        }
        if (empty($ref))
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref')), null, 'errors');
            $action = "create";
            $error++;
        }
        if (!empty($duration_value) && empty($duration_unit))
        {
            setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Unit')), null, 'errors');
            $action = "create";
            $error++;
        }

        if (!$error)
        {
	        $units = GETPOST('units', 'int');

            $object->ref                   = $ref;
            $object->label                 = GETPOST('label', 'alphanohtml');
            $object->price_base_type       = GETPOST('price_base_type', 'aZ09');

            if ($object->price_base_type == 'TTC')
            	$object->price_ttc = GETPOST('price');
            else
            	$object->price = GETPOST('price');
            if ($object->price_base_type == 'TTC')
            	$object->price_min_ttc = GETPOST('price_min');
            else
            	$object->price_min = GETPOST('price_min');

	        $tva_tx_txt = GETPOST('tva_tx', 'alpha'); // tva_tx can be '8.5'  or  '8.5*'  or  '8.5 (XXX)' or '8.5* (XXX)'

	        // We must define tva_tx, npr and local taxes
	        $vatratecode = '';
	        $tva_tx = preg_replace('/[^0-9\.].*$/', '', $tva_tx_txt); // keep remove all after the numbers and dot
	        $npr = preg_match('/\*/', $tva_tx_txt) ? 1 : 0;
	        $localtax1 = 0; $localtax2 = 0; $localtax1_type = '0'; $localtax2_type = '0';
	        // If value contains the unique code of vat line (new recommanded method), we use it to find npr and local taxes
	        if (preg_match('/\((.*)\)/', $tva_tx_txt, $reg))
	        {
	            // We look into database using code (we can't use get_localtax() because it depends on buyer that is not known). Same in update price.
	            $vatratecode = $reg[1];
	            // Get record from code
	            $sql = "SELECT t.rowid, t.code, t.recuperableonly, t.localtax1, t.localtax2, t.localtax1_type, t.localtax2_type";
	            $sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	            $sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$mysoc->country_code."'";
	            $sql .= " AND t.taux = ".((float) $tva_tx)." AND t.active = 1";
	            $sql .= " AND t.code ='".$vatratecode."'";
	            $resql = $db->query($sql);
	            if ($resql)
	            {
	                $obj = $db->fetch_object($resql);
	                $npr = $obj->recuperableonly;
	                $localtax1 = $obj->localtax1;
	                $localtax2 = $obj->localtax2;
	                $localtax1_type = $obj->localtax1_type;
	                $localtax2_type = $obj->localtax2_type;
	            }
	        }

	        $object->default_vat_code = $vatratecode;
	        $object->tva_tx = $tva_tx;
	        $object->tva_npr = $npr;
	        $object->localtax1_tx = $localtax1;
	        $object->localtax2_tx = $localtax2;
	        $object->localtax1_type = $localtax1_type;
	        $object->localtax2_type = $localtax2_type;

            $object->type               	 = $typeprod;
            $object->status             	 = GETPOST('statut');
            $object->status_buy            = GETPOST('statut_buy');
			$object->status_batch = GETPOST('status_batch');

            $object->barcode_type          = GETPOST('fk_barcode_type');
            $object->barcode = GETPOST('barcode');
            // Set barcode_type_xxx from barcode_type id
            $stdobject = new GenericObject($db);
    	    $stdobject->element = 'product';
            $stdobject->barcode_type = GETPOST('fk_barcode_type');
            $result = $stdobject->fetch_barcode();
            if ($result < 0)
            {
            	$error++;
            	$mesg = 'Failed to get bar code type information ';
            	setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
            }
            $object->barcode_type_code      = $stdobject->barcode_type_code;
            $object->barcode_type_coder     = $stdobject->barcode_type_coder;
            $object->barcode_type_label     = $stdobject->barcode_type_label;

            $object->description        	 = dol_htmlcleanlastbr(GETPOST('desc', 'none'));
            $object->url = GETPOST('url');
            $object->note_private          	 = dol_htmlcleanlastbr(GETPOST('note_private', 'none'));
            $object->note               	 = $object->note_private; // deprecated
            $object->customcode              = GETPOST('customcode', 'alphanohtml');
            $object->country_id              = GETPOST('country_id', 'int');
            $object->duration_value     	 = $duration_value;
            $object->duration_unit      	 = $duration_unit;
            $object->fk_default_warehouse	 = GETPOST('fk_default_warehouse');
            $object->seuil_stock_alerte 	 = GETPOST('seuil_stock_alerte') ?GETPOST('seuil_stock_alerte') : 0;
            $object->desiredstock          = GETPOST('desiredstock') ?GETPOST('desiredstock') : 0;
            $object->canvas             	 = GETPOST('canvas');
            $object->net_measure           = GETPOST('net_measure');
            $object->net_measure_units     = GETPOST('net_measure_units'); // This is not the fk_unit but the power of unit
            $object->weight             	 = GETPOST('weight');
            $object->weight_units       	 = GETPOST('weight_units'); // This is not the fk_unit but the power of unit
            $object->length             	 = GETPOST('size');
            $object->length_units       	 = GETPOST('size_units'); // This is not the fk_unit but the power of unit
            $object->width = GETPOST('sizewidth');
            $object->height             	 = GETPOST('sizeheight');
            $object->surface            	 = GETPOST('surface');
            $object->surface_units      	 = GETPOST('surface_units'); // This is not the fk_unit but the power of unit
            $object->volume             	 = GETPOST('volume');
            $object->volume_units       	 = GETPOST('volume_units'); // This is not the fk_unit but the power of unit
            $object->finished           	 = GETPOST('finished', 'alpha');
            $object->fk_unit = GETPOST('units', 'alpha'); // This is the fk_unit of sale

	        $accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
	        $accountancy_code_sell_intra = GETPOST('accountancy_code_sell_intra', 'alpha');
	        $accountancy_code_sell_export = GETPOST('accountancy_code_sell_export', 'alpha');
	        $accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
			$accountancy_code_buy_intra = GETPOST('accountancy_code_buy_intra', 'alpha');
			$accountancy_code_buy_export = GETPOST('accountancy_code_buy_export', 'alpha');

			if ($accountancy_code_sell <= 0) { $object->accountancy_code_sell = ''; } else { $object->accountancy_code_sell = $accountancy_code_sell; }
			if ($accountancy_code_sell_intra <= 0) { $object->accountancy_code_sell_intra = ''; } else { $object->accountancy_code_sell_intra = $accountancy_code_sell_intra; }
			if ($accountancy_code_sell_export <= 0) { $object->accountancy_code_sell_export = ''; } else { $object->accountancy_code_sell_export = $accountancy_code_sell_export; }
			if ($accountancy_code_buy <= 0) { $object->accountancy_code_buy = ''; } else { $object->accountancy_code_buy = $accountancy_code_buy; }
			if ($accountancy_code_buy_intra <= 0) { $object->accountancy_code_buy_intra = ''; } else { $object->accountancy_code_buy_intra = $accountancy_code_buy_intra; }
			if ($accountancy_code_buy_export <= 0) { $object->accountancy_code_buy_export = ''; } else { $object->accountancy_code_buy_export = $accountancy_code_buy_export; }

            // MultiPrix
            if (!empty($conf->global->PRODUIT_MULTIPRICES))
            {
                for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
                {
                    if (GETPOSTISSET("price_".$i))
                    {
                        $object->multiprices["$i"] = price2num($_POST["price_".$i], 'MU');
                        $object->multiprices_base_type["$i"] = $_POST["multiprices_base_type_".$i];
                    }
                    else
                    {
                        $object->multiprices["$i"] = "";
                    }
                }
            }

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) $error++;

			if (!$error)
			{
            	$id = $object->create($user);
			}

            if ($id > 0)
            {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				if (!empty($backtopage))
				{
					$backtopage = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $backtopage); // New method to autoselect project after a New on another form object creation
					if (preg_match('/\?/', $backtopage)) $backtopage .= '&socid='.$object->id; // Old method
					header("Location: ".$backtopage);
					exit;
				}
				else
				{
                	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
                	exit;
				}
            }
            else
			{
            	if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans($object->error), null, 'errors');
                $action = "create";
            }
        }
    }

    // Update a product or service
    if ($action == 'update' && $usercancreate)
    {
    	if (GETPOST('cancel', 'alpha'))
        {
            $action = '';
        }
        else
        {
            if ($object->id > 0)
            {
				$object->oldcopy = clone $object;

                $object->ref                    = $ref;
                $object->label                  = GETPOST('label', 'alphanohtml');
                $object->description            = dol_htmlcleanlastbr(GETPOST('desc', 'none'));
            	$object->url = GETPOST('url');
    			if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    			{
                	$object->note_private = dol_htmlcleanlastbr(GETPOST('note_private', 'none'));
                    $object->note = $object->note_private;
    			}
                $object->customcode             = GETPOST('customcode', 'alpha');
                $object->country_id             = GETPOST('country_id', 'int');
                $object->status                 = GETPOST('statut', 'int');
                $object->status_buy             = GETPOST('statut_buy', 'int');
                $object->status_batch = GETPOST('status_batch', 'aZ09');
                // removed from update view so GETPOST always empty
                $object->fk_default_warehouse   = GETPOST('fk_default_warehouse');
                /*
                $object->seuil_stock_alerte     = GETPOST('seuil_stock_alerte');
                $object->desiredstock           = GETPOST('desiredstock');
                */
                $object->duration_value         = GETPOST('duration_value', 'int');
                $object->duration_unit          = GETPOST('duration_unit', 'alpha');

                $object->canvas                 = GETPOST('canvas');
                $object->net_measure            = GETPOST('net_measure');
                $object->net_measure_units      = GETPOST('net_measure_units'); // This is not the fk_unit but the power of unit
                $object->weight                 = GETPOST('weight');
                $object->weight_units           = GETPOST('weight_units'); // This is not the fk_unit but the power of unit
                $object->length                 = GETPOST('size');
                $object->length_units           = GETPOST('size_units'); // This is not the fk_unit but the power of unit
                $object->width = GETPOST('sizewidth');
                $object->height = GETPOST('sizeheight');

                $object->surface                = GETPOST('surface');
                $object->surface_units          = GETPOST('surface_units'); // This is not the fk_unit but the power of unit
                $object->volume                 = GETPOST('volume');
                $object->volume_units           = GETPOST('volume_units'); // This is not the fk_unit but the power of unit
                $object->finished               = GETPOST('finished', 'alpha');

	            $units = GETPOST('units', 'int');

	            if ($units > 0) {
		            $object->fk_unit = $units;
	            } else {
		            $object->fk_unit = null;
	            }

	            $object->barcode_type = GETPOST('fk_barcode_type');
    	        $object->barcode = GETPOST('barcode');
    	        // Set barcode_type_xxx from barcode_type id
    	        $stdobject = new GenericObject($db);
    	        $stdobject->element = 'product';
    	        $stdobject->barcode_type = GETPOST('fk_barcode_type');
    	        $result = $stdobject->fetch_barcode();
    	        if ($result < 0)
    	        {
    	        	$error++;
    	        	$mesg = 'Failed to get bar code type information ';
            		setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
    	        }
    	        $object->barcode_type_code      = $stdobject->barcode_type_code;
    	        $object->barcode_type_coder     = $stdobject->barcode_type_coder;
    	        $object->barcode_type_label     = $stdobject->barcode_type_label;

    	        $accountancy_code_sell = GETPOST('accountancy_code_sell', 'alpha');
    	        $accountancy_code_sell_intra = GETPOST('accountancy_code_sell_intra', 'alpha');
    	        $accountancy_code_sell_export = GETPOST('accountancy_code_sell_export', 'alpha');
    	        $accountancy_code_buy = GETPOST('accountancy_code_buy', 'alpha');
    	        $accountancy_code_buy_intra = GETPOST('accountancy_code_buy_intra', 'alpha');
    	        $accountancy_code_buy_export = GETPOST('accountancy_code_buy_export', 'alpha');

				if ($accountancy_code_sell <= 0) { $object->accountancy_code_sell = ''; } else { $object->accountancy_code_sell = $accountancy_code_sell; }
				if ($accountancy_code_sell_intra <= 0) { $object->accountancy_code_sell_intra = ''; } else { $object->accountancy_code_sell_intra = $accountancy_code_sell_intra; }
				if ($accountancy_code_sell_export <= 0) { $object->accountancy_code_sell_export = ''; } else { $object->accountancy_code_sell_export = $accountancy_code_sell_export; }
				if ($accountancy_code_buy <= 0) { $object->accountancy_code_buy = ''; } else { $object->accountancy_code_buy = $accountancy_code_buy; }
				if ($accountancy_code_buy_intra <= 0) { $object->accountancy_code_buy_intra = ''; } else { $object->accountancy_code_buy_intra = $accountancy_code_buy_intra; }
				if ($accountancy_code_buy_export <= 0) { $object->accountancy_code_buy_export = ''; } else { $object->accountancy_code_buy_export = $accountancy_code_buy_export; }

                // Fill array 'array_options' with data from add form
        		$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) $error++;

                if (!$error && $object->check())
                {
                    if ($object->update($object->id, $user) > 0)
                    {
						// Category association
						$categories = GETPOST('categories', 'array');
						$object->setCategories($categories);

                        $action = 'view';
                    }
                    else
					{
						if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
                    	else setEventMessages($langs->trans($object->error), null, 'errors');
                        $action = 'edit';
                    }
                }
                else
				{
					if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
                	else setEventMessages($langs->trans("ErrorProductBadRefOrLabel"), null, 'errors');
                    $action = 'edit';
                }
            }
        }
    }

    // Action clone object
    if ($action == 'confirm_clone' && $confirm != 'yes') { $action = ''; }
    if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate)
    {
        if (!GETPOST('clone_content') && !GETPOST('clone_prices'))
        {
        	setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
        }
        else
        {
            $db->begin();

            $originalId = $id;
            if ($object->id > 0)
            {
                $object->ref = GETPOST('clone_ref', 'alphanohtml');
                $object->status = 0;
                $object->status_buy = 0;
                $object->id = null;
                $object->barcode = -1;

                if ($object->check())
                {
                	$object->context['createfromclone'] = 'createfromclone';
                	$id = $object->create($user);
                    if ($id > 0)
                    {
                        if (GETPOST('clone_composition'))
                        {
                            $result = $object->clone_associations($originalId, $id);

                            if ($result < 1)
                            {
                                $db->rollback();
                                setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
                                header("Location: ".$_SERVER["PHP_SELF"]."?id=".$originalId);
                                exit;
                            }
                        }

                        if (GETPOST('clone_categories'))
                        {
                            $result = $object->cloneCategories($originalId, $id);

                            if ($result < 1)
                            {
                                $db->rollback();
                                setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
                                header("Location: ".$_SERVER["PHP_SELF"]."?id=".$originalId);
                                exit;
                            }
                        }

                        if (GETPOST('clone_prices')) {
                            $result = $object->clone_price($originalId, $id);

                            if ($result < 1) {
                                $db->rollback();
                                setEventMessages($langs->trans('ErrorProductClone'), null, 'errors');
                                header('Location: '.$_SERVER['PHP_SELF'].'?id='.$originalId);
                                exit();
                            }
                        }

                        // $object->clone_fournisseurs($originalId, $id);

                        $db->commit();
                        $db->close();

                        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
                        exit;
                    }
                    else
                    {
                        $id = $originalId;

                        if ($object->error == 'ErrorProductAlreadyExists')
                        {
                            $db->rollback();

                            $refalreadyexists++;
                            $action = "";

                            $mesg = $langs->trans("ErrorProductAlreadyExists", $object->ref);
                            $mesg .= ' <a href="'.$_SERVER["PHP_SELF"].'?ref='.$object->ref.'">'.$langs->trans("ShowCardHere").'</a>.';
                            setEventMessages($mesg, null, 'errors');
                            $object->fetch($id);
                        }
                        else
                     	{
                            $db->rollback();
                            if (count($object->errors))
                            {
                            	setEventMessages($object->error, $object->errors, 'errors');
                            	dol_print_error($db, $object->errors);
                            }
                            else
                            {
                            	setEventMessages($langs->trans($object->error), null, 'errors');
                            	dol_print_error($db, $object->error);
                            }
                        }
                    }

                    unset($object->context['createfromclone']);
                }
            }
            else
            {
                $db->rollback();
                dol_print_error($db, $object->error);
            }
        }
    }

    // Delete a product
    if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
    if ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete)
	{
		$result = $object->delete($user);

        if ($result > 0)
        {
            header('Location: '.DOL_URL_ROOT.'/product/list.php?typeprod='.$object->type.'&delprod='.urlencode($object->ref));
            exit;
        }
        else
        {
        	setEventMessages($langs->trans($object->error), null, 'errors');
            $reload = 0;
            $action = '';
        }
    }


    // Add product into object
    if ($object->id > 0 && $action == 'addin')
    {
        $thirpdartyid = 0;
        if (GETPOST('propalid') > 0)
        {
        	$propal = new Propal($db);
	        $result = $propal->fetch(GETPOST('propalid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db, $propal->error);
	            exit;
	        }
	        $thirpdartyid = $propal->socid;
        }
        elseif (GETPOST('commandeid') > 0)
        {
            $commande = new Commande($db);
	        $result = $commande->fetch(GETPOST('commandeid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db, $commande->error);
	            exit;
	        }
	        $thirpdartyid = $commande->socid;
        }
        elseif (GETPOST('factureid') > 0)
        {
    	    $facture = new Facture($db);
	        $result = $facture->fetch(GETPOST('factureid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db, $facture->error);
	            exit;
	        }
	        $thirpdartyid = $facture->socid;
        }

        if ($thirpdartyid > 0) {
            $soc = new Societe($db);
            $result = $soc->fetch($thirpdartyid);
            if ($result <= 0) {
                dol_print_error($db, $soc->error);
                exit;
            }

            $desc = $object->description;

            $tva_tx = get_default_tva($mysoc, $soc, $object->id);
            $tva_npr = get_default_npr($mysoc, $soc, $object->id);
            if (empty($tva_tx)) $tva_npr = 0;
            $localtax1_tx = get_localtax($tva_tx, 1, $soc, $mysoc, $tva_npr);
            $localtax2_tx = get_localtax($tva_tx, 2, $soc, $mysoc, $tva_npr);

            $pu_ht = $object->price;
            $pu_ttc = $object->price_ttc;
            $price_base_type = $object->price_base_type;

            // If multiprice
            if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level) {
                $pu_ht = $object->multiprices[$soc->price_level];
                $pu_ttc = $object->multiprices_ttc[$soc->price_level];
                $price_base_type = $object->multiprices_base_type[$soc->price_level];
            } elseif (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) {
                require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

                $prodcustprice = new Productcustomerprice($db);

                $filter = array('t.fk_product' => $object->id, 't.fk_soc' => $soc->id);

                $result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
                if ($result) {
                    if (count($prodcustprice->lines) > 0) {
                        $pu_ht = price($prodcustprice->lines [0]->price);
                        $pu_ttc = price($prodcustprice->lines [0]->price_ttc);
                        $price_base_type = $prodcustprice->lines [0]->price_base_type;
                        $tva_tx = $prodcustprice->lines [0]->tva_tx;
                    }
                }
            }

			$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
			$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));

            // On reevalue prix selon taux tva car taux tva transaction peut etre different
            // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
            if ($tmpvat != $tmpprodvat) {
                if ($price_base_type != 'HT') {
                    $pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
                } else {
                    $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
                }
            }

            if (GETPOST('propalid') > 0) {
                // Define cost price for margin calculation
                $buyprice = 0;
                if (($result = $propal->defineBuyPrice($pu_ht, GETPOST('remise_percent'), $object->id)) < 0)
                {
                    dol_syslog($langs->trans('FailedToGetCostPrice'));
                    setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
                }
                else
                {
                    $buyprice = $result;
                }

                $result = $propal->addline(
                    $desc,
                    $pu_ht,
                    GETPOST('qty'),
                    $tva_tx,
                    $localtax1_tx, // localtax1
                    $localtax2_tx, // localtax2
                    $object->id,
                    GETPOST('remise_percent'),
                    $price_base_type,
                    $pu_ttc,
                    0,
                    0,
                    -1,
                    0,
                    0,
                    0,
                    $buyprice,
                    '',
                    '',
                    '',
                    0,
                    $object->fk_unit
                );
                if ($result > 0) {
                    header("Location: ".DOL_URL_ROOT."/comm/propal/card_terrain.php?id=".$propal->id);
                    return;
                }

                setEventMessages($langs->trans("ErrorUnknown").": $result", null, 'errors');
            } elseif (GETPOST('commandeid') > 0) {
                // Define cost price for margin calculation
                $buyprice = 0;
                if (($result = $commande->defineBuyPrice($pu_ht, GETPOST('remise_percent'), $object->id)) < 0)
                {
                    dol_syslog($langs->trans('FailedToGetCostPrice'));
                    setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
                }
                else
                {
                    $buyprice = $result;
                }

                $result = $commande->addline(
                    $desc,
                    $pu_ht,
                    GETPOST('qty'),
                    $tva_tx,
                    $localtax1_tx, // localtax1
                    $localtax2_tx, // localtax2
                    $object->id,
                    GETPOST('remise_percent'),
                    '',
                    '',
                    $price_base_type,
                    $pu_ttc,
                    '',
                    '',
                    0,
                    -1,
                    0,
                    0,
                    null,
                    $buyprice,
                    '',
                    0,
                    $object->fk_unit
                );

                if ($result > 0) {
                    header("Location: ".DOL_URL_ROOT."/commande/card_terrain.php?id=".$commande->id);
                    exit;
                }
            } elseif (GETPOST('factureid') > 0) {
                // Define cost price for margin calculation
                $buyprice = 0;
                if (($result = $facture->defineBuyPrice($pu_ht, GETPOST('remise_percent'), $object->id)) < 0)
                {
                    dol_syslog($langs->trans('FailedToGetCostPrice'));
                    setEventMessages($langs->trans('FailedToGetCostPrice'), null, 'errors');
                }
                else
                {
                    $buyprice = $result;
                }

                $result = $facture->addline(
                    $desc,
                    $pu_ht,
                    GETPOST('qty'),
                    $tva_tx,
                    $localtax1_tx,
                    $localtax2_tx,
                    $object->id,
                    GETPOST('remise_percent'),
                    '',
                    '',
                    '',
                    '',
                    '',
                    $price_base_type,
                    $pu_ttc,
                    Facture::TYPE_STANDARD,
                    -1,
                    0,
                    '',
                    0,
                    0,
                    null,
                    $buyprice,
                    '',
                    0,
                    100,
                    '',
                    $object->fk_unit
                );

                if ($result > 0) {
                    header("Location: ".DOL_URL_ROOT."/compta/facture/card_terrain.php?facid=".$facture->id);
                    exit;
                }
            }
        }
        else {
            $action = "";
            setEventMessages($langs->trans("WarningSelectOneDocument"), null, 'warnings');
        }
    }
}



/*
 * View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
//var_dump($object->type);die();
if (GETPOST("typeprod") == '2' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Card_terrain');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
/*if (GETPOST("typeprod") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Card_terrain');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}*/

/*if (GETPOST("type") == '2' || ($object->type == Product::TYPE_PRODUCT))
{
    $title = $langs->trans('terrain')." ".$shortlabel." - ".$langs->trans('Card');
    $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}*/

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (!empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

// Load object modBarCodeProduct
$res = 0;
if (!empty($conf->barcode->enabled) && !empty($conf->global->BARCODE_PRODUCT_ADDON_NUM))
{
	$module = strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);
	$dirbarcode = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);
	foreach ($dirbarcode as $dirroot)
	{
		$res = dol_include_once($dirroot.$module.'.php');
		if ($res) break;
	}
	if ($res > 0)
	{
			$modBarCodeProduct = new $module();
	}
}


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id)
	{
		$object = new Product($db);
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error('', $object->error);
	}
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
}
else
{ // var_dump($usercancreate);die();
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------
	if ($action == 'create' && $usercancreate)
    {

        //WYSIWYG Editor
        require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

		// Load object modCodeProduct
        $module = (!empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
        if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module) - 4);
        }
        $result = dol_include_once('/core/modules/product/'.$module.'.php');
        if ($result > 0)
        {
        	$modCodeProduct = new $module();
        }

        dol_set_focus('input[name="ref"]');

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="type" value="'.$typeprod.'">'."\n";
		if (!empty($modCodeProduct->code_auto))
			print '<input type="hidden" name="code_auto" value="1">';
		if (!empty($modBarCodeProduct->code_auto))
			print '<input type="hidden" name="barcode_auto" value="1">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		if ($typeprod == 2) {
            $picto = 'product';
            $title = $langs->trans("NewTerrain");
		}
		else {
			$picto = 'product';
			$title = $langs->trans("newLot");
		}
        $linkback = "";
        print load_fiche_titre($title, $linkback, $picto);

        dol_fiche_head('');


        print '<table><tr>';

        print  '<td valign="top" align="left" style="top: 0px"><fieldset><legend><h2>'.$langs->trans("infos_generale").'</h2></legend>';




        print '<table class="border centpercent">';


        print '<tr>';
        $tmpcode = '';
        if (!empty($modCodeProduct->code_auto)) $tmpcode = $modCodeProduct->getNextValue($object, $typeprod);
        print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input id="ref" name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref', 'alphanohtml') : $tmpcode).'">';
        if ($refalreadyexists)
        {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label', 'alphanohtml')).'"></td></tr>';




      /*  // On sell
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
        $statutarray = array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
        print $form->selectarray('statut', $statutarray, GETPOST('statut'));
        print '</td></tr>';

        // To buy
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
        $statutarray = array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
        print $form->selectarray('statut_buy', $statutarray, GETPOST('statut_buy'));
        print '</td></tr>';*/



        // Public URL
        print '<input type="hidden" name="url" class="quatrevingtpercent" value="'.GETPOST('url').'">';

        // Units
        if ($conf->global->PRODUCT_USE_UNITS)
        {
            print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
            print '<td colspan="3">';
            print $form->selectUnits('', 'units');
            print '</td></tr>';
        }

        // Other attributes
        $parameters = array('colspan' => 3, 'cols' => '3');
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook))
        {
            print '<tr><td colspan="5"><h3>'.$langs->trans("adresse_terrain").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'adresse');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'ville');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'pays');
            print '<tr><td colspan="5"><h3>'.$langs->trans("local_geo").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'region');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'structure');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'typeproduit');
            print '<tr><td colspan="5"><h3> '.$langs->trans("Intervenants").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'apporteur');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'architecte');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'intermediaire');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'proprietaire');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'notaire');
            print '<tr><td colspan="5"><h3>'.$langs->trans("Dossiers").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'suivipar');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'date_entre');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'statut_terrain');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'date_modif');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'interet');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'probabilite');

            print '</table>';




        print  '</fieldset></td>';

        print  '<td valign="top" align="right" style="top: 0px"><fieldset><legend><h2>Description</h2></legend>';

            print '<table>';

            print '<tr><td colspan="5"><h3>'.$langs->trans("caracteristique_physique").'</h3><hr width="100%"> </td></tr>';

            if (empty($conf->global->PRODUCT_DISABLE_SURFACE))
            {
                // Brut Surface
                print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="3">';
                print '<input name="surface" size="4" value="'.GETPOST('surface').'">';
                print $formproduct->selectMeasuringUnits("surface_units", "surface", GETPOSTISSET('surface_units') ?GETPOST('surface_units', 'alpha') : '0', 0, 2);
                print '</td></tr>';
            }

            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'cos');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'shon');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'shonr');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'pos');

            print '<tr><td colspan="5"><h3>'.$langs->trans("produit_envisagé").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'nature_principale');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'nb_lot');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'surface_habitable');
            // Description (used in invoice, propal...)
            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';
            $doleditor = new DolEditor('desc', GETPOST('desc', 'none'), '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '50%');
            $doleditor->Create();
            print "</td></tr>";
            print '<tr><td colspan="5"><h3>'.$langs->trans("prix_ratios").'</h3><hr width="100%"> </td></tr>';
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phd');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phn');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phts');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phns');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'titrefoncier');
            print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'commentaire');
        }

        print '</table>';






        print  '</fieldset></td>';

        print '</tr></table>';










		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
		print ' &nbsp; &nbsp; ';
		print '<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		print '</div>';

		print '</form>';
	}

    /*
     * Product card
     */

    elseif ($object->id > 0)
    {
        // Fiche en mode edition
		if ($action == 'edit' && $usercancreate)
		{
            //WYSIWYG Editor
            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

            $typeprod = $langs->trans('Terrain');
            if ($object->isService()) $typeprod = $langs->trans('Service');
            //print load_fiche_titre($langs->trans('Modify').' '.$type.' : '.(is_object($object->oldcopy)?$object->oldcopy->ref:$object->ref), "");

            // Main official, simple, and not duplicated code
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
            print '<input type="hidden" name="token" value="'.newToken().'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

            $head = product_prepare_head($object);
            $titre = $langs->trans("Terrain");
            $picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
            dol_fiche_head($head, 'card_terrain', $titre, 0, $picto);

            print '<table><tr>';

            print  '<td valign="top" align="left" style="top: 0px"><fieldset><legend><h2>'.$langs->trans("infos_generale").'</h2></legend>';

            print '<table class="border allwidth">';

            // Ref
            print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

            // Label
            print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth400 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->label).'"></td></tr>';



            // Status To sell
            print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
            print '<select class="flat" name="statut">';
            if ($object->status)
            {
                print '<option value="1" selected>'.$langs->trans("OnSell").'</option>';
                print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("OnSell").'</option>';
                print '<option value="0" selected>'.$langs->trans("NotOnSell").'</option>';
            }
            print '</select>';
            print '</td></tr>';

            // Status To Buy
           print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
            print '<select class="flat" name="statut_buy">';
            if ($object->status_buy)
            {
                print '<option value="1" selected>'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0">'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0" selected>'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            print '</select>';
            print '</td></tr>';



			// Batch number managment
			if ($conf->productbatch->enabled)
			{
				if ($object->isProduct() || !empty($conf->global->STOCK_SUPPORTS_SERVICES))
				{
					print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td colspan="3">';
					$statutarray = array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"));
					print $form->selectarray('status_batch', $statutarray, $object->status_batch);
					print '</td></tr>';
				}
			}

            // Barcode
            $showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
            if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

	        if ($showbarcode)
	        {
		        print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
		        if (isset($_POST['fk_barcode_type']))
		        {
		         	$fk_barcode_type = GETPOST('fk_barcode_type');
		        }
		        else
		        {
	        		$fk_barcode_type = $object->barcode_type;
		        	if (empty($fk_barcode_type) && !empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
		        }
		        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
	            $formbarcode = new FormBarCode($db);
                print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
		        print '</td><td>'.$langs->trans("BarcodeValue").'</td><td>';
		        $tmpcode = isset($_POST['barcode']) ?GETPOST('barcode') : $object->barcode;
		        if (empty($tmpcode) && !empty($modBarCodeProduct->code_auto)) $tmpcode = $modBarCodeProduct->getNextValue($object, $typeprod);
		        print '<input size="40" class="maxwidthonsmartphone" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
		        print '</td></tr>';
	        }
            print "\n";

            // Stock
            if ($object->isProduct() && !empty($conf->stock->enabled))
            {
                // Default warehouse
                print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
                print $formproduct->selectWarehouses($object->fk_default_warehouse, 'fk_default_warehouse', 'warehouseopen', 1);
                print ' <a href="'.DOL_URL_ROOT.'/product/stock/card_terrain.php?action=create&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].'?action=create&typeprod='.GETPOST('typeprod', 'int')).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddWarehouse").'"></span></a>';
                print '</td>';
            }

        	// Units
	        if ($conf->global->PRODUCT_USE_UNITS)
	        {
		        print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
		        print '<td colspan="3">';
		        print $form->selectUnits($object->fk_unit, 'units');
		        print '</td></tr>';
	        }

            // Other attributes
            $parameters = array('colspan' => ' colspan="3"', 'cols' => 3);
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook))
            {
                print '<tr><td colspan="5"><h3>'.$langs->trans("adresse_terrain").'</h3><hr width="100%"> </td></tr>';
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'adresse');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'ville');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'pays');
                print '<tr><td colspan="5"><h3>'.$langs->trans("local_geo").'</h3><hr width="100%"> </td></tr>';
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'region');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'structure');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'typeproduit');
                print '<tr><td colspan="5"><h3> '.$langs->trans("Intervenants").'</h3><hr width="100%"> </td></tr>';
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'apporteur');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'architecte');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'intermediaire');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'proprietaire');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'notaire');
                print '<tr><td colspan="5"><h3>'.$langs->trans("Dossiers").'</h3><hr width="100%"> </td></tr>';
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'suivipar');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'date_entre');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'statut_terrain');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'date_modif');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'interet');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'probabilite');

                print '</table>';

                print  '</fieldset></td>';

                print  '<td valign="top" align="right" style="top: 0px"><fieldset><legend><h2>Description</h2></legend>';

                print '<table>';
                print '<tr><td colspan="5"><h3>'.$langs->trans("caracteristique_physique").'</h3><hr width="100%"> </td></tr>';

                if (empty($conf->global->PRODUCT_DISABLE_SURFACE))
                {
                    // Brut Surface
                    print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="3">';
                    print '<input name="surface" size="5" value="'.$object->surface.'"> ';
                    print $formproduct->selectMeasuringUnits("surface_units", "surface", $object->surface_units, 0, 2);
                    print '</td></tr>';
                }


                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'cos');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'shon');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'shonr');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'pos');
                print '<tr><td colspan="5"><h3>'.$langs->trans("produit_envisagé").'</h3><hr width="100%"> </td></tr>';

                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'nature_principale');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'nb_lot');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'surface_habitable');


                // Description (used in invoice, propal...)
                print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

                // We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
                $doleditor = new DolEditor('desc', $object->description, '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, ROWS_4, '90%');
                $doleditor->Create();

                print "</td></tr>";
                print '<tr><td colspan="5"><h3>'.$langs->trans("prix_ratios").'</h3><hr width="100%"> </td></tr>';
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phd');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phn');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phts');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'phns');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'titrefoncier');
                print $object->showOptionalsNew($extrafields, 'edit', $parameters,'','',0,'commentaire');
            }


            print '</table>';
            print  '</fieldset></td>';

            print '</tr></table>';
            print '<br>';

            print '<table class="border centpercent">';

			print '</table>';

			dol_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		}
        // Fiche en mode visu== fiche détail terrain
        else
		{
            //$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
           // if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

		    $head = product_prepare_head($object);
            $titre = $langs->trans("Terrain");
            $picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

            dol_fiche_head($head, 'card_terrain', $titre, -1, $picto);

            $linkback = '<a href="'.DOL_URL_ROOT.'/product/list_terrain.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
            $object->next_prev_filter = " fk_product_type = ".$object->type;

            $shownav = 1;
            if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

            dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');


            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">';



            // Description
            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="2">'.(dol_textishtml($object->description) ? $object->description : dol_nl2br($object->description, 1, true)).'</td></tr>';



            // Other attributes
            $parameters = array('colspan' => ' colspan="'.(2 + (($showphoto || $showbarcode) ? 1 : 0)).'"', 'cols' => (2 + (($showphoto || $showbarcode) ? 1 : 0)));
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl_terrain.php';



            print '</table>';
            print '</div>';
            print '<div class="fichehalfright"><div class="ficheaddleft">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">';



                if (empty($conf->global->PRODUCT_DISABLE_SURFACE))
                {
                    // Brut Surface
                    print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="2">';
                    if ($object->surface != '')
                    {
                    	print $object->surface." ".measuringUnitString(0, "surface", $object->surface_units);
                    }
                    else
                    {
                        print '&nbsp;';
                    }
                    print "</td></tr>\n";
                }





            // Other attributes
            $parameters = array('colspan' => ' colspan="'.(2 + (($showphoto || $showbarcode) ? 1 : 0)).'"', 'cols' => (2 + (($showphoto || $showbarcode) ? 1 : 0)));
            include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl_terraindesc.php';


            print "</table>\n";
    		print '</div>';

            print '</div></div>';
            print '<div style="clear:both"></div>';

            dol_fiche_end();
        }
    }
    elseif ($action != 'create')
    {
        var_dump(6666);die();
        exit;
    }
}

// Load object modCodeProduct
$module = (!empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) ? $conf->global->PRODUCT_CODEPRODUCT_ADDON : 'mod_codeproduct_leopard');
if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php')
{
    $module = substr($module, 0, dol_strlen($module) - 4);
}
$result = dol_include_once('/core/modules/product/'.$module.'.php');
if ($result > 0)
{
	$modCodeProduct = new $module();
}

$tmpcode = '';
if (!empty($modCodeProduct->code_auto)) $tmpcode = $modCodeProduct->getNextValue($object, $object->type);

// Define confirmation messages
$formquestionclone = array(
	'text' => $langs->trans("ConfirmClone"),
    array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClone"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'size'=>24),
    array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContentProduct"), 'value' => 1),
    array('type' => 'checkbox', 'name' => 'clone_categories', 'label' => $langs->trans("CloneCategoriesProduct"), 'value' => 1),
);
if (!empty($conf->global->PRODUIT_MULTIPRICES)) {
    $formquestionclone[] = array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct").' ('.$langs->trans("CustomerPrices").')', 'value' => 0);
}
if (!empty($conf->global->PRODUIT_SOUSPRODUITS))
{
    $formquestionclone[] = array('type' => 'checkbox', 'name' => 'clone_composition', 'label' => $langs->trans('CloneCompositionProduct'), 'value' => 1);
}

// Confirm delete product
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
    print $form->formconfirm("card_terrain.php?id=".$object->id, $langs->trans("DeleteProduct"), $langs->trans("ConfirmDeleteProduct"), "confirm_delete", '', 0, "action-delete");
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneProduct', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'action-clone', 350, 600);
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */
if ($action != 'create' && $action != 'edit')
{
    print "\n".'<div class="tabsAction">'."\n";

    $parameters = array();
    $reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    if (empty($reshook))
	{
		if ($usercancreate)
        {
            if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';

            if (!isset($object->no_button_copy) || $object->no_button_copy <> 1)
            {
                if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
                {
                    print '<span id="action-clone" class="butAction">'.$langs->trans('ToClone').'</span>'."\n";
                }
                else
    			{
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
                }
            }
        }
        $object_is_used = $object->isObjectUsed($object->id);

        if ($usercandelete)
        {
            if (empty($object_is_used) && (!isset($object->no_button_delete) || $object->no_button_delete <> 1))
            {
                if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
                {
                    print '<span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span>'."\n";
                }
                else
    			{
                    print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';
                }
            }
            else
    		{
                print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ProductIsUsed").'">'.$langs->trans("Delete").'</a>';
            }
        }
        else
    	{
            print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a>';
        }
    }

    print "\n</div>\n";
}

/*
 * All the "Add to" areas
 */

if (!empty($conf->global->PRODUCT_ADD_FORM_ADD_TO) && $object->id && ($action == '' || $action == 'view') && $object->status)
{
    //Variable used to check if any text is going to be printed
    $html = '';
	//print '<div class="fichecenter"><div class="fichehalfleft">';

    // Propals
    if (!empty($conf->propal->enabled) && $user->rights->propale->creer)
    {
        $propal = new Propal($db);

        $langs->load("propal");

        $otherprop = $propal->liste_array(2, 1, 0);

        if (is_array($otherprop) && count($otherprop))
        {
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftProposals").'</td><td>';
        	$html .= $form->selectarray("propalid", $otherprop, 0, 1);
        	$html .= '</td></tr>';
        }
        else
		{
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftProposals").'</td><td>';
        	$html .= $langs->trans("NoDraftProposals");
        	$html .= '</td></tr>';
        }
    }

    // Commande
    if (!empty($conf->commande->enabled) && $user->rights->commande->creer)
    {
        $commande = new Commande($db);

        $langs->load("orders");

        $othercom = $commande->liste_array(2, 1, null);
        if (is_array($othercom) && count($othercom))
        {
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftOrders").'</td><td>';
        	$html .= $form->selectarray("commandeid", $othercom, 0, 1);
        	$html .= '</td></tr>';
        }
        else
		{
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftOrders").'</td><td>';
        	$html .= $langs->trans("NoDraftOrders");
        	$html .= '</td></tr>';
        }
    }

    // Factures
    if (!empty($conf->facture->enabled) && $user->rights->facture->creer)
    {
    	$invoice = new Facture($db);

    	$langs->load("bills");

    	$otherinvoice = $invoice->liste_array(2, 1, null);
    	if (is_array($otherinvoice) && count($otherinvoice))
    	{
    		$html .= '<tr><td style="width: 200px;">';
    		$html .= $langs->trans("AddToDraftInvoices").'</td><td>';
    		$html .= $form->selectarray("factureid", $otherinvoice, 0, 1);
    		$html .= '</td></tr>';
    	}
    	else
    	{
    		$html .= '<tr><td style="width: 200px;">';
    		$html .= $langs->trans("AddToDraftInvoices").'</td><td>';
    		$html .= $langs->trans("NoDraftInvoices");
    		$html .= '</td></tr>';
    	}
    }

    //If any text is going to be printed, then we show the table
    if (!empty($html))
    {
	    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    	print '<input type="hidden" name="token" value="'.newToken().'">';
    	print '<input type="hidden" name="action" value="addin">';

	    print load_fiche_titre($langs->trans("AddToDraft"), '', '');

		dol_fiche_head('');

    	$html .= '<tr><td class="nowrap">'.$langs->trans("Quantity").' ';
    	$html .= '<input type="text" class="flat" name="qty" size="1" value="1"></td>';
        $html .= '<td class="nowrap">'.$langs->trans("ReductionShort").'(%) ';
    	$html .= '<input type="text" class="flat" name="remise_percent" size="1" value="0">';
    	$html .= '</td></tr>';

    	print '<table width="100%" class="border">';
        print $html;
        print '</table>';

        print '<div class="center">';
        print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
        print '</div>';

        dol_fiche_end();

        print '</form>';
    }
}


/*
 * Documents generes
 */

if ($action != 'create' && $action != 'edit' && $action != 'delete')
{
    print '<div class="fichecenter"><div class="fichehalfleft">';
    print '<a name="builddoc"></a>'; // ancre

    // Documents
    $objectref = dol_sanitizeFileName($object->ref);
    $relativepath = $comref.'/'.$objectref.'.pdf';
    $filedir = $conf->product->dir_output.'/'.$objectref;
    $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
    $genallowed = $usercanread;
    $delallowed = $usercancreate;

    print $formfile->showdocuments($modulepart, $object->ref, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
    $somethingshown = $formfile->numoffiles;

    print '</div><div class="fichehalfright"><div class="ficheaddleft">';

    $MAXEVENT = 10;

    $morehtmlright = '<a href="'.DOL_URL_ROOT.'/product/agenda.php?id='.$object->id.'">';
    $morehtmlright .= $langs->trans("SeeAll");
    $morehtmlright .= '</a>';

    // List of actions on element
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
    $formactions = new FormActions($db);
    $somethingshown = $formactions->showactions($object, 'product', 0, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for product

    print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
