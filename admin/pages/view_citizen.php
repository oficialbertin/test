<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';
$citizen = null;
$attendance_history = [];

// Get citizen ID from URL
$citizen_id = $_GET['id'] ?? null;

if (!$citizen_id) {
    header('Location: citizens.php');
    exit;
}

try {
    // Get citizen details
    $stmt = $conn->prepare("SELECT * FROM citizens WHERE id = ?");
    $stmt->execute([$citizen_id]);
    $citizen = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$citizen) {
        header('Location: citizens.php');
        exit;
    }

    // Get attendance history
    $stmt = $conn->prepare("
        SELECT a.*, e.title as event_title, e.event_date, e.location
        FROM attendance a
        JOIN umuganda_events e ON a.event_id = e.id
        WHERE a.citizen_id = ?
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$citizen_id]);
    $attendance_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate attendance statistics
    $total_events = count($attendance_history);
    $present_count = count(array_filter($attendance_history, function($record) {
        return $record['status'] === 'present';
    }));
    $absent_count = $total_events - $present_count;
    $attendance_rate = $total_events > 0 ? ($present_count / $total_events) * 100 : 0;

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Citizen - Umuganda Connect Admin</title>
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
                    <h1 class="h2">Citizen Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="citizens.php" class="btn btn-secondary me-2">
                            <i class="bi bi-arrow-left"></i> Back to Citizens
                        </a>
                        <a href="edit_citizen.php?id=<?php echo $citizen['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Citizen
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Citizen Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Personal Information</h5>
                                <table class="table">
                                    <tr>
                                        <th>Name:</th>
                                        <td><?php echo htmlspecialchars($citizen['name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phone Number:</th>
                                        <td><?php echo htmlspecialchars($citizen['phone_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>ID Number:</th>
                                        <td><?php echo htmlspecialchars($citizen['id_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Gender:</th>
                                        <td><?php echo htmlspecialchars($citizen['gender']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Age:</th>
                                        <td><?php echo htmlspecialchars($citizen['age']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Attendance Statistics</h5>
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <h3 class="text-primary"><?php echo number_format($total_events); ?></h3>
                                        <p>Total Events</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h3 class="text-success"><?php echo number_format($present_count); ?></h3>
                                        <p>Present</p>
                                    </div>
                                    <div class="col-md-4">
                                        <h3 class="text-danger"><?php echo number_format($absent_count); ?></h3>
                                        <p>Absent</p>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $attendance_rate; ?>%"
                                         aria-valuenow="<?php echo $attendance_rate; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo number_format($attendance_rate, 1); ?>%
                                    </div>
                                </div>
                                <p class="text-center mt-2">Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Attendance History</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Marked By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_history)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No attendance records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_history as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['event_title']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($record['event_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['location']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $record['status'] === 'present' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['marked_by']); ?></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 