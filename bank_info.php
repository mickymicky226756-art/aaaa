<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = false;

// Fetch existing bank info if already saved
$bank_name = '';
$account_name = '';
$account_number = '';

try {
    $stmt = $pdo->prepare("SELECT bank_name, account_name, account_number FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $bank_name = $user['bank_name'] ?? '';
        $account_name = $user['account_name'] ?? '';
        $account_number = $user['account_number'] ?? '';
    }
} catch (Exception $e) {}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_bank = trim($_POST['bank_name'] ?? '');
    $holder_name = trim($_POST['account_name'] ?? '');
    $acc_number = trim($_POST['account_number'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($selected_bank) || empty($holder_name) || empty($acc_number) || empty($password)) {
        $error = "All fields are required!";
    } else {
        // Verify user login password for security
        $pass_stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $pass_stmt->execute([$user_id]);
        $db_user = $pass_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$db_user || !password_verify($password, $db_user['password'])) {
            $error = "Incorrect login password! Please enter your correct login password.";
        } else {
            // Update bank details
            $update = $pdo->prepare("UPDATE users SET bank_name = ?, account_name = ?, account_number = ? WHERE id = ?");
            if ($update->execute([$selected_bank, $holder_name, $acc_number, $user_id])) {
                $success = true;
            } else {
                $error = "Failed to save bank information. Please try again.";
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
    <title>Vera - Bank Information</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        // Back ቁልፍ ሲነካ በቅጽበት ወደ home.php እንዲመለስ ማድረግ
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            window.location.replace('home.php');
        });
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 pb-10 flex flex-col justify-between">
        
        <div>
            <!-- Header -->
            <div class="p-4 border-b border-slate-800 flex items-center bg-slate-900/50">
                <a href="home.php" class="text-slate-400 mr-3"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="font-bold text-sm text-slate-200">Bank Information</h2>
            </div>

            <div class="p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="mb-4 bg-red-950/50 border border-red-500 text-red-300 text-xs p-3 rounded-xl flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-2 text-red-400"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form action="" method="POST" class="space-y-4">
                    
                    <!-- Select Bank -->
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Select Bank / Payment Provider</label>
                        <select name="bank_name" required class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 outline-none">
                            <option value="" disabled <?= empty($bank_name) ? 'selected' : '' ?>>Choose bank...</option>
                            <option value="Commercial Bank of Ethiopia (CBE)" <?= $bank_name === 'Commercial Bank of Ethiopia (CBE)' ? 'selected' : '' ?>>Commercial Bank of Ethiopia (CBE)</option>
                            <option value="Awash Bank" <?= $bank_name === 'Awash Bank' ? 'selected' : '' ?>>Awash Bank</option>
                            <option value="Telebirr" <?= $bank_name === 'Telebirr' ? 'selected' : '' ?>>Telebirr</option>
                        </select>
                    </div>

                    <!-- Holder Name -->
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Account Holder Name</label>
                        <input type="text" name="account_name" value="<?= htmlspecialchars($account_name) ?>" required placeholder="Enter full name matching account" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <!-- Account Number -->
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Account Number / Phone Number</label>
                        <input type="text" name="account_number" value="<?= htmlspecialchars($account_number) ?>" required placeholder="Enter account or phone number" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <!-- Login Password Confirmation -->
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Login Password (For Verification)</label>
                        <input type="password" name="password" required placeholder="Enter your login password" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-3.5 rounded-xl transition shadow-lg tracking-wider mt-2">
                        Save Bank Info
                    </button>
                </form>

            </div>
        </div>

        <div class="px-4 mt-4">
            <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl text-[10px] text-slate-400 space-y-1">
                <p>📌 Please make sure your account details are correct.</p>
                <p>📌 Bank info is required before processing any withdrawals.</p>
            </div>
        </div>

    </div>

    <!-- Success Popup Modal -->
    <?php if ($success): ?>
    <div id="successModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex justify-center items-center z-50 p-4">
        <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-6 w-full max-w-xs text-center shadow-2xl animate-fade-in">
            <div class="w-16 h-16 mx-auto bg-emerald-500/20 rounded-full flex items-center justify-center mb-4 border border-emerald-500/40 shadow-lg shadow-emerald-500/10">
                <i class="fa-solid fa-circle-check text-emerald-400 text-3xl"></i>
            </div>
            <h3 class="text-base font-bold text-slate-100 mb-1">Successfully Saved!</h3>
            <p class="text-xs text-slate-400 mb-5">Your bank information has been updated successfully.</p>
            <a href="home.php" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-bold py-3 rounded-xl transition shadow-lg text-center">
                Continue to Home
            </a>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
