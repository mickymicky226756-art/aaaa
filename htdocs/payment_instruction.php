<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$amount = $_GET['amount'] ?? 570.00;
$bank = $_GET['bank'] ?? 'Commercial Bank of Ethiopia';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $pay_amount = $_POST['amount'] ?? 0;
    $payment_reference = trim($_POST['payment_reference'] ?? '');

    if (!empty($payment_reference)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM recharges WHERE payment_reference = ?");
            $stmt->execute([$payment_reference]);
            
            if ($stmt->rowCount() > 0) {
                $error_msg = "This transaction reference or SMS has already been used!";
            } else {
                $insert = $pdo->prepare("INSERT INTO recharges (user_id, amount, payment_reference, status) VALUES (?, ?, ?, 'processing')");
                if ($insert->execute([$user_id, $pay_amount, $payment_reference])) {
                    // ክፍያው ሲሳካ ወደ me.php እንዲሄድ እና የ back ቁልፍ ወደ ኋላ እንዳይመልስ አድርጎ ያስቀምጣል
                    header("Location: transaction_history.php");
                    exit();
                } else {
                    $error_msg = "An error occurred. Please try again.";
                }
            }
        } catch (Exception $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    } else {
        $error_msg = "Please enter valid payment reference or SMS.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Payment Instruction</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        // Force redirect to me.php and prevent going back to previous items
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
            window.location.replace('me.php');
        });

        // Copy to clipboard function
        function copyText(elementId, btnElement) {
            const textToCopy = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(textToCopy).then(() => {
                const originalText = btnElement.innerText;
                btnElement.innerText = 'Copied';
                btnElement.classList.add('bg-emerald-600', 'text-white');
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.classList.remove('bg-emerald-600', 'text-white');
                }, 1500);
            });
        }

        // Countdown Timer Logic (15 Minutes)
        let totalSeconds = 15 * 60;
        function updateTimer() {
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            
            document.getElementById('minTens').innerText = Math.floor(minutes / 10);
            document.getElementById('minUnits').innerText = minutes % 10;
            document.getElementById('secTens').innerText = Math.floor(seconds / 10);
            document.getElementById('secUnits').innerText = seconds % 10;

            if (totalSeconds > 0) {
                totalSeconds--;
            }
        }
        setInterval(updateTimer, 1000);
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 pb-10 flex flex-col justify-between">
        
        <div>
            <!-- Top Bar with Timer -->
            <div class="p-4 border-b border-slate-800 flex justify-between items-center bg-slate-900/50">
                <div class="flex items-center">
                    <a href="me.php" class="text-slate-400 mr-3"><i class="fa-solid fa-arrow-left"></i></a>
                    <span class="text-xs font-bold text-slate-200">Mobile App</span>
                </div>
                <!-- Countdown Timer Boxes -->
                <div class="flex items-center space-x-1 text-[11px] text-slate-300">
                    <span class="text-[10px] text-slate-400 mr-1">Order Remaining</span>
                    <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded font-mono font-bold text-emerald-400" id="minTens">2</span>
                    <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded font-mono font-bold text-emerald-400" id="minUnits">9</span>
                    <span class="text-slate-400 font-bold">:</span>
                    <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded font-mono font-bold text-emerald-400" id="secTens">2</span>
                    <span class="bg-slate-800 border border-slate-700 px-1.5 py-0.5 rounded font-mono font-bold text-emerald-400" id="secUnits">5</span>
                    <span class="text-[10px] text-slate-400 ml-0.5">Sec</span>
                </div>
            </div>

            <!-- Main Container -->
            <div class="p-4">
                
                <?php if (!empty($error_msg)): ?>
                    <div class="mb-4 bg-red-950/50 border border-red-500 text-red-300 text-xs p-3 rounded-xl">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <!-- Step 1 Title -->
                <p class="text-xs font-bold text-slate-300 mb-3 flex items-center">
                    <span class="bg-slate-800 text-slate-200 px-2 py-0.5 rounded mr-2 text-[11px]">Step 1</span> Copy account for payment
                </p>

                <!-- Payment Details Card -->
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 mb-4 shadow-lg">
                    
                    <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-800/80">
                        <span class="text-xs text-slate-400 font-medium">Order Amount</span>
                        <span class="text-lg font-extrabold text-purple-300">ETB <?= number_format($amount, 2) ?></span>
                    </div>

                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <p class="text-[10px] text-slate-500">Payment Channel</p>
                            <p id="bankName" class="text-xs font-bold text-slate-200"><?= htmlspecialchars($bank) ?></p>
                        </div>
                        <a href="recharge_step2.php?amount=<?= $amount ?>" class="border border-slate-700 text-slate-300 text-[10px] px-3 py-1 rounded-full hover:bg-slate-800 transition">switch</a>
                    </div>

                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <p class="text-[10px] text-slate-500">Account Name</p>
                            <p id="accName" class="text-xs font-bold text-slate-200">Hulgizie</p>
                        </div>
                        <button onclick="copyText('accName', this)" class="border border-slate-700 text-slate-300 text-[10px] px-3 py-1 rounded-full hover:bg-slate-800 transition">copy</button>
                    </div>

                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-[10px] text-slate-500">Account Number</p>
                            <p id="accNum" class="text-xs font-bold text-slate-200 font-mono tracking-wider">1000365219797</p>
                        </div>
                        <button onclick="copyText('accNum', this)" class="border border-slate-700 text-slate-300 text-[10px] px-3 py-1 rounded-full hover:bg-slate-800 transition">copy</button>
                    </div>

                </div>

                <!-- Instructions -->
                <div class="text-[11px] text-amber-300/90 space-y-1 mb-5 bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl leading-relaxed">
                    <p class="font-semibold text-amber-200">After payment, check transaction reference starting with: "v2-" or "FT"</p>
                    <p>🔹 v2-hf = Enter 23 digit code</p>
                    <p>🔹 FT = Enter 12 digit code</p>
                </div>

                <!-- Step 2 Title -->
                <p class="text-xs font-bold text-slate-300 mb-2 flex items-center">
                    <span class="bg-slate-800 text-slate-200 px-2 py-0.5 rounded mr-2 text-[11px]">Step 2</span> Paste payment SMS or enter v2- or FT
                </p>

                <!-- Form -->
                <form action="" method="POST">
                    <input type="hidden" name="amount" value="<?= $amount ?>">
                    
                    <div class="mb-4">
                        <textarea name="payment_reference" rows="4" required placeholder="Paste payment message or enter reference (FT or v2-):&#10;&#10;https://mbreciept.cbe.com.et/v2-hfHCxFT8tBhc0zxe95aa" class="w-full bg-slate-900 border border-slate-800 focus:border-purple-500 rounded-xl p-3 text-xs text-slate-200 placeholder-slate-600 outline-none resize-none"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-[#8c788e] hover:bg-[#7b677d] text-white text-xs font-bold py-3.5 rounded-xl transition shadow-lg tracking-wider">
                        Submit
                    </button>
                </form>

            </div>
        </div>

        <!-- Warning Bottom Box -->
        <div class="px-4 mt-4">
            <div class="bg-red-950/30 border border-red-500/30 p-3 rounded-xl flex items-start space-x-2 text-[10px] text-red-300">
                <i class="fa-solid fa-triangle-exclamation text-red-400 mt-0.5"></i>
                <p>⚠️ Do not save wrong numbers! Works with real transactions only. Unauthorized transfers are not allowed.</p>
            </div>
        </div>

    </div>

</body>
</html>
