<?php
include 'config.php';

// Handle Add
if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO tshirt_inventory (name, size, quantity) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['size'], $_POST['quantity']]);
    header("Location: inventory.php");
    exit;
}

// Handle Update
if (isset($_POST['update'])) {
    $stmt = $conn->prepare("UPDATE tshirt_inventory SET name=?, size=?, quantity=? WHERE id=?");
    $stmt->execute([$_POST['name'], $_POST['size'], $_POST['quantity'], $_POST['id']]);
    header("Location: inventory.php");
    exit;
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM tshirt_inventory WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: inventory.php");
    exit;
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
            <li><a href="dashboard.php"><i class="fas fa-home"></i><span>Dashboard</span></a></li>
            <li><a href="order_management.php"><i class="fas fa-gear"></i><span>Order Management</span></a></li>
            <li><a href="employees.php"><i class="fas fa-users"></i><span>Employees</span></a></li>
            <li><a href="inventory.php" class="active"><i class="fas fa-boxes"></i><span>Inventory</span></a></li>
            <li><a href="s&i.php"><i class="fas fa-chart-line"></i><span>Sales & Income</span></a></li>
            <li><a href="reports.php"><i class="fas fa-file-alt"></i><span>Reports</span></a></li>
            <li><a href="clients.php"><i class="fas fa-user-tie"></i><span>Clients</span></a></li>
            <li><a href="authentication.php"><i class="fas fa-lock"></i><span>Authentication</span></a></li>
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
            <?php
            $sizeSummary = [];
            $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
            foreach ($sizes as $size) {
                $stmtSize = $conn->prepare("SELECT SUM(quantity) AS total FROM tshirt_inventory WHERE size = ?");
                $stmtSize->execute([$size]);
                $result = $stmtSize->fetch();
                $sizeSummary[$size] = $result['total'] ?? 0;
            }

            function getColorClass($qty) {
                if ($qty > 50) return 'bg-success text-white';
                if ($qty > 20) return 'bg-warning text-dark';
                return 'bg-danger text-white';
            }
            ?>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title text-primary">Inventory Summary by Size</h5>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($sizeSummary as $size => $total): ?>
                            <div class="p-2 rounded shadow-sm text-center <?= getColorClass($total) ?>" style="width: 80px;">
                                <strong><?= $size ?></strong><br>
                                <?= $total ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Main Card -->
            <div class="card inventory-card">
                <div class="card-body">
                    <!-- Tracking Section -->
                    <div class="tracking-section mb-5">
                        <h4 class="section-title">Tracking</h4>
                        <div class="tracking-info mb-3">
                            <p><strong>Size</strong><br>
                            Color<br>
                            In Stock</p>
                        </div>
                        
                        <table class="table tracking-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Size</th>
                                    <th>Color</th>
                                    <th>In Stock</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $thresholds = ['XS' => 5, 'S' => 5, 'M' => 10, 'L' => 10, 'XL' => 8, 'XXL' => 6];
                                $stmt = $conn->query("SELECT * FROM tshirt_inventory");
                                while ($row = $stmt->fetch()) {
                                    $isLowStock = $row['quantity'] <= ($thresholds[$row['size']] ?? 0);
                                    $rowClass = $isLowStock ? 'low-stock' : '';
                                    echo "<tr class='$rowClass'>";
                                    echo "<td>{$row['size']}</td>";
                                    echo "<td>{$row['name']}</td>";
                                    $badge = $isLowStock ? "<span class='badge bg-danger ms-2'>Low Stock</span>" : "";
                                    echo "<td>{$row['quantity']} $badge</td>";
                                    echo "<td>
                                        <a href='?edit={$row['id']}' class='btn btn-warning btn-sm'>Edit</a>
                                        <a href='?delete={$row['id']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                                      </td>";
                                    echo "</tr>";

                                    if (isset($_GET['edit']) && $_GET['edit'] == $row['id']) {
                                        echo "<tr><td colspan='5'>
                                            <form method='POST'>
                                                <input type='hidden' name='id' value='{$row['id']}'>
                                                <div class='row g-2'>
                                                    <div class='col-md-4'>
                                                        <input type='text' name='name' class='form-control' value='{$row['name']}' required>
                                                    </div>
                                                    <div class='col-md-2'>
                                                        <select name='size' class='form-select' required>
                                                            <option " . ($row['size'] == 'XS' ? 'selected' : '') . ">XS</option>
                                                            <option " . ($row['size'] == 'S' ? 'selected' : '') . ">S</option>
                                                            <option " . ($row['size'] == 'M' ? 'selected' : '') . ">M</option>
                                                            <option " . ($row['size'] == 'L' ? 'selected' : '') . ">L</option>
                                                            <option " . ($row['size'] == 'XL' ? 'selected' : '') . ">XL</option>
                                                            <option " . ($row['size'] == 'XXL' ? 'selected' : '') . ">XXL</option>
                                                        </select>
                                                    </div>
                                                    <div class='col-md-2'>
                                                        <input type='number' name='quantity' class='form-control' value='{$row['quantity']}' required>
                                                    </div>
                                                    <div class='col-md-2'>
                                                        <button type='submit' name='update' class='btn btn-primary'>Update</button>
                                                    </div>
                                                </div>
                                            </form>
                                            </td></tr>";
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Section -->
                    <div class="row">
                        <div class="col-md-6">
                            <h4 class="section-title">Track Levels</h4>
                            <div class="tracking-info mb-3">
                                <p><strong>Size</strong><br>
                                Color<br>
                                In Stock</p>
                            </div>
                            
                            <table class="table levels-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Size</th>
                                        <th>Color</th>
                                        <th>In Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $conn->query("SELECT * FROM tshirt_inventory");
                                    while ($row = $stmt->fetch()) {
                                        echo "<tr>";
                                        echo "<td>{$row['size']}</td>";
                                        echo "<td>{$row['name']}</td>";
                                        echo "<td>{$row['quantity']}</td>";
                                        echo "</tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4 class="section-title">Logo</h4>
                            <ul class="logo-info">
                                <?php
                                $lowStockItems = [];
                                $stmt = $conn->query("SELECT * FROM tshirt_inventory WHERE quantity <= 10");
                                while ($row = $stmt->fetch()) {
                                    $lowStockItems[] = "- {$row['name']} ({$row['size']})";
                                }
                                
                                if (!empty($lowStockItems)) {
                                    echo "<li><strong>Low stock</strong></li>";
                                    foreach ($lowStockItems as $item) {
                                        echo "<li>$item</li>";
                                    }
                                } else {
                                    echo "<li>No low stock items</li>";
                                }
                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>