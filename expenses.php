<?php
require_once 'config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ADD Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
    $item = $_POST['item_name'];
    $cost = floatval($_POST['cost']);
    $category = $_POST['category'];

    $stmt = $conn->prepare("INSERT INTO expenses (item_name, cost, category) VALUES (?, ?, ?)");
    $stmt->execute([$item, $cost, $category]);

    header("Location: expenses.php");
    exit();
}

// UPDATE Expense
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_expense'])) {
    $id = $_POST['expense_id'];
    $item = $_POST['item_name'];
    $cost = floatval($_POST['cost']);
    $category = $_POST['category'];

    $stmt = $conn->prepare("UPDATE expenses SET item_name = ?, cost = ?, category = ? WHERE id = ?");
    $stmt->execute([$item, $cost, $category, $id]);

    header("Location: expenses.php"); // clear ?edit=
    exit();
}

// DELETE Expense
if (isset($_POST['delete_expense'])) {
    $id = $_POST['expense_id'];
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: expenses.php");
    exit();
}

// FETCH
$expenses = $conn->query("SELECT * FROM expenses ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$total_expense = $conn->query("SELECT SUM(cost) FROM expenses")->fetchColumn();
$editing_expense = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editing_expense = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/expenses.css">
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

    <div class="main-content">
        <h2 class="section-title"><?= $editing_expense ? "Edit Expense" : "Add New Expense" ?></h2>

        <!-- FORM START -->
        <form method="POST" class="mb-4" action="expenses.php">
            <?php if ($editing_expense): ?>
                <input type="hidden" name="update_expense" value="1">
                <input type="hidden" name="expense_id" value="<?= $editing_expense['id'] ?>">
            <?php else: ?>
                <input type="hidden" name="add_expense" value="1">
            <?php endif; ?>
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="item_name" class="form-control" placeholder="Item Name" required value="<?= $editing_expense['item_name'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <input type="number" name="cost" class="form-control" step="0.01" placeholder="Cost (₱)" required value="<?= $editing_expense['cost'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <input type="text" name="category" class="form-control" placeholder="Category (optional)" value="<?= $editing_expense['category'] ?? '' ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><?= $editing_expense ? "Update" : "Add" ?></button>
                </div>
            </div>
        </form>
        <!-- FORM END -->

        <!-- Table Display -->
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Item</th>
                        <th>Cost (₱)</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th style="width: 120px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td><?= htmlspecialchars($exp['item_name']) ?></td>
                            <td>₱<?= number_format($exp['cost'], 2) ?></td>
                            <td><?= htmlspecialchars($exp['category']) ?></td>
                            <td><?= $exp['created_at'] ?></td>
                            <td>
                                <div class="d-flex justify-content-between">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-warning editBtn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $exp['id'] ?>"
                                        data-name="<?= htmlspecialchars($exp['item_name']) ?>"
                                        data-cost="<?= $exp['cost'] ?>"
                                        data-category="<?= htmlspecialchars($exp['category']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Are you sure to delete this expense?');">
                                        <input type="hidden" name="expense_id" value="<?= $exp['id'] ?>">
                                        <button type="submit" name="delete_expense" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="export_expenses_excel.php" class="btn btn-success">Export to Excel</a>
        <a href="export_expenses_pdf.php" class="btn btn-danger">Export to PDF</a>



        <!-- Total -->
        <div class="alert alert-info mt-4">
            <strong>Total Expenses:</strong> ₱<?= number_format($total_expense, 2) ?>
        </div>

        <!-- ✅ MODAL FOR EDIT -->
        <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <form method="POST" class="modal-content">
                    <input type="hidden" name="update_expense" value="1">
                    <input type="hidden" name="expense_id" id="edit-expense-id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editModalLabel">Edit Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-item-name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="item_name" id="edit-item-name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-cost" class="form-label">Cost (₱)</label>
                            <input type="number" class="form-control" name="cost" id="edit-cost" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-category" class="form-label">Category</label>
                            <input type="text" class="form-control" name="category" id="edit-category">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Update Expense</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Bootstrap JS (required for modal) + Script to populate modal -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            document.querySelectorAll('.editBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.getElementById('edit-expense-id').value = btn.dataset.id;
                    document.getElementById('edit-item-name').value = btn.dataset.name;
                    document.getElementById('edit-cost').value = btn.dataset.cost;
                    document.getElementById('edit-category').value = btn.dataset.category;
                });
            });
        </script>