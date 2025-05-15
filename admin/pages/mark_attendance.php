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
$citizens = [];

try {
    // Get all active events for the dropdown
    $events = $conn->query("
        SELECT id, title, event_date 
        FROM umuganda_events 
        WHERE status = 'active' 
        ORDER BY event_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // If event is selected, get all citizens
    if (!empty($event_id)) {
        $stmt = $conn->prepare("
            SELECT c.*, 
                   a.status as attendance_status,
                   a.id as attendance_id
            FROM citizens c
            LEFT JOIN attendance a ON c.id = a.citizen_id AND a.event_id = ?
            ORDER BY c.name ASC
        ");
        $stmt->execute([$event_id]);
        $citizens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
        $event_id = $_POST['event_id'];
        $attendance_data = $_POST['attendance'] ?? [];
        $marked_by = $_SESSION['admin_name'] ?? 'Admin';

        // Start transaction
        $conn->beginTransaction();

        try {
            // Delete existing attendance records for this event
            $stmt = $conn->prepare("DELETE FROM attendance WHERE event_id = ?");
            $stmt->execute([$event_id]);

            // Insert new attendance records
            $stmt = $conn->prepare("
                INSERT INTO attendance (event_id, citizen_id, status, marked_by) 
                VALUES (?, ?, ?, ?)
            ");

            foreach ($attendance_data as $citizen_id => $status) {
                $stmt->execute([$event_id, $citizen_id, $status, $marked_by]);
            }

            $conn->commit();
            $success = 'Attendance marked successfully';
            
            // Refresh the page with the same event selected
            header("Location: mark_attendance.php?event_id=" . $event_id . "&success=1");
            exit;
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Umuganda Connect Admin</title>
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
                    <h1 class="h2">Mark Attendance</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="attendance.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Attendance
                        </a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($success || isset($_GET['success'])): ?>
                    <div class="alert alert-success">Attendance marked successfully</div>
                <?php endif; ?>

                <!-- Event Selection Form -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-8">
                                <label for="event_id" class="form-label">Select Event</label>
                                <select class="form-select" id="event_id" name="event_id" required>
                                    <option value="">Choose an event...</option>
                                    <?php foreach ($events as $event): ?>
                                        <option value="<?php echo $event['id']; ?>" 
                                                <?php echo $event_id == $event['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($event['title'] . ' (' . date('M d, Y', strtotime($event['event_date'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search"></i> Load Citizens
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (!empty($event_id) && !empty($citizens)): ?>
                    <!-- Attendance Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
                        
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
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($citizens as $citizen): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($citizen['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($citizen['phone_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($citizen['id_number']); ?></td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <input type="radio" class="btn-check" 
                                                                   name="attendance[<?php echo $citizen['id']; ?>]" 
                                                                   id="present_<?php echo $citizen['id']; ?>" 
                                                                   value="present"
                                                                   <?php echo ($citizen['attendance_status'] ?? '') === 'present' ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-success" 
                                                                   for="present_<?php echo $citizen['id']; ?>">
                                                                Present
                                                            </label>

                                                            <input type="radio" class="btn-check" 
                                                                   name="attendance[<?php echo $citizen['id']; ?>]" 
                                                                   id="absent_<?php echo $citizen['id']; ?>" 
                                                                   value="absent"
                                                                   <?php echo ($citizen['attendance_status'] ?? '') === 'absent' ? 'checked' : ''; ?>>
                                                            <label class="btn btn-outline-danger" 
                                                                   for="absent_<?php echo $citizen['id']; ?>">
                                                                Absent
                                                            </label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" name="mark_attendance" class="btn btn-primary">
                                        <i class="bi bi-save"></i> Save Attendance
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php elseif (!empty($event_id)): ?>
                    <div class="alert alert-info">No citizens found for this event.</div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 