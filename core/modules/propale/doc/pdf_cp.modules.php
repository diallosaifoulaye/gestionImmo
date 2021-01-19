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
 *	\file       htdocs/includes/modules/facture/doc/pdf_contrat.modules.php
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
 *	\brief      Classe permettant de generer les factures au modele contrat
 */

class pdf_cp extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet

    var $phpmin = array(5, 5); // Minimum version of PHP required by module
    var $version = 'dolibarr';


	/**
	 *		Constructor
	 *		@param		db		Database access handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "contrat";
		$this->description = "Contrat de réservation";

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
		//echo "<pre>";var_dump($object);die;
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

			/*$deja_regle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);*/


			// Definition of $dir and $file
		/*	if ($object->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				 $objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/contrat_reservation_" . $objectref . ".pdf";
			}*/

			if ($object->specimen)
			{
				$dir = $conf->propal->multidir_output[$conf->entity];
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propal->multidir_output[$object->entity] . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
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

				// New page
				$pdf->AddPage();
				$pagenb++;
				//$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);
				$posy=40;
				// Prémiere page du contrat 
				$pdf->Image(DOL_DOCUMENT_ROOT .'/core/modules/facture/doc/logo-cgis.png',80,$posy,50,50);
				//$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);	// width=0 (auto), max height=24
				
				
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
						if(($object->lines[$i]->fk_product))
						{
							 $idproduit= $object->lines[$i]->fk_product;
							$id_cat=$object->lines[$i]->rowpr;
							$ref_prod=$object->lines[$i]->product_ref;
							//$weight=$object->lines[$i]->weight;
							$surface =$object->lines[$i]->surface;
							$length=$object->lines[$i]->length;
							$desc=$object->lines[$i]->desc;
							break;
						}
				}
				//$pr= new Product2($this->db);
				$pr2= new Product($this->db);
				$pr2->fetch($idproduit);
				$desc=$pr2->description;
				$idcategorie = $pr2->get_Id_Categorie($object->lines[$i]->fk_product);
				$cat= new Categorie($this->db);
				$cat->fetch($idcategorie);
				//echo "<pre>";var_dump($cat);die;
				//echo "<pre>";var_dump($cat->array_options["options_aut"]);die;
				$notaire=nl2br($cat->array_options["options_notaire"]);
				$nom_societe=nl2br($cat->array_options["options_nomsoc"]);
				
				
				$posy= $posy + 100;
				$posx=$this->marge_gauche+50;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','B',$default_font_size + 10);
				$pdf->SetDrawColor(61,187,86);  
				$pdf->MultiCell(120, 4, "CONTRAT DE RESERVATION", 0, 'L');
				$pdf->Rect($posx-3, $posy-5, 110, 20);

				// Contenu contrat
				$posy=$posy+125;
				$posx=$this->marge_gauche+60;
				$pdf->SetXY($posx,$posy);
				$pdf->SetTextColor(102,65,167);
				$pdf->SetFont('','B', $default_font_size+8);
				$pdf->MultiCell(80, 4, $nom_societe, 0, 'L');
				
				// 2 ieme page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead2($pdf, $object, 1, $outputlangs);
				
				$posy=40;
				$posx=$this->marge_gauche;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','B', $default_font_size+2);
				$pdf->MultiCell(0, 6, html_entity_decode("ENTRE LES SOUSSIGNES :"), 0, 'L');
				
				
				$posy=$posy+8;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode('La Société dénommée ').html_entity_decode(nl2br($conf->global->MAIN_INFO_SOCIETE_NOM)).',
Représentée par Monsieur '. $conf->global->MAIN_INFO_SOCIETE_MANAGERS.', Cogérant de ladite société.

Ci après dénommée "le Réservant",.


