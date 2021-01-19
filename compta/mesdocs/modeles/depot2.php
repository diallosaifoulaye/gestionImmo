<?php
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/fpdf/fpdf.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
//$pr= new Product($db);
$pr2= new Product($db);
// var_dump($invoice->lines[0]->fk_product);die;
$pr2->fetch($invoice->lines[0]->fk_product);



    $id_cat=$invoice->lines[0]->rowpr;
    $ref_prod=$invoice->lines[0]->product_ref;
    $weight=$pr2->array_options["options_typeprod"];
    $surface =$invoice->lines[0]->surface." ".measuringUnitString($invoice->lines[0]->surface_units,"surface");
    $length=$pr2->array_options["options_nbniveau"];

//echo '<pre>';var_dump($pr2);die;
$idcategorie = $pr2->get_Id_Categorie($id_cat);
//$cat= new Categorie($db);
//$cat->fetch($idcategorie);
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
        $this->Cell(40,0,utf8_decode('REÇU DE PAIEMENT'),0,0,'C');
        // Saut de ligne
        $this->Ln(20);
    }

// Pied de page
    function Footer()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('','I',8);
        // Numéro de page
        //$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

// Instanciation de la classe dérivée
$x=10;
$y=65;
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$y=$y+6;
$pdf->SetFont('Times','B',13);
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'De : '.utf8_decode($objp->name),0,'L');
$pdf->SetFont('Times','',13);
$y=$y+6;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'En date du : '.dol_print_date($paiement2->date,'day'),0,'L');

$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','B',13);
$pdf->MultiCell(0,6,'La somme de '. $nb->ConvNumberLetter($paiement2->montant,0,0).'('.price($paiement2->montant).') de Francs Cfa ',0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','',13);
// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$paiement2->type_code)!=("PaymentType".$paiement2->type_code)?$langs->trans("PaymentType".$paiement2->type_code):$paiement2->type_libelle;
$pdf->MultiCell(0,6,'Par '.utf8_decode(html_entity_decode($labeltype)).' : '.utf8_decode($paiement2->numero),0,'L');

$y=$y+12;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('A titre de versement  pour l\'acquisition d\'un(e)  '.$pr2->label),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,utf8_decode('Référence : ').dol_sanitizeFileName($object->ref),0,'L');
$pdf->MultiCell(0,6,utf8_decode('Référence : ').dol_sanitizeFileName($invoice->lines[0]->product_ref),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Type : '.get_TypeProduct($weight),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Superficie : '.utf8_decode(html_entity_decode($surface)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Niveau : '.utf8_decode(get_NiveauProduct($length)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,'Nom programme : '.str_replace("Programme","",$cat->label),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,html_entity_decode(strip_tags('Adresse : '.$cat->description)).' ',0,'L');

$y=$y+54;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('Fait à Dakar, le '.dol_print_date(date('Y-m-d H:i:s'),'day').' '),0,'R');
$y=$y+15;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('La trésorerie'),0,'R');
$dir = $conf->facture->dir_output . "/" . $object->ref;
if (! file_exists($dir))
{
    if (create_exdir($dir) < 0)
    {
        $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
        return 0;
    }
}
$file = $dir . "/" ;
$pdf->Output($file.''.dol_sanitizeFileName('reçu de paiement_2').dol_sanitizeFileName($object->ref).'.pdf','F');
function create_exdir($dir)
{
    dol_mkdir($dir);
}
?>