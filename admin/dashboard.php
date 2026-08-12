<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Statistics counts
$total_users = 0;
$active_users = 0;
$total_recharges = 0;
$total_withdrawals = 0;

try {
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    
    // Active members (users who bought at least one product)
    $active_users = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM my_products")->fetchColumn();

    $total_recharges = $pdo->query("SELECT SUM(amount) FROM recharges WHERE status='approved'")->fetchColumn() ?: 0;
    $total_withdrawals = $pdo->query("SELECT SUM(amount) FROM withdrawals WHERE status='approved'")->fetchColumn() ?: 0;
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; touch-action: manipulation; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex min-h-screen justify-center">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border border-slate-900 pb-24">
        
        <!-- Header -->
        <div class="p-4 border-b border-slate-800 bg-slate-900/50 flex justify-between items-center">
            <h1 class="text-sm font-bold text-emerald-400">Vera Admin Panel</h1>
            <a href="logout.php" class="text-red-400 text-xs"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>

        <!-- Main Content -->
        <div class="p-4 flex-1 overflow-y-auto">
            <h2 class="text-base font-bold mb-4 text-slate-200">System Overview</h2>
            
            <!-- Statistics Cards -->
            <div class="space-y-3 mb-6">
                <!-- Row 1: Total Members & Active Members (ጎን ለጎን) -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow">
                        <p class="text-[10px] text-slate-400 mb-1">Total Members</p>
                        <p class="text-base font-extrabold text-blue-400"><?= number_format($total_users) ?></p>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow">
                        <p class="text-[10px] text-slate-400 mb-1">Active Members</p>
                        <p class="text-base font-extrabold text-emerald-400"><?= number_format($active_users) ?></p>
                    </div>
                </div>

                <!-- Row 2: Total Recharge & Total Withdrawal (ጎን ለጎን) -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow">
                        <p class="text-[10px] text-slate-400 mb-1">Total Recharge</p>
                        <p class="text-xs font-extrabold text-emerald-400">ETB <?= number_format($total_recharges, 2) ?></p>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow">
                        <p class="text-[10px] text-slate-400 mb-1">Total Withdraw</p>
                        <p class="text-xs font-extrabold text-purple-400">ETB <?= number_format($total_withdrawals, 2) ?></p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons Grid (Recharge, Withdraw, User, VIP) -->
            <div class="grid grid-cols-2 gap-3">
                <a href="recharge.php" class="bg-slate-900 border border-slate-800 hover:border-emerald-500/50 p-4 rounded-2xl text-center transition shadow">
                    <i class="fa-solid fa-wallet text-emerald-400 text-lg mb-1"></i>
                    <p class="text-xs font-bold text-slate-200">Recharge</p>
                </a>
                <a href="withdraw.php" class="bg-slate-900 border border-slate-800 hover:border-purple-500/50 p-4 rounded-2xl text-center transition shadow">
                    <i class="fa-solid fa-money-bill-transfer text-purple-400 text-lg mb-1"></i>
                    <p class="text-xs font-bold text-slate-200">Withdraw</p>
                </a>
                <a href="users.php" class="bg-slate-900 border border-slate-800 hover:border-blue-500/50 p-4 rounded-2xl text-center transition shadow">
                    <i class="fa-solid fa-users text-blue-400 text-lg mb-1"></i>
                    <p class="text-xs font-bold text-slate-200">User</p>
                </a>
                <a href="vip.php" class="bg-slate-900 border border-slate-800 hover:border-amber-500/50 p-4 rounded-2xl text-center transition shadow">
                    <i class="fa-solid fa-crown text-amber-400 text-lg mb-1"></i>
                    <p class="text-xs font-bold text-slate-200">VIP</p>
                </a>
            </div>
        </div>

        <!-- Bottom Navigation Bar (dashboard, recharge, withdraw, user, vip) -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-800/80 py-2.5 px-4 flex justify-between items-center text-[10px] text-slate-400 z-50 shadow-2xl backdrop-blur-md">
            <a href="dashboard.php" class="flex flex-col items-center text-emerald-400"><i class="fa-solid fa-chart-pie text-sm mb-0.5"></i><span>Dashboard</span></a>
            <a href="recharge.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-wallet text-sm mb-0.5"></i><span>Recharge</span></a>
            <a href="withdraw.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-money-bill-transfer text-sm mb-0.5"></i><span>Withdraw</span></a>
            <a href="users.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-users text-sm mb-0.5"></i><span>User</span></a>
            <a href="vip.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-crown text-sm mb-0.5"></i><span>VIP</span></a>
        </div>

    </div>

</body>
</html>
