<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';
$event_id = $_GET['event_id'] ?? '';
$attendance_records = [];

try {
    // Get all events for the filter dropdown
    $events = $conn->query("SELECT id, title, event_date FROM umuganda_events ORDER BY event_date DESC")->fetchAll(PDO::FETCH_ASSOC);

    // Base query for attendance records
    $query = "
        SELECT a.*, c.name as citizen_name, c.phone, e.title as event_title, e.event_date
        FROM attendance a
        JOIN citizens c ON a.citizen_id = c.id
        JOIN umuganda_events e ON a.event_id = e.id
        WHERE 1=1
    ";
    $params = [];

    // Add event filter if selected
    if (!empty($event_id)) {
        $query .= " AND a.event_id = ?";
        $params[] = $event_id;
    }

    // Add sorting
    $query .= " ORDER BY e.event_date DESC, c.name ASC";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Umuganda Connect Admin</title>
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
                            <a class="nav-link text-white" href="citizens.php">
                                <i class="bi bi-people"></i> Citizens
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="attendance.php">
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
                    <h1 class="h2">Attendance Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="mark_attendance.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Mark Attendance
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Filter Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-8">
                                <select class="form-select" name="event_id">
                                    <option value="">All Events</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?php echo $event['id']; ?>" 
                                                <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['title'] . ' (' . date('M d, Y', strtotime($event['event_date'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Filter
                                </button>
                                <?php if (!empty($event_id)): ?>
                                    <a href="attendance.php" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Citizen</th>
                                        <th>Phone Number</th>
                                        <th>Status</th>
                                        <th>Marked By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($attendance_records)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No attendance records found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['event_title']); ?></td>
                                                <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['event_date']))); ?></td>
                                                <td><?php echo htmlspecialchars($record['citizen_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['phone']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $record['attended'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $record['attended'] ? 'Present' : 'Absent'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['marked_by'] ?? 'USSD'); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="edit_attendance.php?id=<?php echo $record['id']; ?>" 
                                                           class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="confirmDelete(<?php echo $record['id']; ?>, '<?php echo htmlspecialchars($record['citizen_name']); ?>')"
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
                    Are you sure you want to delete this attendance record?
                </div>
                <div class="modal-footer">
                    <form method="POST" action="delete_attendance.php">
                        <input type="hidden" name="attendance_id" id="deleteAttendanceId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(attendanceId, citizenName) {
            document.getElementById('deleteAttendanceId').value = attendanceId;
            document.querySelector('#deleteModal .modal-body').textContent = 
                `Are you sure you want to delete the attendance record for "${citizenName}"?`;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html> 