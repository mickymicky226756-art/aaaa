<?php
session_start();
include '../db.php';
date_default_timezone_set('Africa/Addis_Ababa');

// አድሚን መሆኑን ማረጋገጫ
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// packages ቴብል መኖሩን ማረጋገጫ እና ከሌለ በራሱ መፍጠር
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `packages` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL,
      `price` decimal(10,2) NOT NULL,
      `daily_income` decimal(10,2) NOT NULL,
      `cycle_days` int(11) NOT NULL,
      `total_income` decimal(10,2) NOT NULL,
      `status` varchar(20) DEFAULT 'buy_now',
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // ቴብሉባዶ ከሆነ ነባሪ መረጃዎችን መሙላት
    $count = $pdo->query("SELECT COUNT(*) FROM packages")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO `packages` (`id`, `name`, `price`, `daily_income`, `cycle_days`, `total_income`, `status`) VALUES
        (1, 'Vera 1', 750.00, 120.00, 160, 19200.00, 'buy_now'),
        (2, 'Vera 2', 1580.00, 220.00, 150, 33000.00, 'buy_now'),
        (3, 'Vera 3', 3570.00, 520.00, 140, 72800.00, 'buy_now'),
        (4, 'Vera 4', 7680.00, 1200.00, 130, 156000.00, 'buy_now'),
        (5, 'Vera 5', 13570.00, 2300.00, 120, 276000.00, 'buy_now'),
        (6, 'Vera 6', 20700.00, 4600.00, 110, 506000.00, 'buy_now'),
        (7, 'Vera 7', 34900.00, 10000.00, 100, 1000000.00, 'buy_now'),
        (8, 'Vera 8', 77500.00, 29000.00, 90, 2610000.00, 'buy_now'),
        (9, 'Vera 9', 128000.00, 65000.00, 80, 5200000.00, 'buy_now'),
        (10, 'Vera 10', 214000.00, 130000.00, 70, 9100000.00, 'buy_now');");
    }
} catch (PDOException $e) {
    // ችግር ካለ ዝም ይላል
}

// የፓኬጅ መረጃዎችን ማስተካከል (Update Package)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_package') {
    $plan_id = intval($_POST['plan_id'] ?? 0);
    $name = $_POST['name'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $daily_income = floatval($_POST['daily_income'] ?? 0);
    $cycle_days = intval($_POST['cycle_days'] ?? 0);
    $total_income = floatval($_POST['total_income'] ?? 0);
    $status = $_POST['status'] ?? 'buy_now';

    if ($plan_id > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE packages SET name = ?, price = ?, daily_income = ?, cycle_days = ?, total_income = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $price, $daily_income, $cycle_days, $total_income, $status, $plan_id]);
            $success = "ፓኬጁ በተሳካ ሁኔታ ተስተካክሏል!";
        } catch (PDOException $e) {
            $error = "የዳታቤዝ ስህተት: " . $e->getMessage();
        }
    } else {
        $error = "ልክ ያልሆነ የፓኬጅ መለያ (ID)።";
    }
}

