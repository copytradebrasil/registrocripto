<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in, if not use user 2 for demo
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 2; // Use the user with real data for demonstration
}

// Get parameters
$days = isset($_GET['days']) ? intval($_GET['days']) : 30;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Set default dates based on days parameter
if (!$start_date) {
    $start_date = date('Y-m-d', strtotime("-{$days} days"));
}
if (!$end_date) {
    $end_date = date('Y-m-d');
}

try {
    // Get chart data
    $chart_data = getDailyProfitData($_SESSION['user_id'], $pdo, $start_date, $end_date);
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($chart_data);
} catch (Exception $e) {
    error_log("Chart data error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error fetching chart data']);
}
?>