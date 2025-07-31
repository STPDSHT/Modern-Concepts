<?php
// =========================
// Session Check & Database
// =========================
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require 'config.php';

// =================
// Add New Inventory
// =================
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO tshirt_inventory (name, size, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['size'], $_POST['quantity']]);
    header("Location: inventory.php");
    exit;
}

// ================
// Update Inventory
// ================
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE tshirt_inventory SET name=?, size=?, quantity=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['size'], $_POST['quantity'], $_POST['id']]);
    header("Location: inventory.php");
    exit;
}

// ================
// Delete Inventory
// ================
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tshirt_inventory WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: inventory.php");
    exit;
}

// ==============================
// Inventory Summary by Size
// ==============================
$sizeSummary = [];
$sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];
foreach ($sizes as $size) {
    $stmtSize = $conn->prepare("SELECT SUM(quantity) AS total FROM tshirt_inventory WHERE size = ?");
    $stmtSize->execute([$size]);
    $result = $stmtSize->fetch();
    $sizeSummary[$size] = $result['total'] ?? 0;
}

function getColorClass($qty)
{
    if ($qty > 50) return 'bg-success text-white';
    if ($qty > 20) return 'bg-warning text-dark';
    return 'bg-danger text-white';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Page</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/inv.css">
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <h1>MODERN CONCEPTS</h1>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fas fa-home"></i><span class="link-text">Dashboard</span></a></li>
        <li><a href="clients.php"><i class="fas fa-user-tie"></i><span class="link-text">Clients</span></a></li>
        <li><a href="employees.php"><i class="fas fa-users"></i><span class="link-text">Employees</span></a></li>
        <li><a href="order_management.php"><i class="fas fa-gear"></i><span class="link-text">Order Management</span></a></li>
        <li><a href="inventory.php" class="active"><i class="fas fa-boxes"></i><span class="link-text">Inventory</span></a></li>
        <li><a href="expenses.php"><i class="fas fa-money-bill-wave"></i><span class="link-text">Expenses</span></a></li>
        <li><a href="s&i.php"><i class="fas fa-chart-line"></i><span class="link-text">Sales & Income</span></a></li>
        <li><a href="reports.php"><i class="fas fa-file-alt"></i><span class="link-text">Reports</span></a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="main-title">Inventory</h2>
            <div class="date-range">
                <span>Jan 1, 2025 - Jan 31, 2025</span>
                <a href="#" class="btn btn-outline-primary btn-sm ms-3">Export to PDF</a>
            </div>
        </div>

        <!-- Inventory Summary by Size -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title text-primary">Inventory Summary by Size</h5>
                <div class="d-flex flex-wrap gap-3">
                    <?php foreach ($sizeSummary as $size => $total): ?>
                        <div class="p-2 rounded shadow-sm text-center <?= getColorClass($total) ?>" style="width: 80px;">
                            <strong><?= $size ?></strong><br><?= $total ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="card inventory-card">
            <div class="card-body">
                <h4 class="section-title">Inventory List</h4>
                <form method="POST" class="mb-4">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" name="name" class="form-control" placeholder="T-shirt Name" required>
                        </div>
                        <div class="col-md-2">
                            <select name="size" class="form-select" required>
                                <?php foreach ($sizes as $size): ?>
                                    <option value="<?= $size ?>"><?= $size ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="quantity" class="form-control" placeholder="Quantity" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>

                <table class="table tracking-table">
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Name</th>
                            <th>Quantity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $conn->query("SELECT * FROM tshirt_inventory ORDER BY size");
                    while ($row = $stmt->fetch()):
                        $isLow = $row['quantity'] <= 10;
                        $badge = $isLow ? "<span class='badge bg-danger ms-2'>Low Stock</span>" : "";
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['size']) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) . " " . $badge ?></td>
                            <td>
                                <a href="?edit=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this item?')" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                        <?php if (isset($_GET['edit']) && $_GET['edit'] == $row['id']): ?>
                            <tr>
                                <td colspan="4">
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <input type="text" name="name" class="form-control" value="<?= $row['name'] ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <select name="size" class="form-select" required>
                                                    <?php foreach ($sizes as $size): ?>
                                                        <option value="<?= $size ?>" <?= $row['size'] === $size ? 'selected' : '' ?>><?= $size ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="number" name="quantity" class="form-control" value="<?= $row['quantity'] ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" name="update" class="btn btn-primary w-100">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</body>
</html>
