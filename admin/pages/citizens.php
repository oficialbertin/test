<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';
$search = $_GET['search'] ?? '';
$citizens = [];

try {
    // Base query
    $query = "SELECT * FROM citizens WHERE 1=1";
    $params = [];

    // Add search condition if search term is provided
    if (!empty($search)) {
        $query .= " AND (phone LIKE ? OR name LIKE ? OR national_id LIKE ?)";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm, $searchTerm];
    }

    // Add sorting
    $query .= " ORDER BY name ASC";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizens - Umuganda Connect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="events.php">
                                <i class="bi bi-calendar-event"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="citizens.php">
                                <i class="bi bi-people"></i> Citizens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="attendance.php">
                                <i class="bi bi-clipboard-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="reports.php">
                                <i class="bi bi-file-text"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Citizens Management</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Search Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-8">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search by name, phone number, or ID number"
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="citizens.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Citizens Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>ID Number</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($citizens)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No citizens found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($citizens as $citizen): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($citizen['name']); ?></td>
                                                <td><?php echo htmlspecialchars($citizen['phone']); ?></td>
                                                <td><?php echo htmlspecialchars($citizen['national_id']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $citizen['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($citizen['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view_citizen.php?id=<?php echo $citizen['id']; ?>" 
                                                           class="btn btn-sm btn-info" title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="edit_citizen.php?id=<?php echo $citizen['id']; ?>" 
                                                           class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="confirmDelete(<?php echo $citizen['id']; ?>, '<?php echo htmlspecialchars($citizen['name']); ?>')"
                                                                title="Delete">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this citizen?
                </div>
                <div class="modal-footer">
                    <form method="POST" action="delete_citizen.php">
                        <input type="hidden" name="citizen_id" id="deleteCitizenId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(citizenId, citizenName) {
            document.getElementById('deleteCitizenId').value = citizenId;
            document.querySelector('#deleteModal .modal-body').textContent = 
                `Are you sure you want to delete the citizen "${citizenName}"?`;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html> 