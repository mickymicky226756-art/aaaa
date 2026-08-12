<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';

// 1. Safe Balance, Bank info, VIP check, Pending check, Time check, Daily limit check
$current_balance = 0.00;
$has_bank_info = false;
$has_vip = false;
$has_pending_withdrawal = false;
$exceeded_daily_limit = false;
$is_out_of_hours = false;

// Time Zone ማስተካከል (የኢትዮጵያን ሰዓት አቆጣጠር ለመጠቀም)
date_default_timezone_set('Africa/Addis_Ababa');
$current_hour = (int)date('G'); // 0 እስከ 23 ሰዓት

// ሰዓቱ ከጠዋቱ 9 (09:00) እስከ ከሰዓት 11 (17:00) ውጪ መሆኑን ማረጋገጫ (09:00 AM - 05:00 PM)
if ($current_hour < 9 || $current_hour >= 17) {
    $is_out_of_hours = true;
}

try {
    // Check balance and bank info from users table
    $stmt = $pdo->prepare("SELECT balance, account_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $current_balance = floatval($user_data['balance'] ?? 0);
        $has_bank_info = !empty($user_data['account_number']);
    }

    // Check if user has purchased at least one package in my_products table
    $vip_stmt = $pdo->prepare("SELECT COUNT(*) FROM my_products WHERE user_id = ?");
    $vip_stmt->execute([$user_id]);
    $vip_count = $vip_stmt->fetchColumn();
    
    if ($vip_count > 0) {
        $has_vip = true;
    }

    // Check if user already has a withdrawal request with 'processing' or 'pending' status
    $pending_stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND status IN ('processing', 'pending')");
    $pending_stmt->execute([$user_id]);
    $pending_count = $pending_stmt->fetchColumn();

    if ($pending_count > 0) {
        $has_pending_withdrawal = true;
    }

    // Check if user has already made 3 withdrawals today
    $today_date = date('Y-m-d');
    $daily_stmt = $pdo->prepare("SELECT COUNT(*) FROM withdrawals WHERE user_id = ? AND DATE(created_at) = ?");
    $daily_stmt->execute([$user_id, $today_date]);
    $daily_count = $daily_stmt->fetchColumn();

    if ($daily_count >= 3) {
        $exceeded_daily_limit = true;
    }
} catch (Exception $e) {
    // Handle gracefully if tables don't exist yet
}

