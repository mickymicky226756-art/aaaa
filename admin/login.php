<?php
session_start();
include '../db.php';

$error_msg = '';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // አዲሱ Username: verako እና Password: verako2026 ተስተካክሏል
    if ($username === 'verako' && $password === 'verako2026') {
        $_SESSION['admin_logged_in'] = true;
        header("Location: dashboard.php");
        exit();
    } else {
        $error_msg = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vera - Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen">
    <div class="w-full max-w-sm bg-slate-900 border border-slate-800 p-6 rounded-2xl shadow-2xl">
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold text-emerald-400">Vera Admin</h1>
            <p class="text-xs text-slate-400 mt-1">Sign in to manage platform</p>
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="mb-4 bg-red-950/50 border border-red-500 text-red-300 text-xs p-3 rounded-xl">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST">
            <div class="mb-4">
                <label class="block text-xs text-slate-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 outline-none focus:border-emerald-500">
            </div>
            <div class="mb-6">
                <label class="block text-xs text-slate-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-3 text-xs text-slate-200 outline-none focus:border-emerald-500">
            </div>
            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs py-3.5 rounded-xl transition">
                Login
            </button>
        </form>
    </div>
</body>
</html>
