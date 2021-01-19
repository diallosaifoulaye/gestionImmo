<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Marcos García		 <marcosgdf@gmail.com>
 * Copyright (C) 2015	   Juanjo Menent		 <jmenent@2byte.es>
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
 *	    \file       htdocs/compta/paiement/card.php
 *		\ingroup    facture
 *		\brief      Page of a customer payment
 *		\remarks	Nearly same file than fournisseur/paiement/card.php
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
if (! empty($conf->banque->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills','banks','companies'));

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$backtopage=GETPOST('backtopage', 'alpha');

// Security check
if ($user->societe_id) $socid=$user->societe_id;
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$object = new Paiement($db);


/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->facture->paiement)
{
    $db->begin();

    $object->fetch($id);
    $result = $object->update_note(GETPOST('note', 'none'));
    if ($result > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
        setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
    }
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->facture->paiement)
{
    $db->begin();

    $object->fetch($id);
    $result = $object->delete();
    if ($result > 0)
    {
        $db->commit();

        if ($backtopage)
        {
            header("Location: ".$backtopage);
            exit;
        }
        else
        {
            header("Location: list.php");
            exit;
        }
    }
    else
    {
        $langs->load("errors");
        setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
    }
}

if ($action == 'confirm_valide' && $confirm == 'yes' && $user->rights->facture->paiement)
{
    $db->begin();

    $object->fetch($id);
    if ($object->valide($user) > 0)
    {
        $db->commit();

        // Loop on each invoice linked to this payment to rebuild PDF
        $factures=array();
        foreach($factures as $id)
        {
            $fac = new Facture($db);
            $fac->fetch($id);

            $outputlangs = $langs;
            if (! empty($_REQUEST['lang_id']))
            {
                $outputlangs = new Translate("", $conf);
                $outputlangs->setDefaultLang($_REQUEST['lang_id']);
            }
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                $fac->generateDocument($fac->modelpdf, $outputlangs);
            }
        }

        header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);
        exit;
    }
    else
    {
        $langs->load("errors");
        setEventMessages($object->error, $object->errors, 'errors');
        $db->rollback();
    }
}

if ($action == 'setnum_paiement' && ! empty($_POST['num_paiement']))
{
    $object->fetch($id);
    $res = $object->update_num($_POST['num_paiement']);
    if ($res === 0)
    {
        setEventMessages($langs->trans('PaymentNumberUpdateSucceeded'), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans('PaymentNumberUpdateFailed'), null, 'errors');
    }
}

if ($action == 'setdatep' && ! empty($_POST['datepday']))
{
    $object->fetch($id);
    $datepaye = dol_mktime(GETPOST('datephour', 'int'), GETPOST('datepmin', 'int'), GETPOST('datepsec', 'int'), GETPOST('datepmonth', 'int'), GETPOST('datepday', 'int'), GETPOST('datepyear', 'int'));
    $res = $object->update_date($datepaye);
    if ($res === 0)
    {
        setEventMessages($langs->trans('PaymentDateUpdateSucceeded'), null, 'mesgs');
    }
    else
    {
        setEventMessages($langs->trans('PaymentDateUpdateFailed'), null, 'errors');
    }
}


/*
 * View
 */

llxHeader('', $langs->trans("Payment"));

$thirdpartystatic=new Societe($db);

$result=$object->fetch($id, $ref);
if ($result <= 0)
{
    dol_print_error($db, 'Payement '.$id.' not found in database');
    exit;
}

$form = new Form($db);

$head = payment_prepare_head($object);

dol_fiche_head($head, 'payment', $langs->trans("PaymentCustomerInvoice"), -1, 'payment');

// Confirmation de la suppression du paiement
if ($action == 'delete')
{
    print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete', '', 0, 2);
}

// Confirmation de la validation du paiement
if ($action == 'valide')
{
    $facid = $_GET['facid'];
    print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide', '', 0, 2);
}

$linkback = '<a href="' . DOL_URL_ROOT . '/compta/paiement/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent">'."\n";

