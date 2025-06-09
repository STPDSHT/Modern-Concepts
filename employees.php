<?php
$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = $_POST['name'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $photo = '';

    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/";
        $photo = basename($_FILES["photo"]["name"]);
        move_uploaded_file($_FILES["photo"]["tmp_name"], $targetDir . $photo);
    }

    $stmt = $pdo->prepare("INSERT INTO employees (name, role, photo, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $role, $photo, $status]);
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
    <style>
        .main-content { margin-left: 260px; padding: 30px; background-color: var(--main-bg); min-height: 100vh; }
        h2, h3 { color: var(--text-dark); }
        .form-control, .btn { box-shadow: var(--shadow); }
        table { background-color: var(--card-bg); box-shadow: var(--shadow); }
        th { background-color: var(--sidebar-hover); color: var(--text-dark); }
        td { vertical-align: middle; }
    </style>
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
        <div class="container">
            <h2 class="mb-4">Add New Employee</h2>
            <form method="POST" enctype="multipart/form-data" class="row g-3 mb-5">
                <input type="hidden" name="add_employee" value="1">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" required class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Role</label>
                    <input type="text" name="role" required class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Photo</label>
                    <input type="file" name="photo" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>

            <h3>Employees List</h3>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Photo</th>
                            <th scope="col">Name</th>
                            <th scope="col">Role</th>
                            <th scope="col">Status</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td>
                                <?php if ($emp['photo']): ?>
                                    <img src="uploads/<?= htmlspecialchars($emp['photo']) ?>" style="width:50px; height:50px; object-fit:cover; border-radius:50%;">
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($emp['name']) ?></td>
                            <td><?= htmlspecialchars($emp['role']) ?></td>
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
</body>
</html>
