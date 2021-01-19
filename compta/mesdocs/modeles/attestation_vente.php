<?php
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/fpdf/fpdf.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
//require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
//require_once(DOL_DOCUMENT_ROOT.'/product/class/product2.class.php');
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
$pr2= new Product($db);
// var_dump($invoice->lines[0]->fk_product);die;
$pr2->fetch($invoice->lines[0]->fk_product);
$id_cat=$invoice->lines[0]->rowpr;
$ref_prod=$invoice->lines[0]->product_ref;
$weight=$pr2->array_options["options_typeprod"];
$surface =$invoice->lines[0]->surface." ".measuring_units_string($invoice->lines[0]->surface_units,"surface");
$length=$pr2->array_options["options_nbniveau"];
//$pr= new Product2($db);
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

        $accountstatic=new Account($db);
        $accountstatic->fetch($bankline->fk_account);

    }
}


//$nblignes = sizeof($invoice2->lines);
$object = new Paiement($db);
//echo '<pre>';var_dump('object  ',$object);die;
$result=$object->fetch($id, $ref);

//echo '<pre>';var_dump($invoice2);die;
$idcategorie = $pr2->get_Id_Categorie($id_cat);
//$idcategorie = $pr2->get_Id_Categorie($invoice2->lines[0]->rowpr);
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
        $this->Image(DOL_DOCUMENT_ROOT.'/core/modules/facture/doc/logo-cgis.png',10,6,20,15);
        // Police Arial gras 15
        $this->SetFont('Arial','B',20);
        // Décalage à droite
        $this->SetXY(100,30);
        // Titre
        $this->Cell(40,0,'ATTESTATION DE VENTE',0,0,'C');
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
$pdf->MultiCell(0,6,''.utf8_decode('Nous soussignés, ').html_entity_decode($resl->label).', 

Certifions et attestons que :',0,1);
$y=$y+20;
/*$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'En date du : '.dol_print_date($paiement2->date,'day'),0,'L');
$y=$y+6;
*/

$pdf->SetFont('Times','',13);

$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode($objectcont->getCivilityLabel().' '.$client->nom." né le ".dol_print_date($objectcont->birthday,'day')." demeurant à ".$client->address." (").utf8_decode(html_entity_decode(getCountry(22, 0, $db).")"))." ; titulaire d'un(e) ".utf8_decode(get_TypePiece($client->array_options['options_typepiece'])).utf8_decode(" N° ".$client->array_options['options_numpiece']) ,0,'L');
$y=$y+15;
/*$pdf->SetXY($x,$y);
$pdf->SetFont('Times','B',13);
$pdf->MultiCell(0,6,'La somme de '. $nb->ConvNumberLetter($paiement2->montant,0,0).'('.price($paiement2->montant).') de Francs Cfa ',0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->SetFont('Times','',13);
// Payment type (VIR, LIQ, ...)
$labeltype=$langs->trans("PaymentType".$paiement2->type_code)!=("PaymentType".$paiement2->type_code)?$langs->trans("PaymentType".$paiement2->type_code):$paiement2->type_libelle;*/
//$pdf->MultiCell(0,6,'Par '.html_entity_decode($labeltype).' : '.utf8_decode($paiement2->numero),0,'L');

/*$pdf->SetFont('Times','',13);
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('A acquis la propriété ci aprés :'),0,'L');
$y=$y+7;
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
$pdf->MultiCell(0,6,html_entity_decode('Adresse : '.$cat->description.' '),0,'L');*/
//echo '<pre>';var_dump($invoice2);die;
//echo '<pre>';var_dump($invoice2->lines[0]->product_ref);die;
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
/*$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'Nom programme : '.str_replace("Programme","",html_entity_decode($cat->label)),0,'L');*/
$y=$y+7;
/*$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Adresse : '.$cat->description).'',0,'L');*/
$y=$y+15;
$pdf->SetXY($x,$y);

// total paye
/*   $paiement = $invoice2->getSommePaiement();
   $creditnotes=$invoice2->getSumCreditNotesUsed();
   $deposits=$invoice2->getSumDepositsUsed();
   $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
   $remaintopay=price2num($invoice2->total_ttc - $paiement - $creditnotes - $deposits,'MT');
$pdf->MultiCell(0,6,utf8_decode('Le montant total versé à ce jour par ses soins est donc de '.$nb->ConvNumberLetter($alreadypayed,0,0).'('.price($alreadypayed).') de Francs Cfa.'),0,'L');
$pdf->SetFont('Times','',13);*/
$y=$y+15;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('EN FOI DE QUOI, la présente attestation est délivrée à '.$objectcont->getCivilityLabel().' '.$client->nom.' pour servir et valoir ce que de droit.'),0,'L');
$pdf->SetFont('Arial','B',13);
$y=$y+54;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('Fait à Dakar, le '.dol_print_date(date('Y-m-d H:i:s'),'day').' '),0,'R');
$y=$y+8;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('La Direction'),0,'R');
//$dir = $conf->facture->dir_output . "/" . $invoice2->ref.'/'.dol_sanitizeFileName($object->ref);
$dir = $conf->facture->dir_output . "/" . $object->ref;
//var_dump($dir);die;
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
$pdf->Output($file.''.dol_sanitizeFileName('attestation_vente').dol_sanitizeFileName($object->ref).'.pdf','F');
?>