Et', 0, 'L');

				// infos clients
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','BILLING');
				//echo "<pre>";var_dump($object);die;
				if (sizeof($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}
	
				// Recipient name
				if (! empty($usecontact))
				{
					// On peut utiliser le nom de la societe du contact
					if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) $socname = $object->contact->socname;
					else $socname = $object->thirdparty->nom;
					$carac_client_name=$outputlangs->convToOutputCharset($socname);
				}
				else
				{
					//$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
					$carac_client_name=$outputlangs->convToOutputCharset($object->thirdparty->nom);
				}
				//echo "<pre>";var_dump($object->thirdparty);die;
				//$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');
				$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,$object->contact,$usecontact,'target');

					$sql = "SELECT p.rowid ";
					$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as p";
					//var_dump($object->thirdparty->email);die();
					$sql .= " WHERE p.email = '" . $object->thirdparty->email . "'";
					$result = $this->db->query($sql);
					$objetc = $this->db->fetch_object($result);
					$objectcont = new Contact($this->db);
					$objectcont->fetch($objetc->rowid);

				//echo "<pre>";var_dump(htmlentities($objectcont->country));die;
				$posy=$posy+40;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode(''.$objectcont->getCivilityLabel().' '.$carac_client_name.'
Date et lieu de naissance :  '.dol_print_date($objectcont->birthday,'day').'
Situation matrimoniale : ……………… au régime de ………………
Adresse Personnelle : '.$object->thirdparty->address.'
Ville : '.$object->thirdparty->town.'
Pays de Résidence :'.mb_strtoupper($objectcont->country).'
Nationalité : ………………
Type et Numéro Pièce d’identité : '.get_TypePiece($object->thirdparty->array_options['options_typepiece']).' n° '.$object->thirdparty->array_options['options_numpiece'].'
Profession : '.$objectcont->poste.'
Numéro mobile : '.$object->thirdparty->phone.'
Adresse email : '.$object->thirdparty->email.'
Employeur : ……………… '), 0, 'L');


				$posy=$posy+60;
				$pdf->SetXY($posx+30,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode('Ci-après dénommé(e) "le Réservataire".'), 0, 'L');
			
				$posy=$posy+10;
				$pdf->SetXY($posx+40,$posy);
				$pdf->SetFont('','B', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode('IL A ETE PREALABLEMENT EXPOSE CE QUI SUIT :'), 0, 'L');
				
				if($object->thirdparty->civilite_id=="MR") // le client est un homme
				$text_regime= $object->thirdparty->regime." le consentement de la conjointe (n’) a (pas) été requis. En conséquence, Mme ".$object->thirdparty->nom_epouse." épouse de M. ".$carac_client_name;
				else  // le client est une femme
				$text_regime= $object->thirdparty->regime." le consentement du conjoint (n’) a (pas) été requis. En conséquence, M. ".$object->thirdparty->nom_epouse." époux de Mme ".$carac_client_name;
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Le Réservant entend édifier un ensemble immobilier en vue de le vendre par lots placés sous le régime de la copropriété, suivant la formule de vente en l’état futur d’achèvement.
				
Le Réservataire s'est déclaré intéressé et a souhaité que lui soit consentie la réservation ci- après spécifiée.

Le Réservataire est dûment informé qu'au stade actuel, les détails du programme de construction, de sa consistance et de ses caractéristiques, ne sont pas définitivement arrêtés de sorte que des modifications peuvent leur être apportées, ce dont le Réservataire prend acte et ce qu'il déclare accepter.

Le réservataire étant marié sous le régime de ".$text_regime." (n’) est (pas) partie aux présentes.






Ceci exposé, il a été convenu et arrêté ce qui suit :"), 0, 'L');


				 // 3 ieme page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead2($pdf, $object, 1, $outputlangs);
				
				$posy=40;
				$posx=$this->marge_gauche;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','B', $default_font_size+2);
				$pdf->MultiCell(0, 6, html_entity_decode("Première partie : Conditions générales"), 0, 'L');
				
				$posy=$posy+10;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode('Article 1 : Projet de construction'), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("1.1. Sur le terrain visé au paragraphe 2.1, le réservant projette la réalisation d’un ensemble immobilier de 41 appartements et des places de parking au sous sol.
				
1.2. A ce titre, un permis de construire a été sollicité par le dépôt d’une demande auprès de l’autorité compétente. L’autorisation est effective sous le numéro ").html_entity_decode($cat->array_options["options_aut"]), 0, 'L');


				$posy=$posy+25;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode('Article 2 : Consistance et caractéristiques de l’ensemble immobilier'), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("2.1. L’assiette foncière de l’ensemble immobilier est constituée par ").html_entity_decode($cat->description), 0, 'L');
				
				$posy=$posy+20;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("2.2. La consistance et les caractéristiques techniques de l’immeuble résultent :"), 0, 'L');
				$posy=$posy+6;
				$pdf->SetXY($posx+5,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("-	du descriptif sommaire des travaux annexés au présent contrat ;
-	du plan de masse prévisionnel de l'ensemble immobilier à réaliser et du plan prévisionnel des locaux choisis par le réservataire."), 0, 'L');

				$posy=$posy+18;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("2.3. Le descriptif porte sur les matériaux et éléments d'équipement généraux du bâtiment composant l'ensemble immobilier ; ce descriptif, dans l'esprit des parties, constitue le minimum des prestations du réservant (constructeur) à l'égard du réservataire quant à ses parties privatives et aux parties communes."), 0, 'L');
				
				$posy=$posy+18;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 3 : Modification du descriptif"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("3.1. Le plan et les documents ci-dessus mentionnés sont susceptibles de recevoir des modifications jusqu'à la mise au point des plans et documents d'exécution avec les entrepreneurs. Les différences de moins de 5 % des surfaces exprimées par les plans sont réputées être acceptées par le réservataire et ne peuvent fonder aucune réclamation.
				
3.2. Lorsque les modifications entrainent une révision du prix de vente et éventuellement une prolongation du délai de livraison de lot, elles font l'objet d'un avenant."), 0, 'L');


				$posy=$posy+30;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 4 : Réservation"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("4.1. Le présent contrat de réservation est conclu en préparation de l’acquisition par le réservataire des biens et droits immobiliers faisant l’objet d’un lot dans le cadre de l’ensemble immobilier visé à l’article 2.
				
4.2 Les parties acceptent par les présentes, la réservation des biens et droits immobiliers désignés à l’article 16, envisagés dans leur état futur d’achèvement par le réservant au réservataire suivant les conditions arrêtées dans les présentes conditions générales et dans les conditions particulières.

4.3. En conséquence, le Réservant confère par les présentes au Réservataire la faculté d’acquérir, par préférence à tout autre, les biens et droits immobiliers ci-après désignés ce que le Réservataire déclare accepter, par les présentes."), 0, 'L');

				
				$posy=$posy+42;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 5 : Conditions suspensives"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("5.1. Les parties soumettent le présent Contrat de réservation et les obligations qui en résultent aux conditions suspensives ci-après :"), 0, 'L');
				$posy=$posy+10;
				$pdf->SetXY($posx+5,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("-	la souscription par le réservant d'une assurance dommages-ouvrages ;
- la souscription par le réservant d'une assurance constructeur non réalisateur concernant l'ensemble immobilier dont dépendent les biens objet des présentes ;"), 0, 'L');		
				
				
				 // 4 ieme page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead2($pdf, $object, 1, $outputlangs);
				
				$posy=40;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("5.2. Ces conditions suspensives doivent être parfaites avant le début des travaux. Elles sont stipulées dans l’intérêt exclusif des parties. La partie bénéficiaire de la condition peut y renoncer, et cela à tout moment, avant la date fixée au présent paragraphe. Cette renonciation n’est efficace qu’à condition d’avoir été notifiée à l’autre partie par tout moyen permettant d’attester de sa réception par le destinataire.
				
5.3. A défaut de réalisation de l'une quelconque des conditions suspensives visées au paragraphe 5.1 et sauf renonciation, les parties sont libérées de toutes obligations nées du présent contrat sans indemnité d'aucune sorte de part et d'autre à moins que la défaillance de la condition soit due au fait de la partie qui l’invoque pour se libérer."), 0, 'L');

				
				$posy=$posy+45;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 6 : Prix de vente"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.1 Détermination du prix"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.1.1. Le prix de vente ferme et définitif des locaux ci-dessus désignés est déterminé dans les «Conditions Particulières», le montant étant exprimé hors taxes. Le prix ainsi défini, est celui auquel la vente sera conclue sous la réserve expresse que l'acte notarié de vente soit signé par le Réservataire à la date de livraison effective du bien. Passé ce délai, ce prix sera majoré de 1% par mois de retard. Pour l’application de la majoration, tout mois commencé est réputé accompli en entier."), 0, 'L');
				
				
				$posy=$posy+25;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.1.2. Le prix de vente ainsi fixé ne tient pas compte :"), 0, 'L');
				
				$posy=$posy+6;
				$pdf->SetXY($posx+5,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("-	des frais d'acte notarié de vente y compris les frais de formalités ;
-	des frais d'établissement du règlement de copropriété ; 
-	des travaux personnels décidés par le Réservataire ;
-	des frais que le réservataire a l’intention d’utiliser ou de solliciter pour financer la présente acquisition."), 0, 'L');

				$posy=$posy+25;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.2 Modalités de paiement"), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.2.1. Le prix stipulé dans l'acte de vente est payable en fonction de l'avancement des travaux suivant l'échéancier figurant à l'article 21 du présent contrat (conditions particulières) sans pouvoir excéder :"), 0, 'L');
				
				// Condition de paiement 
				$sql = "SELECT code, libelle_facture, libelle_contrat";
        		$sql.= " FROM ".MAIN_DB_PREFIX.'c_payment_term';
        		$sql.= " WHERE rowid='".$object->cond_reglement_id."'";
				$resql = $this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					if ($num>0)
            		{
						$objcond = $this->db->fetch_object($resql);
						$libelle=($langs->trans("PaymentConditionShort".$objcond->code)!=("PaymentConditionShort".$objcond->code)?$langs->trans("PaymentConditionShort".$objcond->code):($objcond->libelle_facture!='-'?$objcond->libelle_facture:''));
						$conditions=$objcond->libelle_facture;
						$conditions2=$objcond->libelle_contrat;
					}
					else
						$conditions2 ="+ 45 % à l'élévation des murs du RDC ;
+ 75 % à l’élévation des murs du 4ème étage ;
+ 90 % à l'achèvement de l'immeuble, le pourcentage restant payable à la remise des clés. ";
				}
				
				$posy=$posy+12;
				$pdf->SetXY($posx+5,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode($conditions2), 0, 'L');
				
				
				$posy=$posy+35;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("6.2.2. L'état d'avancement des travaux est établi par des certificats du Maître d'œuvre d'exécution de l'opération.

6.2.3. La fraction du prix exigible à la signature de l'acte de vente est déterminée conformément à l'échelonnement  convenu  à l’article 21 du présent contrat (conditions particulières).

6.2.4. Les fractions de prix payables à terme ne portent pas intérêt mais les versements correspondants doivent intervenir au plus tard dans un délai déterminé dans les conditions particulières suivant la notification du stade d'avancement des travaux rendant exigible une nouvelle fraction de prix."), 0, 'L');


				 // 5 ieme page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead2($pdf, $object, 1, $outputlangs);
				
				$posy=40;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','BU', $default_font_size+2);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 7 : Réalisation de la vente"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("7.1. Délais"), 0, 'L');
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("7.1.1. L’acte de vente est établi par acte notarié à l’initiative du Réservant après l’achèvement total des travaux.

7.1.2. L’acte est adressé au réservataire par tout moyen écrit permettant d’attester de sa réception par le destinataire. Il comporte les informations et est accompagné des documents suivants :"), 0, 'L');


				$posy=$posy+20;
				$pdf->SetXY($posx+5,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("-	indication du prix définitif de la vente ;
-	copie du projet d’acte ainsi que ses annexes, notamment le plan du local à usage d’habitation avec indication des 	surfaces, la notice descriptive des équipements propres à ce local et, le cas échéant, des équipements extérieurs communs, l’indication de l’étude du notaire où sont déposés les pièces et documents qui ne seront pas annexés à l’acte, mais auxquels il sera fait référence ;
-	copie du cahier des charges comportant l’indication des numéros affectés par l’état descriptif de division aux biens 	et droits immobiliers objet des présentes."), 0, 'L');

				$posy=$posy+30;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("7.2. Modalités de la vente"), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("La vente est conclue en l'état futur d'achèvement."), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("7.3 Démarrage des travaux"), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("En vue de sauvegarder les intérêts des réservataires, le Réservant s’oblige dès la signature de 60% du programme et du dépôt effectif des acomptes contractuels, à procéder immédiatement au début des travaux pour le bloc d’immeuble concerné."), 0, 'L');
				
				$posy=$posy+20;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','U', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("Article 8 – Dépôt de garantie"), 0, 'L');
				
				$posy=$posy+7;
				$pdf->SetXY($posx,$posy);
				$pdf->SetFont('','', $default_font_size);
				$pdf->MultiCell(0, 6, html_entity_decode("8.1. En considération de la présente réservation, et en contrepartie du préjudice qui pourrait en résulter pour le Réservant en cas de non signature de l’acte de vente par le seul fait du réservataire, toutes les conditions suspensives ayant été réalisées, et notamment par suite de la perte qu'il éprouverait du fait de l'obligation dans laquelle il se trouverait de rechercher un nouvel acquéreur, le Réservataire verse le dépôt de garantie indiqué à l’article 25 du présent contrat (conditions particulières).

8.2. Cette somme qui est indisponible, incessible et insaisissable jusqu'à la conclusion du contrat de vente (sauf réalisation des hypothèses prévues aux 2ème et 3ème paragraphes du présent article) et qui n’est pas productive d'intérêt :

1°/ s'impute sur le prix de vente si celle-ci se réalise ;

2°/ est restituée sans indemnité de part et d'autre au bénéficiaire de la présente réservation dans les trois (3) mois de sa demande en cas d’exercice par le réservataire de sa faculté de rétractation dans le délai de sept (7) jours conformément à l’article 9 du présent contrat.

3°/ est restituée, sans retenue ni pénalité, dans l'hypothèse où le Réservant notifie au Réservataire son intention de ne pas réaliser l'opération immobilière projetée ; sans que ceci puisse entraîner une quelconque demande d'indemnité de part et d'autre.
"), 0, 'L');
				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();


				
				// Pied de page
				//$this->_pagefoot($pdf,$object,$outputlangs);
				//$pdf->AliasNbPages();
				//$pagenb++;
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


	/**
	 *  Show payments table
     *  @param      pdf             Object PDF
     *  @param      object          Object invoice
     *  @param      posy            Position y in PDF
     *  @param      outputlangs     Object langs for output
     *  @return     int             <0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		$tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetXY ($tab3_posx, $tab3_top - 5);
		$pdf->MultiCell(60, 5, $outputlangs->transnoentities("PaymentsAlreadyDone"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY ($tab3_posx, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+21, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+40, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY ($tab3_posx+58, $tab3_top );
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);

		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY ($tab3_posx, $tab3_top+$y );
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3 );

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog($this->db,$this->error, LOG_ERR);
			return -1;
		}

		// Loop on each payment
		$sql = "SELECT p.datep as date, p.fk_paiement as type, p.num_paiement as num, pf.amount as amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid and pf.fk_facture = ".$object->id;
		$sql.= " ORDER BY p.datep";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY ($tab3_posx, $tab3_top+$y );
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($row->amount), 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->getTradFromKey("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY ($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3 );

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog($this->db,$this->error, LOG_ERR);
			return -1;
		}

	}


	/**
	 *	\brief      Affiche infos divers
	 *	\param      pdf             Objet PDF
	 *	\param      object          Objet facture
	 *	\param		posy			Position depart
	 *	\param		outputlangs		Objet langs
	 *	\return     y               Position pour suite
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');

			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY(52, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell(80, 4, $lib_condition_paiement,0,'L');

			$posy=$pdf->GetY()+3;
		}


		if ($object->type != 2)
		{
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
			&& ! $conf->global->FACTURE_CHQ_NUMBER
			&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
				$pdf->SetTextColor(0,0,0);

				$posy=$pdf->GetY()+1;
			}

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR')
			{
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell(80, 5, $titre, 0, 'L');

				$pdf->SetFont('','', $default_font_size - 2);
				$pdf->SetXY(50, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');

				$posy=$pdf->GetY()+2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
			{
				// Si mode reglement non force ou si force a CHQ
				if ($conf->global->FACTURE_CHQ_NUMBER)
				{
					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - 2);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
						$posy=$pdf->GetY()+1;

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','', $default_font_size - 2);
						$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($account->adresse_proprio), 0, 'L', 0);
						$posy=$pdf->GetY()+2;
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
					{
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - 2);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$outputlangs->convToOutputCharset($this->emetteur->name).' '.$outputlangs->transnoentities('SendTo').':',0,'L',0);
						$posy=$pdf->GetY()+1;

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','', $default_font_size - 2);
						$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
						$posy=$pdf->GetY()+2;
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
			{
				if (! empty($conf->global->FACTURE_RIB_NUMBER))
				{
					$account = new Account($this->db);
					$account->fetch($conf->global->FACTURE_RIB_NUMBER);

					$curx=$this->marge_gauche;
					$cury=$posy;

					$posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account);

					$posy+=2;
				}
			}
		}

		return $posy;
	}


	/**
	 *	\brief      Affiche le total a payer
	 *	\param      pdf             Objet PDF
	 *	\param      object          Objet facture
	 *	\param      deja_regle      Montant deja regle
	 *	\param		posy			Position depart
	 *	\param		outputlangs		Objet langs
	 *	\return     y               Position pour suite
	 */
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

	/**
	 *   	\brief      Show header of page
	 *      \param      pdf             Object PDF
	 *      \param      object          Object invoice
	 *      \param      showaddress     0=no, 1=yes
	 *      \param      outputlangs		Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress=1, $outputlangs)
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
		$pdf->SetFont('','B', $default_font_size + 3);

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
		
		// Titre 
		$posy= $posy + 30;
		$posx=$this->marge_gauche+50;
		$pdf->SetXY($posx,$posy);
		$pdf->SetFont('','B',$default_font_size + 8);
		$pdf->MultiCell(100, 4, "CONTRAT DE RESERVATION", 0, 'L');
		$pdf->Rect($posx-8, $posy, 110, 8);
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
		$pdf->SetFont('','B', $default_font_size + 3);

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
