<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;

$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");
$stmt = $pdo->query("SELECT name, company_name, contact FROM client");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$html = '<h3>Client List</h3><table border="1" cellpadding="5"><tr><th>Name</th><th>Company</th><th>Contact</th></tr>';
foreach ($clients as $c) {
    $html .= "<tr><td>{$c['name']}</td><td>{$c['company_name']}</td><td>{$c['contact']}</td></tr>";
}
$html .= '</table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("clients.pdf", ["Attachment" => true]);
?>