// 2. Handle Withdrawal Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $withdraw_amount = floatval($_POST['amount'] ?? 0);

    if ($is_out_of_hours) {
        $error = "Withdrawal is only allowed between 09:00 AM and 05:00 PM.";
    } elseif ($has_pending_withdrawal) {
        $error = "You already have a pending withdrawal request in progress! Please wait for admin approval.";
    } elseif ($exceeded_daily_limit) {
        $error = "You have reached the maximum limit of 3 withdrawals for today.";
    } elseif (!$has_vip) {
        $error = "You must purchase at least one VIP plan before withdrawing!";
    } elseif (!$has_bank_info) {
        $error = "Please fill in your bank information first before withdrawing!";
    } elseif ($withdraw_amount < 250) {
        $error = "Minimum withdrawal amount is 250 ETB.";
    } else {
        $fee = $withdraw_amount * 0.10;
        $net_amount = $withdraw_amount - $fee; 
        $total_deducted = $withdraw_amount; // Full requested amount deducted from balance

        if ($current_balance < $total_deducted) {
            $error = "Insufficient balance! You don't have enough balance for this withdrawal.";
        } else {
            try {
                $pdo->beginTransaction();

                $new_balance = $current_balance - $total_deducted;
                $update_bal = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $update_bal->execute([$new_balance, $user_id]);

                $insert_wd = $pdo->prepare("INSERT INTO withdrawals (user_id, amount, fee, total_deducted, status) VALUES (?, ?, ?, ?, 'processing')");
                $insert_wd->execute([$user_id, $net_amount, $fee, $total_deducted]);

                $pdo->commit();
                header("Location: transaction_history.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Transaction failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Withdraw</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.href = 'home.php';
            }
        });

        function setAmount(val) {
            document.getElementById('amountInput').value = val;
            calculateFee(val);
        }

        function calculateFee(val) {
            let amount = parseFloat(val) || 0;
            let fee = amount * 0.10;
            let actualReceive = amount - fee;
            
            document.getElementById('feeDisplay').innerText = fee.toFixed(2) + ' ETB';
            document.getElementById('totalDisplay').innerText = actualReceive.toFixed(2) + ' ETB';
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 flex flex-col justify-start">
        
        <!-- Header -->
        <div class="px-4 py-2 border-b border-slate-800 flex items-center bg-slate-900/50">
            <a href="home.php" class="text-slate-400 mr-3"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="font-bold text-sm text-slate-200">Withdraw Funds</h2>
        </div>

        <div class="p-3 space-y-2.5">
            
            <div class="p-2.5 bg-slate-900 border border-slate-800 rounded-xl flex justify-between items-center shadow-lg">
                <div>
                    <p class="text-[10px] text-slate-400">Available Balance</p>
                    <h3 class="text-base font-extrabold text-emerald-400"><?= number_format($current_balance, 2) ?> ETB</h3>
                </div>
                <div class="text-right">
                    <span class="text-[9px] bg-slate-800 text-slate-300 px-2 py-0.5 rounded-full border border-slate-700">Fee: 10%</span>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-red-950/50 border border-red-500 text-red-300 text-[11px] p-2 rounded-lg">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($is_out_of_hours): ?>
                <div class="bg-amber-950/40 border border-amber-500/50 p-3 rounded-xl text-center space-y-1">
                    <p class="text-[11px] text-amber-200 font-bold">⏰ Withdrawal Closed</p>
                    <p class="text-[10px] text-slate-400">Withdrawal hours are only from <strong>09:00 AM</strong> to <strong>05:00 PM</strong>.</p>
                </div>
            <?php elseif ($has_pending_withdrawal): ?>
                <div class="bg-amber-950/40 border border-amber-500/50 p-3 rounded-xl text-center space-y-2">
                    <p class="text-[11px] text-amber-200 font-bold">⏳ You have a withdrawal request currently in progress.</p>
                    <p class="text-[10px] text-slate-400">Please wait until the admin approves or rejects your previous request before making a new one.</p>
                    <a href="transaction_history.php" class="inline-block bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">View Transaction History</a>
                </div>
            <?php elseif ($exceeded_daily_limit): ?>
                <div class="bg-amber-950/40 border border-amber-500/50 p-3 rounded-xl text-center space-y-1">
                    <p class="text-[11px] text-amber-200 font-bold">🚫 Daily Limit Reached</p>
                    <p class="text-[10px] text-slate-400">You can only make a maximum of 3 withdrawal requests per day.</p>
                </div>
            <?php elseif (!$has_vip): ?>
                <div class="bg-amber-950/40 border border-amber-500/50 p-2.5 rounded-xl text-center">
                    <p class="text-[11px] text-amber-200 mb-1.5">⚠️ You must purchase at least one VIP plan before withdrawing.</p>
                    <a href="home.php" class="inline-block bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">Go to Home & Buy VIP</a>
                </div>
            <?php elseif (!$has_bank_info): ?>
                <div class="bg-amber-950/40 border border-amber-500/50 p-2.5 rounded-xl text-center">
                    <p class="text-[11px] text-amber-200 mb-1.5">⚠️ You must fill in your bank info before withdrawing.</p>
                    <a href="bank_info.php" class="inline-block bg-amber-600 hover:bg-amber-500 text-white text-[11px] font-bold px-3 py-1.5 rounded-lg transition">Add Bank Information</a>
                </div>
            <?php endif; ?>

            <?php if (!$is_out_of_hours && !$has_pending_withdrawal && !$exceeded_daily_limit): ?>
            <form action="" method="POST">
                
                <p class="text-[11px] font-bold text-slate-300 mb-1.5">Select or Enter Amount</p>

                <!-- Compact Grid for Amounts -->
                <div class="grid grid-cols-4 gap-1.5 mb-2.5">
                    <?php 
                    $amounts = [250, 500, 1000, 1500, 2500, 5000, 7000, 10000, 15000, 25000, 50000, 100000];
                    foreach ($amounts as $amt): 
                    ?>
                        <button type="button" onclick="setAmount(<?= $amt ?>)" class="bg-slate-900 hover:border-emerald-500 border border-slate-800 text-slate-200 text-[11px] font-bold py-1.5 rounded-lg transition text-center">
                            <?= $amt ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="mb-2.5">
                    <input type="number" name="amount" id="amountInput" min="250" step="1" required oninput="calculateFee(this.value)" placeholder="Enter amount (Min: 250 ETB)" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl px-3 py-2 text-xs text-slate-200 placeholder-slate-600 outline-none">
                </div>

                <div class="bg-slate-900/60 border border-slate-800/80 p-2 rounded-xl mb-2.5 space-y-0.5 text-[11px]">
                    <div class="flex justify-between text-slate-400">
                        <span>Withdrawal Fee (10%):</span>
                        <span id="feeDisplay" class="text-amber-400 font-mono">0.00 ETB</span>
                    </div>
                    <div class="flex justify-between text-slate-200 font-bold border-t border-slate-800/80 pt-1">
                        <span>You Will Receive:</span>
                        <span id="totalDisplay" class="text-emerald-400 font-mono">0.00 ETB</span>
                    </div>
                </div>

                <button type="submit" <?= (!$has_vip || !$has_bank_info) ? 'disabled' : '' ?> class="w-full <?= ($has_vip && $has_bank_info) ? 'bg-emerald-600 hover:bg-emerald-500 cursor-pointer' : 'bg-slate-800 text-slate-500 cursor-not-allowed' ?> text-white text-xs font-bold py-3 rounded-xl transition shadow-lg tracking-wider mb-2.5">
                    Withdraw Now
                </button>
            </form>
            <?php endif; ?>

            <!-- Footer Notes -->
            <div class="bg-slate-900 border border-slate-800 p-2 rounded-xl text-[9px] text-slate-400 space-y-0.5">
                <p>📌 Hours: **09:00 AM - 05:00 PM** (Max 3 times per day).</p>
                <p>📌 Minimum limit: **250 ETB** (10% fee applies).</p>
            </div>

        </div>

    </div>

</body>
</html>
