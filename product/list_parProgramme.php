<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *  \file       htdocs/product/liste.php
 *  \ingroup    produit
 *  \brief      Page to list products and services
 *  \version    $Id: liste.php,v 1.152 2011/07/31 23:19:25 eldy Exp $
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
if ($conf->categorie->enabled) require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");

$langs->load("products");
$langs->load("stocks");

$sref=GETPOST("sref");
$sbarcode=GETPOST("sbarcode");
$snom=GETPOST("snom");
$sall=GETPOST("sall");
$type=GETPOST("type","int");
$search_sale = GETPOST("search_sale");
$search_categ = GETPOST("search_categ");
$tosell = GETPOST("tosell");
$tobuy = GETPOST("tobuy");

$rowid=GETPOST("rowid");
//print $rowid;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;

$action = GETPOST('action');

// Security check

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
//if (!empty($id)) $object->getCanvas($id);
$canvas = (!empty($object->canvas)?$object->canvas:GETPOST("canvas"));
if (! empty($canvas))
{
    require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
    $objcanvas = new Canvas($db,$action);
    //$objcanvas->getCanvas('product','xxx',$canvas);

    // Security check
    if ($type=='0') $result=$objcanvas->restrictedArea($user,'produit');
    else if ($type=='1') $result=$objcanvas->restrictedArea($user,'service');
    else $result=$objcanvas->restrictedArea($user,'produit|service');
}
else
{
    // Security check
    if ($type=='0') $result=restrictedArea($user,'produit');
    else if ($type=='1') $result=restrictedArea($user,'service');
    else $result=restrictedArea($user,'produit|service');
}


/*
 * Actions
 */

if (isset($_POST["button_removefilter_x"]))
{
    $sref="";
    $sbarcode="";
    $snom="";
}

if ($conf->categorie->enabled && GETPOST('catid'))
{
    $catid = GETPOST('catid','int');
}



/*
 * View
 */

$htmlother=new FormOther($db);


$sql1 = "SELECT * FROM " . MAIN_DB_PREFIX . "categorie WHERE rowid =".$rowid;
//var_dump($sql);
dol_syslog($script_file . " sql=" . $sql, LOG_DEBUG);
$resql1 = $db->query($sql1);
if ($resql1) {
            $obj1 = $db->fetch_object($resql1);
}

if (! empty($objcanvas->template_dir))
{
    $classname = 'Product'.ucfirst($canvas);
    include_once(DOL_DOCUMENT_ROOT.'/product/canvas/'.$canvas.'/product.'.$canvas.'.class.php');

    $object = new $classname($db);
    $object->getFieldList();
    $object->LoadListDatas($limit, $offset, $sortfield, $sortorder);
    $title = $object->getTitle();
}
else
{
    $title=$langs->trans("ProductsAndServices");

    if (isset($_GET["type"]))
    {
        if ($type==1)
        {
            $texte = $langs->trans("Services");
        }
        else
        {
            //$texte = $langs->trans("Products");
            $texte = $langs->trans("lot")." du programme : ".$obj1->label;
        }
    }
    else
    {
        $texte = $langs->trans("ProductsAndServices");
    }
}

//$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
/*$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, pe.nature_lot, pe.type_lot, pe.prix_gestion,pe.prix_commercial, pe.statut_lot, pe.type_lot, p.barcode, p.price, p.price_ttc, p.price_base_type, nat.label as labelnature,';
$sql.= ' p.fk_product_type, p.tms as datem,';
$sql.= ' p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe, '.MAIN_DB_PREFIX.'nature_lot as nat';*/

$sql = 'SELECT p.rowid, p.ref, p.label, pe.nature_lot, pe.type, pe.prix_gestion,pe.prix_commercial, pe.statut_lot, nat.label as labelnature';
//$sql.=',pe.type_lot, p.barcode, p.price, p.price_ttc, p.price_base_type, nat.label as labelnature,';
//$sql.= ' p.fk_product_type, p.tms as datem,';
//$sql.= ' p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte';
$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe ,'.MAIN_DB_PREFIX.'categorie as c,'.MAIN_DB_PREFIX.'categorie_product as cp,' .MAIN_DB_PREFIX.'nature_lot as nat';
 //$sql.=',' .MAIN_DB_PREFIX.'nature_lot as nat,  ';
