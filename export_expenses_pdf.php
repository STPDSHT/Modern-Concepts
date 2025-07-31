<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php'; // mPDF autoloader

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$expenses = $conn->query("SELECT * FROM expenses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$mpdf = new \Mpdf\Mpdf();

$html = "
<h2 style='text-align:center;'>Modern Concepts - Expenses Report</h2>
<table border='1' style='width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;'>
    <thead>
        <tr style='background-color: #f2f2f2;'>
            <th style='padding: 8px;'>Item</th>
            <th style='padding: 8px;'>Cost (₱)</th>
            <th style='padding: 8px;'>Category</th>
            <th style='padding: 8px;'>Date</th>
        </tr>
    </thead>
    <tbody>
";

foreach ($expenses as $exp) {
    $html .= "<tr>
        <td style='padding: 8px;'>" . htmlspecialchars($exp['item_name']) . "</td>
        <td style='padding: 8px;'>₱" . number_format($exp['cost'], 2) . "</td>
        <td style='padding: 8px;'>" . htmlspecialchars($exp['category']) . "</td>
        <td style='padding: 8px;'>" . $exp['created_at'] . "</td>
    </tr>";
}

$html .= "
    </tbody>
</table>";

$mpdf->WriteHTML($html);
$mpdf->Output("expenses_report.pdf", "D"); // "D" for download
exit;
