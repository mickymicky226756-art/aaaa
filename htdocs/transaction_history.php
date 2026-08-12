<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Recharges History safely
$recharges = [];
try {
    $stmt = $pdo->prepare("SELECT amount, payment_reference, status, created_at FROM recharges WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Fetch Withdrawals History safely
$withdrawals = [];
try {
    $stmt_w = $pdo->prepare("SELECT amount, fee, total_deducted, status, created_at FROM withdrawals WHERE user_id = ? ORDER BY created_at DESC");
    $stmt_w->execute([$user_id]);
    $withdrawals = $stmt_w->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Transaction History</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        // Back ቁልፍ ሲነካ በቅጽበት ወደ me.php እንዲወስድ ማድረግ
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            window.location.replace('me.php');
        });

        // Tab switching function
        function switchTab(tabName) {
            const rechargeTab = document.getElementById('rechargeSection');
            const withdrawTab = document.getElementById('withdrawSection');
            const rechargeBtn = document.getElementById('rechargeBtn');
            const withdrawBtn = document.getElementById('withdrawBtn');

            if (tabName === 'recharge') {
                rechargeTab.classList.remove('hidden');
                withdrawTab.classList.add('hidden');
                rechargeBtn.classList.add('bg-emerald-500', 'text-slate-950', 'font-bold');
                rechargeBtn.classList.remove('text-slate-400');
                withdrawBtn.classList.remove('bg-emerald-500', 'text-slate-950', 'font-bold');
                withdrawBtn.classList.add('text-slate-400');
            } else {
                withdrawTab.classList.remove('hidden');
                rechargeTab.classList.add('hidden');
                withdrawBtn.classList.add('bg-emerald-500', 'text-slate-950', 'font-bold');
                withdrawBtn.classList.remove('text-slate-400');
                rechargeBtn.classList.remove('bg-emerald-500', 'text-slate-950', 'font-bold');
                rechargeBtn.classList.add('text-slate-400');
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 flex flex-col justify-between">
        
        <div class="flex-grow pb-10">
            <!-- Header -->
            <div class="p-4 border-b border-slate-800 flex items-center bg-slate-900/50">
                <a href="me.php" class="text-slate-400 mr-3 hover:text-white transition"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="font-bold text-sm text-slate-200">Transaction History</h2>
            </div>

            <!-- Tab Switcher Buttons -->
            <div class="p-4">
                <div class="flex bg-slate-900 border border-slate-800 rounded-xl p-1 mb-5">
                    <button id="rechargeBtn" onclick="switchTab('recharge')" class="flex-1 py-2 text-xs rounded-lg transition bg-emerald-500 text-slate-950 font-bold">
                        Recharges
                    </button>
                    <button id="withdrawBtn" onclick="switchTab('withdraw')" class="flex-1 py-2 text-xs rounded-lg transition text-slate-400 font-medium">
                        Withdrawals
                    </button>
                </div>

                <!-- Recharges Section -->
                <div id="rechargeSection" class="space-y-3">
                    <?php if (empty($recharges)): ?>
                        <div class="text-center py-16 text-slate-500 text-xs">
                            <i class="fa-solid fa-receipt text-3xl mb-2 block text-slate-600"></i>
                            No recharge history found.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recharges as $item): ?>
                            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl flex justify-between items-center shadow-lg">
                                <div>
                                    <p class="text-xs font-bold text-emerald-400 mb-0.5">+ ETB <?= number_format($item['amount'], 2) ?></p>
                                    <p class="text-[10px] text-slate-400 font-mono truncate max-w-[180px]">Ref: <?= htmlspecialchars($item['payment_reference']) ?></p>
                                    <p class="text-[9px] text-slate-500 mt-1"><?= $item['created_at'] ?></p>
                                </div>
                                <div>
                                    <?php 
                                        $status = $item['status'];
                                        $badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                        if ($status === 'approved') $badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                        if ($status === 'reject') $badgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                    ?>
                                    <span class="text-[10px] px-2.5 py-1 rounded-full border uppercase font-bold tracking-wider <?= $badgeClass ?>">
                                        <?= $status ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Withdrawals Section -->
                <div id="withdrawSection" class="space-y-3 hidden">
                    <?php if (empty($withdrawals)): ?>
                        <div class="text-center py-16 text-slate-500 text-xs">
                            <i class="fa-solid fa-money-bill-transfer text-3xl mb-2 block text-slate-600"></i>
                            No withdrawal history found.
                        </div>
                    <?php else: ?>
                        <?php foreach ($withdrawals as $w_item): ?>
                            <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl flex justify-between items-center shadow-lg">
                                <div>
                                    <p class="text-xs font-bold text-purple-300 mb-0.5">- ETB <?= number_format($w_item['total_deducted'], 2) ?></p>
                                    <p class="text-[10px] text-slate-400">Amount: <?= number_format($w_item['amount'], 2) ?> | Fee: <?= number_format($w_item['fee'], 2) ?></p>
                                    <p class="text-[9px] text-slate-500 mt-1"><?= $w_item['created_at'] ?></p>
                                </div>
                                <div>
                                    <?php 
                                        $w_status = $w_item['status'];
                                        $w_badgeClass = 'bg-amber-500/10 text-amber-400 border-amber-500/20';
                                        if ($w_status === 'approved') $w_badgeClass = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                                        if ($w_status === 'reject') $w_badgeClass = 'bg-red-500/10 text-red-400 border-red-500/20';
                                    ?>
                                    <span class="text-[10px] px-2.5 py-1 rounded-full border uppercase font-bold tracking-wider <?= $w_badgeClass ?>">
                                        <?= $w_status ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
