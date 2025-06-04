<?php
require_once 'config.php';

// Authentication check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get admin details from session
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role = $_SESSION['admin_role'] ?? 'Admin';

// Dashboard data (simulated)
$dashboard_data = [
    'total_sales' => 80450,
    'active_employees' => 20,
    'active_orders' => 560,
    'out_for_delivery' => 32,
    'unpaid_tasks' => 8,
    'predicted_income' => 15200,
    'monthly_projection' => '+12.4%'
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