// Date payment
print '<tr><td class="titlefield">'.$form->editfieldkey("Date", 'datep', $object->date, $object, $user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Date", 'datep', $object->date, $object, $user->rights->facture->paiement, 'datehourpicker', '', null, $langs->trans('PaymentDateUpdateSucceeded'));
print '</td></tr>';

// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$object->type_code)!=("PaymentType".$object->type_code)?$langs->trans("PaymentType".$object->type_code):$object->type_libelle;
print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>'.$labeltype;
print $object->num_paiement?' - '.$object->num_paiement:'';
print '</td></tr>';

// Amount
print '<tr><td>'.$langs->trans('Amount').'</td><td>'.price($object->amount, '', $langs, 0, -1, -1, $conf->currency).'</td></tr>';

$disable_delete = 0;
// Bank account
if (! empty($conf->banque->enabled))
{
    $bankline=new AccountLine($db);

    if ($object->fk_account > 0)
    {
        $bankline->fetch($object->bank_line);
        if ($bankline->rappro)
        {
            $disable_delete = 1;
            $title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
        }

        print '<tr>';
        print '<td>'.$langs->trans('BankAccount').'</td>';
        print '<td>';
        $accountstatic=new Account($db);
        $accountstatic->fetch($bankline->fk_account);
        print $accountstatic->getNomUrl(1);
        print '</td>';
        print '</tr>';
    }
}

// Payment numero
/*
$titlefield=$langs->trans('Numero').' <em>('.$langs->trans("ChequeOrTransferNumber").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'num_paiement',$object->num_paiement,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('PaymentNumberUpdateSucceeded'));
print '</td></tr>';

// Check transmitter
$titlefield=$langs->trans('CheckTransmitter').' <em>('.$langs->trans("ChequeMaker").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqemetteur',$object->,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqemetteur',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeMakeUpdateSucceeded'));
print '</td></tr>';

// Bank name
$titlefield=$langs->trans('Bank').' <em>('.$langs->trans("ChequeBank").')</em>';
print '<tr><td>'.$form->editfieldkey($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer).'</td><td>';
print $form->editfieldval($titlefield,'chqbank',$object->aaa,$object,$object->statut == 0 && $user->rights->fournisseur->facture->creer,'string','',null,$langs->trans('ChequeBankUpdateSucceeded'));
print '</td></tr>';
*/

// Bank account
if (! empty($conf->banque->enabled))
{
    if ($object->fk_account > 0)
    {
        if ($object->type_code == 'CHQ' && $bankline->fk_bordereau > 0)
        {
            dol_include_once('/compta/paiement/cheque/class/remisecheque.class.php');
            $bordereau = new RemiseCheque($db);
            $bordereau->fetch($bankline->fk_bordereau);

            print '<tr>';
            print '<td>'.$langs->trans('CheckReceipt').'</td>';
            print '<td>';
            print $bordereau->getNomUrl(1);
            print '</td>';
            print '</tr>';
        }
    }

    print '<tr>';
    print '<td>'.$langs->trans('BankTransactionLine').'</td>';
    print '<td>';
    print $bankline->getNomUrl(1, 0, 'showconciliated');
    print '</td>';
    print '</tr>';
}

// Comments
print '<tr><td class="tdtop">'.$form->editfieldkey("Comments", 'note', $object->note, $object, $user->rights->facture->paiement).'</td><td>';
print $form->editfieldval("Note", 'note', $object->note, $object, $user->rights->facture->paiement, 'textarea:'.ROWS_3.':90%');
print '</td></tr>';

print '</table>';

print '</div>';

dol_fiche_end();


/*
 * List of invoices
 */
$id_invoice=0;
$sql = 'SELECT f.rowid as facid, f.ref, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, s.nom as name, s.rowid as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql.= ' WHERE pf.fk_facture = f.rowid';
$sql.= ' AND f.fk_soc = s.rowid';
$sql.= ' AND f.entity IN ('.getEntity('invoice').')';
$sql.= ' AND pf.fk_paiement = '.$object->id;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    $i = 0;
    $total = 0;

    $moreforfilter='';

    print '<br>';

    print '<div class="div-table-responsive">';
    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans('Bill').'</td>';
    print '<td>'.$langs->trans('Company').'</td>';
    if($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED )print '<td>'.$langs->trans('Entity').'</td>';
    print '<td class="right">'.$langs->trans('ExpectedToPay').'</td>';
    print '<td class="right">'.$langs->trans('PayedByThisPayment').'</td>';
    print '<td class="right">'.$langs->trans('RemainderToPay').'</td>';
    print '<td class="right">'.$langs->trans('Status').'</td>';
    print "</tr>\n";

    if ($num > 0)
    {
        while ($i < $num)
        {
            $objp = $db->fetch_object($resql);

            $thirdpartystatic->fetch($objp->socid);

            $invoice=new Facture($db);
            $invoice->fetch($objp->facid);

            $paiement = $invoice->getSommePaiement();
            $creditnotes=$invoice->getSumCreditNotesUsed();
            $deposits=$invoice->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits, 'MT');
            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits, 'MT');

           // echo '<pre>';var_dump($invoice->id);die;
            if($id_invoice==0)
            {
                $invoice->fetch_lines();
                $nblignes = sizeof($invoice->lines);
                for ($j =0 ; $j < $nblignes ; $j++)
                {
                    if($invoice->lines[$j]->rowpr)
                    {
                        $id_invoice=$invoice->id;
                        break;
                    }
                }
            }

            print '<tr class="oddeven">';

            // Invoice
            print '<td>';
            print $invoice->getNomUrl(1);
            print "</td>\n";

            // Third party
            print '<td>';
            print $thirdpartystatic->getNomUrl(1);
            print '</td>';

            // Expected to pay
            if($conf->global->MULTICOMPANY_INVOICE_SHARING_ENABLED ){
                print '<td>';
                $mc->getInfo($objp->entity);
                print $mc->label;
                print '</td>';
            }
            // Expected to pay
            print '<td class="right">'.price($objp->total_ttc).'</td>';

            // Amount payed
            print '<td class="right">'.price($objp->amount).'</td>';

            // Remain to pay
            print '<td class="right">'.price($remaintopay).'</td>';

            // Status
            print '<td class="right">'.$invoice->getLibStatut(5, $alreadypayed).'</td>';

            print "</tr>\n";
            if ($objp->paye == 1)	// If at least one invoice is paid, disable delete
            {
                $disable_delete = 1;
                $title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid"));
            }
            $total = $total + $objp->amount;
            $i++;
        }
    }


    print "</table>\n";
    print '</div>';

    if ($action == 'builddoc')	// En get ou en post
    {
        $model = GETPOST('model');

        $ref_fac=GETPOST("ref_fac",'int');
        $invoice2=new Facture($db);
        //var_dump($ref_fac);die;
        $invoice2->fetch($ref_fac);
        $invoice2->fetch_lines();
        $paiement2 = new Paiement($db);
        $paiement2->fetch($id);
        $bankline2=new AccountLine($db);
        $bankline2->fetch($paiement2->bank_line);
        $compte = new Account($db);
        $compte->fetch($bankline2->fk_account);
        //$pr2= new Product($db);
       // var_dump($invoice2->lines[0]->fk_product);die;
       // $pr2->fetch($invoice2->lines[0]->fk_product);
         //var_dump($pr2);die;
        include_once(DOL_DOCUMENT_ROOT .'/compta/mesdocs/modeles/'.$model.'.php');
        $dir = $conf->facture->dir_output . "/" . $object->ref;
        //$dir = $conf->facture->dir_output . "/" . $invoice2->ref.''.dol_sanitizeFileName($object->ref);


        if (! file_exists($dir))
        {
            if (create_exdir($dir) < 0)
            {
                $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }
        $file = $dir . "/" ;
        // get the HTML

        Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
        exit;
    }
//
     print '<table width="100%"><tr><td width="50%" valign="top">';
    print '<a name="builddoc"></a>'; // ancre

    print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" name="builddoc" method="post">';
   // echo '<pre>';var_dump($invoice->id);die;
     //$invoice->fetch($id_invoice);
    print '<input type="hidden" name="action" value="builddoc">';
    print '<input type="hidden" name="ref_fac" value="'.$invoice->id.'">';
    print '<input type="hidden" name="token" value="4fba6c79d67311bf4ad9ce1b9ab8b8b8">';
    print '<div class="titre">Fichiers joints</div>';
    print '<table class="border formdoc" summary="listofdocumentstable" width="100%">';
    print '<tr class="pair"><td align="center" class="formdoc">Mod&egrave;le';
    print '<select id="model" class="flat" name="model" >';
    print '<option value="depot" selected="selected">reçu de paiement 1</option>';
    print '<option value="depot2" >reçu de paiement 2</option>';
    print '<option value="versement2">versement</option>';
    print '<option value="versement3">versement 2</option>';
    print '<option value="versement4">versement 3</option>';
    print '<option value="promesse_vente2">promesse vente</option>';
    print '<option value="attestation_solde">Attestation de solde</option>';
    print '<option value="attestation_vente">Attestation de vente</option>';
    print '<option value="remise_cle">Remise de clé</option>';
    print '</select></td>';
    print '<td align="center" class="formdoc">&nbsp;</td>';
    print '<td align="center" colspan="2" class="formdocbutton">';
    print '<input class="button" id="builddoc_generatebutton" type="submit" value="G&eacute;n&eacute;rer">';
    print '</td></tr></table>';
    print '</form>';
    $filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
    /* print 'dir '.$conf->facture->dir_output . "/" . $invoice2->ref.''.dol_sanitizeFileName($object->ref).'<br />';
     print 'filedir '.$filedir.'<br />';
     print 'refav '.$ref_fac.'<br />';*/
    /*if($dossier = @opendir($filedir))
    {
        $out = '<div class="titre">'.$titletoshow.'</div>';
        $out.= '<table class="border" summary="listofdocumentstable" width="100%">';
        while ($Fichier = readdir($dossier)) {
            if ($Fichier != "." && $Fichier != "..") {
                $nomFichier = $filedir."/".$Fichier;
                //echo $nomFichier."<BR>";
                $out.= "<tr ".$bc[$var].">";
                $out.= '<td >';
                print $nomFichier;
                //$out.= '<a href="'.$nomFichier.'" target="_blank">';
                $out.= '<a href="'.DOL_URL_ROOT . '/document.php?modulepart=paiement&amp;file='.urlencode($nomFichier).'" target="_blank">';
                $out.= img_mime($Fichier,$langs->trans("File").': '.$Fichier).' '.dol_trunc($Fichier,25).'('.dol_print_size(dol_filesize($nomFichier)).')';
                $out.= '</a>';
                $out.= '</td>';
                $out.= '<td nowrap="nowrap" >';
                $out.= '</td>';
                $out.= '</tr>';
            }
        }

        closedir($dossier);
        $out.= '</table>';
        print $out;


    }*/
    print '</td><td valign="top" width="50%">';

    print '<br>';


    /*
     * Linked object block
     */
    // $somethingshown=$object->showLinkedObjectBlock();
    print '<br>';



    print '</td></tr></table>';



    $db->free($resql);
}
else
{
    dol_print_error($db);
}