//$sql.= " WHERE c.entity IN (" . getEntity('category') . ")";
/*$sql.= " AND p.rowid=pe.fk_object ";
$sql.= " AND c.rowid=cp.fk_categorie ";
$sql.= " AND p.rowid=cp.fk_product";
$sql.= " AND c.type = " . (int) $type;
$sql.= " AND c.rowid =". $rowid." GROUP BY p.ref" ;*/



// We'll need this table joined to the select in order to filter by categ
if ($search_categ) $sql.= ", ".MAIN_DB_PREFIX."categorie_product as cp";
if ($_GET["fourn_id"] > 0)  // The DISTINCT is used to avoid duplicate from this link
{
    $fourn_id = $_GET["fourn_id"];
    $sql.= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
}
$sql.= ' WHERE p.entity IN (0,'.(! empty($conf->entities['product']) ? $conf->entities['product'] : $conf->entity).')';




$sql.= " AND p.rowid = pe.fk_object";

$sql.= " AND c.rowid=cp.fk_categorie ";
$sql.= " AND p.rowid=cp.fk_product";
//$sql.= " AND nat.rowid = pe.nature_lot";
$sql.= " AND c.rowid =". $rowid ;




if ($search_categ) $sql.= " AND p.rowid = cp.fk_product";	// Join for the needed table to filter by categ
if ($sall)
{
    $sql.= " AND (p.ref LIKE '%".$db->escape($sall)."%' OR p.label LIKE '%".$db->escape($sall)."%' OR p.description LIKE '%".$db->escape($sall)."%' OR p.note LIKE '%".$db->escape($sall)."%')";
}
# if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($type))
{
    if ($type==1) {
        $sql.= " AND p.fk_product_type = '1'";
    } else {
        $sql.= " AND p.fk_product_type <> '1'";
    }
}
if ($sref)     $sql.= " AND p.ref like '%".$sref."%'";
if ($sbarcode) $sql.= " AND p.barcode like '%".$sbarcode."%'";
if ($snom)     $sql.= " AND p.label like '%".$db->escape($snom)."%'";
if (isset($tosell) && dol_strlen($tosell) > 0)
{
    $sql.= " AND p.tosell = ".$db->escape($tosell);
}
if (isset($tobuy) && dol_strlen($tobuy) > 0)
{
    $sql.= " AND p.tobuy = ".$db->escape($tobuy);
}
if (dol_strlen($canvas) > 0)
{
    $sql.= " AND p.canvas = '".$db->escape($canvas)."'";
}
if($catid)
{
    $sql.= " AND cp.fk_categorie = ".$catid;
}
if ($fourn_id > 0)
{
    $sql.= " AND p.rowid = pf.fk_product AND pf.fk_soc = ".$fourn_id;
}
// Insert categ filter
if ($search_categ)
{
    $sql .= " AND cp.fk_categorie = ".$db->escape($search_categ);
}
$sql.= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,";
$sql.= " p.fk_product_type, p.tms,";
$sql.= " p.duration, p.tosell, p.tobuy, p.seuil_stock_alerte";

if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1 ,$offset);
$resql = $db->query($sql) ;

//var_dump($sql);die();

