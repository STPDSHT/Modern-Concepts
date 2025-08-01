<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config.php';
// Total Expenses
$total_expenses_stmt = $conn->query("SELECT SUM(cost) FROM expenses");
$total_expenses = $total_expenses_stmt->fetchColumn() ?? 0;

// Pending Orders
$pending_orders_stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$pending_orders = $pending_orders_stmt->fetchColumn();
// Calculate total sales from Delivered orders
$total_sales_stmt = $conn->query("SELECT SUM(total_price) AS total_sales FROM orders WHERE status = 'Delivered'");
$total_sales = $total_sales_stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;

// Calculate total expenses from expenses table
$total_expense_stmt = $conn->query("SELECT SUM(cost) AS total_expenses FROM expenses");
$total_expenses = $total_expense_stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'] ?? 0;

// Safe: set dashboard_data as array
$dashboard_data = [
    'total_sales' => $total_sales,
    'total_expenses' => $total_expenses,
    'net_profit' => $total_sales - $total_expenses,
];

$net_profit = $dashboard_data['total_sales'] - $total_expenses;



// Authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get admin details from session
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'Admin';

// Fetch total sales from delivered orders
$sales_stmt = $conn->query("SELECT SUM(total_price) as total_sales FROM orders WHERE status = 'Delivered'");
$total_sales = $sales_stmt->fetch(PDO::FETCH_ASSOC)['total_sales'] ?? 0;

// Fetch total expenses
$expenses_stmt = $conn->query("SELECT SUM(cost) as total_expenses FROM expenses");
$total_expenses = $expenses_stmt->fetch(PDO::FETCH_ASSOC)['total_expenses'] ?? 0;

// Predicted income = sales - expenses
$predicted_income = $total_sales - $total_expenses;

// Count active employees
$employees_stmt = $conn->query("SELECT COUNT(*) FROM employees WHERE status = 'Active'");
$active_employees = $employees_stmt->fetchColumn();

// Count active orders
$orders_stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'");
$active_orders = $orders_stmt->fetchColumn();

// Count out for delivery
$out_stmt = $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Out for Delivery'");
$out_for_delivery = $out_stmt->fetchColumn();

// Count unpaid tasks (Done but not marked as Paid)
$unpaid_stmt = $conn->query("
    SELECT COUNT(DISTINCT o.id) 
    FROM orders o
    LEFT JOIN employee_salaries es ON o.id = es.order_id
    WHERE o.status = 'Done' AND es.id IS NULL
");
$unpaid_tasks = $unpaid_stmt->fetchColumn();

// Monthly projection placeholder
$monthly_projection = '+12.4%';

$dashboard_data = [
    'total_sales' => $total_sales,
    'active_employees' => $active_employees,
    'active_orders' => $active_orders,
    'out_for_delivery' => $out_for_delivery,
    'unpaid_tasks' => $unpaid_tasks,
    'predicted_income' => $predicted_income,
    'monthly_projection' => $monthly_projection
];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Concepts Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/dashb.css">
</head>

<body>
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
        <div class="dashboard-header">
            <h2>Dashboard</h2>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="user-role"><?php echo htmlspecialchars($admin_role); ?></div>
                </div>
                <div class="user-avatar"><?php echo substr($admin_name, 0, 1); ?></div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>

        <div class="cards-grid">
            <!-- Total Sales Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Total Sales</div>
                    <div class="card-icon sales-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <div class="big-number"><?php echo number_format($dashboard_data['total_sales']); ?></div>
                <div class="info-row">
                    <span class="info-label">Active Employees</span>
                    <span class="info-value"><?php echo $dashboard_data['active_employees']; ?></span>
                </div>
            </div>

            <!-- Active Orders Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Active Orders</div>
                    <div class="card-icon orders-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="big-number"><?php echo $dashboard_data['active_orders']; ?></div>
                <div class="info-row">
                    <span class="info-label">Quick Status Indicator</span>
                </div>
                <div class="status-container">
                    <div class="status-item">
                        <div class="status-label">
                            <span class="status-indicator indicator-blue"></span>
                            <span>Out for delivery</span>
                        </div>
                        <span class="info-value"><?php echo $dashboard_data['out_for_delivery']; ?></span>
                    </div>
                    <div class="status-item">
                        <div class="status-label">
                            <span class="status-indicator indicator-red"></span>
                            <span>Unpaid task</span>
                        </div>
                        <span class="info-value"><?php echo $dashboard_data['unpaid_tasks']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Predicted Income Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Predicted Income</div>
                    <div class="card-icon income-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                </div>
                <div class="big-number"><?php echo number_format($dashboard_data['predicted_income']); ?></div>
                <div class="info-row">
                    <span class="info-label">Monthly Projection</span>
                    <span class="info-value"><?php echo $dashboard_data['monthly_projection']; ?></span>
                </div>
            </div>
        </div>
        <!-- Total Expenses Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Total Expenses</div>
                <div class="card-icon expenses-icon">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
            <div class="big-number">₱<?php echo number_format($total_expenses, 2); ?></div>
            <div class="info-row">
                <span class="info-label">Track your company’s spending</span>
            </div>
        </div>

        <!-- Pending Orders Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Pending Orders</div>
                <div class="card-icon pending-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div class="big-number"><?php echo $pending_orders; ?></div>
            <div class="info-row">
                <span class="info-label">From Order Management</span>
            </div>
        </div>

        <!-- Net Profit Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Net Profit</div>
                <div class="card-icon income-icon">
                    <i class="fas fa-coins"></i>
                </div>
            </div>
            <div class="big-number">₱<?= number_format($net_profit, 2); ?></div>
            <div class="info-row">
                <span class="info-label">After Expenses</span>
            </div>
        </div>



        <!-- Additional Dashboard Content -->
        <div class="dashboard-footer">
            <div class="footer-card">
                <h3>Recent Activity</h3>
                <ul class="activity-list">
                    <li>
                        <i class="fas fa-user-plus activity-icon user-add"></i>
                        <div class="activity-details">
                            <p>New employee registered</p>
                            <small>2 hours ago</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-shopping-cart activity-icon order"></i>
                        <div class="activity-details">
                            <p>Order #4567 completed</p>
                            <small>5 hours ago</small>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-file-invoice-dollar activity-icon payment"></i>
                        <div class="activity-details">
                            <p>Payment received from client</p>
                            <small>Yesterday</small>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        // Simple JavaScript for navigation and active states
        document.addEventListener('DOMContentLoaded', function() {
            // Set active navigation link
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-links a');

            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }

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
        });
    </script>
</body>

</html>