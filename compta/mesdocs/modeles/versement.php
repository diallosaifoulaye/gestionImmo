<?php 
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
$pr= new Product2($db);
$idcategorie = $pr->get_Id_Categorie($invoice2->lines[0]->rowid);
$cat= new Categorie($db);
$cat->fetch($idcategorie);
?>
<style type="text/css">
.titre {
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 36px;
	text-decoration: underline;
}
.textenormal {
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 16px;
}
.textenormalRouge {
	font-family: Georgia, "Times New Roman", Times, serif;
	font-size: 16px;
	color: #F00;
}
</style>
<page backtop="7mm" backbottom="7mm" backleft="10mm" backright="10mm">
<table width="510" border="0" align="center" cellspacing="6">
  <tr>
    <td width="496" align="center" class="titre">ATTESTATION DE VERSEMENT</td>
  </tr>
  <tr>
    <td align="left" valign="top" class="textenormal">Nous soussignés, Sablux Group Sarl, sis au N°G - Avenue Cardinal Hyacinthe Thiandoum,<br><br>
      Attestons par la présente avoir reçu<br>
      Le <?php echo dol_print_date($paiement2->date,'day'); ?> <br>
      De <?php echo $objp->nom; ?>,  <br>
      La somme de  <?php echo $nb->ConvNumberLetter($paiement2->montant,0,0); ?> ( <?php echo price($paiement2->montant) ?>)<strong> de Francs Cfa</strong>  
      <br>
      Par chèque  <?php echo $paiement2->numero; ?> 
      <br>
      Sur notre compte courant numéro <?php echo $compte->number; ?>
      <br> 
      Domicilié à la <?php echo $compte->bank; ?> Groupe Attijariwafa Bank, 
      <br>à titre de dépôt de réservation  pour l&rsquo;acquisition d&rsquo;un <?php echo $invoice2->lines[0]->libelle; ?> <br>
      Sise à <?php echo $cat->description; ?><br>
      <br>
      Le montant total versé à ce jour par ses soins est donc 
      <br>de <strong><?php echo $nb->ConvNumberLetter($paiement2->montant,0,0); ?> ( <?php echo price($paiement2->montant) ?>) de francs cfa</strong>.<br><br>
    En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.<br></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="textenormal">Le <?php echo dol_print_date(date('Y-m-d H:i:s'),'day'); ?></td>
  </tr>
  <tr>
    <td align="right" valign="top" class="textenormal"><strong>La Trésorerie</strong></td>
  </tr>
</table>
</page>