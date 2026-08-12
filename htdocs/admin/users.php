<?php
session_start();
include '../db.php';

// አድሚን መሆኑን ማረጋገጫ
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// 1. Handle Actions (Bonus, Reset Password, Delete, Login As User)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';

    if ($action === 'add_bonus') {
        $bonus_amount = floatval($_POST['bonus_amount'] ?? 0);
        if ($bonus_amount > 0 && !empty($user_id)) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$bonus_amount, $user_id]);
            $success = "ቦነሱ በተሳካ ሁኔታ ተጨመረ!";
        } else {
            $error = "እባክዎ ትክክለኛ የገንዘብ መጠን ያስገቡ።";
        }
    } elseif ($action === 'reset_password') {
        $new_pass = $_POST['new_password'] ?? '';
        if (!empty($new_pass) && !empty($user_id)) {
            $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_pass, $user_id]);
            $success = "የይለፍ ቃሉ በተሳካ ሁኔታ ተቀይሯል!";
        } else {
            $error = "እባክዎ አዲስ የይለፍ ቃል ያስገቡ።";
        }
    } elseif ($action === 'delete_user') {
        if (!empty($user_id)) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = "ተጠቃሚው ከዳታቤዝ ተሰርዟል!";
        }
    } elseif ($action === 'login_as_user') {
        if (!empty($user_id)) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user_data) {
                // ተጠቃሚው ያለ ፓስዎርድ በቀጥታ እንዲገባ ሴሽኖቹን ማስተካከል
                $_SESSION['user_id'] = $user_data['id'];
                $_SESSION['phone'] = $user_data['phone'];
                
                // ወደ ተጠቃሚው ዋና ገጽ (home.php) መውሰጃ
                header("Location: ../home.php");
                exit();
            }
        }
    }
}