/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

/*if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
{
	if ($user->societe_id == 0 && $object->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}

if ($user->societe_id == 0 && $action == '')
{
	if ($user->rights->facture->paiement)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$title_button.'">'.$langs->trans('Delete').'</a>';
		}
	}
}*/


print '</div>';

function showdocuments($modulepart, $modulesubdir, $filedir, $urlsource, $genallowed, $delallowed = 0, $modelselected = '', $allowgenifempty = 1, $forcenomultilang = 0, $iconPDF = 0, $notused = 0, $noform = 0, $param = '', $title = '', $buttonlabel = '', $codelang = '', $morepicto = '', $object = null, $hideifempty = 0)
{
    // Deprecation warning
    if (!empty($iconPDF)) {
        dol_syslog(__METHOD__.": passing iconPDF parameter is deprecated", LOG_WARNING);
    }

    global $langs, $conf, $user, $hookmanager;
    global $form;

    if (!is_object($form)) $form = new Form($this->db);

    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    // For backward compatibility
    if (!empty($iconPDF)) {
        return $this->getDocumentsLink($modulepart, $modulesubdir, $filedir);
    }

    // Add entity in $param if not already exists
    if (!preg_match('/entity\=[0-9]+/', $param)) {
        $param .= 'entity='.(!empty($object->entity) ? $object->entity : $conf->entity);
    }

    $printer = 0;
    if (in_array($modulepart, array('facture', 'supplier_proposal', 'propal', 'proposal', 'order', 'commande', 'expedition', 'commande_fournisseur', 'expensereport', 'livraison', 'ticket')))	// The direct print feature is implemented only for such elements
    {
        $printer = (!empty($user->rights->printing->read) && !empty($conf->printing->enabled)) ?true:false;
    }

    $hookmanager->initHooks(array('formfile'));

    // Get list of files
    $file_list = null;
    if (!empty($filedir))
    {
        $file_list = dol_dir_list($filedir, 'files', 0, '', '(\.meta|_preview.*.*\.png)$', 'date', SORT_DESC);
    }
    if ($hideifempty && empty($file_list)) return '';

    $out = '';
    $forname = 'builddoc';
    $headershown = 0;
    $showempty = 0;
    $i = 0;

    $out .= "\n".'<!-- Start show_document -->'."\n";
    //print 'filedir='.$filedir;

    if (preg_match('/massfilesarea_/', $modulepart))
    {
        $out .= '<div id="show_files"><br></div>'."\n";
        $title = $langs->trans("MassFilesArea").' <a href="" id="togglemassfilesarea" ref="shown">('.$langs->trans("Hide").')</a>';
        $title .= '<script>
				jQuery(document).ready(function() {
					jQuery(\'#togglemassfilesarea\').click(function() {
						if (jQuery(\'#togglemassfilesarea\').attr(\'ref\') == "shown")
						{
							jQuery(\'#'.$modulepart.'_table\').hide();
							jQuery(\'#togglemassfilesarea\').attr("ref", "hidden");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Show")).')");
						}
						else
						{
							jQuery(\'#'.$modulepart.'_table\').show();
							jQuery(\'#togglemassfilesarea\').attr("ref","shown");
							jQuery(\'#togglemassfilesarea\').text("('.dol_escape_js($langs->trans("Hide")).')");
						}
						return false;
					});
				});
				</script>';
    }

    $titletoshow = $langs->trans("Documents");
    if (!empty($title)) $titletoshow = $title;

    // Show table
    if ($genallowed)
    {
        $modellist = array();

        if ($modulepart == 'company')
        {
            $showempty = 1;
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
                $modellist = ModeleThirdPartyDoc::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'propal')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
                $modellist = ModelePDFPropales::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'supplier_proposal')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
                $modellist = ModelePDFSupplierProposal::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'commande')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
                $modellist = ModelePDFCommandes::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'expedition')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
                $modellist = ModelePDFExpedition::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'reception')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/reception/modules_reception.php';
                $modellist = ModelePdfReception::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'livraison')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
                $modellist = ModelePDFDeliveryOrder::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'ficheinter')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
                $modellist = ModelePDFFicheinter::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'facture')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
                $modellist = ModelePDFFactures::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'contract')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
                $modellist = ModelePDFContract::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'project')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
                $modellist = ModelePDFProjects::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'project_task')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
                $modellist = ModelePDFTask::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'product')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
                $modellist = ModelePDFProduct::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'product_batch')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/product_batch/modules_product_batch.class.php';
                $modellist = ModelePDFProductBatch::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'stock')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_stock.php';
                $modellist = ModelePDFStock::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'movement')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
                $modellist = ModelePDFMovement::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'export')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
                $modellist = ModeleExports::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'commande_fournisseur' || $modulepart == 'supplier_order')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
                $modellist = ModelePDFSuppliersOrders::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'facture_fournisseur' || $modulepart == 'supplier_invoice')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
                $modellist = ModelePDFSuppliersInvoices::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'supplier_payment')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_payment/modules_supplier_payment.php';
                $modellist = ModelePDFSuppliersPayments::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'remisecheque')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';
                $modellist = ModeleChequeReceipts::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'donation')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
                $modellist = ModeleDon::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'member')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/member/modules_cards.php';
                $modellist = ModelePDFCards::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'agenda' || $modulepart == 'actions')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/action/modules_action.php';
                $modellist = ModeleAction::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'expensereport')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
                $modellist = ModeleExpenseReport::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'unpaid')
        {
            $modellist = '';
        }
        elseif ($modulepart == 'user')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/user/modules_user.class.php';
                $modellist = ModelePDFUser::liste_modeles($this->db);
            }
        }
        elseif ($modulepart == 'usergroup')
        {
            if (is_array($genallowed)) $modellist = $genallowed;
            else
            {
                include_once DOL_DOCUMENT_ROOT.'/core/modules/usergroup/modules_usergroup.class.php';
                $modellist = ModelePDFUserGroup::liste_modeles($this->db);
            }
        }
        else
        {
            $submodulepart = $modulepart;

            // modulepart = 'nameofmodule' or 'nameofmodule:nameofsubmodule'
            $tmp = explode(':', $modulepart);
            if (!empty($tmp[1])) {
                $modulepart = $tmp[0];
                $submodulepart = $tmp[1];
            }

            // For normalized standard modules
            $file = dol_buildpath('/core/modules/'.$modulepart.'/modules_'.$submodulepart.'.php', 0);
            if (file_exists($file))
            {
                $res = include_once $file;
            }
            // For normalized external modules.
            else
            {
                $file = dol_buildpath('/'.$modulepart.'/core/modules/'.$modulepart.'/modules_'.$submodulepart.'.php', 0);
                $res = include_once $file;
            }
            $class = 'ModelePDF'.ucfirst($submodulepart);

            if (class_exists($class))
            {
                $modellist = call_user_func($class.'::liste_modeles', $this->db);
            }
            else
            {
                dol_print_error($this->db, "Bad value for modulepart '".$modulepart."' in showdocuments");
                return -1;
            }
        }

        // Set headershown to avoid to have table opened a second time later
        $headershown = 1;

        if (empty($buttonlabel)) $buttonlabel = $langs->trans('Generate');

        if ($conf->browser->layout == 'phone') $urlsource .= '#'.$forname.'_form'; // So we switch to form after a generation
        if (empty($noform)) $out .= '<form action="'.$urlsource.(empty($conf->global->MAIN_JUMP_TAG) ? '' : '#builddoc').'" id="'.$forname.'_form" method="post">';
        $out .= '<input type="hidden" name="action" value="builddoc">';
        $out .= '<input type="hidden" name="token" value="'.newToken().'">';

        $out .= load_fiche_titre($titletoshow, '', '');
        $out .= '<div class="div-table-responsive-no-min">';
        $out .= '<table class="liste formdoc noborder centpercent">';

        $out .= '<tr class="liste_titre">';

        $addcolumforpicto = ($delallowed || $printer || $morepicto);
        $colspan = (3 + ($addcolumforpicto ? 1 : 0)); $colspanmore = 0;

        $out .= '<th colspan="'.$colspan.'" class="formdoc liste_titre maxwidthonsmartphone center">';

        // Model
        if (!empty($modellist))
        {
            asort($modellist);
            $out .= '<span class="hideonsmartphone">'.$langs->trans('Model').' </span>';
            if (is_array($modellist) && count($modellist) == 1)    // If there is only one element
            {
                $arraykeys = array_keys($modellist);
                $modelselected = $arraykeys[0];
            }
            $out .= $form->selectarray('model', $modellist, $modelselected, $showempty, 0, 0, '', 0, 0, 0, '', 'minwidth100');
            if ($conf->use_javascript_ajax)
            {
                $out .= ajax_combobox('model');
            }
        }
        else
        {
            $out .= '<div class="float">'.$langs->trans("Files").'</div>';
        }

        // Language code (if multilang)
        if (($allowgenifempty || (is_array($modellist) && count($modellist) > 0)) && $conf->global->MAIN_MULTILANGS && !$forcenomultilang && (!empty($modellist) || $showempty))
        {
            include_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
            $formadmin = new FormAdmin($this->db);
            $defaultlang = $codelang ? $codelang : $langs->getDefaultLang();
            $morecss = 'maxwidth150';
            if ($conf->browser->layout == 'phone') $morecss = 'maxwidth100';
            $out .= $formadmin->select_language($defaultlang, 'lang_id', 0, null, 0, 0, 0, $morecss);
        }
        else
        {
            $out .= '&nbsp;';
        }

        // Button
        $genbutton = '<input class="button buttongen" id="'.$forname.'_generatebutton" name="'.$forname.'_generatebutton"';
        $genbutton .= ' type="submit" value="'.$buttonlabel.'"';
        if (!$allowgenifempty && !is_array($modellist) && empty($modellist)) $genbutton .= ' disabled';
        $genbutton .= '>';
        if ($allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid')
        {
            $langs->load("errors");
            $genbutton .= ' '.img_warning($langs->transnoentitiesnoconv("WarningNoDocumentModelActivated"));
        }
        if (!$allowgenifempty && !is_array($modellist) && empty($modellist) && empty($conf->dol_no_mouse_hover) && $modulepart != 'unpaid') $genbutton = '';
        if (empty($modellist) && !$showempty && $modulepart != 'unpaid') $genbutton = '';
        $out .= $genbutton;
        $out .= '</th>';

        if (!empty($hookmanager->hooks['formfile']))
        {
            foreach ($hookmanager->hooks['formfile'] as $module)
            {
                if (method_exists($module, 'formBuilddocLineOptions'))
                {
                    $colspanmore++;
                    $out .= '<th></th>';
                }
            }
        }
        $out .= '</tr>';

        // Execute hooks
        $parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart);
        if (is_object($hookmanager))
        {
            $reshook = $hookmanager->executeHooks('formBuilddocOptions', $parameters, $GLOBALS['object']);
            $out .= $hookmanager->resPrint;
        }
    }

    // Get list of files
    if (!empty($filedir))
    {
        $link_list = array();
        if (is_object($object))
        {
            require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
            $link = new Link($this->db);
            $sortfield = $sortorder = null;
            $res = $link->fetchAll($link_list, $object->element, $object->id, $sortfield, $sortorder);
        }

        $out .= '<!-- html.formfile::showdocuments -->'."\n";

        // Show title of array if not already shown
        if ((!empty($file_list) || !empty($link_list) || preg_match('/^massfilesarea/', $modulepart))
            && !$headershown)
        {
            $headershown = 1;
            $out .= '<div class="titre">'.$titletoshow.'</div>'."\n";
            $out .= '<div class="div-table-responsive-no-min">';
            $out .= '<table class="noborder centpercent" id="'.$modulepart.'_table">'."\n";
        }

        // Loop on each file found
        if (is_array($file_list))
        {
            foreach ($file_list as $file)
            {
                // Define relative path for download link (depends on module)
                $relativepath = $file["name"]; // Cas general
                if ($modulesubdir) $relativepath = $modulesubdir."/".$file["name"]; // Cas propal, facture...
                if ($modulepart == 'export') $relativepath = $file["name"]; // Other case

                $out .= '<tr class="oddeven">';

                $documenturl = DOL_URL_ROOT.'/document.php';
                if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) $documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP; // To use another wrapper

                // Show file name with link to download
                $out .= '<td class="minwidth200">';
                $out .= '<a class="documentdownload paddingright" href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).($param ? '&'.$param : '').'"';

                $mime = dol_mimetype($relativepath, '', 0);
                if (preg_match('/text/', $mime)) $out .= ' target="_blank"';
                $out .= '>';
                $out .= img_mime($file["name"], $langs->trans("File").': '.$file["name"]);
                $out .= dol_trunc($file["name"], 150);
                $out .= '</a>'."\n";
                $out .= $this->showPreview($file, $modulepart, $relativepath, 0, $param);
                $out .= '</td>';

                // Show file size
                $size = (!empty($file['size']) ? $file['size'] : dol_filesize($filedir."/".$file["name"]));
                $out .= '<td class="nowrap right">'.dol_print_size($size, 1, 1).'</td>';

                // Show file date
                $date = (!empty($file['date']) ? $file['date'] : dol_filemtime($filedir."/".$file["name"]));
                $out .= '<td class="nowrap right">'.dol_print_date($date, 'dayhour', 'tzuser').'</td>';

                if ($delallowed || $printer || $morepicto)
                {
                    $out .= '<td class="right nowraponall">';
                    if ($delallowed)
                    {
                        $tmpurlsource = preg_replace('/#[a-zA-Z0-9_]*$/', '', $urlsource);
                        $out .= '<a href="'.$tmpurlsource.((strpos($tmpurlsource, '?') === false) ? '?' : '&amp;').'action=remove_file&amp;file='.urlencode($relativepath);
                        $out .= ($param ? '&amp;'.$param : '');
                        //$out.= '&modulepart='.$modulepart; // TODO obsolete ?
                        //$out.= '&urlsource='.urlencode($urlsource); // TODO obsolete ?
                        $out .= '">'.img_picto($langs->trans("Delete"), 'delete').'</a>';
                    }
                    if ($printer)
                    {
                        //$out.= '<td class="right">';
                        $out .= '<a class="paddingleft" href="'.$urlsource.(strpos($urlsource, '?') ? '&amp;' : '?').'action=print_file&amp;printer='.$modulepart.'&amp;file='.urlencode($relativepath);
                        $out .= ($param ? '&amp;'.$param : '');
                        $out .= '">'.img_picto($langs->trans("PrintFile", $relativepath), 'printer.png').'</a>';
                    }
                    if ($morepicto)
                    {
                        $morepicto = preg_replace('/__FILENAMEURLENCODED__/', urlencode($relativepath), $morepicto);
                        $out .= $morepicto;
                    }
                    $out .= '</td>';
                }

                if (is_object($hookmanager))
                {
                    $parameters = array('colspan'=>($colspan + $colspanmore), 'socid'=>(isset($GLOBALS['socid']) ? $GLOBALS['socid'] : ''), 'id'=>(isset($GLOBALS['id']) ? $GLOBALS['id'] : ''), 'modulepart'=>$modulepart, 'relativepath'=>$relativepath);
                    $res = $hookmanager->executeHooks('formBuilddocLineOptions', $parameters, $file);
                    if (empty($res))
                    {
                        $out .= $hookmanager->resPrint; // Complete line
                        $out .= '</tr>';
                    }
                    else
                    {
                        $out = $hookmanager->resPrint; // Replace all $out
                    }
                }
            }

            $this->numoffiles++;
        }
        // Loop on each link found
        if (is_array($link_list))
        {
            $colspan = 2;

            foreach ($link_list as $file)
            {
                $out .= '<tr class="oddeven">';
                $out .= '<td colspan="'.$colspan.'" class="maxwidhtonsmartphone">';
                $out .= '<a data-ajax="false" href="'.$file->url.'" target="_blank">';
                $out .= $file->label;
                $out .= '</a>';
                $out .= '</td>';
                $out .= '<td class="right">';
                $out .= dol_print_date($file->datea, 'dayhour');
                $out .= '</td>';
                if ($delallowed || $printer || $morepicto) $out .= '<td></td>';
                $out .= '</tr>'."\n";
            }
            $this->numoffiles++;
        }

        if (count($file_list) == 0 && count($link_list) == 0 && $headershown)
        {
            $out .= '<tr><td colspan="'.(3 + ($addcolumforpicto ? 1 : 0)).'" class="opacitymedium">'.$langs->trans("None").'</td></tr>'."\n";
        }
    }

    if ($headershown)
    {
        // Affiche pied du tableau
        $out .= "</table>\n";
        $out .= "</div>\n";
        if ($genallowed)
        {
            if (empty($noform)) $out .= '</form>'."\n";
        }
    }
    $out .= '<!-- End show_document -->'."\n";
    //return ($i?$i:$headershown);
    return $out;
}

