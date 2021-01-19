<?php
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/fpdf/fpdf.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product2.class.php');
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
$pr= new Product2($db);
$pr2= new Product($db);
// var_dump($invoice->lines[0]->fk_product);die;
$pr2->fetch($invoice->lines[0]->fk_product);
if($invoice2->lines[0]->rowpr>0)
{
	$id_cat=$invoice2->lines[0]->rowpr;
	$ref_prod=$invoice2->lines[0]->product_ref;
	$weight=$invoice2->lines[0]->weight;
	$surface =$invoice2->lines[0]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface");
	$length=$invoice2->lines[0]->length;
}
else
{
	$id_cat=$invoice2->lines[1]->rowpr;
	$ref_prod=$invoice2->lines[1]->product_ref;
	$weight=$invoice2->lines[1]->weight;
	$surface =$invoice2->lines[1]->surface." ".measuring_units_string($invoice2->lines[1]->surface_units,"surface");
	$length=$invoice2->lines[1]->length;
	
}
$idcategorie = $pr2->get_Id_Categorie($id_cat);

//$idcategorie = $pr->get_Id_Categorie($invoice2->lines[0]->rowpr);
$cat= new Categorie($db);
$cat->fetch($idcategorie);
$fact= new Facture($db);
$fact->fetch($paiement2->facid);

$client =new Societe($db);
$client->fetch($objp->socid);
$sql = "SELECT p.rowid ";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " WHERE p.fk_soc = ".$objp->socid;
	
$result = $db->query($sql);
$objet = $db->fetch_object($result);
$objectcont = new Contact($db);
$objectcont->fetch($objet->rowid);

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
    $this->SetXY(110,30);
    // Titre
    $this->Cell(40,0,'ATTESTATION DE REMISE DE CLES',0,0,'C');
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
$pdf->SetFont('Times','',13);
$pdf->MultiCell(0,6,''.utf8_decode('Nous soussignés, '). html_entity_decode($conf->global->MAIN_INFO_SOCIETE_NOM).utf8_decode(', 

Représentée par Monsieur Amadou Lamine NDIAYE, Co-Gérant de ladite société :'),0,1);
$y=$y+15;
/*$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'En date du : '.dol_print_date($paiement2->date,'day'),0,'L');
$y=$y+6;
*/
$pdf->SetFont('Times','',13);
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode("Remet par la présente à ".$objectcont->getCivilityLabel().' '.$objp->nom." qui accepte, l' ".strtolower(get_TypeProduct2($invoice2->lines[0]->weight))." suivant : ") ,0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Référence : ').$ref_prod,0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Type : '.get_TypeProduct($weight),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Superficie : '.$surface,0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Niveau : '.utf8_decode(get_NiveauProduct($length)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Nom programme : '.str_replace("Programme","",$cat->label),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Adresse : ').html_entity_decode($cat->description).' ',0,'L');
$y=$y+13;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode("Tous les droits et obligations relatifs à la propriété dudit appartement sont désormais transférés à l'acquéreur.") ,0,'L');
//$y=$y+40;
/*$pdf->SetXY($x,$y);
$pdf->SetFont('Times','B',13);
$pdf->MultiCell(0,6,'La somme de '. $nb->ConvNumberLetter($paiement2->montant,0,0).'('.price($paiement2->montant).') de Francs Cfa ',0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','',13);
// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$paiement2->type_code)!=("PaymentType".$paiement2->type_code)?$langs->trans("PaymentType".$paiement2->type_code):$paiement2->type_libelle;*/
//$pdf->MultiCell(0,6,'Par '.utf8_decode($labeltype).' : '.utf8_decode($paiement2->numero),0,'L');

//$pdf->SetFont('Times','',13);
//$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,utf8_decode('A acquis un '.get_TypeProduct($invoice2->lines[0]->weight).' formant n° '.$invoice2->lines[0]->product_ref." ".utf8_decode(" d'une superficie approximative totale de").$invoice2->lines[0]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface")." au ".get_NiveauProduct($invoice2->lines[0]->length)." de notre immeuble ".str_replace("Programme","",utf8_decode($cat->label)).", ".$cat->description),0,'L');
/*$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Référence : ').$invoice2->lines[0]->product_ref,0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Type : '.get_TypeProduct($invoice2->lines[0]->weight),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Superficie : '.$invoice2->lines[0]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface"),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Niveau : '.utf8_decode(get_NiveauProduct($invoice2->lines[0]->length)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Nom programme : '.str_replace("Programme","",utf8_decode($cat->label)),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Adresse : '.$cat->description).'',0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);*/

// total paye
           /* $paiement = $invoice2->getSommePaiement();
            $creditnotes=$invoice2->getSumCreditNotesUsed();
            $deposits=$invoice2->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
            $remaintopay=price2num($invoice2->total_ttc - $paiement - $creditnotes - $deposits,'MT');
$pdf->MultiCell(0,6,utf8_decode('Toutefois, la propriété pleine et entière sera effective après avoir payé le reliquat dû '.$nb->ConvNumberLetter($remaintopay,0,0).'('.price($remaintopay).') de Francs Cfa.
'),0,'L');
$pdf->SetFont('Times','',13);*/
$y=$y+20;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode("Les frais d'actes devront également être payés pour disposer d'un acte notarié conformément aux textes et lois.

En foi de quoi, il lui est délivré la présente attestation pour servir et valoir ce que de droit.

La présente attestation est faite en deux exemplaires signés par les parties."),0,'L');
$pdf->SetFont('Arial','B',13);
$y=$y+54;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,7,utf8_decode('La Direction'),0,'L');
$pdf->SetXY($x+50,$y);
$pdf->MultiCell(0,7,utf8_decode($objectcont->getCivilityLabel().' '.$objp->nom.'
Acquéreur'),0,'R');
//$dir = $conf->facture->dir_output . "/" . $invoice2->ref.'/'.dol_sanitizeFileName($object->ref);
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
//$pdf->Output($file.''.dol_sanitizeFileName($object->ref).'.pdf','F');
$pdf->Output($file.''.dol_sanitizeFileName('remise clé').dol_sanitizeFileName($object->ref).'.pdf','F');
?>