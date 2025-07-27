<?php
$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// Add employee
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $status = 'Active';
    $photo = '';

    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/";
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $photo);
    }

    $salary_rate = isset($_POST['salary_rate']) ? floatval($_POST['salary_rate']) : 0.00;

    $stmt = $pdo->prepare("INSERT INTO employees (name, contact, photo, status, salary_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $contact, $photo, $status, $salary_rate]);
}

// Mark salary as paid
if (isset($_POST['mark_paid'])) {
    $employee_id = $_POST['employee_id'];
    $payment_method = $_POST['payment_method'];
    $payment_proof = '';

    if (!empty($_FILES['payment_proof']['name'])) {
        $proofDir = "uploads/payments/";
        if (!is_dir($proofDir)) mkdir($proofDir, 0777, true);
        $payment_proof = basename($_FILES['payment_proof']['name']);
        move_uploaded_file($_FILES['payment_proof']['tmp_name'], $proofDir . $payment_proof);
    }

    // Get unpaid total salary for employee
    $stmt = $pdo->prepare("SELECT SUM(salary_amount) FROM employee_salaries WHERE employee_id = ? AND is_paid = 0");
    $stmt->execute([$employee_id]);
    $total_paid = $stmt->fetchColumn();

    if ($total_paid > 0) {
        // Insert into employee_payments
        $stmt = $pdo->prepare("INSERT INTO employee_payments (employee_id, total_paid, payment_method, payment_proof) VALUES (?, ?, ?, ?)");
        $stmt->execute([$employee_id, $total_paid, $payment_method, $payment_proof]);

        // Mark employee_salaries as paid
        $stmt = $pdo->prepare("UPDATE employee_salaries SET is_paid = 1 WHERE employee_id = ? AND is_paid = 0");
        $stmt->execute([$employee_id]);
    }
}

// Delete employee
if (isset($_POST['delete_employee'])) {
    $empId = $_POST['employee_id'];

    $stmt = $pdo->prepare("SELECT photo FROM employees WHERE id = ?");
    $stmt->execute([$empId]);
    $photo = $stmt->fetchColumn();
    if ($photo && file_exists("uploads/" . $photo)) {
        unlink("uploads/" . $photo);
    }

    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$empId]);
}

// Get employee data
$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
$salarySums = $pdo->query("SELECT employee_id, SUM(salary_amount) AS total_salary FROM employee_salaries WHERE is_paid = 0 GROUP BY employee_id")->fetchAll(PDO::FETCH_KEY_PAIR);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/emp.css">
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>MODERN CONCEPTS</h1>
        </div>
        <ul class="nav-links">
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span class="link-text">Dashboard</span></a></li>
            <li><a href="order_management.php"><i class="fas fa-gear"></i><span class="link-text">Order Management</span></a></li>
            <li><a href="employees.php" class="active"><i class="fas fa-users"></i><span class="link-text">Employees</span></a></li>
            <li><a href="inventory.php"><i class="fas fa-boxes"></i><span class="link-text">Inventory</span></a></li>
            <li><a href="s&i.php"><i class="fas fa-chart-line"></i><span class="link-text">Sales & Income</span></a></li>
            <li><a href="reports.php"><i class="fas fa-file-alt"></i><span class="link-text">Reports</span></a></li>
            <li><a href="clients.php"><i class="fas fa-user-tie"></i><span class="link-text">Clients</span></a></li>
            <li><a href="authentication.php"><i class="fas fa-lock"></i><span class="link-text">Authentication</span></a></li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container-fluid">
            <h2 class="main-title">Employee Management</h2>

            <div class="row row-equal">
                <!-- Add Employee Form Column -->
                <div class="col-form">
                    <div class="form-container">
                        <h3 class="section-title">Add/Edit Employee</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="add_employee" value="1">
                            <div class="mb-3">
                                <label class="form-label">Upload photo</label>
                                <input type="file" name="photo" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" required class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact</label>
                                <input type="text" name="contact" class="form-control">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Salary Rate (per shirt)</label>
                                <input type="number" step="0.01" name="salary_rate" class="form-control" required>
                            </div>

                            <div class="button-group">
                                <button type="submit" class="btn btn-add">Add</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Employee List Column -->
                <div class="col-table">
                    <div class="employee-container">
                        <h3 class="section-title">Employees List</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th scope="col">Photo</th>
                                        <th scope="col">Name</th>
                                        <th scope="col">Contact</th>
                                        <th scope="col">Salary Rate</th>
                                        <th scope="col">Total Salary</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td>
                                                <?php if ($emp['photo']): ?>
                                                    <img src="uploads/<?= htmlspecialchars($emp['photo']) ?>" class="employee-photo">
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($emp['name']) ?></td>
                                            <td><?= htmlspecialchars($emp['contact'] ?? '') ?></td>
                                            <td>₱<?= number_format($emp['salary_rate'], 2) ?></td>

                                            <td>
                                                ₱<?= isset($salarySums[$emp['id']]) ? number_format($salarySums[$emp['id']], 2) : '0.00' ?>

                                                <!-- Always show payment form regardless of payment status -->
                                                <form method="POST" enctype="multipart/form-data" class="mt-1">
                                                    <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                                    <select name="payment_method" class="form-select form-select-sm mb-1" required>
                                                        <option value="">Method</option>
                                                        <option value="Cash">Cash</option>
                                                        <option value="GCash">GCash</option>
                                                        <option value="Maya">Maya</option>
                                                        <option value="Bank Transfer">Bank Transfer</option>
                                                    </select>
                                                    <input type="file" name="payment_proof" class="form-control form-control-sm mb-1" required>
                                                    <button type="submit" name="mark_paid" class="btn btn-sm btn-success">Mark as Paid</button>
                                            </td>

                                            <td>
                                                <span class="badge bg-<?= $emp['status'] === 'Active' ? 'success' : 'secondary' ?>">
                                                    <?= htmlspecialchars($emp['status']) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this employee?');">
                                                    <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                                                    <button type="submit" name="delete_employee" class="btn btn-danger btn-sm">Remove</button>
                                                </form>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- Transaction History -->
                <div class="transaction-history mt-5">
                    <h3 class="section-title">Salary Payment History</h3>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Employee</th>
                                    <th>Amount Paid</th>
                                    <th>Payment Method</th>
                                    <th>Proof</th>
                                    <th>Paid Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $transactions = $pdo->query("
                    SELECT ep.*, e.name 
                    FROM employee_payments ep
                    JOIN employees e ON ep.employee_id = e.id
                    ORDER BY ep.paid_date DESC
                ")->fetchAll(PDO::FETCH_ASSOC);

                                if ($transactions):
                                    foreach ($transactions as $txn):
                                ?>
                                        <tr>
                                            <td><?= htmlspecialchars($txn['name']) ?></td>
                                            <td>₱<?= number_format($txn['total_paid'], 2) ?></td>
                                            <td><?= htmlspecialchars($txn['payment_method']) ?></td>
                                            <td>
                                                <?php if ($txn['payment_proof']): ?>
                                                    <a href="uploads/payments/<?= htmlspecialchars($txn['payment_proof']) ?>" target="_blank">View</a>
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($txn['paid_date']) ?></td>
                                        </tr>
                                    <?php endforeach;
                                else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No transaction history found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>