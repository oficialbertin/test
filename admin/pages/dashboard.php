<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Get statistics
try {
    // Total citizens
    $stmt = $conn->query("SELECT COUNT(*) as total FROM citizens");
    $total_citizens = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total events
    $stmt = $conn->query("SELECT COUNT(*) as total FROM umuganda_events");
    $total_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Total attendance
    $stmt = $conn->query("SELECT COUNT(*) as total FROM attendance");
    $total_attendance = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Recent events
    $stmt = $conn->query("
        SELECT e.*, a.name as admin_name 
        FROM umuganda_events e 
        LEFT JOIN admins a ON e.created_by = a.id 
        ORDER BY e.event_date DESC 
        LIMIT 5
    ");
    $recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Umuganda Connect Admin</title>
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
                            <a class="nav-link active text-white" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="events.php">
                                <i class="bi bi-calendar-event"></i> Events
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="citizens.php">
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <span class="btn btn-sm btn-outline-secondary">
                                Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Citizens</h5>
                                <h2 class="card-text"><?php echo number_format($total_citizens); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Events</h5>
                                <h2 class="card-text"><?php echo number_format($total_events); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Attendance</h5>
                                <h2 class="card-text"><?php echo number_format($total_attendance); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Events</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Created By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_events as $event): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($event['event_date']))); ?></td>
                                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                                        <td><?php echo htmlspecialchars($event['location']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $event['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo htmlspecialchars($event['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($event['admin_name'] ?? 'N/A'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html> 