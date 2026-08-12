<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$balance = $user['balance'] ?? 0.00;

// 1. Fetch ONLY Approved Recharges
$total_recharge = 0.00;
try {
    $stmt_rech = $pdo->prepare("SELECT SUM(amount) AS total FROM recharges WHERE user_id = ? AND status = 'approved'");
    $stmt_rech->execute([$user_id]);
    $rech_res = $stmt_rech->fetch(PDO::FETCH_ASSOC);
    if ($rech_res && $rech_res['total']) {
        $total_recharge = $rech_res['total'];
    }
} catch (Exception $e) {
    // In case the table doesn't exist yet
}

// 2. Fetch ONLY Approved Withdrawals
$total_withdraw = 0.00;
try {
    $stmt_with = $pdo->prepare("SELECT SUM(amount) AS total FROM withdrawals WHERE user_id = ? AND status = 'approved'");
    $stmt_with->execute([$user_id]);
    $with_res = $stmt_with->fetch(PDO::FETCH_ASSOC);
    if ($with_res && $with_res['total']) {
        $total_withdraw = $with_res['total'];
    }
} catch (Exception $e) {
    // In case the table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - My Profile</title>
    <!-- Tailwind CSS for Modern Styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <!-- Mobile Container Frame -->
    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900">

        <!-- Main Content Area -->
        <div class="flex-grow p-4 pb-32">
            
            <!-- Top Header Title -->
            <div class="flex items-center justify-between mb-3 px-1">
                <h1 class="text-base font-bold text-slate-200">My Profile</h1>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>

            <!-- Premium Profile & Balance Card (ኮምፓክት እና ጠበብ ያለ) -->
            <div class="bg-slate-900 rounded-[2rem] p-4 text-white shadow-xl relative overflow-hidden mb-4 border border-emerald-500/20">
                <div class="relative z-10">
                    <!-- User Header -->
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400">
                            <i class="fa-solid fa-user text-sm"></i>
                        </div>
                        <div>
                            <h2 class="text-xs font-extrabold text-slate-100 tracking-wide">+251 <?php echo htmlspecialchars($user['phone'] ?? ''); ?></h2>
                            <span class="inline-flex items-center gap-1 mt-0.5 px-2 py-0.2 rounded-md bg-emerald-500/20 border border-emerald-500/30 text-[9px] text-emerald-300 font-bold uppercase tracking-wider">
                                <i class="fa-solid fa-crown text-amber-400"></i> VIP 0
                            </span>
                        </div>
                    </div>

                    <!-- Main Balance & Approved Totals in Grid layout to save space -->
                    <div class="grid grid-cols-2 gap-2 mb-2">
                        <div class="bg-slate-950/60 p-2.5 rounded-xl border border-white/5">
                            <p class="text-[10px] text-slate-400 font-medium mb-0.5 flex items-center gap-1">
                                <i class="fa-solid fa-wallet text-emerald-400"></i> Balance
                            </p>
                            <h1 class="text-lg font-black tracking-tight text-white"><?= number_format($balance, 2) ?> <span class="text-[10px] font-semibold text-emerald-400">ETB</span></h1>
                        </div>
                        <div class="bg-slate-950/40 border border-white/5 p-2.5 rounded-xl flex flex-col justify-between">
                            <div>
                                <p class="text-[9px] text-slate-400 font-medium">Recharge: <span class="text-emerald-400 font-bold">+<?= number_format($total_recharge, 2) ?></span></p>
                            </div>
                            <div class="border-t border-white/5 pt-1 mt-1">
                                <p class="text-[9px] text-slate-400 font-medium">Withdraw: <span class="text-rose-400 font-bold">-<?= number_format($total_withdraw, 2) ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recharge & Withdraw Action Buttons (Side by Side) -->
            <div class="flex gap-3 mb-5">
                <a href="recharge.php" onclick="navigate(event, this.href)" class="flex-1 bg-emerald-500 hover:bg-emerald-400 active:scale-95 transition-all text-slate-950 font-extrabold py-3.5 rounded-2xl flex items-center justify-center gap-2 shadow-lg shadow-emerald-500/10 text-xs">
                    <i class="fa-solid fa-wallet text-sm"></i> Recharge
                </a>
                <a href="withdraw.php" onclick="navigate(event, this.href)" class="flex-1 bg-slate-900 hover:bg-slate-800 border border-slate-800 active:scale-95 transition-all text-slate-200 font-extrabold py-3.5 rounded-2xl flex items-center justify-center gap-2 shadow-lg text-xs">
                    <i class="fa-solid fa-money-bill-transfer text-sm text-teal-400"></i> Withdraw
                </a>
            </div>

            <!-- Action Buttons Menu -->
            <div class="bg-slate-900 border border-slate-800 rounded-3xl overflow-hidden divide-y divide-slate-800/80 mb-5 shadow-lg">
                <a href="transaction_history.php" onclick="navigate(event, this.href)" class="p-3.5 flex justify-between items-center hover:bg-slate-800/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <span class="text-slate-200 font-bold text-xs">Transaction History</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
                
                <a href="bank_info.php" onclick="navigate(event, this.href)" class="p-3.5 flex justify-between items-center hover:bg-slate-800/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <span class="text-slate-200 font-bold text-xs">Bank Info</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
                
                <a href="security.php" onclick="navigate(event, this.href)" class="p-3.5 flex justify-between items-center hover:bg-slate-800/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <span class="text-slate-200 font-bold text-xs">Security</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
                
                <a href="support.php" onclick="navigate(event, this.href)" class="p-3.5 flex justify-between items-center hover:bg-slate-800/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-400 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <span class="text-slate-200 font-bold text-xs">Support</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
                
                <a href="about.php" onclick="navigate(event, this.href)" class="p-3.5 flex justify-between items-center hover:bg-slate-800/50 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-slate-800 border border-slate-700 text-slate-300 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <span class="text-slate-200 font-bold text-xs">About Vera</span>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-600 text-xs"></i>
                </a>
            </div>

            <!-- Logout Button (ከሰር በኩል በቀኝ በኩል የተደረደረ) -->
            <div class="flex justify-end mb-4">
                <a href="logout.php" onclick="navigate(event, this.href)" class="bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 font-bold py-2.5 px-5 rounded-xl transition inline-flex items-center gap-2 text-xs shadow-md">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </div>

        </div>

        <!-- Bottom Navigation Bar (Fixed) -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-between items-center text-[10px] font-bold text-slate-400 z-50">
            <a href="home.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-house text-base"></i>
                <span>Home</span>
            </a>
            <a href="my_product.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-layer-group text-base"></i>
                <span>Product</span>
            </a>
            <a href="task.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-tasks text-base"></i>
                <span>Task</span>
            </a>
            <a href="team.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-user-group text-base"></i>
                <span>Team</span>
            </a>
            <a href="me.php" onclick="navigate(event, this.href)" class="flex flex-col items-center text-emerald-400 transition gap-1">
                <i class="fa-solid fa-user text-base"></i>
                <span>Me</span>
            </a>
        </div>

    </div>

    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        function navigate(event, url) {
            event.preventDefault();
            window.location.replace(url);
        }
    </script>
</body>
</html>
