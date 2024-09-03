<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'conn.php';

// Start the session
session_start();

header('Content-Type: application/json');

if (!isset($_GET['task_id']) || !isset($_GET['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing task_id or user_id']);
    exit;
}

$task_id = intval($_GET['task_id']);
$user_id = intval($_GET['user_id']);

// Check if the task has already been completed by the user
$check_query = "SELECT * FROM user_tasks WHERE user_id = ? AND task_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $user_id, $task_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Task already completed']);
    exit;
}

// Get the task points
$points_query = "SELECT points FROM tasks WHERE id = ?";
$stmt = $conn->prepare($points_query);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$task_result = $stmt->get_result();
$task = $task_result->fetch_assoc();

if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task not found']);
    exit;
}

$points = $task['points'];

// Check CSRF token
if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}


// Start a transaction
$conn->begin_transaction();

try {
    // Insert into user_tasks table
    $insert_query = "INSERT INTO user_tasks (user_id, task_id, status, completed_at) VALUES (?, ?, 'done', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ii", $user_id, $task_id);
    $stmt->execute();

    // Update user's task_bonus in the users table
    $update_query = "UPDATE users SET tasks_bonus = IFNULL(tasks_bonus, 0) + ? WHERE telegram_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $points, $user_id);
    $stmt->execute();

    // Commit the transaction
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Task completed successfully', 'points' => $points]);
} catch (Exception $e) {
    // An error occurred, rollback the transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error completing task: ' . $e->getMessage()]);
}

$conn->close();
?>