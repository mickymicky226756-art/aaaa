<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approved') {
        $stmt = $pdo->prepare("SELECT user_id, amount FROM recharges WHERE id = ? AND status = 'processing'");
        $stmt->execute([$id]);
        $recharge = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($recharge) {
            $user_id = $recharge['user_id'];
            $amount = $recharge['amount'];

            $up_user = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $up_user->execute([$amount, $user_id]);

            $up_rec = $pdo->prepare("UPDATE recharges SET status = 'approved' WHERE id = ?");
            $up_rec->execute([$id]);
        }
    } elseif ($action === 'reject') {
        $up_rec = $pdo->prepare("UPDATE recharges SET status = 'reject' WHERE id = ?");
        $up_rec->execute([$id]);
    }
    header("Location: recharge.php");
    exit();
}

// Fetch all recharges safely without crashing if columns differ
$recharges = [];
try {
    // Try fetching with phone number if column exists
    $stmt = $pdo->query("SELECT r.*, u.username, u.phone FROM recharges r JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
    $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        // Fallback query if phone or other column has issue
        $stmt = $pdo->query("SELECT r.*, u.username FROM recharges r JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
        $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        // Absolute fallback just recharges table
        $stmt = $pdo->query("SELECT * FROM recharges ORDER BY id DESC");
        $recharges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Manage Recharges</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; touch-action: manipulation; }</style>
    <script>
        function showCustomAlert(message) {
            const modal = document.getElementById('customAlertModal');
            document.getElementById('customAlertMessage').innerText = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeCustomAlert() {
            const modal = document.getElementById('customAlertModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
        function copyTextContent(textId) {
            var textToCopy = document.getElementById(textId).innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                showCustomAlert("ስኬታማ! ትራንዛክሽን መታወቂያው ተቀድቷል።");
            });
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex min-h-screen justify-center">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border border-slate-900 pb-24">
        
        <!-- Header -->
        <div class="p-4 border-b border-slate-800 bg-slate-900/50 flex justify-between items-center">
            <h1 class="text-sm font-bold text-emerald-400">Recharge Requests</h1>
            <a href="dashboard.php" class="text-xs text-slate-400 hover:text-white"><i class="fa-solid fa-arrow-left mr-1"></i> Back</a>
        </div>

        <!-- Main Content -->
        <div class="p-4 flex-1 overflow-y-auto">
            <?php if (empty($recharges)): ?>
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl text-center text-slate-500 text-xs">
                    No recharge requests found.
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach($recharges as $index => $r): 
                        $trans_id_element = "trans_id_" . $index;
                    ?>
                        <div class="bg-slate-900 border border-slate-800 p-4 rounded-2xl shadow">
                            
                            <!-- User & Amount -->
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="text-[10px] text-slate-400">User / Phone</p>
                                    <p class="text-xs font-bold text-slate-200"><?= htmlspecialchars($r['username'] ?? 'User ID: ' . $r['user_id']) ?></p>
                                    <p class="text-[11px] font-mono text-emerald-400"><?= htmlspecialchars($r['phone'] ?? 'N/A') ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-slate-400">Amount</p>
                                    <p class="text-xs font-extrabold text-emerald-400">ETB <?= number_format($r['amount'], 2) ?></p>
                                </div>
                            </div>

                            <!-- Bank Method & Transaction ID with Copy Button -->
                            <div class="mb-3 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80 space-y-2">
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="text-slate-400">Bank Method:</span>
                                    <span class="font-bold text-slate-200 uppercase"><?= htmlspecialchars($r['bank_name'] ?? 'Telebirr / CBE') ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-[10px] text-slate-400">Transaction ID / Reference</p>
                                        <p id="<?= $trans_id_element ?>" class="text-xs font-mono text-slate-300 break-all"><?= htmlspecialchars($r['payment_reference']) ?></p>
                                    </div>
                                    <button onclick="copyTextContent('<?= $trans_id_element ?>')" class="bg-slate-800 hover:bg-slate-700 text-emerald-400 px-2.5 py-1 rounded-lg text-[10px] font-bold transition ml-2 shrink-0">
                                        <i class="fa-regular fa-copy mr-1"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <!-- Status & Actions (Approve / Reject) -->
                            <div class="flex justify-between items-center pt-2 border-t border-slate-800/80">
                                <div>
                                    <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full 
                                        <?= ($r['status'] ?? '') === 'approved' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : (($r['status'] ?? '') === 'reject' ? 'bg-red-500/10 text-red-400 border border-red-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20') ?>">
                                        <?= htmlspecialchars($r['status'] ?? 'processing') ?>
                                    </span>
                                </div>
                                <div>
                                    <?php if (($r['status'] ?? 'processing') === 'processing'): ?>
                                        <div class="space-x-1">
                                            <a href="recharge.php?action=approved&id=<?= $r['id'] ?>" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-[10px] font-bold px-3 py-1.5 rounded-xl transition inline-block">Approve</a>
                                            <a href="recharge.php?action=reject&id=<?= $r['id'] ?>" class="bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white text-[10px] font-bold px-3 py-1.5 rounded-xl transition inline-block border border-red-500/30">Reject</a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-500 italic">Processed</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Custom Alert Modal -->
        <div id="customAlertModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden justify-center items-center z-50 p-4">
            <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-6 w-full max-w-xs text-center shadow-2xl">
                <h3 class="text-base font-bold text-slate-100 mb-1">Notification</h3>
                <p id="customAlertMessage" class="text-xs text-slate-400 mb-5"></p>
                <button onclick="closeCustomAlert()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-bold py-2.5 rounded-xl">OK</button>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-800/80 py-2.5 px-4 flex justify-between items-center text-[10px] text-slate-400 z-50 shadow-2xl backdrop-blur-md">
            <a href="dashboard.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-chart-pie text-sm mb-0.5"></i><span>Dashboard</span></a>
            <a href="recharge.php" class="flex flex-col items-center text-emerald-400"><i class="fa-solid fa-wallet text-sm mb-0.5"></i><span>Recharge</span></a>
            <a href="withdraw.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-money-bill-transfer text-sm mb-0.5"></i><span>Withdraw</span></a>
            <a href="users.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-users text-sm mb-0.5"></i><span>User</span></a>
            <a href="vip.php" class="flex flex-col items-center hover:text-slate-200"><i class="fa-solid fa-crown text-sm mb-0.5"></i><span>VIP</span></a>
        </div>

    </div>

</body>
</html>
