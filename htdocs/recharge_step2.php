<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$amount = $_GET['amount'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Select Bank</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 pb-10">
        <!-- Header -->
        <div class="p-4 border-b border-slate-800 flex items-center">
            <a href="recharge.php" class="text-slate-400 mr-4"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="font-bold text-sm">Select Bank</h2>
        </div>

        <div class="p-5">
            <div class="mb-6 p-4 bg-slate-900 border border-slate-800 rounded-xl">
                <p class="text-[11px] text-slate-400">Recharge Amount</p>
                <h3 class="text-xl font-extrabold text-emerald-400"><?= number_format($amount) ?> ETB</h3>
            </div>

            <p class="text-xs text-slate-400 mb-4">Step 2: Select your bank to proceed</p>

            <!-- CBE Selection -->
            <button onclick="window.location.href='payment_instruction.php?amount=<?= $amount ?>&bank=CBE'" class="w-full bg-slate-900 border border-slate-700 hover:border-emerald-500 rounded-2xl p-4 flex items-center justify-between transition group">
                <div class="flex items-center">
                    <!-- CBE Logo Image from local folder -->
                    <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center p-1 mr-4 overflow-hidden">
                        <img src="image/cbe.jpg" alt="CBE" class="w-full h-full object-contain">
                    </div>
                    <div class="text-left">
                        <h4 class="text-sm font-bold text-slate-100">Commercial Bank of Ethiopia</h4>
                        <p class="text-[10px] text-slate-500">CBE Birr / Mobile Banking</p>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-right text-slate-600 group-hover:text-emerald-500 transition"></i>
            </button>
        </div>
    </div>
</body>
</html>
