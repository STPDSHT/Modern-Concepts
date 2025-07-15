<?php
$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// Handle form submission
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

    $stmt = $pdo->prepare("INSERT INTO employees (name, contact, photo, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $contact, $photo, $status]);
}

// Handle deletion
if (isset($_POST['delete_employee'])) {
    $empId = $_POST['employee_id'];

    // Delete photo file if exists
    $stmt = $pdo->prepare("SELECT photo FROM employees WHERE id = ?");
    $stmt->execute([$empId]);
    $photo = $stmt->fetchColumn();
    if ($photo && file_exists("uploads/" . $photo)) {
        unlink("uploads/" . $photo);
    }

    // Delete from database
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$empId]);
}

// Fetch employees
$employees = $pdo->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
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
            </div>
        </div>
    </div>
</body>

</html>