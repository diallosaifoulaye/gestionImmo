<?php
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/fpdf/fpdf.php');

require_once(DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php');
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
require_once(DOL_DOCUMENT_ROOT ."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT.'/product/class/product2.class.php');

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
$pr= new Product2($db);
//$objectSoc = fetchcustom($db,$objp->socid);
//var monadress;
$client =new Societe($db);
$client->fetch($objp->socid);
$sql = "SELECT p.rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " WHERE p.fk_soc = ".$objp->socid;

$result = $db->query($sql);
$objet = $db->fetch_object($result);
$objectcont = new Contact($db);
$objectcont->fetch($objet->rowid);
$country_label=getCountry(22, 0, $db);
//echo '<pre>';var_dump($objectcont);die;

//NEW
$pr2= new Product($db);
// var_dump($invoice->lines[0]->fk_product);die;
$pr2->fetch($invoice->lines[0]->fk_product);
$id_cat=$invoice->lines[0]->rowpr;
$ref_prod=$invoice->lines[0]->product_ref;
$weight=$pr2->array_options["options_typeprod"];
$surface =$invoice->lines[0]->surface." ".measuring_units_string($invoice->lines[0]->surface_units,"surface");
$length=$pr2->array_options["options_nbniveau"];

//echo '<pre>';var_dump($conf->global->MAIN_INFO_SOCIETE_NOM);die;
$idcategorie = $pr2->get_Id_Categorie($id_cat);
$cat= new Categorie($db);
$cat->fetch($idcategorie);
$fact= new Facture($db);
$fact->fetch($paiement2->facid);
$id_cat=$invoice->lines[0]->rowpr;
$ref_prod=$invoice->lines[0]->product_ref;
$weight=$pr2->array_options["options_typeprod"];
$surface =$invoice->lines[0]->surface." ".measuring_units_string($invoice->lines[0]->surface_units,"surface");
$length=$pr2->array_options["options_nbniveau"];

//echo '<pre>';var_dump($object->ref);die;
$idcategorie = $pr2->get_Id_Categorie($id_cat);
$cat= new Categorie($db);
$cat->fetch($idcategorie);
$fact= new Facture($db);
$fact->fetch($paiement2->facid);



class PDF extends FPDF
{
// En-tête
    function Header()
    {
        // Logo
        $this->Image(DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/logo-cgis.png',10,6,20,15);
        // Police Arial gras 15
        $this->SetFont('Arial','B',20);
        // Décalage à droite
        $this->SetXY(100,30);
        // Titre
        $this->Cell(40,0,'ATTESTATION DE RESERVATION',0,0,'C');
        // Saut de ligne
        $this->Ln(10);
    }

// Pied de page
    function Footer()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial','I',8);
        // Numéro de page
        //$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Instanciation de la classe dérivée
$x=10;
$y=70;
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times','',11);
$pdf->MultiCell(0,6,utf8_decode('Nous soussignés, '.$conf->global->MAIN_INFO_SOCIETE_NOM.',

Representée par'. $conf->global->MAIN_INFO_SOCIETE_MANAGERS.', Co-Gerant de ladite société,'),0,1);
$pdf->SetXY($x,$y+4);
$pdf->MultiCell(0,4,utf8_decode('Certifions et attestons que :
Madame, Monsieur :'.$objp->name),0,'L');
$y=$y+12;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Née le (date et lieu de naissance) :'.dol_print_date($objectcont->birthday,'day')),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Nationalité :'.html_entity_decode($country_label)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Type pièce :'.get_TypePiece($client->array_options['options_typepiece'])),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Numéro pièce :'.$client->array_options['options_numpiece']),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Adresse :'.$objectcont->address),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Se propose d acquerir un appartement decrit comme suit : '),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Type : '.get_TypeProduct($weight)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Superficie : '.utf8_decode(html_entity_decode($surface))),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Niveau : '.utf8_decode(get_NiveauProduct($length)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Nom programme : '.str_replace("Programme","",$cat->label)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Adresse : '.$cat->description),0,'L');
/*$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Bati sur les lots:: ',0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Inscrits au titre foncier numero: ',0,'L');*/
$y=$y+13;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Ladite vente aura lieu au comptant moyennant le pri) FRANCS CFA hors frais selon les conditions suivantes :'),0,'L');
$y=$y+13;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Acompte de ( ) de Francs Cfa fait en contrepartie de sa réservation, 
le Réservataire verse à ce jour au Réservant qui le reconnait et lui en accorde bonne et valable quittance, un depot de garantie non producteur d\'interet a faire valoir sur le prix de vente définitif ;
La réalisation de la vente est assujettie au versement intégral du prix principal.'),0,'L');
$y=$y+25;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Les frais globaux s\'éléveront a la somme de '.$nb->ConvNumberLetter($paiement2->montant,0,0).' ('.price($paiement2->montant).') FRANCS CFA. 
Soit au total la somme de  '.$nb->ConvNumberLetter($paiement2->montant,0,0).' ('.price($paiement2->montant).') FRANCS CFA. 
La vente des appartements du projet est domiciliée chez '.$cat->notaire.'.
Cette présente attestation est valable 30 jours calendaires, durée pendant laquelle le client devra verser l\'integralite du montant de la vente ou justifier par un établissement de credit d\'un emprunt obtenu en vue de l\'acquisition du present bien.
Passé ce delai de 30 jours calendaires, Sablux Group se réserve le droit de proposer le bien a un autre client de plein droit et sans qu il ait besoin d\'une mise en demeure

EN FOI DE QUOI, la présente attestation est delivrée à afin de la verser dans son dossier de demande de credit.'),0,'L');
$pdf->SetFont('Times','B',11);
$y=$y+71;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,4,'Le 21 Dec 2013
Mr Souleymane DIALLO
Administrateur',0,'R');
$dir = $conf->facture->dir_output . "/" . $object->ref;
if (! file_exists($dir))
{
    if (create_exdir($dir) < 0)
    {
        //$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
        return 0;
    }
}
$file = $dir . "/" ;
$pdf->Output($file.'Promesse_vente_'.dol_sanitizeFileName($object->ref).'.pdf','F');

?>


