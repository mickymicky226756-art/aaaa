<?php
session_start();
include 'db.php';
date_default_timezone_set('Africa/Addis_Ababa');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized session. Please login again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$bonus_amount = 10.00; // 10 ETB Check-in Bonus

try {
    // Check if user has already checked in today (Table name: checkins or user_checkins)
    // Make sure you have created a table named 'checkins' or adjust accordingly.
    // Recommended table schema: id, user_id, check_date
    
    // Check if table exists, if not create it dynamically for safety
    $pdo->exec("CREATE TABLE IF NOT EXISTS checkins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        check_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $stmt = $pdo->prepare("SELECT * FROM checkins WHERE user_id = ? AND check_date = ?");
    $stmt->execute([$user_id, $today]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'already', 'message' => 'You have already checked in today. Come back tomorrow!']);
        exit();
    }

    // Begin Transaction to record checkin and add 10 ETB to user balance
    $pdo->beginTransaction();

    // Insert check-in record
    $insertStmt = $pdo->prepare("INSERT INTO checkins (user_id, check_date) VALUES (?, ?)");
    $insertStmt->execute([$user_id, $today]);

    // Update user balance (Assuming balance field is named 'balance' in 'users' table)
    $updateStmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $updateStmt->execute([$bonus_amount, $user_id]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Successfully checked in! You received 10.00 ETB bonus.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred. Please try again.']);
}
?>
