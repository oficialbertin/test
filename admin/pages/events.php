<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Handle event deletion
if (isset($_POST['delete_event']) && isset($_POST['event_id'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM umuganda_events WHERE id = ?");
        if ($stmt->execute([$_POST['event_id']])) {
            $success = "Event deleted successfully";
        } else {
            $error = "Error deleting event";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get events with attendance count
try {
    $status_filter = $_GET['status'] ?? '';
    $where_clause = '';
    $params = [];
    
    if ($status_filter) {
        $where_clause = "WHERE e.status = ?";
        $params[] = $status_filter;
    }
    
    $stmt = $conn->prepare("
        SELECT e.*, 
               COUNT(DISTINCT a.citizen_id) as total_attendance,
               COUNT(DISTINCT CASE WHEN a.attended = 1 THEN a.citizen_id END) as present_count,
               COUNT(DISTINCT CASE WHEN a.attended = 0 THEN a.citizen_id END) as absent_count
        FROM umuganda_events e
        LEFT JOIN attendance a ON e.id = a.event_id
        $where_clause
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'scheduled':
            return 'bg-primary';
        case 'ongoing':
            return 'bg-success';
        case 'completed':
            return 'bg-info';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Umuganda Connect Admin</title>
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
                            <a class="nav-link active text-white" href="events.php">
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
                    <h1 class="h2">Events</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add_event.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Add New Event
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Status Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-center">
                            <div class="col-auto">
                                <label for="status" class="form-label">Filter by Status:</label>
                                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                                    <option value="">All Events</option>
                                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                                    <option value="ongoing" <?php echo $status_filter === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Events Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Attendance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($event['status']); ?>">
                                                    <?php echo ucfirst($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="small">
                                                    Total: <?php echo $event['total_attendance']; ?><br>
                                                    Present: <?php echo $event['present_count']; ?><br>
                                                    Absent: <?php echo $event['absent_count']; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_event.php?id=<?php echo $event['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="view_attendance.php?event_id=<?php echo $event['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-clipboard-check"></i>
                                                    </a>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteEvent(<?php echo $event['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
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
    <script>
        function deleteEvent(eventId) {
            if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
                window.location.href = `delete_event.php?id=${eventId}`;
            }
        }
    </script>
</body>
</html> 