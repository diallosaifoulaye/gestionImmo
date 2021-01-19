<?php
/* Copyright (C) 2004      Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
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
 *	    \file       htdocs/compta/paiement/fiche.php
 *		\ingroup    facture
 *		\brief      Page of a customer payment
 *		\remarks	Nearly same file than fournisseur/paiement/fiche.php
 *		\version    $Id: fiche.php,v 1.77 2011/08/05 21:06:55 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
//require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
if ($conf->banque->enabled) require_once(DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
//require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/files.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product2.class.php');
require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
require_once(DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php');
$langs->load('bills');
$langs->load('banks');
$langs->load('companies');

// Security check
$id=str_replace('.', '',GETPOST("id"));
$action=GETPOST("action");
$formfile = new FormFile($db);
if ($user->societe_id) $socid=$user->societe_id;
// TODO ajouter regle pour restreindre acces paiement
//$result = restrictedArea($user, 'facture', $id,'');

$mesg='';


/*
 * Actions
 */

if ($action == 'setnote' && $user->rights->facture->paiement)
{
    $db->begin();

    $paiement = new Paiement($db);
    $paiement->fetch($id);
    $result = $paiement->update_note(GETPOST('note'));
    if ($result > 0)
    {
        $db->commit();
        $action='';
    }
    else
    {
        $mesg='<div class="error">'.$paiement->error.'</div>';
        $db->rollback();
    }
}

if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$paiement = new Paiement($db);
	$paiement->fetch($id);
	$result = $paiement->delete();
	if ($result > 0)
	{
        $db->commit();
        Header("Location: liste.php");
        exit;
	}
	else
	{
	    $langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($paiement->error).'</div>';
        $db->rollback();
	}
}

if ($action == 'confirm_valide' && GETPOST('confirm') == 'yes' && $user->rights->facture->paiement)
{
	$db->begin();

	$paiement = new Paiement($db);
    $paiement->fetch($id);
	if ($paiement->valide() > 0)
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
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			facture_pdf_create($db, $fac, '', $fac->modelpdf, $outputlangs);
		}

		Header('Location: fiche.php?id='.$paiement->id);
		exit;
	}
	else
	{
	    $langs->load("errors");
		$mesg='<div class="error">'.$langs->trans($paiement->error).'</div>';
		$db->rollback();
	}
}


/*
 * View
*/

llxHeader();

$thirdpartystatic=new Societe($db);

$paiement = new Paiement($db);
$result=$paiement->fetch($id);
if ($result <= 0)
{
	dol_print_error($db,'Payement '.$id.' not found in database');
	exit;
}

$html = new Form($db);

$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$id;
$head[$h][1] = $langs->trans("Card");
$hselected = $h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/paiement/info.php?id='.$id;
$head[$h][1] = $langs->trans("Info");
$h++;


dol_fiche_head($head, $hselected, $langs->trans("PaymentCustomerInvoice"), 0, 'payment');

/*
 * Confirmation de la suppression du paiement
 */
if ($action == 'delete')
{
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id, $langs->trans("DeletePayment"), $langs->trans("ConfirmDeletePayment"), 'confirm_delete','',0,2);
	if ($ret == 'html') print '<br>';
}

/*
 * Confirmation de la validation du paiement
 */
if ($action == 'valide')
{
	$facid = $_GET['facid'];
	$ret=$html->form_confirm('fiche.php?id='.$paiement->id.'&amp;facid='.$facid, $langs->trans("ValidatePayment"), $langs->trans("ConfirmValidatePayment"), 'confirm_valide','',0,2);
	if ($ret == 'html') print '<br>';
}


dol_htmloutput_mesg($mesg);


print '<table class="border" width="100%">';

// Ref
print '<tr><td valign="top" width="140">'.$langs->trans('Ref').'</td><td colspan="3">'.$paiement->id.'</td></tr>';

// Date
print '<tr><td valign="top" width="120">'.$langs->trans('Date').'</td><td colspan="3">'.dol_print_date($paiement->date,'day').'</td></tr>';

// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$paiement->type_code)!=("PaymentType".$paiement->type_code)?$langs->trans("PaymentType".$paiement->type_code):$paiement->type_libelle;
print '<tr><td valign="top">'.$langs->trans('Mode').'</td><td colspan="3">'.$labeltype.'</td></tr>';

// Numero
//if ($paiement->montant)
//{
	print '<tr><td valign="top">'.$langs->trans('Numero').'</td><td colspan="3">'.$paiement->numero.'</td></tr>';
//}

// Amount
print '<tr><td valign="top">'.$langs->trans('Amount').'</td><td colspan="3">'.price($paiement->montant).'&nbsp;'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';


// Note
print '<tr><td valign="top">'.$html->editfieldkey("Note",'note',$paiement->note,'id',$paiement->id,$user->rights->facture->paiement).'</td><td colspan="3">';
print $html->editfieldval("Note",'note',$paiement->note,'id',$paiement->id,$user->rights->facture->paiement,'text');
print '</td></tr>';

// Bank account
if ($conf->banque->enabled)
{
    if ($paiement->bank_account)
    {
    	$bankline=new AccountLine($db);
    	$bankline->fetch($paiement->bank_line);

    	print '<tr>';
    	print '<td>'.$langs->trans('BankTransactionLine').'</td>';
		print '<td colspan="3">';
		print $bankline->getNomUrl(1,0,'showall');
    	print '</td>';
    	print '</tr>';
    }
}

