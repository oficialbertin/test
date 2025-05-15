<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-t'); // Last day of current month

try {
    // Get total statistics
    $stats = [
        'total_citizens' => $conn->query("SELECT COUNT(*) FROM citizens")->fetchColumn(),
        'total_events' => $conn->query("SELECT COUNT(*) FROM umuganda_events")->fetchColumn(),
        'total_attendance' => $conn->query("SELECT COUNT(*) FROM attendance")->fetchColumn(),
        'present_count' => $conn->query("SELECT COUNT(*) FROM attendance WHERE attended = 1")->fetchColumn(),
        'absent_count' => $conn->query("SELECT COUNT(*) FROM attendance WHERE attended = 0")->fetchColumn()
    ];

    // Get attendance by event
    $stmt = $conn->prepare("
        SELECT e.title, e.event_date, e.location,
               COUNT(a.id) as total_attendance,
               SUM(CASE WHEN a.attended = 1 THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN a.attended = 0 THEN 1 ELSE 0 END) as absent_count
        FROM umuganda_events e
        LEFT JOIN attendance a ON e.id = a.event_id
        WHERE e.event_date BETWEEN ? AND ?
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $attendance_by_event = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get attendance by location
    $stmt = $conn->prepare("
        SELECT e.location,
               COUNT(a.id) as total_attendance,
               SUM(CASE WHEN a.attended = 1 THEN 1 ELSE 0 END) as present_count,
               SUM(CASE WHEN a.attended = 0 THEN 1 ELSE 0 END) as absent_count
        FROM umuganda_events e
        LEFT JOIN attendance a ON e.id = a.event_id
        WHERE e.event_date BETWEEN ? AND ?
        GROUP BY e.location
        ORDER BY total_attendance DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $attendance_by_location = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Umuganda Connect Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a class="nav-link text-white" href="attendance.php">
                                <i class="bi bi-clipboard-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="reports.php">
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
                    <h1 class="h2">Reports & Analytics</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Date Range Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                       value="<?php echo htmlspecialchars($start_date); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                       value="<?php echo htmlspecialchars($end_date); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bi bi-filter"></i> Apply Filter
                                </button>
                                <a href="reports.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Citizens</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_citizens']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Events</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_events']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Attendance Records</h5>
                                <h2 class="card-text"><?php echo number_format($stats['total_attendance']); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Attendance by Event</h5>
                                <canvas id="attendanceByEventChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Attendance by Location</h5>
                                <canvas id="attendanceByLocationChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Tables -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Attendance by Event Details</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Total Attendance</th>
                                        <th>Present</th>
                                        <th>Absent</th>
                                        <th>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($attendance_by_event as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td><?php echo number_format($event['total_attendance']); ?></td>
                                            <td><?php echo number_format($event['present_count']); ?></td>
                                            <td><?php echo number_format($event['absent_count']); ?></td>
                                            <td>
                                                <?php 
                                                $rate = $event['total_attendance'] > 0 
                                                    ? ($event['present_count'] / $event['total_attendance']) * 100 
                                                    : 0;
                                                echo number_format($rate, 1) . '%';
                                                ?>
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
        // Attendance by Event Chart
        new Chart(document.getElementById('attendanceByEventChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($attendance_by_event, 'title')); ?>,
                datasets: [{
                    label: 'Present',
                    data: <?php echo json_encode(array_column($attendance_by_event, 'present_count')); ?>,
                    backgroundColor: 'rgba(40, 167, 69, 0.5)',
                    borderColor: 'rgb(40, 167, 69)',
                    borderWidth: 1
                }, {
                    label: 'Absent',
                    data: <?php echo json_encode(array_column($attendance_by_event, 'absent_count')); ?>,
                    backgroundColor: 'rgba(220, 53, 69, 0.5)',
                    borderColor: 'rgb(220, 53, 69)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Attendance by Location Chart
        new Chart(document.getElementById('attendanceByLocationChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($attendance_by_location, 'location')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($attendance_by_location, 'present_count')); ?>,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.5)',
                        'rgba(0, 123, 255, 0.5)',
                        'rgba(255, 193, 7, 0.5)',
                        'rgba(23, 162, 184, 0.5)',
                        'rgba(220, 53, 69, 0.5)'
                    ],
                    borderColor: [
                        'rgb(40, 167, 69)',
                        'rgb(0, 123, 255)',
                        'rgb(255, 193, 7)',
                        'rgb(23, 162, 184)',
                        'rgb(220, 53, 69)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html> 