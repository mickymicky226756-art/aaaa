<?php
session_start();
include 'db.php';
date_default_timezone_set('Africa/Addis_Ababa');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please login. ID: Not set in session']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;

    $plans = [
        1 => ['name' => 'Vera 1', 'price' => 750.00, 'daily' => 120.00],
        2 => ['name' => 'Vera 2', 'price' => 1580.00, 'daily' => 220.00],
        3 => ['name' => 'Vera 3', 'price' => 3570.00, 'daily' => 520.00],
        4 => ['name' => 'Vera 4', 'price' => 7680.00, 'daily' => 1200.00],
        5 => ['name' => 'Vera 5', 'price' => 13570.00, 'daily' => 2300.00],
        6 => ['name' => 'Vera 6', 'price' => 20700.00, 'daily' => 4600.00],
        7 => ['name' => 'Vera 7', 'price`' => 34900.00, 'daily' => 10000.00],
        8 => ['name' => 'Vera 8', 'price' => 77500.00, 'daily' => 29000.00],
        9 => ['name' => 'Vera 9', 'price' => 128000.00, 'daily' => 65000.00],
        10 => ['name' => 'Vera 10', 'price' => 214000.00, 'daily' => 130000.00],
    ];

    // Fix array key lookup check
    if (!isset($plans[$plan_id])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid investment package selected.']);
        exit();
    }

    $selected_plan = $plans[$plan_id];
    $price = floatval($selected_plan['price']);
    $plan_name = $selected_plan['name'];
    $daily_income = floatval($selected_plan['daily']);

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT balance, phone, referred_by FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $buyer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$buyer) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'User account not found in database for ID: ' . $user_id]);
            exit();
        }

        $current_balance = floatval($buyer['balance']);

        if ($current_balance < $price) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => "Insufficient balance! Your ID: $user_id, Balance: $current_balance, Required: $price"]);
            exit();
        }

        $new_balance = $current_balance - $price;
        $update_user = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
        $update_user->execute([$new_balance, $user_id]);

        $purchase_date = date('Y-m-d H:i:s');
        $insert_product = $pdo->prepare("INSERT INTO my_products (user_id, plan_name, price, daily_income, purchase_date, last_income_date) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_product->execute([$user_id, $plan_name, $price, $daily_income, $purchase_date, $purchase_date]);

        if (!empty($buyer['referred_by'])) {
            $lv1_phone = $buyer['referred_by'];
            $stmt_lv1 = $pdo->prepare("SELECT id, balance, referred_by FROM users WHERE phone = ? FOR UPDATE");
            $stmt_lv1->execute([$lv1_phone]);
            $lv1_user = $stmt_lv1->fetch(PDO::FETCH_ASSOC);

            if ($lv1_user) {
                $lv1_commission = $price * 0.20;
                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$lv1_commission, $lv1_user['id']]);

                if (!empty($lv1_user['referred_by'])) {
                    $lv2_phone = $lv1_user['referred_by'];
                    $stmt_lv2 = $pdo->prepare("SELECT id, balance, referred_by FROM users WHERE phone = ? FOR UPDATE");
                    $stmt_lv2->execute([$lv2_phone]);
                    $lv2_user = $stmt_lv2->fetch(PDO::FETCH_ASSOC);

                    if ($lv2_user) {
                        $lv2_commission = $price * 0.03;
                        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$lv2_commission, $lv2_user['id']]);

                        if (!empty($lv2_user['referred_by'])) {
                            $lv3_phone = $lv2_user['referred_by'];
                            $stmt_lv3 = $pdo->prepare("SELECT id, balance FROM users WHERE phone = ? FOR UPDATE");
                            $stmt_lv3->execute([$lv3_phone]);
                            $lv3_user = $stmt_lv3->fetch(PDO::FETCH_ASSOC);

                            if ($lv3_user) {
                                $lv3_commission = $price * 0.01;
                                $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$lv3_commission, $lv3_user['id']]);
                            }
                        }
                    }
                }
            }
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'እንኳን ደስ አለዎት! ' . $plan_name . ' ፓኬጅን በተሳካ ሁኔታ ገዝተዋል።']);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'System error: ' . $e->getMessage()]);
    }
}
?>
