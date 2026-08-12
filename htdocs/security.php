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

// Handle Password Change Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        // Verify current password from database
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            $error = "Incorrect current password!";
        } else {
            // Hash new password and update
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            
            if ($update_stmt->execute([$hashed_password, $user_id])) {
                $success = true;
            } else {
                $error = "Failed to update password. Please try again.";
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
    <title>Vera - Security & Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 pb-10 flex flex-col justify-between">
        
        <div>
            <!-- Header -->
            <div class="p-4 border-b border-slate-800 flex items-center bg-slate-900/50">
                <a href="me.php" class="text-slate-400 mr-3"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="font-bold text-sm text-slate-200">Security Settings</h2>
            </div>

            <div class="p-4">
                
                <?php if (!empty($error)): ?>
                    <div class="mb-4 bg-red-950/50 border border-red-500 text-red-300 text-xs p-3 rounded-xl flex items-center">
                        <i class="fa-solid fa-triangle-exclamation mr-2 text-red-400"></i>
                        <span><?= $error ?></span>
                    </div>
                <?php endif; ?>

                <!-- Change Password Form -->
                <form action="" method="POST" class="space-y-4">
                    
                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Current Login Password</label>
                        <input type="password" name="current_password" required placeholder="Enter current password" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">New Login Password</label>
                        <input type="password" name="new_password" required placeholder="Enter new password (min 6 chars)" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <div>
                        <label class="block text-[11px] text-slate-400 mb-1">Confirm New Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm new password" class="w-full bg-slate-900 border border-slate-800 focus:border-emerald-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none">
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold py-3.5 rounded-xl transition shadow-lg tracking-wider mt-2">
                        Update Password
                    </button>
                </form>

            </div>
        </div>

        <div class="px-4 mt-4">
            <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl text-[10px] text-slate-400 space-y-1">
                <p>📌 Choose a strong password containing letters and numbers.</p>
                <p>📌 Keep your login credentials secure and do not share them.</p>
            </div>
        </div>

    </div>

    <!-- Success Popup Modal -->
    <?php if ($success): ?>
    <div id="successModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm flex justify-center items-center z-50 p-4">
        <div class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-6 w-full max-w-xs text-center shadow-2xl">
            <div class="w-16 h-16 mx-auto bg-emerald-500/20 rounded-full flex items-center justify-center mb-4 border border-emerald-500/40 shadow-lg shadow-emerald-500/10">
                <i class="fa-solid fa-circle-check text-emerald-400 text-3xl"></i>
            </div>
            <h3 class="text-base font-bold text-slate-100 mb-1">Password Updated!</h3>
            <p class="text-xs text-slate-400 mb-5">Your login password has been changed successfully.</p>
            <a href="me.php" class="block w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 text-xs font-bold py-3 rounded-xl transition shadow-lg text-center">
                Back to Profile
            </a>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
