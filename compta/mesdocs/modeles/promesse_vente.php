<?php 
require_once('chiffreEnLettre.php');
$nb= new chiffreEnLettre();
$pr= new Product2($db);
$idcategorie = $pr->get_Id_Categorie($invoice2->lines[0]->rowid);
$cat= new Categorie($db);
$cat->fetch($idcategorie);
$fact= new Facture($db);
$fact->fetch($paiement2->facid);
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
.tableau1{
	background:url(/home/www/1ad5b497e43a3518625aabfca63d861a/web/gescom/htdocs/includes/modules/facture/doc/1.png);
}
.tableau2{
	background:url(/home/www/1ad5b497e43a3518625aabfca63d861a/web/gescom/htdocs/includes/modules/facture/doc/2.png);
}
.tableau3{
	background:url(/home/www/1ad5b497e43a3518625aabfca63d861a/web/gescom/htdocs/includes/modules/facture/doc/3.png);
}
</style>
<page backtop="7mm" backbottom="7mm" backleft="10mm" backright="10mm" background="entete.jpg" >
<table width="100%" border="0" align="center" cellspacing="6" class="tableau">
  <tr class="tableau3">
    <td width="655" align="center" class="titre">ATTESTATION DE RESERVATION</td>
  </tr>
  <tr class="tableau2">
    <td align="left" valign="top" class="textenormal"><p><br/>
        Nous soussignée, SABLUX GROUP, société à responsabilité limitée, au capital social de CINQ MILLIONS (5.000.000) DE FRANCS CFA, ayant son siège social à Dakar, N°27, Comico VDN et immatriculée au registre du commerce et du crédit mobilier de Dakar sous le numéro SN-DKR 2009.B.16077,<br/>
        
        Représentée par Monsieur Amadou Lamine NDIAYE, Co-Gérant de ladite société,<br/>
        Certifions et attestons que&nbsp;:<br/>
        Madame, Monsieur <br />
        <?php echo $objp->nom; ?>, <br />
        Née le (date et lieu de naissance): <?php echo $objp->nom; ?>, <br />
        Nationalité  <?php echo $objp->nom; ?> <br />
        Type piece n° <?php echo $objp->nom; ?> délivré le <?php echo $objp->nom; ?>&nbsp;et <br />
        Adresse <?php echo $objp->nom; ?>&nbsp;; <br/>
        
        Se propose d&rsquo;acquérir&nbsp;un appartement décrit comme suit&nbsp;:<br/>
        Type: <?php echo get_TypeProduct($invoice2->lines[0]->weight); ?><br/>
        Superficie: <?php echo $invoice2->lines[0]->surface." ".measuring_units_string($invoice2->lines[0]->surface_units,"surface"); ?>, <br/>
        Niveau: <?php echo get_NiveauProduct($invoice2->lines[0]->length); ?>, <br/>
        Nom programme: <?php print $cat->description; ?>, <br/>
        Adresse: <?php echo $cat->description; ?> , <br/>
        Bâti sur les lots: ,
        <br/>
        Inscrits au titre foncier numéro : ,
        <br/>
        Ladite vente aura lieu au comptant moyennant le prix principal de <?php echo $nb->ConvNumberLetter($invoice2->total_ttc,0,0); ?> ( <?php echo price($invoice2->total_ttc) ?>) FRANCS CFA hors frais selon les conditions suivantes&nbsp;:<br/>
        <br>
        Acompte de <?php echo $nb->ConvNumberLetter($paiement2->montant,0,0); ?> ( <?php echo price($paiement2->montant) ?>)de Francs Cfa fait en contrepartie de sa réservation, le Réservataire verse à ce jour au Réservant qui le reconnait et lui en accorde bonne et valable quittance, un dépôt de garantie non producteur d&rsquo;intérêt à faire valoir sur le prix de vente définitif&nbsp;;<br>
        La réalisation de la vente est assujettie au versement intégral du prix principal.<br>
        <br>
        Les frais globaux s&rsquo;élèveront à la somme de <?php echo $nb->ConvNumberLetter($paiement2->montant,0,0); ?> ( <?php echo price($paiement2->montant) ?>) FRANCS CFA.<br />
        Soit au total la somme de <?php echo $nb->ConvNumberLetter($invoice2->total_ttc,0,0); ?> ( <?php echo price($invoice2->total_ttc) ?>) FRANCS CFA.<br/><br/>
        La vente des appartements du projet est domiciliée chez Maître Mahmoudou Aly Touré, Notaire à Dakar  - Sacré-Cœur 3 N° 9253.<br/><br/>
        Cette présente attestation est valable 30 jours calendaires, <br>durée pendant laquelle le client devra verser l&rsquo;intégralité du montant de la vente ou justifier par un établissement de crédit d&rsquo;un emprunt obtenu en vue de l&rsquo;acquisition du présent bien.<br/>
        Passé ce délai de 30 jours calendaires&nbsp;:<br/>
       
        Sablux Group se réserve le droit de proposer le bien à un autre client de plein droit et sans qu&rsquo;il ait besoin d&rsquo;une mise en demeure&nbsp;;<br>
        Un montant représentant 50 % des frais de réservation sera conservé par Sablux Group à titre compensatoire.<br>
        <br>
        EN FOI DE QUOI, la présente attestation est délivrée à <?php echo $objp->nom; ?> afin de la verser dans son dossier de demande de crédit.<br/>
    </p></td>
  </tr>
  <tr class="tableau1">
    <td align="right" valign="top" class="textenormal">Le <?php echo dol_print_date(date('Y-m-d H:i:s'),'day'); ?>
    <br>
    Mr Souleymane DIALLO<br>                                                                   Administrateur </td>
  </tr>
</table>
</page>