if ($resql)
{
    $num = $db->num_rows($resql);

    $i = 0;

    if ($num == 1 && ($sall || $snom || $sref || $sbarcode) && $action != 'list')
    {
        $objp = $db->fetch_object($resql);
        Header("Location: fiche.php?id=".$objp->rowid);
        exit;
    }

    $helpurl='';
    if (isset($_GET["type"]) && $_GET["type"] == 0)
    {
        $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
    }
    if (isset($_GET["type"]) && $_GET["type"] == 1)
    {
        $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
    }



    $newcardbutton.= dolGetButtonTitle($langs->trans('NewLot'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/product/card.php?leftmenu=product&amp;action=create&amp;type=0');



    llxHeader('',$title,$helpurl,'');

    print  '<p align="right">'.$newcardbutton.'</p>';

    // Displays product removal confirmation
    if (GETPOST('delprod'))	dol_htmloutput_mesg($langs->trans("ProductDeleted",GETPOST('delprod')));

    $param="&amp;sref=".$sref.($sbarcode?"&amp;sbarcode=".$sbarcode:"")."&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy;
    $param.=($fourn_id?"&amp;fourn_id=".$fourn_id:"");
    $param.=isset($type)?"&amp;type=".$type:"";
    print_barre_liste($texte, $page, "liste.php", $param, $sortfield, $sortorder,'',$num);

    if (isset($catid))
    {
        print "<div id='ways'>";
        $c = new Categorie ($db, $catid);
        $ways = $c->print_all_ways(' &gt; ','product/liste.php');
        print " &gt; ".$ways[0]."<br>\n";
        print "</div><br>";
    }

    if (!empty($_GET["canvas"]) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/product.'.$_GET["canvas"].'.class.php'))
    {
        $fieldlist = $object->field_list;
        $datas = $object->list_datas;
        $picto='title.png';
        if (empty($conf->browser->firefox)) $picto='title.gif';
        $title_picto = img_picto('',$picto);
        $title_text = $title;

        // Default templates directory
        $template_dir = DOL_DOCUMENT_ROOT . '/product/canvas/'.$_GET["canvas"].'/tpl/';

        // Check if a custom template is present
        if (file_exists(DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$_GET["canvas"].'/list.tpl')
            || file_exists(DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$_GET["canvas"].'/list.tpl.php'))
        {
            $template_dir = DOL_DOCUMENT_ROOT . '/theme/'.$conf->theme.'/tpl/product/'.$_GET["canvas"].'/';
        }

        include($template_dir.'list.tpl.php');	// Include native PHP templates
    }
    else
    {
        print '<form action="liste.php" method="post" name="formulaire">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="list">';
        print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        print '<input type="hidden" name="type" value="'.$type.'">';

        print '<table class="liste" width="100%">';

        // Filter on categories
        $moreforfilter='';
        if ($conf->categorie->enabled)
        {
            $moreforfilter.=$langs->trans('Categories'). ': ';
            $moreforfilter.=$htmlother->select_categories(0,$search_categ,'search_categ');
            $moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
        }
        if ($moreforfilter)
        {
            print '<tr class="liste_titre">';
            print '<td class="liste_titre" colspan="9">';
            print $moreforfilter;
            print '</td></tr>';
        }

        // Lignes des titres
        print "<tr class=\"liste_titre\">";

        //print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("AccountIdShort"), $_SERVER["PHP_SELF"], "p.ref", $param,"","",$sortfield,$sortorder);

        print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label",$param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("NatureLot"), $_SERVER["PHP_SELF"], "p.nature_lot", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Type"), $_SERVER["PHP_SELF"], "p.type_lot", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("PrixGestion"), $_SERVER["PHP_SELF"], "p.type_lot", $param,"",'class="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("PrixCommercial"), $_SERVER["PHP_SELF"], "p.type_lot", $param,"",'class="right"',$sortfield,$sortorder);
        print_liste_field_titre($langs->trans(""), $_SERVER["PHP_SELF"], "p.statut", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Statut"), $_SERVER["PHP_SELF"], "p.statut", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans(""), $_SERVER["PHP_SELF"], "p.statut", $param,"","",$sortfield,$sortorder);





        print "</tr>\n";

        // Lignes des champs de filtre
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="sref" size="8" value="'.$sref.'">';
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="snom" size="12" value="'.$snom.'">';
        print '</td>';
        if ($conf->barcode->enabled)
        {
            print '<td class="liste_titre">';
            print '<input class="flat" type="text" name="sbarcode" size="6" value="'.$sbarcode.'">';
            print '</td>';
        }
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';
        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';

        // Duration
        if ($conf->service->enabled && $type != 0)
        {
            print '<td class="liste_titre">';
            print '&nbsp;';
            print '</td>';
        }

        // Sell price
        if (empty($conf->global->PRODUIT_MULTIPRICES))
        {
            print '<td class="liste_titre">';
            print '&nbsp;';
            print '</td>';
        }

        // Stock
        if ($conf->stock->enabled && $user->rights->stock->lire && $type != 1)
        {
            print '<td class="liste_titre">';
            print '&nbsp;';
            print '</td>';
        }

        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';

        print '<td class="liste_titre">';
        print '&nbsp;';
        print '</td>';

        print '<td colspan="2" class="liste_titre" align="right">';
        print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '<input type="image" class="liste_titre" name="button_removefilter" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
        print '</td>';
        print '</tr>';


        $product_static=new Product($db);

        $var=True;
        while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($resql);
            //var_dump($objp);die();

            // Multilangs
            if ($conf->global->MAIN_MULTILANGS) // si l'option est active
            {
                $sql = "SELECT label";
                $sql.= " FROM ".MAIN_DB_PREFIX."product_lang";
                $sql.= " WHERE fk_product=".$objp->rowid;
                $sql.= " AND lang='". $langs->getDefaultLang() ."'";
                $sql.= " LIMIT 1";

                $result = $db->query($sql);
                if ($result)
                {
                    $objtp = $db->fetch_object($result);

                    if ($objtp->label != '') $objp->label = $objtp->label;
                }
            }

            $var=!$var;
            print '<tr '.$bc[$var].'>';

            // Ref
            print '<td nowrap="nowrap">';
            $product_static->id = $objp->rowid;
            $product_static->ref = $objp->ref;
            $product_static->type = $objp->fk_product_type;
            print $product_static->getNomUrl(1,'',24);
            print "</td>\n";

            // Label
            print '<td>'.dol_trunc($objp->label,40).'</td>';


            // Nature
            print '<td>'.dol_trunc($objp->labelnature,40).'</td>';

            if($objp->type_lot == 1){
                print '<td>'.dol_trunc('1p',40).'</td>';
            }
            elseif ($objp->type_lot == 2){
                print '<td>'.dol_trunc('2p',40).'</td>';
            }
            elseif ($objp->type_lot == 3){
                print '<td>'.dol_trunc('3p',40).'</td>';
            }
            elseif ($objp->type_lot == 4){
                print '<td>'.dol_trunc('4p',40).'</td>';
            }
            elseif ($objp->type_lot == 5){
                print '<td>'.dol_trunc('5p',40).'</td>';
            }
            elseif ($objp->type_lot == 6){
                print '<td>'.dol_trunc('garage',40).'</td>';
            }
            elseif ($objp->type_lot == 7){
                print '<td>'.dol_trunc('Pk-ext.',40).'</td>';
            }
            elseif ($objp->type_lot == 8){
                print '<td>'.dol_trunc('Pk-int.',40).'</td>';
            }else  print '<td> </td>';


            print '<td align="right">';
            print price($objp->prix_gestion);
            print '</td>';

            print '<td align="right">';
            print price($objp->prix_commercial);
            print '</td>';


            print '<td align="right">';
           // print price($objp->prix_commercial);
            print '</td>';



            // Barcode
           /* if ($conf->barcode->enabled)
            {
                print '<td align="right">'.$objp->barcode.'</td>';
            }*/


            if($objp->statut_lot == 1){
                print '<td>'.dol_trunc('En Stock',40).'</td>';
            }
            elseif ($objp->statut_lot == 2){
                print '<td>'.dol_trunc('Option',40).'</td>';
            }
            elseif ($objp->statut_lot == 3){
                print '<td>'.dol_trunc('En allotement',40).'</td>';
            }
            elseif ($objp->statut_lot == 4){
                print '<td>'.dol_trunc('Réservé',40).'</td>';
            }
            elseif ($objp->statut_lot == 5){
                print '<td>'.dol_trunc('Non offert',40).'</td>';
            }
            elseif ($objp->statut_lot == 6){
                print '<td>'.dol_trunc('Vendu',40).'</td>';
            }
            elseif ($objp->statut_lot == 7){
                print '<td>'.dol_trunc('Livré',40).'</td>';
            }
            else  print '<td> </td>';

            print '<td align="right">';
            //print '<a href="card.php?leftmenu=product&rowid='.$obj->rowid.'" title="Modifier"><i class="fas fa-edit" style="margin-right:8px;"></i></a>';
            print '<a href="card.php?action=edit&id='.$objp->rowid.'" title="Modifier"><i class="fas fa-edit" style="margin-right:8px;"></i></a>';

            print '</td>';
            print "</tr>\n";
            $i++;
        }




if ($num > $conf->liste_limit)
        {
            if ($sref || $snom || $sall || $sbarcode || $_POST["search"])
            {
                print_barre_liste('', $page, "liste.php", "&amp;sref=".$sref."&amp;snom=".$snom."&amp;sall=".$sall."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy, $sortfield, $sortorder,'',$num);
            }
            else
            {
                print_barre_liste('', $page, "liste.php", "&amp;sref=$sref&amp;snom=$snom&amp;fourn_id=$fourn_id".(isset($type)?"&amp;type=$type":"")."&amp;tosell=".$tosell."&amp;tobuy=".$tobuy, $sortfield, $sortorder,'',$num);
            }
        }

        $db->free($resql);

        print "</table>";
        print '</form>';
    }
}
else
{
    dol_print_error($db);
}


$db->close();

llxFooter('$Date: 2011/07/31 23:19:25 $ - $Revision: 1.152 $');
?>
