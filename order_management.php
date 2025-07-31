<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

include 'config.php'; // ✅ This line ensures $conn is available

if (!file_exists('img')) {
    mkdir('img', 0777, true);
}

$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch employees
$employees = $pdo->query("SELECT id, name FROM employees")->fetchAll(PDO::FETCH_ASSOC);
// Fetch clients
$clients = $pdo->query("SELECT id, name FROM client ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);


// Handle new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_id'])) {
    require_once 'config.php';

    $client_id = $_POST['client_id'];
    $deadline = $_POST['deadline'];
    $assigned_employee = $_POST['assigned_employee'];
    $order_desc = $_POST['order_desc'];
    $quantity = (int)$_POST['quantity'];
    $size = $_POST['size'];
    $price = (float)$_POST['price'];
    $total_price = $quantity * $price;
    $image_path = '';

    // Upload image
    if (isset($_FILES['design_image']) && $_FILES['design_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'img/';
        $filename = uniqid() . '_' . basename($_FILES['design_image']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['design_image']['tmp_name'], $targetPath)) {
            $image_path = $targetPath;
        }
    }

    // Check available stock
    $stockCheck = $conn->prepare("SELECT quantity FROM tshirt_inventory WHERE size = ?");
    $stockCheck->execute([$size]);
    $availableStock = $stockCheck->fetchColumn();

    if ($availableStock === false) {
        echo "<script>alert('Selected size does not exist in inventory.');</script>";
    } elseif ($quantity > $availableStock) {
        echo "<script>alert('Not enough stock for size $size. Available: $availableStock');</script>";
    } else {
        // Insert into orders
        $stmt = $conn->prepare("INSERT INTO orders (client_id, order_desc, assigned_employee, status, deadline, image_path, quantity, price, total_price, size, created_at)
                                VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, NOW())");

        $stmt->execute([
            $client_id,
            $order_desc,
            $assigned_employee,
            $deadline,
            $image_path,
            $quantity,
            $price,
            $total_price,
            $size
        ]);

        // Deduct from inventory
        $deductStock = $conn->prepare("UPDATE tshirt_inventory SET quantity = quantity - ? WHERE size = ?");
        $deductStock->execute([$quantity, $size]);

        header("Location: order_management.php");
        exit;
    }
}


// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['new_status'];

    // Get previous status before updating
    $stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $prevStatus = $stmt->fetchColumn();

    // Update to new status
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);

    // Undo salary only if changing from Done → Pending
    if ($prevStatus === 'Done' && $newStatus === 'Pending') {
        $stmt = $pdo->prepare("DELETE FROM employee_salaries WHERE order_id = ?");
        $stmt->execute([$orderId]);
    }


    // ✅ Insert salary if changing to Done
    if ($newStatus === 'Done') {
        $stmt = $pdo->prepare("SELECT assigned_employee, quantity FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $employeeId = $order['assigned_employee'];
            $quantity = $order['quantity'];

            $stmt = $pdo->prepare("SELECT salary_rate FROM employees WHERE id = ?");
            $stmt->execute([$employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                $rate = $employee['salary_rate'];
                $salary = $rate * $quantity;

                $stmt = $pdo->prepare("INSERT INTO employee_salaries (order_id, employee_id, salary_amount, quantity, rate)
                                       VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$orderId, $employeeId, $salary, $quantity, $rate]);
            }
        }
    }

    header('Location: order_management.php');
    exit;
}

// Delete and restock inventory
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_restock'])) {
    $orderId = $_POST['delete_order_restock'];

    // Get order details (size, quantity, image)
    $stmt = $conn->prepare("SELECT size, quantity, image_path FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order) {
        $size = $order['size'];
        $quantity = $order['quantity'];

        // Restore stock
        $updateStock = $conn->prepare("UPDATE tshirt_inventory SET quantity = quantity + ? WHERE size = ?");
        $updateStock->execute([$quantity, $size]);

        // Delete image if exists
        if (!empty($order['image_path']) && file_exists($order['image_path'])) {
            unlink($order['image_path']);
        }

        // Delete order
        $delete = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $delete->execute([$orderId]);
    }

    header("Location: order_management.php");
    exit;
}

// Delete order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $stmt = $pdo->prepare("SELECT image_path FROM orders WHERE id = ?");
    $stmt->execute([$_POST['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($order && !empty($order['image_path']) && file_exists($order['image_path'])) {
        unlink($order['image_path']);
    }

    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$_POST['order_id']]);

    header('Location: order_management.php');
    exit;
}

