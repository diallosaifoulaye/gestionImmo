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
 *  \file       htdocs/product/card.php
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
$type = (GETPOST('type', 'int') !== '') ? GETPOST('type', 'int') : Product::TYPE_PRODUCT;
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');
$duration_value = GETPOST('duration_value', 'int');
$duration_unit = GETPOST('duration_unit', 'alpha');
if (!empty($user->socid)) $socid = $user->socid;

$object = new Product($db);
$object->type = $type; // so test later to fill $usercancxxx is correct
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
    $objcanvas->getCanvas('product', 'card', $canvas);
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

$usercanread = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->lire) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->lire));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->creer) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->creer));
$usercandelete = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->supprimer) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->supprimer));
$createbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->creer_advance)) $createbarcode = 0;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
    $title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Card');
    $helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
    $title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Card');
    $helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

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
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------

    /*
     * Product card
     */

      if ($object->id > 0)
    {
        // Fiche en mode edition



        // Fiche en mode visu

            $showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
            if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

            $head = product_prepare_head($object);
            $titre = $langs->trans("CardProduct".$object->type);
            $picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

            dol_fiche_head($head, 'card', $titre, -1, $picto);

            $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
            $object->next_prev_filter = " fk_product_type = ".$object->type;

            $shownav = 1;
            if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

            dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');


            print '<div class="fichecenter">';
            print '<div class="fichehalfleft">';
            // Fiche de souscription lot
            print '<tr><td class="tdtop" align="center"><strong>'.$langs->trans("Fichesouslot").'</strong></td></tr>';
            print '<br/>';


            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">';

            // Type
            if (!empty($conf->product->enabled) && !empty($conf->service->enabled))
            {
                // TODO change for compatibility with edit in place
                $typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");

            }

            // Description
            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="2">'.(dol_textishtml($object->description) ? $object->description : dol_nl2br($object->description, 1, true)).'</td></tr>';

            if (empty($conf->global->PRODUCT_DISABLE_SURFACE))
            {
                // Brut Surface
                print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="2">';
                if ($object->surface != '')
                {
                    require_once DOL_DOCUMENT_ROOT . '/core/class/cunits.class.php';
                    $measuringUnits = new CUnits($db);
                    echo $object->surface_units ;
                    $result = $measuringUnits->fetch2($object->surface_units);
                    //var_dump();exit;

                    print $object->surface." ".$measuringUnits->short_label;
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
            }

            // Programmes
            if ($conf->categorie->enabled) {
                print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td colspan="3">';
                print $form->showCategories($object->id, Categorie::TYPE_PRODUCT, 1);
                print "</td></tr>";
            }

            // Note private
            if (!empty($conf->global->MAIN_DISABLE_NOTES_TAB))
            {
                print '<!-- show Note --> '."\n";
                print '<tr><td class="tdtop">'.$langs->trans("NotePrivate").'</td><td colspan="'.(2 + (($showphoto || $showbarcode) ? 1 : 0)).'">'.(dol_textishtml($object->note_private) ? $object->note_private : dol_nl2br($object->note_private, 1, true)).'</td></tr>'."\n";
                print '<!-- End show Note --> '."\n";
            }
            // Default warehouse
            if ($object->isProduct() && !empty($conf->stock->enabled))
            {
                $warehouse = new Entrepot($db);
                $warehouse->fetch($object->fk_default_warehouse);

                print '<tr><td>'.$langs->trans("DefaultWarehouse").'</td><td>';
                print (!empty($warehouse->id) ? $warehouse->getNomUrl(1) : '');
                print '</td>';
            }

            // Parent product.
            if (!empty($conf->variants->enabled) && ($object->isProduct() || $object->isService())) {
                $combination = new ProductCombination($db);

                if ($combination->fetchByFkProductChild($object->id) > 0) {
                    $prodstatic = new Product($db);
                    $prodstatic->fetch($combination->fk_product_parent);

                    // Parent product
                    print '<tr><td>'.$langs->trans("ParentProduct").'</td><td colspan="2">';
                    print $prodstatic->getNomUrl(1);
                    print '</td></tr>';
                }
            }

            print '</table>';
            print '</div>';
            print '<div class="fichehalfright"><div class="ficheaddleft">';

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border tableforfield" width="100%">';

            if ($object->isService())
            {
                // Duration
                print '<tr><td class="titlefield">'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
                if ($object->duration_value > 1)
                {
                    $dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hours"), "d"=>$langs->trans("Days"), "w"=>$langs->trans("Weeks"), "m"=>$langs->trans("Months"), "y"=>$langs->trans("Years"));
                }
                elseif ($object->duration_value > 0)
                {
                    $dur = array("i"=>$langs->trans("Minute"), "h"=>$langs->trans("Hour"), "d"=>$langs->trans("Day"), "w"=>$langs->trans("Week"), "m"=>$langs->trans("Month"), "y"=>$langs->trans("Year"));
                }
                print (!empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";

                print '</td></tr>';
            }
            else
            {

                // Other attributes
                $parameters = array('colspan' => ' colspan="'.(2 + (($showphoto || $showbarcode) ? 1 : 0)).'"', 'cols' => (2 + (($showphoto || $showbarcode) ? 1 : 0)));
                include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl_lot.php';

            }

                 print "</table>\n";
                print '</div>';

                print '</div></div>';


                print '<div style="clear:both"></div>';

                // Fiche de souscription lot
                print '<tr><td class="tdtop" align="center"><strong>'.$langs->trans("Fichesouscli").'</strong></td></tr>';
                print '<br/>';

                print '<tr>';
                print '<td class="fieldrequired">' . $langs->trans('Customer') . '</td>';
                if ($socid > 0) {
                    print '<td>';
                    print $soc->getNomUrl(1);
                    print '<input type="hidden" name="socid" value="' . $soc->id . '">';
                    print '</td>';
                    if (! empty($conf->global->SOCIETE_ASK_FOR_SHIPPING_METHOD) && ! empty($soc->shipping_method_id)) {
                        $shipping_method_id = $soc->shipping_method_id;
                    }
                } else {
                    print '<td>';
                    print $form->select_company('', 'socid', '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 'SelectThirdParty', 0, 0, null, 0, 'minwidth300');
                    // reload page to retrieve customer informations
                    if (!empty($conf->global->RELOAD_PAGE_ON_CUSTOMER_CHANGE))
                    {
                        print '<script type="text/javascript">
			$(document).ready(function() {
				$("#socid").change(function() {
					var socid = $(this).val();
					alert(socid);
					// reload page
					window.location.href = "'.$_SERVER["PHP_SELF"].'?action=create&socid="+socid+"&ref_client="+$("input[name=ref_client]").val();
				});
			});
			</script>';
                    }
                    print ' <a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'"><span class="valignmiddle text-plus-circle">'.$langs->trans("AddThirdParty").'</span><span class="fa fa-plus-circle valignmiddle paddingleft"></span></a>';
                    print '</td>';
                }
                print '</tr>' . "\n";

                if ($socid > 0)
                {
                    // Contacts (ask contact only if thirdparty already defined). TODO do this also into order and invoice.
                    print "<tr><td>" . $langs->trans("DefaultContact") . '</td><td>';
                    $form->select_contacts($soc->id, $contactid, 'contactid', 1, $srccontactslist);
                    print '</td></tr>';

                    // Ligne info remises tiers
                    print '<tr><td>' . $langs->trans('Discounts') . '</td><td>';

                    $absolute_discount = $soc->getAvailableDiscounts();

                    $thirdparty = $soc;
                    $discount_type = 0;
                    $backtopage = urlencode($_SERVER["PHP_SELF"] . '?socid=' . $thirdparty->id . '&action=' . $action . '&origin=' . GETPOST('origin') . '&originid=' . GETPOST('originid'));
                    include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
                    print '</td></tr>';
                }



                dol_fiche_end();
            }


    elseif ($action != 'create')
    {
        exit;
    }
}



// End of page
llxFooter();
$db->close();
