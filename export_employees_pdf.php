<?php
require_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php'; // mPDF autoloader

session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// Get employees
$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$salarySums = $pdo->query("SELECT employee_id, SUM(salary_amount) AS total_salary FROM employee_salaries WHERE is_paid = 0 GROUP BY employee_id")->fetchAll(PDO::FETCH_KEY_PAIR);

// Get salary payment history
$transactions = $pdo->query("
    SELECT ep.*, e.name 
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    ORDER BY ep.paid_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Start building PDF content
$mpdf = new \Mpdf\Mpdf();

$html = "<h2 style='text-align:center;'>Modern Concepts - Employee Salary Report</h2>";

$html .= "
<h3>Employee List</h3>
<table border='1' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; font-family: Arial, sans-serif;'>
    <thead>
        <tr style='background-color: #f2f2f2;'>
            <th>Name</th>
            <th>Contact</th>
            <th>Salary Rate (₱)</th>
            <th>Unpaid Salary (₱)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>";

foreach ($employees as $emp) {
    $unpaid = isset($salarySums[$emp['id']]) ? number_format($salarySums[$emp['id']], 2) : '0.00';
    $html .= "<tr>
        <td>" . htmlspecialchars($emp['name']) . "</td>
        <td>" . htmlspecialchars($emp['contact']) . "</td>
        <td>₱" . number_format($emp['salary_rate'], 2) . "</td>
        <td>₱" . $unpaid . "</td>
        <td>" . htmlspecialchars($emp['status']) . "</td>
    </tr>";
}

$html .= "</tbody></table><br><br>";

// Payment History Section
$html .= "
<h3>Salary Payment History</h3>
<table border='1' cellpadding='8' cellspacing='0' width='100%' style='border-collapse: collapse; font-family: Arial, sans-serif;'>
    <thead>
        <tr style='background-color: #f2f2f2;'>
            <th>Employee</th>
            <th>Amount Paid (₱)</th>
            <th>Method</th>
            <th>Paid Date</th>
        </tr>
    </thead>
    <tbody>";

if ($transactions) {
    foreach ($transactions as $txn) {
        $html .= "<tr>
            <td>" . htmlspecialchars($txn['name']) . "</td>
            <td>₱" . number_format($txn['total_paid'], 2) . "</td>
            <td>" . htmlspecialchars($txn['payment_method']) . "</td>
            <td>" . htmlspecialchars($txn['paid_date']) . "</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='4' style='text-align:center;'>No transaction history found.</td></tr>";
}

$html .= "</tbody></table>";

$mpdf->WriteHTML($html);
$mpdf->Output("employee_salary_report.pdf", "D"); // Force download
exit;
