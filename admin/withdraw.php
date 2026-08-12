<?php
session_start();
include '../db.php';

// አድሚን መሆኑን ማረጋገጫ
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

// 1. Action Handler (Approve or Reject)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'reject') {
        $stmt = $pdo->prepare("SELECT user_id, total_deducted FROM withdrawals WHERE id = ? AND status = 'processing'");
        $stmt->execute([$id]);
        $w_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($w_data) {
            $user_id = $w_data['user_id'];
            $refund = $w_data['total_deducted'];

            $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$refund, $user_id]);
            $pdo->prepare("UPDATE withdrawals SET status = 'rejected' WHERE id = ?")->execute([$id]);
        }
    } elseif ($action === 'approved') {
        $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?")->execute([$id]);
    }
    header("Location: withdraw.php");
    exit();
}

// 2. Fetch only 'processing' withdrawals
$withdrawals = $pdo->query("
    SELECT w.*, u.phone, u.account_number, u.account_name, u.bank_name 
    FROM withdrawals w 
    JOIN users u ON w.user_id = u.id 
    WHERE w.status = 'processing' 
    ORDER BY w.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vera Admin - Withdrawals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; padding-bottom: 80px; }</style>
    <script>
        function copyToClipboard(text, buttonElement) {
            navigator.clipboard.writeText(text).then(() => {
                let originalText = buttonElement.innerHTML;
                buttonElement.innerHTML = '<i class="fa-solid fa-check text-emerald-400"></i>';
                setTimeout(() => { buttonElement.innerHTML = originalText; }, 2000);
            });
        }

        // Custom Popup Alert for confirmation
        function confirmAction(actionType, url) {
            let message = actionType === 'approve' 
                ? "ይህንን የገንዘብ ማውጣት ጥያቄ ማጽደቅ (Approve) ይፈልጋሉ?" 
                : "ይህንን ጥያቄ ውድቅ (Reject) አድርገው ገንዘቡን ወደ ተጠቃሚው ባላንስ መመለስ ይፈልጋሉ?";
            
            let title = actionType === 'approve' ? "ማረጋገጫ - Approve" : "ማረጋገጫ - Reject";
            
            // Create Tailwind Modal Dynamically
            let modalBg = document.createElement('div');
            modalBg.className = "fixed inset-0 bg-black/70 backdrop-blur-sm flex justify-center items-center z-50 p-4 animate-fade-in";
            modalBg.innerHTML = `
                <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-4">
                    <div class="w-12 h-12 mx-auto rounded-full flex items-center justify-center ${actionType === 'approve' ? 'bg-emerald-950 text-emerald-400' : 'bg-red-950 text-red-400'} text-xl">
                        <i class="fa-solid ${actionType === 'approve' ? 'fa-circle-check' : 'fa-triangle-exclamation'}"></i>
                    </div>
                    <h3 class="font-bold text-slate-100 text-sm">${title}</h3>
                    <p class="text-xs text-slate-400">${message}</p>
                    <div class="flex space-x-2 pt-2">
                        <button id="cancelBtn" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold py-2.5 rounded-xl transition">ይቅር (Cancel)</button>
                        <a id="confirmBtn" href="${url}" class="flex-1 ${actionType === 'approve' ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-red-600 hover:bg-red-500'} text-white text-xs font-bold py-2.5 rounded-xl transition flex items-center justify-center">አዎ (Confirm)</a>
                    </div>
                </div>
            `;
            document.body.appendChild(modalBg);

            modalBg.querySelector('#cancelBtn').onclick = () => {
                modalBg.remove();
            };
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 p-4">
    
    <div class="max-w-6xl mx-auto mb-10">
        <div class="flex justify-between items-center mb-6 bg-slate-900 p-4 rounded-2xl border border-slate-800">
            <h1 class="text-sm font-bold text-purple-400"><i class="fa-solid fa-wallet mr-2"></i> Pending Withdrawals</h1>
        </div>

        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-x-auto shadow-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-800/50 text-slate-400">
                    <tr><th class="p-3">User</th><th class="p-3">Amount</th><th class="p-3">Details</th><th class="p-3">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php if (empty($withdrawals)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-slate-500">No pending withdrawal requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach($withdrawals as $w): ?>
                            <tr>
                                <td class="p-3 font-bold"><?= htmlspecialchars($w['phone']) ?></td>
                                <td class="p-3 text-emerald-400 font-bold"><?= number_format($w['total_deducted'], 2) ?> ETB</td>
                                <td class="p-3">
                                    <?= htmlspecialchars($w['bank_name']) ?> <br>
                                    <span class="text-emerald-300 font-mono"><?= htmlspecialchars($w['account_number']) ?></span>
                                </td>
                                <td class="p-3 space-x-1">
                                    <button onclick="confirmAction('approve', 'withdraw.php?action=approved&id=<?= $w['id'] ?>')" class="bg-emerald-600 hover:bg-emerald-500 px-2.5 py-1.5 rounded-lg text-white transition"><i class="fa-solid fa-check"></i></button>
                                    <button onclick="confirmAction('reject', 'withdraw.php?action=reject&id=<?= $w['id'] ?>')" class="bg-red-600 hover:bg-red-500 px-2.5 py-1.5 rounded-lg text-white transition"><i class="fa-solid fa-xmark"></i></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <div class="fixed bottom-0 left-0 w-full bg-slate-900 border-t border-slate-800 p-2 flex justify-around items-center z-40">
        <a href="dashboard.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-house text-lg"></i> Dashboard
        </a>
        <a href="recharge.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-credit-card text-lg"></i> Recharge
        </a>
        <a href="withdraw.php" class="flex flex-col items-center text-purple-400 text-[10px]">
            <i class="fa-solid fa-wallet text-lg"></i> Withdraw
        </a>
        <a href="users.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-users text-lg"></i> Users
        </a>
        <a href="vip.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-star text-lg"></i> VIP
        </a>
    </div>

</body>
</html>
