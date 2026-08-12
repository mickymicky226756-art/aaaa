<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Recharge</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        // Force redirect to home.php and prevent going back to unwanted history states
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
            window.location.replace('home.php');
        });
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 pb-10">
        <!-- Header -->
        <div class="p-4 border-b border-slate-800 flex items-center">
            <a href="home.php" class="text-slate-400 mr-4"><i class="fa-solid fa-arrow-left"></i></a>
            <h2 class="font-bold text-sm">Recharge Balance</h2>
        </div>

        <div class="p-5">
            <p class="text-xs text-slate-400 mb-4">Step 1: Select or enter recharge amount</p>

            <!-- Amount Grid -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <?php 
                $amounts = [750, 1580, 3570, 7680, 13570, 20700, 34900, 77500, 128000, 214000];
                foreach ($amounts as $amt) {
                    echo "<button type=\"button\" onclick=\"selectAmount('$amt')\" class=\"bg-slate-900 border border-slate-700 hover:border-emerald-500 hover:bg-emerald-950/30 py-3.5 rounded-xl text-xs font-bold transition text-slate-200\">$amt</button>";
                }
                ?>
            </div>

            <!-- Custom Input -->
            <div class="mb-8">
                <label class="text-xs text-slate-400 mb-2 block">If you want to recharge an amount greater than 750 ETB, you can use the custom input below</label>
                <input type="number" id="customAmount" placeholder="Enter amount" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-slate-100 outline-none focus:border-emerald-500">
            </div>

            <!-- Next Button -->
            <button onclick="proceedToStep2()" class="w-full bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold py-3.5 rounded-xl text-xs transition shadow-lg shadow-emerald-500/20">
                Next Step <i class="fa-solid fa-arrow-right ml-2 text-[10px]"></i>
            </button>
        </div>
    </div>

    <script>
        function selectAmount(amt) {
            document.getElementById('customAmount').value = amt;
        }

        function proceedToStep2() {
            let finalAmount = document.getElementById('customAmount').value;
            if (finalAmount < 750) {
                alert("Minimum recharge amount is 750 ETB");
                return;
            }
            window.location.href = "recharge_step2.php?amount=" + finalAmount;
        }
    </script>
</body>
</html>
