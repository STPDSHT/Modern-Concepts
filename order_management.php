<?php
// order_management.php
session_start();

// Simulate authentication
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
}

// Simulated employees data
$employees = [
    ['id' => 1, 'name' => 'Windyl Aguilar'],
    ['id' => 2, 'name' => 'John Smith'],
    ['id' => 3, 'name' => 'Maria Garcia'],
    ['id' => 4, 'name' => 'Robert Johnson'],
    ['id' => 5, 'name' => 'Emily Davis']
];

// Simulated orders data
$orders = [
    [
        'id' => '#001',
        'client' => 'Juan Dela Cruz',
        'employee' => 'Windyl Aguilar',
        'status' => 'Pending',
        'deadline' => 'June 10, 2025'
    ],
    [
        'id' => '#002',
        'client' => 'Maria Santos',
        'employee' => 'John Smith',
        'status' => 'Ongoing',
        'deadline' => 'June 8, 2025'
    ],
    [
        'id' => '#003',
        'client' => 'Robert Lim',
        'employee' => 'Maria Garcia',
        'status' => 'Delivered',
        'deadline' => 'June 6, 2025'
    ],
    [
        'id' => '#004',
        'client' => 'Anna Torres',
        'employee' => 'Robert Johnson',
        'status' => 'Pending',
        'deadline' => 'June 12, 2025'
    ]
];

// Latest order data
$latestOrder = [
    'id' => 'A0001',
    'client' => 'Juan Dela Cruz',
    'total_today' => 5
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real application, you would save this to a database
    $newOrder = [
        'id' => '#' . str_pad(count($orders) + 1, 3, '0', STR_PAD_LEFT),
        'client' => $_POST['client_name'],
        'employee' => $_POST['assigned_employee'],
        'status' => 'Pending',
        'deadline' => date('F j, Y', strtotime($_POST['deadline']))
    ];
    
    array_unshift($orders, $newOrder);
    $latestOrder = [
        'id' => $newOrder['id'],
        'client' => $newOrder['client'],
        'total_today' => $latestOrder['total_today'] + 1
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css">
    <style>
        :root {
            --sidebar-bg: #b3907a;
            --sidebar-accent: #b3907a;
            --sidebar-text: #e2e8f0;
            --sidebar-hover: #cabdad;
            --main-bg: #f1f5f9;
            --card-bg: #ffffff;
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-dark: #0f172a;
            --text-medium: #334155;
            --text-light: #64748b;
            --border-color: #e2e8f0;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        /* Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--main-bg);
            color: var(--text-medium);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            transition: var(--transition);
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-header {
            padding: 24px 20px;
            background: var(--sidebar-accent);
            border-bottom: 1px solid #b3907a;
        }

        .sidebar-header h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(to right, #000000, #ffffff);
            background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: 0.8px;
            padding-left: 10px;
        }

        .nav-links {
            padding: 20px 0;
        }

        .nav-links li {
            list-style: none;
            position: relative;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            text-decoration: none;
            color: var(--sidebar-text);
            font-size: 16px;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .nav-links li a:hover {
            background: var(--sidebar-hover);
            border-left: 3px solid var(--primary);
        }

        .nav-links li a.active {
            background: var(--sidebar-hover);
            border-left: 3px solid var(--primary);
        }

        .nav-links li a i {
            margin-right: 12px;
            width: 24px;
            text-align: center;
            font-size: 18px;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 30px;
            transition: var(--transition);
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary), #60a5fa);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        /* Order Management Styles */
        .order-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 25px;
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .latest-order {
            text-align: center;
            padding: 20px 0;
        }

        .order-id {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 10px;
        }

        .client-name {
            font-size: 20px;
            margin-bottom: 15px;
            color: var(--text-medium);
        }

        .order-stats {
            background: rgba(16, 185, 129, 0.1);
            padding: 15px;
            border-radius: 8px;
            color: var(--success);
            font-weight: 500;
        }

        /* Status Cards */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .status-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 20px;
            text-align: center;
        }

        .status-header {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .status-value {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .status-icon {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .pending .status-icon {
            color: var(--warning);
        }

        .ongoing .status-icon {
            color: var(--primary);
        }

        .delivered .status-icon {
            color: var(--success);
        }

        /* Order Table */
        .table-container {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 25px;
            overflow: hidden;
        }

        .table-header {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text-dark);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }

        .orders-table th,
        .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table th {
            font-weight: 600;
            color: var(--text-dark);
            background-color: rgba(241, 245, 249, 0.5);
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover td {
            background-color: rgba(241, 245, 249, 0.3);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-ongoing {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }

        .status-delivered {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                width: 80px;
            }
            .sidebar-header h1, .link-text {
                display: none;
            }
            .nav-links li a {
                justify-content: center;
                padding: 20px;
                border-left: none;
            }
            .nav-links li a i {
                margin-right: 0;
                font-size: 20px;
            }
            .main-content {
                margin-left: 80px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            .order-container {
                grid-template-columns: 1fr;
            }
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            .user-info {
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            .sidebar.active {
                width: 260px;
                z-index: 1000;
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-menu-btn {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                background: var(--primary);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 8px;
                font-size: 20px;
                cursor: pointer;
                z-index: 1001;
            }
            .status-cards {
                grid-template-columns: 1fr;
            }
        }

        /* Mobile menu button (hidden by default) */
        .mobile-menu-btn {
            display: none;
        }
    </style>
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
                <form method="POST">
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
                                <option value="<?php echo $employee['name']; ?>"><?php echo $employee['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="order-id"><?php echo $latestOrder['id']; ?></div>
                    <div class="client-name"><?php echo $latestOrder['client']; ?></div>
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
                <div class="status-value">2</div>
                <div class="status-deadline">June 10, 2025</div>
            </div>
            
            <div class="status-card ongoing">
                <div class="status-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <div class="status-header">Ongoing</div>
                <div class="status-value">1</div>
                <div class="status-deadline">June 8, 2025</div>
            </div>
            
            <div class="status-card delivered">
                <div class="status-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="status-header">Delivered</div>
                <div class="status-value">1</div>
                <div class="status-deadline">June 6, 2025</div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <div class="table-header">Order ID</div>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Client Name</th>
                        <th>Assigned Employee</th>
                        <th>Status</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?php echo $order['id']; ?></td>
                            <td><?php echo $order['client']; ?></td>
                            <td><?php echo $order['employee']; ?></td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $order['deadline']; ?></td>
                            <td>
                                <button class="action-btn">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="action-btn">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
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
        });
    </script>
</body>
</html>