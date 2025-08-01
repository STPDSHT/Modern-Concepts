<?php
require 'config.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=employees_report.xls");

$conn = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// EMPLOYEE LIST
echo "Employee List\n";
echo "Name\tContact\tSalary Rate\tUnpaid Salary\tStatus\n";

$employees = $conn->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$salarySums = $conn->query("SELECT employee_id, SUM(salary_amount) AS total_salary FROM employee_salaries WHERE is_paid = 0 GROUP BY employee_id")->fetchAll(PDO::FETCH_KEY_PAIR);

foreach ($employees as $emp) {
    $unpaid = isset($salarySums[$emp['id']]) ? number_format($salarySums[$emp['id']], 2) : '0.00';
    echo "{$emp['name']}\t{$emp['contact']}\t{$emp['salary_rate']}\t{$unpaid}\t{$emp['status']}\n";
}

echo "\n"; // Separator

// SALARY PAYMENT HISTORY
echo "Salary Payment History\n";
echo "Employee\tAmount Paid\tMethod\tPaid Date\n";

$transactions = $conn->query("
    SELECT ep.total_paid, ep.payment_method, ep.paid_date, e.name 
    FROM employee_payments ep
    JOIN employees e ON ep.employee_id = e.id
    ORDER BY ep.paid_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($transactions) {
    foreach ($transactions as $txn) {
        $formattedDate = date("Y-m-d H:i:s", strtotime($txn['paid_date']));
        echo "{$txn['name']}\t₱" . number_format($txn['total_paid'], 2) . "\t{$txn['payment_method']}\t{$formattedDate}\n";
    }
} else {
    echo "No payment history found.\n";
}
exit;