// Fetch all orders with employee and client names
$orders = $pdo->query("
    SELECT o.*, 
           e.name AS employee_name, 
           c.name AS client_name_display 
    FROM orders o 
    LEFT JOIN employees e ON o.assigned_employee = e.id 
    LEFT JOIN client c ON o.client_id = c.id 
    ORDER BY o.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$currentOrders = [];
$doneOrders = [];

foreach ($orders as $order) {
    if ($order['status'] === 'Done') {
        $doneOrders[] = $order;
    } else {
        $currentOrders[] = $order;
    }
}


// Status counts and stats
$pendingCount = 0;
$doneCount = 0;
$deliveredCount = 0;
$pendingDeadline = null;
$latestOrder = null;
$dailyOrderCount = 0;

foreach ($orders as $order) {
    switch ($order['status']) {
        case 'Pending':
            $pendingCount++;
            $deadlineTime = strtotime($order['deadline']);
            if ($pendingDeadline === null || $deadlineTime < $pendingDeadline) {
                $pendingDeadline = $deadlineTime;
            }
            break;
        case 'Done':
            $doneCount++;
            break;
        case 'Delivered':
            $deliveredCount++;
            break;
    }

    if (date('Y-m-d', strtotime($order['created_at'])) === date('Y-m-d')) {
        $dailyOrderCount++;
    }
}

// Fetch available t-shirt sizes (with stock > 0)
$availableSizes = [];
$stmt = $conn->query("SELECT DISTINCT size FROM tshirt_inventory WHERE quantity > 0 ORDER BY size");
while ($row = $stmt->fetch()) {
    $availableSizes[] = $row['size'];
}


$latestOrder = count($orders) > 0 ? [
    'id' => 'A' . str_pad($orders[0]['id'], 4, '0', STR_PAD_LEFT),
    'client' => $orders[0]['client_name_display'],
    'image' => $orders[0]['image_path']
] : null;

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/om.css">
</head>

<body>
    <!-- Mobile menu button -->
    <button class="mobile-menu-btn">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar Navigation -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h1>MODERN CONCEPTS</h1>
        </div>
        <ul class="nav-links">
            <li>
                <a href="dashboard.php">
                    <i class="fas fa-home"></i>
                    <span class="link-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="clients.php">
                    <i class="fas fa-user-tie"></i>
                    <span class="link-text">Clients</span>
                </a>
            </li>
            <li>
                <a href="employees.php">
                    <i class="fas fa-users"></i>
                    <span class="link-text">Employees</span>
                </a>
            </li>
            <li>
                <a href="order_management.php">
                    <i class="fas fa-gear"></i>
                    <span class="link-text">Order Management</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class="fas fa-boxes"></i>
                    <span class="link-text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="expenses.php">
                    <i class="fas fa-money-bill-wave"></i>
                    <span class="link-text">Expenses</span>
                </a>
            </li>
            <li>
                <a href="s&i.php">
                    <i class="fas fa-chart-line"></i>
                    <span class="link-text">Sales & Income</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class="fas fa-file-alt"></i>
                    <span class="link-text">Reports</span>
                </a>
            </li>
        </ul>

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h2>Order Management</h2>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name">Admin</div>
                    <div class="user-role">Administrator</div>
                </div>
                <div class="user-avatar">A</div>
            </div>
        </div>

        <div class="order-container">
            <!-- Order Form -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Upload Design</div>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="clientName">Client Name</label>
                        <select id="clientName" name="client_id" class="form-control" required>
                            <option value="">-- Select Client --</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?= $client['id'] ?>"><?= htmlspecialchars($client['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="assignedEmployee">Assign Employee</label>
                        <select id="assignedEmployee" name="assigned_employee" class="form-control" required>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= htmlspecialchars($employee['id']); ?>"><?= htmlspecialchars($employee['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="orderDesc">Order Description</label>
                        <textarea id="orderDesc" name="order_desc" class="form-control" placeholder="Enter order details..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="size">Size</label>
                        <select id="size" name="size" class="form-control" required>
                            <option value="">Select size</option>
                            <?php
                            $stmt = $conn->query("SELECT size, SUM(quantity) as stock FROM tshirt_inventory WHERE quantity > 0 GROUP BY size ORDER BY size");
                            $stockMap = [];
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $size = $row['size'];
                                $stock = $row['stock'];
                                $stockMap[$size] = $stock;
                                echo "<option value=\"$size\">$size (Stock: $stock)</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Enter quantity" required min="1">
                    </div>

                    <div class="form-group">
                        <label for="price">Price (per item)</label>
                        <input type="number" step="0.01" id="price" name="price" class="form-control" placeholder="Enter price per item" required min="0">
                    </div>

                    <div class="file-upload">
                        <label class="file-upload-label" for="designImage">
                            <i class="fas fa-cloud-upload-alt"></i> Upload Design Image
                        </label>
                        <input type="file" id="designImage" name="design_image" class="file-upload-input" accept="image/*">
                        <span id="fileName" class="file-name">No file selected</span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Add Order</button>
                </form>
            </div>

            <!-- Latest Order -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Latest Order</div>
                </div>
                <div class="latest-order">
                    <?php if ($latestOrder): ?>
                        <div class="design-preview">
                            <?php if (!empty($latestOrder['image'])): ?>
                                <img src="<?php echo $latestOrder['image']; ?>" alt="Latest Design">
                            <?php else: ?>
                                <div class="design-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>No design uploaded</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="order-id"><?php echo $latestOrder['id']; ?></div>
                        <div class="client-name-label">Client Name</div>
                        <div class="client-name-value"><?php echo htmlspecialchars($latestOrder['client']); ?></div>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-inbox"></i>
                            <p>No orders yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Status Cards -->
        <div class="status-cards">
            <div class="status-card pending">
                <div class="status-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="status-header">Pending</div>
                <div class="status-value"><?php echo $pendingCount; ?></div>
                <div class="status-deadline">
                    <?php if ($pendingDeadline): ?>
                        <?php echo date('F j, Y', $pendingDeadline); ?>
                    <?php else: ?>
                        No pending orders
                    <?php endif; ?>
                </div>
            </div>

            <div class="status-card ongoing">
                <div class="status-icon">
                    <i class="fas fa-check"></i>
                </div>
                <div class="status-header">Done</div>
                <div class="status-value"><?php echo $doneCount; ?></div>
            </div>

            <div class="status-card delivered">
                <div class="status-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-header">Delivered</div>
                <div class="status-value"><?php echo $deliveredCount; ?></div>
            </div>
        </div>

        <!-- Orders Table -->
        <!-- ✅ Current Orders Table (Pending only) -->
        <div class="table-container">
            <div class="table-header">Current Orders</div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Order Description</th>
                        <th>Assigned Employee</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Size</th>
                        <th>Total Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $hasPendingOrders = false;
                    if (count($currentOrders) > 0):
                        foreach ($currentOrders as $order):
                            if ($order['status'] !== 'Pending') continue;
                            $hasPendingOrders = true;
                    ?>
                            <tr>
                                <td class="order-image-cell">
                                    <?php if (!empty($order['image_path'])): ?>
                                        <img src="<?php echo $order['image_path']; ?>" alt="Design" class="order-image">
                                    <?php else: ?>
                                        <div class="image-placeholder">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['client_name_display'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($order['order_desc']); ?></td>
                                <td><?php echo htmlspecialchars($order['employee_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $order['deadline']; ?></td>
                                <td><?php echo htmlspecialchars($order['size']); ?></td>
                                <td>₱<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="status-form" onsubmit="return false;">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="Done">
                                        <input type="hidden" name="update_status" value="">
                                        <label>
                                            <input type="checkbox" onchange="toggleStatus(this)">
                                            Done
                                        </label>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this order and restore inventory?');" style="display:inline;">
                                        <input type="hidden" name="delete_order_restock" value="<?= $order['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete & Restock">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>

                                </td>
                            </tr>
                        <?php
                        endforeach;
                    endif;
                    if (!$hasPendingOrders):
                        ?>
                        <tr>
                            <td colspan="10" class="no-orders-row">
                                <div class="no-orders-message">
                                    <i class="fas fa-inbox"></i>
                                    <p>No current orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- ✅ Done Orders Table -->
        <div class="table-container" style="margin-top: 30px;">
            <div class="table-header">Completed Orders (Done)</div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Order Description</th>
                        <th>Assigned Employee</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Size</th>
                        <th>Total Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasDone = false;
                    foreach ($orders as $order):
                        if ($order['status'] === 'Done'):
                            $hasDone = true;
                    ?>
                            <tr>
                                <td class="order-image-cell">
                                    <?php if (!empty($order['image_path'])): ?>
                                        <img src="<?= $order['image_path']; ?>" alt="Design" class="order-image">
                                    <?php else: ?>
                                        <div class="image-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $order['id']; ?></td>
                                <td><?= htmlspecialchars($order['client_name_display']); ?></td>
                                <td><?= htmlspecialchars($order['order_desc']); ?></td>
                                <td><?= htmlspecialchars($order['employee_name']); ?></td>
                                <td><span class="status-badge status-done">Done</span></td>
                                <td><?= $order['deadline']; ?></td>
                                <td><?php echo htmlspecialchars($order['size']); ?></td>
                                <td>₱<?= number_format($order['total_price'], 2); ?></td>
                                <td class="status-actions">
                                    <!-- Undo (Back to Pending) -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="Pending">
                                        <input type="hidden" name="update_status" value="1">
                                        <button type="submit" class="btn btn-sm btn-warning" title="Undo to Pending">Undo</button>
                                    </form>

                                    <!-- Mark as Delivered -->
                                    <form method="POST" style="display:inline; margin-left: 5px;">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="Delivered">
                                        <input type="hidden" name="update_status" value="1">
                                        <button type="submit" class="btn btn-sm btn-success" title="Mark as Delivered">Delivered</button>
                                    </form>
                                </td>
                            </tr>
                    <?php endif;
                    endforeach; ?>

                    <?php if (!$hasDone): ?>
                        <tr>
                            <td colspan="10" class="no-orders-row">
                                <div class="no-orders-message">
                                    <i class="fas fa-check-circle"></i>
                                    <p>No done orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>


        <!-- ✅ Delivered Orders Table -->
        <div class="table-container" style="margin-top: 30px;">
            <div class="table-header">Delivered Orders</div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Order Description</th>
                        <th>Assigned Employee</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Size</th>
                        <th>Total Price</th>
                        <th>Actions</th> <!-- ✅ Actions column added -->
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $hasDelivered = false;
                    foreach ($orders as $order):
                        if ($order['status'] === 'Delivered'):
                            $hasDelivered = true;
                    ?>
                            <tr>
                                <td class="order-image-cell">
                                    <?php if (!empty($order['image_path'])): ?>
                                        <img src="<?= $order['image_path']; ?>" alt="Design" class="order-image">
                                    <?php else: ?>
                                        <div class="image-placeholder"><i class="fas fa-image"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $order['id']; ?></td>
                                <td><?= htmlspecialchars($order['client_name_display']); ?></td>
                                <td><?= htmlspecialchars($order['order_desc']); ?></td>
                                <td><?= htmlspecialchars($order['employee_name']); ?></td>
                                <td><span class="status-badge status-delivered">Delivered</span></td>
                                <td><?= $order['deadline']; ?></td>
                                <td><?php echo htmlspecialchars($order['size']); ?></td>
                                <td>₱<?= number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <form method="POST" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this delivered order?');">
                                        <input type="hidden" name="delete_order" value="1">
                                        <input type="hidden" name="order_id" value="<?= $order['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn" title="Delete Order">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                    <?php endif;
                    endforeach; ?>

                    <?php if (!$hasDelivered): ?>
                        <tr>
                            <td colspan="10" class="no-orders-row">
                                <div class="no-orders-message">
                                    <i class="fas fa-inbox"></i>
                                    <p>No delivered orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            const submitBtn = document.querySelector('button[type="submit"]');

            function validateQuantity() {
                const size = sizeSelect.value;
                const maxQty = sizeStockMap[size] || 0;
                const currentQty = parseInt(qtyInput.value) || 0;

                if (currentQty > maxQty || currentQty < 1) {
                    submitBtn.disabled = true;
                } else {
                    submitBtn.disabled = false;
                }
            }

            qtyInput.addEventListener('input', validateQuantity);
            sizeSelect.addEventListener('change', validateQuantity);
            const sizeStockMap = <?= json_encode($stockMap); ?>;

            const sizeSelect = document.getElementById('size');
            const qtyInput = document.getElementById('quantity');

            sizeSelect.addEventListener('change', function() {
                const selectedSize = this.value;
                const maxQty = sizeStockMap[selectedSize] || 0;
                qtyInput.max = maxQty;
                qtyInput.placeholder = "Max: " + maxQty;

                // Reset value if it’s higher than new max
                if (parseInt(qtyInput.value) > maxQty) {
                    qtyInput.value = maxQty;
                }
            });

            qtyInput.addEventListener('input', function() {
                const selectedSize = sizeSelect.value;
                const maxQty = sizeStockMap[selectedSize] || 0;

                if (parseInt(this.value) > maxQty) {
                    this.value = maxQty;
                }
            });

            document.getElementById('designImage').addEventListener('change', function() {
                const fileName = this.files[0]?.name || "No file selected";
                document.getElementById('fileName').textContent = fileName;
            });

            function toggleDoneTable() {
                const table = document.getElementById('doneOrdersTable');
                table.style.display = (table.style.display === 'none') ? 'table' : 'none';
            }
            // Delete confirmation
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to delete this order?')) {
                        e.preventDefault();
                    }
                });
            });
            // Simple JavaScript for navigation and active states
            document.addEventListener('DOMContentLoaded', function() {
                // Set active navigation link
                const navLinks = document.querySelectorAll('.nav-links a');

                navLinks.forEach(link => {
                    link.addEventListener('click', function(e) {
                        navLinks.forEach(item => item.classList.remove('active'));
                        this.classList.add('active');
                    });
                });

                // Toggle sidebar on mobile
                const sidebar = document.querySelector('.sidebar');
                document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });

                // Set minimum date for deadline (today)
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('deadline').min = today;

                // File upload display
                const fileInput = document.getElementById('designImage');
                const fileNameDisplay = document.getElementById('fileName');

                fileInput.addEventListener('change', function() {
                    if (this.files.length > 0) {
                        fileNameDisplay.textContent = this.files[0].name;
                    } else {
                        fileNameDisplay.textContent = 'No file selected';
                    }
                });

                // Status selector functionality
                document.querySelectorAll('.status-select').forEach(select => {
                    select.addEventListener('change', function() {
                        const orderId = this.dataset.orderId;
                        const newStatus = this.value;

                        // Create a form to submit the status change
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';

                        const orderIdInput = document.createElement('input');
                        orderIdInput.type = 'hidden';
                        orderIdInput.name = 'order_id';
                        orderIdInput.value = orderId;

                        const statusInput = document.createElement('input');
                        statusInput.type = 'hidden';
                        statusInput.name = 'new_status';
                        statusInput.value = newStatus;

                        const updateInput = document.createElement('input');
                        updateInput.type = 'hidden';
                        updateInput.name = 'update_status';
                        updateInput.value = '1';

                        form.appendChild(orderIdInput);
                        form.appendChild(statusInput);
                        form.appendChild(updateInput);

                        document.body.appendChild(form);
                        form.submit();
                    });
                });
            });

            function toggleStatus(checkbox) {
                const form = checkbox.closest('form');
                const hiddenInput = form.querySelector('input[name="new_status"]');
                const orderId = form.querySelector('input[name="order_id"]').value;
                const updateStatusInput = form.querySelector('input[name="update_status"]');

                hiddenInput.value = checkbox.checked ? 'Done' : 'Pending';
                updateStatusInput.value = '1'; // Important: set this so PHP handles update

                const postData = new FormData(form);

                fetch('', {
                    method: 'POST',
                    body: postData
                }).then(() => {
                    location.reload();
                });
            }
            // New function to switch between Pending and Delivered using the dropdown
            function toggleDeliveryStatus(select) {
                const orderId = select.dataset.orderId;
                const newStatus = select.value;

                // Create a hidden form for submission
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';

                const orderIdInput = document.createElement('input');
                orderIdInput.type = 'hidden';
                orderIdInput.name = 'order_id';
                orderIdInput.value = orderId;

                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;

                const updateStatusInput = document.createElement('input');
                updateStatusInput.type = 'hidden';
                updateStatusInput.name = 'update_status';
                updateStatusInput.value = '1';

                form.appendChild(orderIdInput);
                form.appendChild(statusInput);
                form.appendChild(updateStatusInput);

                document.body.appendChild(form);
                form.submit();
            }

            // Attach the event listeners for the dropdowns
            document.querySelectorAll('.status-select').forEach(select => {
                select.addEventListener('change', function() {
                    toggleDeliveryStatus(this);
                });
            });
        </script>
</body>

</html>