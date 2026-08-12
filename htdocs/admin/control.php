<?php
session_start();
include '../db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vera - Control Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 p-4 md:p-8">
    <div class="max-w-xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-base font-bold text-blue-400">Control Panel</h1>
            <a href="dashboard.php" class="text-xs text-slate-400 hover:text-white"><i class="fa-solid fa-arrow-left mr-1"></i> Back</a>
        </div>

        <div class="bg-slate-900 border border-slate-800 p-5 rounded-2xl shadow-xl space-y-4">
            <p class="text-xs text-slate-300">Global system settings and configuration parameters can be managed here.</p>
            <div class="border-t border-slate-800 pt-4 flex justify-between items-center">
                <span class="text-xs text-slate-400">Platform Maintenance Mode</span>
                <span class="bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] px-3 py-1 rounded-full font-bold">Active</span>
            </div>
        </div>
    </div>
</body>
</html>
