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


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/motif.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
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

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
if (! $sortfield) $sortfield="m.rowid";
if (! $sortorder) $sortorder="ASC";

$limit = $conf->liste_limit;

$action = GETPOST('action');


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


        $texte = $langs->trans("Motifs");


//$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
$sql = 'SELECT DISTINCT m.rowid, m.label, m.code, m.description,m.statut';
$sql.= ' FROM '.MAIN_DB_PREFIX.'motif as m';
if ($sall)
{
    $sql.= " AND (m.rowid LIKE '%".$db->escape($sall)."%' OR m.label LIKE '%".$db->escape($sall)."%' OR m.description LIKE '%".$db->escape($sall)."%' OR m.code LIKE '%".$db->escape($sall)."%')";
}

if ($srowid)     $sql.= " AND m.rowid like '%".$srowid."%'";
if ($scode)   $sql.= " AND m.code like '%".$scode."%'";
if ($snom)     $sql.= " AND m.label like '%".$db->escape($snom)."%'";

// Insert categ filter
$sql.= " GROUP BY m.rowid, m.label, m.code,m.description,m.statut";

$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit + 1 ,$offset);

//var_dump($sql);die();
$resql = $db->query($sql) ;

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



        $newcardbutton.= dolGetButtonTitle($langs->trans('NewMotif'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/motif/card.php?leftmenu=motif&amp;action=addMotif&amp;type=0');



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
      /*  $moreforfilter='';
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
        }*/

        // Lignes des titres
        print "<tr class=\"liste_titre\">";

        //print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "p.ref",$param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("AccountIdShort"), $_SERVER["PHP_SELF"], "p.ref", $param,"","",$sortfield,$sortorder);

        print_liste_field_titre($langs->trans("Label"), $_SERVER["PHP_SELF"], "p.label",$param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("code"), $_SERVER["PHP_SELF"], "p.nature_lot", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("description"), $_SERVER["PHP_SELF"], "p.type_lot", $param,"","",$sortfield,$sortorder);
        print_liste_field_titre($langs->trans("Statut"), $_SERVER["PHP_SELF"], "p.statut", $param,"","",$sortfield,$sortorder);



        print "</tr>\n";

        // Lignes des champs de filtre
        print '<tr class="liste_titre">';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="srowid" size="8" value="'.$srowid.'">';
        print '</td>';
        print '<td class="liste_titre" align="left">';
        print '<input class="flat" type="text" name="snom" size="12" value="'.$snom.'">';
        print '</td>';


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

            // Rowid
            print '<td nowrap="nowrap">';

            print '<a style="font-weight: bold; font-size: 13px;" href="card.php?id='.$obj->rowid.'&save_lastsearch_values=1">' . $objp->rowid. '</a>';

            print "</td>\n";

            // Label
            print '<td>'.dol_trunc($objp->label,40).'</td>';
            // code
            print '<td>'.dol_trunc($objp->code,40).'</td>';

             // code
            print '<td>'.dol_trunc($objp->description,40).'</td>';

            if($objp->statut == 1){
                print '<td>'.dol_trunc('Activé',40).'</td>';
            }
            elseif ($objp->statut == 0){
                print '<td>'.dol_trunc('Desactivé',40).'</td>';
            }


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
