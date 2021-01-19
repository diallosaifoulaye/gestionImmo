<?php
require_once(DOL_DOCUMENT_ROOT.'/lib/fpdf/fpdf.php');
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
class PDF extends FPDF
{
// En-tête
function Header()
{
    // Logo
    $this->Image('/home/www/1ad5b497e43a3518625aabfca63d861a/web/gescom/htdocs/includes/modules/facture/doc/papier-entete-sabluximmo.jpg',10,6,180,290);
    // Police Arial gras 15
    $this->SetFont('Arial','B',20);
    // Décalage à droite
    $this->SetXY(100,30);
    // Titre
    $this->Cell(40,0,utf8_decode('BON DE PAIEMENT'),0,0,'C');
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
//$pdf->MultiCell(0,6,'Date du : '.dol_print_date($paiement2->date,'day'),0,'L');
$pdf->MultiCell(0,6,'Date du : '.dol_print_date(date("Y-m-d"),'day'),0,'L');
$pdf->SetFont('Times','',13);
$y=$y+6;
$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,'Bénéficiaire : '.utf8_decode($objp->nom),0,'L');
$pdf->MultiCell(0,6,'Bénéficiaire : Bocar Sy',0,'L');

$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','B',13);
//$pdf->MultiCell(0,6,'Montant :'. $nb->ConvNumberLetter($paiement2->montant,0,0).'('.price($paiement2->montant).') de Francs Cfa ',0,'L');
$pdf->MultiCell(0,6,'Montant :'. $nb->ConvNumberLetter(100000,0,0).'('.price(100000).') de Francs Cfa ',0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','B',13);
$pdf->MultiCell(0,6,'Motif :'. $nb->ConvNumberLetter(100000,0,0).'('.price(100000).') de Francs Cfa ',0,'L');
$y=$y+12;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Comptabilité '),0,'L');
$pdf->SetXY($x+50,$y);
$pdf->MultiCell(0,6,utf8_decode('Bénéficiaire : ').$invoice2->lines[0]->product_ref,0,'R');
$dir = $conf->facture->dir_output . "/" . $invoice2->ref.'/'.$id;
		if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					//$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}
		$file = $dir . "/" ;
$pdf->Output($file.'ticket.pdf','F');
?>