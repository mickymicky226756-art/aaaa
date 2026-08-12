<?php
session_start();
include 'db.php';
date_default_timezone_set('Africa/Addis_Ababa'); // GMT+03:00 East Africa Time

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'product';

// --- AUTOMATED 24-HOUR DAILY INCOME CLAIM LOGIC ---
try {
    // Fetch active products for this user
    $stmt_prods = $pdo->prepare("SELECT * FROM my_products WHERE user_id = ?");
    $stmt_prods->execute([$user_id]);
    $user_products = $stmt_prods->fetchAll(PDO::FETCH_ASSOC);

    $current_time = time();

    foreach ($user_products as $prod) {
        $last_income_time = strtotime($prod['last_income_date']);
        // Check if 24 hours (86400 seconds) have passed since the last income
        if (($current_time - $last_income_time) >= 86400) {
            $daily_income = floatval($prod['daily_income']);
            $product_id = $prod['id'];

            // Start transaction for safety
            $pdo->beginTransaction();

            // 1. Add daily income to user balance
            $update_balance = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $update_balance->execute([$daily_income, $user_id]);

            // 2. Update last_income_date to current time
            $new_income_date = date('Y-m-d H:i:s');
            $update_prod = $pdo->prepare("UPDATE my_products SET last_income_date = ? WHERE id = ?");
            $update_prod->execute([$new_income_date, $product_id]);

            // 3. Insert record into income_history table (create table if not exists safely)
            $pdo->exec("CREATE TABLE IF NOT EXISTS income_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                amount DECIMAL(10,2),
                income_date DATETIME
            )");

            $insert_history = $pdo->prepare("INSERT INTO income_history (user_id, amount, income_date) VALUES (?, ?, ?)");
            $insert_history->execute([$user_id, $daily_income, $new_income_date]);

            $pdo->commit();
        }
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
// ---------------------------------------------------

// Fetch user's active products
$products_stmt = $pdo->prepare("SELECT * FROM my_products WHERE user_id = ? ORDER BY purchase_date DESC");
$products_stmt->execute([$user_id]);
$my_products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch daily income history
$income_history = [];
try {
    $income_stmt = $pdo->prepare("SELECT * FROM income_history WHERE user_id = ? ORDER BY income_date DESC");
    $income_stmt->execute([$user_id]);
    $income_history = $income_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not be created yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VERA - My Products</title>
    <!-- Tailwind CSS for Modern Styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; -webkit-overflow-scrolling: touch; }
    </style>
    <script>
        // Prevent form resubmission and back button multi-click glitch
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        function navigate(event, url) {
            event.preventDefault();
            window.location.replace(url);
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <!-- Mobile Container Frame -->
    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900">

        <!-- Main Content Area -->
        <div class="flex-grow p-4 pb-32 overflow-y-auto">
            
            <!-- Header (VERA Theme) -->
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-200 tracking-wider">VERA</h2>
                    <p class="text-[11px] text-slate-400 font-medium">Your active packages and automated daily income</p>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>

            <!-- Top Action Buttons (Product & Daily Income Toggles) -->
            <div class="grid grid-cols-2 gap-3 mb-5">
                <a href="my_product.php?tab=product" onclick="navigate(event, this.href)" class="<?= $tab == 'product' ? 'bg-emerald-500 text-slate-950 font-black shadow-lg shadow-emerald-500/10' : 'bg-slate-900 text-slate-400 border border-slate-800 hover:text-slate-200' ?> text-xs font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-box"></i> Product
                </a>
                <a href="my_product.php?tab=income" onclick="navigate(event, this.href)" class="<?= $tab == 'income' ? 'bg-emerald-500 text-slate-950 font-black shadow-lg shadow-emerald-500/10' : 'bg-slate-900 text-slate-400 border border-slate-800 hover:text-slate-200' ?> text-xs font-bold py-2.5 rounded-xl transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-coins"></i> Daily Income
                </a>
            </div>
            
            <!-- Dynamic Content Based on Selected Tab -->
            <div class="space-y-4">

                <?php if ($tab == 'product'): ?>
                    <!-- PRODUCTS TAB CONTENT -->
                    <?php if (count($my_products) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($my_products as $prod): ?>
                                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex flex-col justify-between">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h3 class="text-xs font-bold text-slate-200"><?= htmlspecialchars($prod['plan_name']) ?></h3>
                                            <p class="text-[10px] text-slate-500 font-medium">Purchased: <span class="text-slate-300 font-semibold"><?= $prod['purchase_date'] ?> (EAT)</span></p>
                                        </div>
                                        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">
                                            Active
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-slate-800 text-center">
                                        <div>
                                            <p class="text-[10px] text-slate-500 font-medium">Price</p>
                                            <p class="text-xs font-bold text-slate-200"><?= number_format($prod['price'], 2) ?> ETB</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500 font-medium">Daily Income</p>
                                            <p class="text-xs font-black text-emerald-400"><?= number_format($prod['daily_income'], 2) ?> ETB</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-slate-500 font-medium">Last Credited</p>
                                            <p class="text-[10px] font-bold text-amber-400"><?= $prod['last_income_date'] ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State for Products -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl text-center py-12">
                            <i class="fa-solid fa-box-open text-slate-600 text-4xl mb-3"></i>
                            <h3 class="text-xs font-bold text-slate-300 mb-1">No Active Products</h3>
                            <p class="text-[11px] text-slate-500 mb-4 font-medium">You haven't purchased any investment packages yet.</p>
                            <a href="home.php" onclick="navigate(event, this.href)" class="inline-block bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold px-4 py-2.5 rounded-xl transition shadow-lg shadow-emerald-500/10">
                                View Packages
                            </a>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <!-- DAILY INCOME TAB CONTENT -->
                    <?php if (count($income_history) > 0): ?>
                        <div class="space-y-3">
                            <?php foreach ($income_history as $inc): ?>
                                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl flex justify-between items-center shadow-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400">
                                            <i class="fa-solid fa-arrow-down-long text-sm"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-bold text-slate-200">Daily Income Credited</h4>
                                            <p class="text-[10px] text-slate-500 font-medium"><?= $inc['income_date'] ?> (EAT)</p>
                                        </div>
                                    </div>
                                    <span class="text-xs font-black text-emerald-400">+<?= number_format($inc['amount'], 2) ?> ETB</span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Empty State for Income -->
                        <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl text-center py-12">
                            <i class="fa-solid fa-coins text-slate-600 text-4xl mb-3"></i>
                            <h3 class="text-xs font-bold text-slate-300 mb-1">No Income History Yet</h3>
                            <p class="text-[11px] text-slate-500 mb-4 font-medium">Your 24-hour automated daily income records will appear here.</p>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

            </div>

        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-between items-center text-[10px] font-bold text-slate-400 z-50">
            <a href="home.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-house text-base"></i>
                <span>Home</span>
            </a>
            <a href="my_product.php" onclick="navigate(event, this.href)" class="flex flex-col items-center text-emerald-400 transition gap-1">
                <i class="fa-solid fa-layer-group text-base"></i>
                <span>Product</span>
            </a>
            <a href="task.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-tasks text-base"></i>
                <span>Task</span>
            </a>
            <a href="team.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-user-group text-base"></i>
                <span>Team</span>
            </a>
            <a href="me.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-user text-base"></i>
                <span>Me</span>
            </a>
        </div>

    </div>

</body>
</html>
