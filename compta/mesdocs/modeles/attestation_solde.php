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
$id_cat=$invoice->lines[0]->rowpr;
$ref_prod=$invoice->lines[0]->product_ref;
$weight=$pr2->array_options["options_typeprod"];
$surface =$invoice->lines[0]->surface." ".measuring_units_string($invoice->lines[0]->surface_units,"surface");
$length=$pr2->array_options["options_nbniveau"];
/*if($invoice2->lines[0]->rowpr>0)
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
	$surface =$invoice2->lines[1]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface");
	$length=$invoice2->lines[1]->length;
	
}*/
$nblignes = sizeof($invoice2->lines);
for ($i =0 ; $i < $nblignes ; $i++)
{
		if(($invoice2->lines[$i]->rowpr)&&($invoice2->lines[$i]->weight>0))
		{
			$id_cat=$invoice2->lines[$i]->rowpr;
			$ref_prod=$invoice2->lines[$i]->product_ref;
			$weight=$invoice2->lines[$i]->weight;
			$surface =$invoice2->lines[$i]->surface." ".measuring_units_string($invoice2->lines[$i]->surface_units,"surface");
			$length=$invoice2->lines[$i]->length;
			$temoin=1;
			break;
		}
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
    $this->SetXY(100,30);
    // Titre
    $this->Cell(40,0,'ATTESTATION DE SOLDE',0,0,'C');
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

    $bankline=new AccountLine($db);

    if ($object->fk_account > 0)
    {
        $bankline->fetch($object->bank_line);
        if ($bankline->rappro)
        {
            $disable_delete = 1;
            $title_button = dol_escape_htmltag($langs->transnoentitiesnoconv("CantRemoveConciliatedPayment"));
        }
        $accountstatic=new Account($db);
        $accountstatic->fetch($bankline->fk_account);
        $resl=$accountstatic;
       //echo '<pre>'; var_dump($resl->label);die;
    }

// Instanciation de la classe dérivée
$x=10;
$y=65;
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Times','',13);
//echo '<pre>';var_dump("banque",$cat);die;
//echo '<pre>';var_dump("birthday",$objectcont);die;
//echo '<pre>';var_dump("info client",$client);die;
$pdf->MultiCell(0,6,''.utf8_decode('Nous soussignés, ').html_entity_decode($resl->label).utf8_decode(', 

Certifions et attestons que :'),0,1);
$y=$y+10;
/*$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'En date du : '.dol_print_date($paiement2->date,'day'),0,'L');
$y=$y+6;
*/
$pdf->SetFont('Times','',13);

$pdf->SetXY($x,$y);
//$pdf->MultiCell(0,6,utf8_decode($objectcont->getCivilityLabel().' '.$client->nom." né le ".dol_print_date($objectcont->birthday,'day')." demeurant à ".$client->address." (").html_entity_decode($client->pays.")")." ; titulaire d'un(e) ".utf8_decode($client->siret).utf8_decode(" N° ".$client->tva_intra) ,0,'L');
$pdf->MultiCell(0,6,utf8_decode($objectcont->getCivilityLabel().' '.$client->nom." né le ".dol_print_date($objectcont->birthday,'day')." demeurant à ".$client->address." (").utf8_decode(html_entity_decode(getCountry(22, 0, $db).")"))." ; titulaire d'un(e) ".utf8_decode(get_TypePiece($client->array_options['options_typepiece'])).utf8_decode(" N° ".$client->array_options['options_numpiece']) ,0,'L');

$y=$y+15;

$paiement = $invoice2->getSommePaiement();
            $creditnotes=$invoice2->getSumCreditNotesUsed();
            $deposits=$invoice2->getSumDepositsUsed();
            $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
            $remaintopay=price2num($invoice2->total_ttc - $paiement - $creditnotes - $deposits,'MT');
			
			

    $pdf->SetXY($x,$y);
    $pdf->MultiCell(0,6,utf8_decode("A versé à ce jour par ses soins la somme de ".$nb->ConvNumberLetter($alreadypayed,0,0)."(").price($alreadypayed)."). francs cfa pour l'acquisition hors taxe d'un ".get_TypeProduct($invoice2->lines[0]->weight)." d'une superficie approximative de ".$invoice2->lines[0]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface")." au ".utf8_decode(get_NiveauProduct($invoice2->lines[0]->length))." dans notre ".html_entity_decode($cat->description.' ') ,0,'L');
$y=$y+15;

$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('EN FOI DE QUOI, la présente attestation est délivrée à '.$objectcont->getCivilityLabel().' '.$objp->name.' pour servir et valoir ce que de droit.'),0,'L');
$pdf->SetFont('Arial','B',13);
$y=$y+54;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('Fait à Dakar, le '.dol_print_date(date('Y-m-d H:i:s'),'day').' '),0,'R');
$y=$y+8;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('La Trésorerie'),0,'R');
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
//$file = $dir . "/" ;
//$pdf->Output($file.''.dol_sanitizeFileName($object->ref).'.pdf','F');
$file = $dir . "/" ;
$pdf->Output($file.''.dol_sanitizeFileName('attestation_solde').dol_sanitizeFileName($object->ref).'.pdf','F');
?>