<?php
require_once 'config.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=expenses_report.xls");
header("Pragma: no-cache");
header("Expires: 0");

$expenses = $conn->query("SELECT * FROM expenses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Item</th><th>Cost (php)</th><th>Category</th><th>Date</th></tr>";
foreach ($expenses as $exp) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($exp['item_name']) . "</td>";
    echo "<td>PHP " . number_format($exp['cost'], 2) . "</td>";
    echo "<td>" . htmlspecialchars($exp['category']) . "</td>";
    echo "<td>" . $exp['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";
exit;