// ሁሉንም ፓኬጆች ከዳታቤዝ ማምጣት
try {
    $packages = $pdo->query("SELECT * FROM packages ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
    $error = "ፓኬጆቹን ማምጣት አልተቻለም።";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin - VIP Packages Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; touch-action: manipulation; -webkit-overflow-scrolling: touch; }</style>
    <script>
        function openEditModal(pkg) {
            document.getElementById('editPlanId').value = pkg.id;
            document.getElementById('editName').value = pkg.name;
            document.getElementById('editPrice').value = pkg.price;
            document.getElementById('editDaily').value = pkg.daily_income;
            document.getElementById('editDays').value = pkg.cycle_days;
            document.getElementById('editTotal').value = pkg.total_income;
            document.getElementById('editStatus').value = pkg.status;
            
            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900">
        <div class="flex-grow p-4 pb-32 overflow-y-auto">
            
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-5 rounded-3xl mb-5 shadow-lg shadow-emerald-500/10 flex justify-between items-center text-slate-950">
                <div>
                    <p class="text-[11px] text-emerald-100 font-bold uppercase tracking-wider">Admin Panel</p>
                    <h2 class="text-base font-black mt-0.5 text-white">Vera 1 - Vera 10 Manager</h2>
                </div>
                <a href="dashboard.php" class="bg-slate-950/20 border border-white/20 text-white text-xs font-bold px-3.5 py-2 rounded-xl hover:bg-slate-950/30 transition shadow-sm backdrop-blur-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="mb-4 bg-emerald-950/80 border border-emerald-500/30 text-emerald-300 text-xs p-3 rounded-2xl flex items-center shadow-lg">
                    <i class="fa-solid fa-circle-check mr-2 text-base"></i>
                    <span><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-4 bg-red-950/80 border border-red-500/30 text-red-300 text-xs p-3 rounded-2xl flex items-center shadow-lg">
                    <i class="fa-solid fa-triangle-exclamation mr-2 text-base"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <div class="flex items-center space-x-2 mb-4">
                <i class="fa-solid fa-star text-amber-400 animate-pulse text-base"></i>
                <h3 class="text-sm font-extrabold text-slate-200 tracking-wide">All VIP Packages (1 to 10)</h3>
            </div>

            <div class="space-y-3">
                <?php if (empty($packages)): ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center text-slate-400 text-xs">
                        ምንም ፓኬጆች አልተገኙም።
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $p): ?>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg flex flex-col justify-between hover:border-slate-700 transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-200 mb-1"><?= htmlspecialchars($p['name']) ?></h4>
                                    <div class="space-y-0.5 text-[10px] text-slate-400 font-medium">
                                        <p>Price: <span class="text-emerald-400 font-bold"><?= number_format($p['price'], 2) ?> ETB</span></p>
                                        <p>Cycle: <span class="text-slate-300"><?= $p['cycle_days'] ?> Days</span></p>
                                        <p>Daily: <span class="text-emerald-400 font-bold"><?= number_format($p['daily_income'], 2) ?> ETB</span></p>
                                        <p>Total: <span class="text-emerald-400 font-bold"><?= number_format($p['total_income'], 2) ?> ETB</span></p>
                                    </div>
                                </div>
                                <div>
                                    <?php if (($p['status'] ?? 'buy_now') === 'coming_soon'): ?>
                                        <span class="bg-amber-500/10 border border-amber-500/20 text-amber-400 px-2.5 py-1 rounded-xl text-[9px] font-bold uppercase tracking-wider block text-center">
                                            Coming Soon
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-2.5 py-1 rounded-xl text-[9px] font-bold uppercase tracking-wider block text-center">
                                            Buy Now
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button onclick='openEditModal(<?= json_encode($p) ?>)' class="w-full bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-black py-2 rounded-xl transition border border-slate-700 flex items-center justify-center gap-2">
                                <i class="fa-solid fa-pen-to-square text-emerald-400"></i> Edit Package
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

        <!-- Edit Package Modal -->
        <div id="editModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden justify-center items-center z-50 p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 w-full max-w-xs shadow-2xl space-y-4">
                <div class="flex justify-between items-center border-b border-slate-800 pb-3">
                    <h3 class="text-xs font-bold text-slate-200"><i class="fa-solid fa-pen-to-square text-emerald-400 mr-1"></i> Edit VIP Package</h3>
                    <button onclick="closeEditModal()" class="text-slate-400 hover:text-white text-xs"><i class="fa-solid fa-xmark text-sm"></i></button>
                </div>
                
                <form action="" method="POST" class="space-y-3">
                    <input type="hidden" name="action" value="update_package">
                    <input type="hidden" name="plan_id" id="editPlanId">
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Package Name</label>
                        <input type="text" name="name" id="editName" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Price (ETB)</label>
                            <input type="number" step="0.01" name="price" id="editPrice" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Daily Income</label>
                            <input type="number" step="0.01" name="daily_income" id="editDaily" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Cycle Days</label>
                            <input type="number" name="cycle_days" id="editDays" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Total Income</label>
                            <input type="number" step="0.01" name="total_income" id="editTotal" required class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Status</label>
                        <select name="status" id="editStatus" class="w-full bg-slate-950 border border-slate-800 rounded-xl p-2.5 text-xs text-slate-200 outline-none focus:border-emerald-500">
                            <option value="buy_now">Buy Now</option>
                            <option value="coming_soon">Coming Soon</option>
                        </select>
                    </div>

                    <div class="flex space-x-2 pt-2">
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold py-2.5 rounded-xl transition">Cancel</button>
                        <button type="submit" class="flex-1 bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black py-2.5 rounded-xl transition shadow-lg shadow-emerald-500/10">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-around items-center text-[10px] font-bold text-slate-400 z-40">
            <a href="dashboard.php" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-chart-pie text-base"></i>
                <span>Dashboard</span>
            </a>
            <a href="users.php" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-users text-base"></i>
                <span>Users</span>
            </a>
            <a href="vip.php" class="flex flex-col items-center text-emerald-400 transition gap-1">
                <i class="fa-solid fa-star text-base"></i>
                <span>VIP Plans</span>
            </a>
        </div>

    </div>

</body>
</html>