/*if ($action != 'prerelance' && $action != 'presend')
{*/
$form = new Form($db);
$formfile = new FormFile($db);


print '<div class="fichecenter"><div class="fichehalfleft">';
print '<a name="builddoc"></a>'; // ancre

// Documents generes
$filename = dol_sanitizeFileName($object->ref);
$filedir = $conf->facture->dir_output . '/' . dol_sanitizeFileName($object->ref);
$urlsource = $_SERVER['PHP_SELF'] . '?facid=' . $object->id;
$genallowed = $usercanread;
$delallowed = $usercancreate;

/* print $filename.'<br />';
 print $filedir.'<br />';*/
print $formfile->showdocuments('facture', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);
$somethingshown = $formfile->numoffiles;

/*// Show links to link elements
$linktoelem = $form->showLinkToObjectBlock($object, null, array('invoice'));

$compatibleImportElementsList = false;
if($usercancreate
    && $object->statut == Facture::STATUS_DRAFT
    && ($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT || $object->type == Facture::TYPE_DEPOSIT || $object->type == Facture::TYPE_PROFORMA || $object->type == Facture::TYPE_SITUATION) )
{
    $compatibleImportElementsList = array('commande','propal'); // import from linked elements
}
$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem, $compatibleImportElementsList);*/


// Show online payment link
$useonlinepayment = (! empty($conf->paypal->enabled) || ! empty($conf->stripe->enabled) || ! empty($conf->paybox->enabled));

if ($object->statut != Facture::STATUS_DRAFT && $useonlinepayment)
{
    print '<br><!-- Link to pay -->'."\n";
    require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
    print showOnlinePaymentUrl('invoice', $object->ref).'<br>';
}

// Show direct download link
if ($object->statut != Facture::STATUS_DRAFT && ! empty($conf->global->INVOICE_ALLOW_EXTERNAL_DOWNLOAD))
{
    print '<br><!-- Link to download main doc -->'."\n";
    print showDirectDownloadLink($object).'<br>';
}

print '</div><div class="fichehalfright"><div class="ficheaddleft">';

// List of actions on element
/*include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
$formactions = new FormActions($db);
$somethingshown = $formactions->showactions($object, 'invoice', $socid, 1);*/

print '</div></div></div>';
//}
// End of page
llxFooter();
$db->close();
