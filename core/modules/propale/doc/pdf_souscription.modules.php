<?php
/* Copyright (C) 2004-2011 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin          <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand       <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2011 Juanjo Menent		  <jmenent@2byte.es>
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
 *	\file       htdocs/includes/modules/facture/doc/pdf_souscription.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate invoices from crab model
 *	\author	    Laurent Destailleur
 *	\version    $Id: pdf_contrat.modules.php,v 1.12 2011/07/31 23:28:15 eldy Exp $
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');



/**
 *	\class      pdf_contrat
 *	\brief      Classe permettant de generer les factures au modele souscription
 */

class pdf_souscription extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet

    var $phpmin = array(5, 5); // Minimum version of PHP required by module
    var $version = 'dolibarr';


	/**
	 *		Constructor
	 *		@param		db		Database access handler
	 */
	function pdf_souscription($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "souscription";
		$this->description = "Fiche de souscription";

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=111;
		$this->posxup=126;
		$this->posxqty=145;
		$this->posxdiscount=162;
		$this->postotalht=174;

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


	/**
     *  Function to build pdf onto disk
     *  @param      object          Id of object to generate
     *  @param      outputlangs     Lang output object
     *  @param      srctemplatepath Full path of source filename for generator using a template file
     *  @param      hidedetails     Do not show line details
     *  @param      hidedesc        Do not show desc
     *  @param      hideref         Do not show ref
     *  @return     int             1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (!class_exists('TCPDF')) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		$default_font_size = pdf_getPDFFontSize($outputlangs);
//////ABY
		$nblignes = count($object->lines);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray=array();
		if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE))
		{
			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto = new Product($this->db);
				$objphoto->fetch($object->lines[$i]->fk_product);

				$pdir = get_exdir($object->lines[$i]->fk_product, 2, 0, 0, $objphoto, 'product') . $object->lines[$i]->fk_product ."/photos/";
				$dir = $conf->product->dir_output.'/'.$pdir;

				$realpath='';
				foreach ($objphoto->liste_photos($dir, 1) as $key => $obj)
				{
					$filename=$obj['photo'];
					//if ($obj['photo_vignette']) $filename='thumbs/'.$obj['photo_vignette'];
					$realpath = $dir.$filename;
					break;
				}

				if ($realpath) $realpatharray[$i]=$realpath;
			}
		}
		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;
/// fin
		if ($conf->facture->dir_output)
		{
			$object->fetch_thirdparty();


			//$deja_regle = $object->getSommePaiement();
			//$deja_regle = $object->getSommePaiement();
			//$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			//$amount_deposits_included = $object->getSumDepositsUsed();

			//$deja_regle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			//$amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			//$amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);


			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				 $objectref = dol_sanitizeFileName($object->ref);
				 //var_dump($objectref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/souscription_" . $objectref . ".pdf";
			}
			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				$nblignes = sizeof($object->lines);

                $pdf=pdf_getInstance($this->format);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Invoice"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				$ref = GETPOST("ref_facture");
//recupération des infos de la souscription
				$sql = "SELECT s.rowid as socid,s.nom,s.address,s.email,s.phone,";
				$sql.= ' se.profession,se.numpiece,se.nationalite,se.date_naiss,se.lieu_naiss,se.situation_matrimoniale,se.employeur,se.regime,se.nomconjoint,';
				$sql.= ' f.ref, f.ref_client, ';
				$sql.= ' f.paye as paye, f.fk_statut,';
				$sql.= ' f.datec as date_creation, f.tms as date_update,';
				$sql.= ' pr.label,pr.rowid as prid,pr.ref as num_parcelle, ';
				$sql.= ' pe.prix_commercial, ';
				$sql.= ' cat.label as labelcat,';
				$sql.= ' u.firstname,u.lastname';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_extrafields as se ON s.rowid=se.fk_object";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON s.rowid=f.fk_soc";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as pd ON f.rowid=pd.fk_facture ";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as pr ON pr.rowid=pd.fk_product ";
				$sql.= "LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON pr.rowid=cp.fk_product ";
				$sql.= "LEFT JOIN ".MAIN_DB_PREFIX."categorie as cat ON cat.rowid=cp.fk_categorie ";
				$sql.= "LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid=sc.fk_soc ";
				$sql.= "LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON pr.rowid=pe.fk_object ";
				$sql.= "LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid=sc.fk_user ";
				$sql.= ' WHERE f.fk_soc = s.rowid ';
				$sql.='  AND f.fk_statut = 1';
				$sql.='  AND pr.rowid=pd.fk_product';
				$sql.='  AND pr.fk_product_type = 0';
				$sql.='  AND f.ref = "'.$ref.'"';
				$sql.= ' AND f.entity IN ('.getEntity('invoice').')';
				//var_dump($sql);die();
				$result = $this->db->query($sql);
				$objet = $this->db->fetch_object($result);

				// 1 ere page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead2($pdf, $object, 1, $outputlangs);

				$posy=40;
				$posx=$this->marge_gauche;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','B', $default_font_size+2);
				$pdf->MultiCell(0, 6, html_entity_decode('FICHE DE SOUSCRIPTION PROGRAMME « '.$objet->labelcat.' »'), 0, 'C');
				// infos clients
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','BILLING');


				$posy=$posy+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);


				$pdf->MultiCell(0, 6, html_entity_decode('
Nom & Prénom: '.$objet->nom.'    
                         
Date et lieu de naissance: '.dol_print_date($objet->date_naiss, "day", false).'                à : '.$objet->lieu_naiss.'    

Nationalité : '.$objet->nationalite.'  

Adresse : '.$objet->address.' 

Téléphone:  '.$objet->phone.'                        Email : '.$objet->email.' 

Profession: '.$objet->profession.'                             Employeur : '.$objet->employeur.'        

Numéro de la pièce d\'identité(verso):                 '.$objet->numpiece.'  

Situation matrimoniale '), 0, 'L');

				$posy=$posy+65;
				$pdf->SetXY($posx-3,$posy);
				$pdf->SetFont('','', $default_font_size+1);

				$pdf->MultiCell(0, 6, html_entity_decode('
       
       Célibataire(e)      '), 0, 'L');

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;
				$posxf=42;
				$posyf=125;


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posy+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//fin checknox
				$posy=$posyf;
				$pdf->SetXY($posxf+10,$posyf-15);
				$pdf->SetFont('','', $default_font_size+1);

				$pdf->MultiCell(0, 6, html_entity_decode('
       
       
       Marié(e)      '), 0, 'L');

				//debut checkbox

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				//$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;
				$posxf=$posxf+35;


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posyf+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//fin checknox
				$posy=$posyf;
				$pdf->SetXY($posxf+15,$posy-15);
				$pdf->SetFont('','', $default_font_size+1);

				$pdf->MultiCell(0, 6, html_entity_decode('
       
       
       Veuf/ veuve (e)      '), 0, 'L');
// gestion checked
				if ($objet->situation_matrimoniale==1){
				$posy=$posyf;
				$pdf->SetXY($posxf-35,$posy);
				$pdf->SetFont('','', $default_font_size+2);

				$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
				}
				if ($objet->situation_matrimoniale==2){
				$posy=$posyf;
				$pdf->SetXY($posxf,$posy);
				$pdf->SetFont('','', $default_font_size+2);

				$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
				}
				if ($objet->situation_matrimoniale==3){

				$posy=$posyf;
				$pdf->SetXY($posxf+53,$posy);
				$pdf->SetFont('','', $default_font_size+2);

				$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
				}
					if ($objet->situation_matrimoniale==4){
				$posy=$posyf;
				$pdf->SetXY($posxf+93,$posy);
				$pdf->SetFont('','', $default_font_size+2);

				$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
					}
				//debut checkbox

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				//$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;
				$posxf=$posxf+53;
				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf+30, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posyf-5;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//fin checknox
				$posy=$posyf-15;
				$pdf->SetXY($posxf+10,$posy);
				$pdf->SetFont('','', $default_font_size+1);

				$pdf->MultiCell(0, 6, html_entity_decode('
       
       
      Divorcé(e)    '), 0, 'L');

				//debut checkbox

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				//$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;
				$posxf=$posxf+40;



				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posyf+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//fin checknox
				$posy=$posyf-5;
				$pdf->SetXY($posxf,$posy);
				$pdf->SetFont('','', $default_font_size+1);

				$posy=$posy+10;
				$posx=$this->marge_gauche;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				$pdf->MultiCell(0, 6, html_entity_decode('       
Régime matrimonial :  Séparation de biens   '), 0, 'L');

				//debut checkbox

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				//$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;
				$posxf=90;
				$posyf=135;


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//var_dump($result);die();

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posy+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//fin checknox

				$posy=$posy+83;
				$posx=$this->marge_gauche+90;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				$pdf->MultiCell(0, 6, html_entity_decode('      Communauté de biens  
'), 0, 'L');
				//debut checkbox

				$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

				// Show sender
				//$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->marge_gauche;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-10;

				$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 2 : 3;
				$posxf=150;
				$posyf=135;


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				//Recipient name
				// On peut utiliser le nom de la societe du contact
				if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
					$thirdparty = $object->contact;
				} else {
					$thirdparty = $object->thirdparty;
				}

				$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

				$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

				// Show recipient== gerer largeur frame
				$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 4 : 5;
				if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
				$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
				$posy+=$top_shift;
				$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
				if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

				// Show recipient frame
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxf, $posyf);
				//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
				$pdf->Rect($posxf, $posyf, $widthrecbox, $hautcadre);
				$posy=$posy+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				//suite données souscrition
				$sql1 = "SELECT s.nom as nom_apporteur ";
				$sql1.= ' FROM '.MAIN_DB_PREFIX.'societe as s';
				$sql1.= " LEFT JOIN ".MAIN_DB_PREFIX."product_extrafields as pe ON pe.apporteur=s.rowid";
				//$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as fd ON pe.fk_object=pd.fk_product ";
				$sql1.= ' WHERE pe.fk_object =1';
				$sql1.='  And s.fournisseur = 1 ';
				//var_dump($sql1);die();
				$result1 = $this->db->query($sql1);
				$objet1 = $this->db->fetch_object($result1);
				//var_dump($objet1);die();

				//gestion checked regime
				if ($objet->regime==3){
					$posy=$posyf;
					$pdf->SetXY($posxf,$posy);
					$pdf->SetFont('','', $default_font_size+2);

					$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
				}

				if ($objet->regime==2){
					$posy=$posyf;
					$pdf->SetXY($posxf-60,$posy);
					$pdf->SetFont('','', $default_font_size+2);

					$pdf->MultiCell(0, 6, html_entity_decode('X'), 0, 'L');
				}
				//fin checknox


				$posy=$posyf+5;
				$posx=$this->marge_gauche;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size+1);
				$pdf->MultiCell(0, 6, html_entity_decode('
Nom et prénoms du conjoint : '.$objet->nomconjoint.'  

Numéro (s) parcelle (s) : '.$objet->num_parcelle.'  

Prix parcell(s) : '.price($objet->prix_commercial).'  

Prénom et Nom du vendeur : '.$objet->firstname.' '.$objet->lastname.' 

Prénom et Nom de l’apporteur d’affaire (Eventuellement) : '.$objet1->nom_apporteur.'

Durée de paiement :                    mois

Périodicité des paiements : ………………………………………………………..      
                     
Montant des échéances périodiques : ……………………………………………
'), 0, 'L');

				$posyf=$posyf+75;
				$pdf->SetXY($posxf,$posyf);
				$pdf->SetFont('','B', $default_font_size+1);
				//$html='<input type="checkbox" readonly disabled>' ;
				// output the HTML content
				$pdf->writeHTML($html, true, false, true, false, '');

				$pdf->MultiCell(0, 6, html_entity_decode('
								SIGNATURE                                                                                                               VISA ADV 
    








     NB : LES FRAIS DE DOSSIERS NE SONT PAS REMBOURSABLES'), 0, 'L');














				// 2 ieme page
				$pdf->AddPage();
				$pagenb++;

				$posy=10;
				$pdf->SetXY($posxf,$posy);
				$pdf->SetFont('','B', $default_font_size);
				//$html='<input type="checkbox" readonly disabled>' ;
				// output the HTML content
				$pdf->writeHTML($html, true, false, true, false, '');

				$pdf->MultiCell(0, 6, html_entity_decode('
						
La réservation d’un lot ne devient définitive qu’après le règlement intégral des frais de dossier et de l’apport initial et la signature du contrat de réservation par les 2 parties
	'), 0, 'L');

				$posy=$posy+30;
				$posx=$posx+100;
				$pdf->SetXY($posx+52,$posy);
				$pdf->SetFont('','', $default_font_size);
				//$html='<input type="checkbox" readonly disabled>' ;
				// output the HTML content
				//$pdf->writeHTML($html, true, false, true, false, '');
				$pdf->MultiCell(0, 6, html_entity_decode('Version du'), 0, '');

				//$posy=$posy;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell('', 3, dol_print_date($object->date, "day", false), '', 'R');


				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();



				// Pied de page

				$pdf->setPage($pagenb);
				$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.


				$pdf->Close();

				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
				chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		return 0;   // Erreur par defaut
	}




    
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$lltot = 200; $col1x = 120; $col2x = 170; $largcol2 = $lltot - $col2x;

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY ($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
		$pdf->SetXY ($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + $object->remise), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			foreach( $this->tva as $tvakey => $tvaval )
			{
				if ($tvakey > 0)    // On affiche pas taux 0
				{
					$this->atleastoneratenotnull++;

					$index++;
					$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
					$tvacompl='';
					if (preg_match('/\*/',$tvakey))
					{
						$tvakey=str_replace('*','',$tvakey);
						$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
					}
					$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
					$totalvat.=vatrate($tvakey,1).$tvacompl;
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
					$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
				}
			}

			if (! $this->atleastoneratenotnull)	// If no vat at all
			{
				$index++;
				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);
				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva), 0, 'R', 1);

				// Total LocalTax1
				if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on' && $object->total_localtax1>0)
				{
					$index++;
					$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT1".$mysoc->pays_code), $useborder, 'L', 1);
					$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax1), $useborder, 'R', 1);
				}

				// Total LocalTax2
				if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on' && $object->total_localtax2>0)
				{
					$index++;
					$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalLT2".$mysoc->pays_code), $useborder, 'L', 1);
					$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_localtax2), $useborder, 'R', 1);
				}
			}
			else
			{
				if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				{
					//Local tax 1
					foreach( $this->localtax1 as $tvakey => $tvaval )
					{
						if ($tvakey>0)    // On affiche pas taux 0
						{
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat =$outputlangs->transnoentities("TotalLT1".$mysoc->pays_code).' ';
							$totalvat.=vatrate($tvakey,1).$tvacompl;
							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
						}
					}
				}

				if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				{
					//Local tax 2
					foreach( $this->localtax2 as $tvakey => $tvaval )
					{
						if ($tvakey>0)    // On affiche pas taux 0
						{
						//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl='';
							if (preg_match('/\*/',$tvakey))
							{
								$tvakey=str_replace('*','',$tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat =$outputlangs->transnoentities("TotalLT2".$mysoc->pays_code).' ';
							$totalvat.=vatrate($tvakey,1).$tvacompl;
							$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
						}
					}
				}
			}
		}

		// Total TTC
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$index++;
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$text=$outputlangs->transnoentities("TotalTTC");
			if ($object->type == 2) $text=$outputlangs->transnoentities("TotalTTCToYourCredit");
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $text, $useborder, 'L', 1);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc), $useborder, 'R', 1);
		}
		$pdf->SetTextColor(0,0,0);

		$creditnoteamount=$object->getSumCreditNotesUsed();
		$depositsamount=$object->getSumDepositsUsed();
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0)
		{
			// Already paid + Deposits
			$index++;
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount)
			{
				$index++;
				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount), 0, 'R', 0);
			}

			// Escompte
			if ($object->close_code == 'discount_vat')
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);
				$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount), $useborder, 'R', 1);

				$resteapayer=0;
			}

			$index++;
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer), $useborder, 'R', 1);

			// Fin
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *   Affiche la grille des lignes de factures
	 *   @param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size - 2);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
		$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param et 4eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 2eme param et 4eme param
		$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

		$pdf->SetFont('','', $default_font_size - 1);

		$pdf->SetXY ($this->posxdesc-1, $tab_top+1);
		$pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');

		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			$pdf->SetXY ($this->posxtva-3, $tab_top+1);
			$pdf->MultiCell($this->posxup-$this->posxtva+3,2, $outputlangs->transnoentities("VAT"),'','C');
		}

		$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxup-1, $tab_top+1);
		$pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceUHT"),'','C');

		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxqty-1, $tab_top+1);
		$pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');

		$pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
		if ($this->atleastonediscount)
		{
			$pdf->SetXY ($this->posxdiscount-1, $tab_top+1);
			$pdf->MultiCell($this->postotalht-$this->posxdiscount+1,2, $outputlangs->transnoentities("ReductionShort"),'','C');
		}

		if ($this->atleastonediscount)
		{
			$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		$pdf->SetXY ($this->postotalht-1, $tab_top+1);
		$pdf->MultiCell(30,2, $outputlangs->transnoentities("TotalHT"),'','C');

	}

	function _pagehead2(&$pdf, $object, $showaddress=1, $outputlangs)
	{
		global $conf,$langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

        if($object->statut==0 && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )
        {
		      pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FACTURE_DRAFT_WATERMARK);
        }

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','', $default_font_size + 3);

		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);	// width=0 (auto), max height=24
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'C');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}
		$posy+=4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("date")." : " . dol_print_date($object->date, "day", false, $outputlangs), '', 'R');

	}

	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	/*function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}*/
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}

}

?>
