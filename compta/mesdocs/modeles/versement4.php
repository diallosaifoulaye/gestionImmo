<?php
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/fpdf/fpdf.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/mesdocs/product.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php');
require_once(DOL_DOCUMENT_ROOT.'/product/class/product.class.php');
require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');

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
        $this->Cell(40,0,'ATTESTATION DE VERSEMENT',0,0,'C');
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
$pdf->MultiCell(0,6,''.utf8_decode('Nous soussignés, ').utf8_decode($conf->global->MAIN_INFO_SOCIETE_NOM).htmlspecialchars_decode(', 
Attestons par la présente avoir recu en date du : '.dol_print_date($paiement2->date,'day'),0,'L'));
$y=$y+6;
$pdf->SetFont('Times','B',13);
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,'De : '.utf8_decode($objp->name),0,'L');
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
$pdf->MultiCell(0,6,utf8_decode('A titre de versement  pour l\'acquisition d\'un appartement '),0,'L');
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,utf8_decode('Référence : ').dol_sanitizeFileName($object->ref),0,'L');
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
//$pdf->MultiCell(0,6,'Nom programme : '.str_replace("Programme","",html_entity_decode($cat->label)),0,'L');
$pdf->MultiCell(0,6,'Nom programme : '.html_entity_decode($cat->label));
$y=$y+7;
$pdf->SetXY($x,$y);
$pdf->MultiCell(0,6,('Adresse : '.htmlspecialchars_decode($cat->description)).'',0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);


/*print '<tr class="visibleifcustomer"><td>' . $form->editfieldkey('CustomersCategoriesShort', 'custcats', '', $object, 0) . '</td>';
print '<td colspan="3">';
$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, null, null, null, 1);
$c = new Categorie($db);
$cats = $c->containing($object->id, Categorie::TYPE_CUSTOMER);
$arrayselected=array();
foreach ($cats as $cat) {
    $arrayselected[] = $cat->id;
}
print $form->multiselectarray('custcats', $cate_arbo, $arrayselected, '', 0, '', 0, '90%');
print "</td></tr>";*/

// total paye
$paiement = $invoice->getSommePaiement();
$creditnotes=$invoice->getSumCreditNotesUsed();
$deposits=$invoice->getSumDepositsUsed();
$alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
$remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
/*$pdf->MultiCell(0,6,utf8_decode('Le montant total versé à ce jour par ses soins est donc de '.$nb->ConvNumberLetter($alreadypayed,0,0).'('.price($alreadypayed).') de Francs Cfa.'),0,'L');
$y=$y+15;
$pdf->SetXY($x,$y);*/
$pdf->MultiCell(0,6,utf8_decode('En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit. '),0,'L');
$pdf->SetFont('Arial','B',13);
$y=$y+54;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('Fait à Dakar, le '.dol_print_date(date('Y-m-d H:i:s'),'day').' '),0,'R');
$y=$y+8;
$pdf->SetXY($x+130,$y);
$pdf->MultiCell(0,7,utf8_decode('La Trésorerie'),0,'R');
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
$pdf->Output($file.'versement_'.dol_sanitizeFileName($object->ref).'pdf','F');

/*function get_Id_Categorie($id)
{
    global $db;
    require_once(DOL_DOCUMENT_ROOT.'/product/class/con_gescom.php');
    //require_once('con_gescom.php');
    $list = -1;

    $sql = "SELECT fk_categorie";
    $sql.= " FROM ".MAIN_DB_PREFIX."categorie_product as p";
    $sql.= " WHERE p.fk_product = ".$id;
    $result=$db->query($sql);
    if ($result)
    {
        $num = mysqli_num_rows($result);
        $i=0;
        while ($i < $num)
        {
            $objp = mysqli_fetch_assoc($result);
            $list = $objp['fk_categorie'];
            $i++;
        }
    }
    else
    {

    }
    return $list;
}*/

?>