print '</table>';


/*
 * List of invoices
 */
$id_invoice=0;
$disable_delete = 0;
$sql = 'SELECT f.rowid as facid, f.type, f.total_ttc, f.paye, f.fk_statut, pf.amount, s.nom, s.rowid as socid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf,'.MAIN_DB_PREFIX.'facture as f,'.MAIN_DB_PREFIX.'societe as s';
$sql.= ' WHERE pf.fk_facture = f.rowid';
$sql.= ' AND f.fk_soc = s.rowid';
$sql.= ' AND s.entity = '.$conf->entity;
$sql.= ' AND pf.fk_paiement = '.$paiement->id;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$i = 0;
	$total = 0;
	print '<br><table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans('Bill').'</td>';
	print '<td>'.$langs->trans('Company').'</td>';
	print '<td align="right">'.$langs->trans('ExpectedToPay').'</td>';
    print '<td align="right">'.$langs->trans('PayedByThisPayment').'</td>';
    print '<td align="right">'.$langs->trans('RemainderToPay').'</td>';
    print '<td align="right">'.$langs->trans('Status').'</td>';
	print "</tr>\n";

	if ($num > 0)
	{
		$var=True;

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$var=!$var;
			print '<tr '.$bc[$var].'>';

            $invoice=new Facture($db);
            $invoice->fetch($objp->facid);
            $paiement = $invoice->getSommePaiement();
            $creditnotes=$invoice->getSumCreditNotesUsed();
            $deposits=$invoice->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
            $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
			
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

            // Invoice
			print '<td>';
			print $invoice->getNomUrl(1);
			print "</td>\n";

			// Third party
			print '<td>';
			$thirdpartystatic->id=$objp->socid;
			$thirdpartystatic->nom=$objp->nom;
			print $thirdpartystatic->getNomUrl(1);
			print '</td>';

			// Expected to pay
			print '<td align="right">'.price($objp->total_ttc).'</td>';

            // Amount payed
            print '<td align="right">'.price($objp->amount).'</td>';

            // Remain to pay
            print '<td align="right">'.price($remaintopay).'</td>';

			// Status
			print '<td align="right">'.$invoice->getLibStatut(5, $alreadypayed).'</td>';

			print "</tr>\n";
			if ($objp->paye == 1)	// If at least one invoice is paid, disable delete
			{
				$disable_delete = 1;
			}
			$total = $total + $objp->amount;
			$i++;
		}
	}
	$var=!$var;

	print "</table>\n";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print '</div>';
if ($action == 'builddoc')	// En get ou en post
{
	$model = GETPOST('model');
	$ref_fac=GETPOST("ref_fac");
	$invoice2=new Facture($db);
    $invoice2->fetch($ref_fac);
	$invoice2->fetch_lines();
	$paiement2 = new Paiement($db);
	$paiement2->fetch($id);
	$bankline2=new AccountLine($db);
    $bankline2->fetch($paiement2->bank_line);
	$compte = new Account($db);
	$compte->fetch($bankline2->fk_account);
	$pr2= new Product2($db);
	$pr2->fetch($invoice2->lines[0]->rowid);
    include_once(DOL_DOCUMENT_ROOT .'/compta/mesdocs/modeles/'.$model.'.php');
		$dir = $conf->facture->dir_output . "/" . $invoice2->ref.'/'.$id;
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
			   $invoice->fetch($id_invoice);
			   print '<input type="hidden" name="action" value="builddoc">';
			   print '<input type="hidden" name="ref_fac" value="'.$id_invoice.'">';
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
			   $filedir= $conf->facture->dir_output . "/".$invoice->ref.'/'.$id;
			  
				if($dossier = @opendir($filedir))
				{
				$out = '<div class="titre">'.$titletoshow.'</div>';
				$out.= '<table class="border" summary="listofdocumentstable" width="100%">';
				while ($Fichier = readdir($dossier)) {
				  if ($Fichier != "." && $Fichier != "..") {
					$nomFichier = $filedir."/".$Fichier;
					//echo $nomFichier."<BR>";
					$out.= "<tr ".$bc[$var].">";
					$out.= '<td >';
					//print $nomFichier;
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
				}
               print '</td><td valign="top" width="50%">';

               print '<br>';
                /*
                 * Linked object block
                 */
               // $somethingshown=$object->showLinkedObjectBlock();
                print '<br>';

         

                print '</td></tr></table>';

/*
 * Boutons Actions
 */

print '<div class="tabsAction">';

if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
{
	if ($user->societe_id == 0 && $paiement->statut == 0 && $_GET['action'] == '')
	{
		if ($user->rights->facture->paiement)
		{
			print '<a class="butAction" href="fiche.php?id='.$id.'&amp;facid='.$objp->facid.'&amp;action=valide">'.$langs->trans('Valid').'</a>';
		}
	}
}

if ($user->societe_id == 0 && $action == '')
{
	if ($user->rights->facture->paiement)
	{
		if (! $disable_delete)
		{
			print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemovePaymentWithOneInvoicePaid")).'">'.$langs->trans('Delete').'</a>';
		}
	}
}

print '</div>';

$db->close();

llxFooter('$Date: 2011/08/05 21:06:55 $ - $Revision: 1.77 $');
?>
