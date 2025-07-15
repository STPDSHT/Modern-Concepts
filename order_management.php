<?php
// order_management.php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
}

if (!file_exists('img')) {
    mkdir('img', 0777, true);
}

$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fetch employees
$employees = $pdo->query("SELECT id, name FROM employees")->fetchAll(PDO::FETCH_ASSOC);

// Handle new order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['client_name'])) {
    $imagePath = '';

    if (isset($_FILES['design_image']) && $_FILES['design_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'img/';
        $filename = uniqid() . '_' . basename($_FILES['design_image']['name']);
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['design_image']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        }
    }

    // Get and calculate values
    $quantity = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0.0;
    $total_price = $quantity * $price;
    $size = isset($_POST['size']) ? $_POST['size'] : '';

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (client_name, order_desc, assigned_employee, status, deadline, image_path, quantity, price, total_price, size, created_at)
                           VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->execute([
        $_POST['client_name'],
        $_POST['order_desc'],
        $_POST['assigned_employee'],
        $_POST['deadline'],
        $imagePath,
        $quantity,
        $price,
        $total_price,
        $size
    ]);

    header('Location: order_management.php');
    exit;
}

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([
        $_POST['new_status'],
        $_POST['order_id']
    ]);

    header('Location: order_management.php');
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

// Fetch all orders
$orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

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

$latestOrder = count($orders) > 0 ? [
    'id' => 'A' . str_pad($orders[0]['id'], 4, '0', STR_PAD_LEFT),
    'client' => $orders[0]['client_name'],
    'total_today' => $dailyOrderCount,
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
                <a href="order_management.php" class="active">
                    <i class="fas fa-gear"></i>
                    <span class="link-text">Order Management</span>
                </a>
            </li>
            <li>
                <a href="employees.php">
                    <i class="fas fa-users"></i>
                    <span class="link-text">Employees</span>
                </a>
            </li>
            <li>
                <a href="inventory.php">
                    <i class="fas fa-boxes"></i>
                    <span class="link-text">Inventory</span>
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
            <li>
                <a href="clients.php">
                    <i class="fas fa-user-tie"></i>
                    <span class="link-text">Clients</span>
                </a>
            </li>
            <li>
                <a href="authentication.php">
                    <i class="fas fa-lock"></i>
                    <span class="link-text">Authentication</span>
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
                        <input type="text" id="clientName" name="client_name" class="form-control" placeholder="Client Name" required>
                    </div>
                    <div class="form-group">
                        <label for="orderDesc">Order Description</label>
                        <textarea id="orderDesc" name="order_desc" class="form-control" placeholder="Order description..." required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="deadline">Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="assignedEmployee">Assign Employee</label>
                        <select id="assignedEmployee" name="assigned_employee" class="form-control" required>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo htmlspecialchars($employee['name']); ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" placeholder="Enter quantity" required min="1">
                    </div>
                    <div class="form-group">
                        <label for="size">Size</label>
                        <select id="size" name="size" class="form-control" required>
                            <option value="">Select size</option>
                            <option value="S">S</option>
                            <option value="M">M</option>
                            <option value="L">L</option>
                            <option value="XL">XL</option>
                            <option value="XXL">XXL</option>
                            <option value="XXXL">XXXL</option>
                        </select>
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

                    <button type="submit" class="btn btn-block">Add Order</button>
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
                        <div class="client-name"><?php echo $latestOrder['client']; ?></div>
                    <?php else: ?>
                        <div class="no-orders">
                            <i class="fas fa-inbox"></i>
                            <p>No orders yet</p>
                        </div>
                    <?php endif; ?>
                    <div class="order-stats">
                        Total Orders Today: <?php echo $latestOrder['total_today']; ?>
                    </div>
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
            <div class="table-container">
                <div class="table-header">Order ID</div>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Order ID</th>
                            <th>Client Name</th>
                            <th>Assigned Employee</th>
                            <th>Status</th>
                            <th>Deadline</th>
                            <th>Total Price</th> <!-- NEW COLUMN -->
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($orders) > 0): ?>
                            <?php foreach ($orders as $order): ?>
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
                                    <td><?php echo $order['client_name']; ?></td>
                                    <td><?php echo $order['assigned_employee']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php echo $order['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $order['deadline']; ?></td>
                                    <td>₱<?php echo number_format($order['total_price'], 2); ?></td> <!-- NEW VALUE -->
                                    <td>
                                        <div class="status-actions">
                                            <form method="POST" class="status-form" onsubmit="return false;">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $order['status'] === 'Done' ? 'Pending' : 'Done'; ?>">
                                                <input type="hidden" name="update_status" value="">
                                                <label>
                                                    <input type="checkbox"
                                                        onchange="toggleStatus(this)"
                                                        <?php echo $order['status'] === 'Done' ? 'checked' : ''; ?>>
                                                    Done
                                                </label>
                                            </form>
                                            <form method="POST" class="delete-form">
                                                <input type="hidden" name="delete_order" value="1">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" class="action-btn delete-btn" title="Delete Order">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>

                                            <div class="status-selector">
                                                <select class="status-select" data-order-id="<?php echo $order['id']; ?>">
                                                    <option value="Pending" <?php echo $order['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="no-orders-row">
                                    <div class="no-orders-message">
                                        <i class="fas fa-inbox"></i>
                                        <p>No orders found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <script>
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