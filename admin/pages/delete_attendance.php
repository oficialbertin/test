<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Check if attendance_id is provided
if (!isset($_POST['attendance_id'])) {
    header('Location: attendance.php');
    exit;
}

$attendance_id = $_POST['attendance_id'];

try {
    // Delete the attendance record
    $stmt = $conn->prepare("DELETE FROM attendance WHERE id = ?");
    if ($stmt->execute([$attendance_id])) {
        $_SESSION['success'] = "Attendance record deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting attendance record";
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to attendance page
header('Location: attendance.php');
exit; 