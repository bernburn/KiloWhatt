<?php
// api/export_pdf.php
require_once '../vendor/autoload.php';
require_once 'session_check.php';
requireLogin();

use Dompdf\Dompdf;
use Dompdf\Options;

$html = file_get_contents('php://input');

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Load the HTML content
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output the generated PDF
$dompdf->stream("KiloWhatt-Energy-Report.pdf", ["Attachment" => true]);
