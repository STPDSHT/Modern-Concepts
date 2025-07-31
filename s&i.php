<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>

<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales and Inventory page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/sales_income.css">
</head>

<body>
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
</body>

</html>