<?php
ob_start();
    include('depot.php');
    $content = ob_get_clean();
	
    // convert in PDF
    require_once("html2pdf/html2pdf.class.php");
    try
    {
        $html2pdf = new HTML2PDF('P', 'A4', 'fr', true, 'UTF-8', 0);
//      $html2pdf->setModeDebug();
        $html2pdf->setDefaultFont('Times',8);
        $html2pdf->writeHTML($content);
        $html2pdf->Output('test.pdf','I');
    }
    catch(HTML2PDF_exception $e) {
        echo $e;
        exit;
    }

?>
