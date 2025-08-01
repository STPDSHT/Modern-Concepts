<?php
$pdo = new PDO("mysql:host=localhost;dbname=modern_concept", "root", "");

// Add client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_client"])) {
    $stmt = $pdo->prepare("INSERT INTO client (name, company_name, contact) VALUES (?, ?, ?)");
    $stmt->execute([$_POST["name"], $_POST["company_name"] ?? null, $_POST["contact"]]);
}

// Update client
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_client"])) {
    $stmt = $pdo->prepare("UPDATE client SET name = ?, company_name = ?, contact = ? WHERE id = ?");
    $stmt->execute([$_POST["name"], $_POST["company_name"] ?? null, $_POST["contact"], $_POST["client_id"]]);
}

// Delete client
if (isset($_POST["delete_client"])) {
    $stmt = $pdo->prepare("DELETE FROM client WHERE id = ?");
    $stmt->execute([$_POST["client_id"]]);
}

// Search, Sort, Pagination
$search = $_GET['search'] ?? '';
$sort = in_array($_GET['sort'] ?? '', ['name', 'company_name', 'contact']) ? $_GET['sort'] : 'created_at';
$order = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 5;
$offset = ($page - 1) * $limit;

$where = '';
$params = [];

if ($search) {
    $where = "WHERE name LIKE :search OR company_name LIKE :search OR contact LIKE :search";
    $params[':search'] = "%$search%";
}

$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM client $where");
$total_stmt->execute($params);
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

$stmt = $pdo->prepare("SELECT * FROM client $where ORDER BY $sort $order LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Export to Excel
if (isset($_GET['export']) && in_array($_GET['export'], ['excel', 'pdf'])) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=clients." . ($_GET['export'] === 'pdf' ? 'xls' : 'xls'));
    echo "Name\tCompany\tContact\n";
    foreach ($clients as $c) {
        echo "{$c['name']}\t{$c['company_name']}\t{$c['contact']}\n";
    }
    exit;
}

// Highlight function
function highlight($text, $term)
{
    return preg_replace("/(" . preg_quote($term, '/') . ")/i", "<mark>$1</mark>", htmlspecialchars($text));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Clients Page</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/client.css">
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

    <div class="main-content" style="margin-left: 260px; padding: 20px;">
        <div class="container-fluid">
            <h2 class="mb-4">Client Management</h2>

            <!-- Add Client Form -->
            <div class="card mb-4">
                <div class="card-header">Add Client</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="add_client" value="1">
                        <div class="mb-3">
                            <label class="form-label">Client Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Company Name (optional)</label>
                            <input type="text" name="company_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-add-client">Add Client</button>
                    </form>
                </div>
            </div>

            <!-- Client List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <form method="GET" class="d-flex align-items-center">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control form-control-sm me-2" placeholder="Search...">
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search"></i></button>
                    </form>
                    <div>
                        <a href="?export=excel" class="btn btn-sm btn-success">Export Excel</a>
                        <a href="export_pdf.php" class="btn btn-sm btn-danger">Export PDF</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($clients): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <?php
                                        $columns = ['name' => 'Name', 'company_name' => 'Company', 'contact' => 'Contact'];
                                        foreach ($columns as $col => $label): ?>
                                            <th>
                                                <a href="?<?= http_build_query(array_merge($_GET, ['sort' => $col, 'order' => ($sort === $col && $order === 'asc') ? 'desc' : 'asc'])) ?>" class="text-decoration-none text-dark">
                                                    <?= $label ?>
                                                    <?php if ($sort === $col): ?>
                                                        <i class="fas fa-sort-<?= $order === 'asc' ? 'up' : 'down' ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                        <?php endforeach; ?>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($clients as $client): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($client['name']) ?></td>
                                            <td><?= htmlspecialchars($client['company_name']) ?></td>
                                            <td><?= htmlspecialchars($client['contact']) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $client['id'] ?>">
                                                    Edit
                                                </button>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                                    <button name="delete_client" class="btn btn-sm btn-danger" onclick="return confirm('Delete this client?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?= $client['id'] ?>" tabindex="-1" aria-labelledby="editModalLabel<?= $client['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?= $client['id'] ?>">Edit Client</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Client Name</label>
                                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($client['name']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Company Name</label>
                                                                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($client['company_name']) ?>">
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Contact</label>
                                                                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($client['contact']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="submit" name="update_client" class="btn btn-primary">Save Changes</button>
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav>
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php else: ?>
                        <p class="text-muted">No clients found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>