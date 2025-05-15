<?php
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

// Check if citizen_id is provided
if (!isset($_POST['citizen_id'])) {
    header('Location: citizens.php');
    exit;
}

$citizen_id = $_POST['citizen_id'];

try {
    // Start transaction
    $conn->beginTransaction();

    // Delete related attendance records first
    $stmt = $conn->prepare("DELETE FROM attendance WHERE citizen_id = ?");
    $stmt->execute([$citizen_id]);

    // Delete the citizen
    $stmt = $conn->prepare("DELETE FROM citizens WHERE id = ?");
    if ($stmt->execute([$citizen_id])) {
        $conn->commit();
        $_SESSION['success'] = "Citizen deleted successfully";
    } else {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting citizen";
    }
} catch (PDOException $e) {
    $conn->rollBack();
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}

// Redirect back to citizens page
header('Location: citizens.php');
exit; 