// 2. Search & Fetch Users
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone LIKE ? OR id LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $users = $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vera Admin - Users Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; padding-bottom: 80px; }</style>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }
        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
        function setUserId(id, modalType) {
            if (modalType === 'bonus') {
                document.getElementById('bonusUserId').value = id;
                openModal('bonusModal');
            } else if (modalType === 'password') {
                document.getElementById('passUserId').value = id;
                openModal('passwordModal');
            } else if (modalType === 'delete') {
                document.getElementById('deleteUserId').value = id;
                openModal('deleteModal');
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 p-4">

    <div class="max-w-6xl mx-auto mb-10">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-slate-900 p-4 rounded-2xl border border-slate-800 gap-4">
            <h1 class="text-sm font-bold text-emerald-400"><i class="fa-solid fa-users mr-2"></i> Manage Users</h1>
            
            <!-- Search Bar with Button -->
            <form method="GET" action="" class="flex items-center w-full md:w-auto gap-2">
                <div class="relative w-full md:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-search"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="በ ስልክ ቁጥር ወይም ID ፈልግ..." class="w-full bg-slate-950 border border-slate-800 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-200 outline-none focus:border-emerald-500">
                </div>
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs px-4 py-2 rounded-xl transition font-bold flex items-center gap-1 cursor-pointer">
                    <i class="fa-solid fa-search"></i> ፈልግ
                </button>
                <?php if(!empty($search)): ?>
                    <a href="users.php" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs px-3 py-2 rounded-xl transition">አጥራ</a>
                <?php endif; ?>
            </form>

            <span class="text-xs bg-slate-800 px-3 py-1.5 rounded-xl text-slate-300">Total Users: <?= count($users) ?></span>
        </div>

        <?php if (!empty($success)): ?>
            <div class="mb-4 bg-emerald-950 border border-emerald-500 text-emerald-300 text-xs p-3 rounded-xl">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="mb-4 bg-red-950 border border-red-500 text-red-300 text-xs p-3 rounded-xl">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Users Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-x-auto shadow-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-800/50 text-slate-400">
                    <tr>
                        <th class="p-3">Phone & ID</th>
                        <th class="p-3">Balance</th>
                        <th class="p-3">Bank Details</th>
                        <th class="p-3">Joined Date</th>
                        <th class="p-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">ምንም ተጠቃሚ አልተገኘም።</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $u): ?>
                            <tr>
                                <td class="p-3">
                                    <span class="text-slate-100 font-bold text-sm"><?= htmlspecialchars($u['phone']) ?></span><br>
                                    <span class="text-emerald-400 font-mono text-[11px]">ID: <?= $u['id'] ?></span>
                                </td>
                                <td class="p-3 text-purple-300 font-bold">
                                    <?= number_format($u['balance'], 2) ?> ETB
                                </td>
                                <td class="p-3 text-slate-300">
                                    Bank: <strong class="text-white"><?= htmlspecialchars($u['bank_name'] ?? 'N/A') ?></strong><br>
                                    A/C: <span class="font-mono text-emerald-300"><?= htmlspecialchars($u['account_number'] ?? 'N/A') ?></span>
                                </td>
                                <td class="p-3 text-slate-400 text-[10px]">
                                    <?= htmlspecialchars($u['created_at']) ?>
                                </td>
                                <td class="p-3 text-center space-x-1 whitespace-nowrap">
                                    <!-- Login As User Form/Button -->
                                    <form action="" method="POST" class="inline">
                                        <input type="hidden" name="action" value="login_as_user">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white px-2 py-1.5 rounded-lg transition cursor-pointer" title="Login to User Account">
                                            <i class="fa-solid fa-right-to-bracket"></i>
                                        </button>
                                    </form>
                                    <!-- Bonus Button -->
                                    <button onclick="setUserId(<?= $u['id'] ?>, 'bonus')" class="bg-purple-600 hover:bg-purple-500 text-white px-2 py-1.5 rounded-lg transition cursor-pointer" title="Give Bonus">
                                        <i class="fa-solid fa-gift"></i>
                                    </button>
                                    <!-- Reset Password Button -->
                                    <button onclick="setUserId(<?= $u['id'] ?>, 'password')" class="bg-amber-600 hover:bg-amber-500 text-white px-2 py-1.5 rounded-lg transition cursor-pointer" title="Reset Password">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <!-- Delete Button -->
                                    <button onclick="setUserId(<?= $u['id'] ?>, 'delete')" class="bg-red-600 hover:bg-red-500 text-white px-2 py-1.5 rounded-lg transition cursor-pointer" title="Delete User">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. Bonus Modal -->
    <div id="bonusModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex justify-center items-center z-50 p-4">
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl max-w-sm w-full shadow-2xl space-y-4">
            <h3 class="font-bold text-sm text-purple-400"><i class="fa-solid fa-gift mr-1"></i> ተጠቃሚው ላይ ቦነስ መጨመር</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="add_bonus">
                <input type="hidden" name="user_id" id="bonusUserId">
                <div class="mb-4">
                    <label class="block text-[11px] text-slate-400 mb-1">የቦነስ መጠን (ETB)</label>
                    <input type="number" step="0.01" name="bonus_amount" required placeholder="ለምሳሌ: 100" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 outline-none focus:border-purple-500">
                </div>
                <div class="flex space-x-2">
                    <button type="button" onclick="closeModal('bonusModal')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold py-2.5 rounded-xl cursor-pointer">ይቅር</button>
                    <button type="submit" class="flex-1 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold py-2.5 rounded-xl cursor-pointer">አስገባ</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Password Reset Modal -->
    <div id="passwordModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex justify-center items-center z-50 p-4">
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl max-w-sm w-full shadow-2xl space-y-4">
            <h3 class="font-bold text-sm text-amber-400"><i class="fa-solid fa-key mr-1"></i> የይለፍ ቃል መቀየር (Reset Password)</h3>
            <form action="" method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="passUserId">
                <div class="mb-4">
                    <label class="block text-[11px] text-slate-400 mb-1">አዲስ የይለፍ ቃል</label>
                    <input type="text" name="new_password" required placeholder="አዲስ ፓስዎርድ ያስገቡ" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 outline-none focus:border-amber-500">
                </div>
                <div class="flex space-x-2">
                    <button type="button" onclick="closeModal('passwordModal')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold py-2.5 rounded-xl cursor-pointer">ይቅር</button>
                    <button type="submit" class="flex-1 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold py-2.5 rounded-xl cursor-pointer">ቀይር</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Delete Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex justify-center items-center z-50 p-4">
        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl max-w-sm w-full shadow-2xl text-center space-y-4">
            <div class="w-12 h-12 mx-auto rounded-full bg-red-950 text-red-400 flex items-center justify-center text-xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="font-bold text-sm text-red-400">ተጠቃሚውን ማጥፋት (Delete User)</h3>
            <p class="text-xs text-slate-400">ይህንን ተጠቃሚ ከዳታቤዝ ውስጥ ሙሉ በሙሉ ማጥፋት ይፈልጋሉ?</p>
            <form action="" method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div class="flex space-x-2 pt-2">
                    <button type="button" onclick="closeModal('deleteModal')" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold py-2.5 rounded-xl cursor-pointer">ይቅር</button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-500 text-white text-xs font-bold py-2.5 rounded-xl cursor-pointer">አዎ አጥፋ</button>
                </div>
            </form>
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
        <a href="withdraw.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-wallet text-lg"></i> Withdraw
        </a>
        <a href="users.php" class="flex flex-col items-center text-emerald-400 text-[10px]">
            <i class="fa-solid fa-users text-lg"></i> User
        </a>
        <a href="vip.php" class="flex flex-col items-center text-slate-400 hover:text-white text-[10px]">
            <i class="fa-solid fa-star text-lg"></i> VIP
        </a>
    </div>

</body>
</html>
