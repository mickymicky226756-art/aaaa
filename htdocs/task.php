<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];$message = "";
$msg_type = "";

// 1. ተጠቃሚው የራሱን ሪፈራል ኮድ እንድናውቅ
$stmt_u =$pdo->prepare("SELECT referral_code FROM users WHERE id = ?");
$stmt_u->execute([$user_id]);
$current_user =$stmt_u->fetch(PDO::FETCH_ASSOC);
$my_ref_code =$current_user['referral_code'] ?? '';

// 2. የ Level 1 (Direct Referrals) ሰዎችን ጠቅላላ ሪቻርጅ (Recharge) ማሰላት
$total_lv1_recharge = 0.00;
if (!empty($my_ref_code)) {
    // Level 1 ተጠቃሚዎችን ማምጣት
    $stmt_lv1 =$pdo->prepare("SELECT id FROM users WHERE referred_by = ?");
    $stmt_lv1->execute([$my_ref_code]);
    $lv1_users =$stmt_lv1->fetchAll(PDO::FETCH_ASSOC);

    if (count($lv1_users) > 0) {$lv1_ids = [];
        foreach ($lv1_users as$u) {
            $lv1_ids[] =$u['id'];
        }

        // የ Lv1 ሰዎች የገዙት ምርት ጠቅላላ ዋጋ (Recharge/Investment sum)
        $placeholders = implode(',', array_fill(0, count($lv1_ids), '?'));
        $stmt_sum =$pdo->prepare("SELECT SUM(price) as total_recharge FROM my_products WHERE user_id IN ($placeholders)");
        $stmt_sum->execute($lv1_ids);
        $res_sum =$stmt_sum->fetch(PDO::FETCH_ASSOC);
        $total_lv1_recharge = floatval($res_sum['total_recharge'] ?? 0);
    }
}

// 3. ሽልማት የመጠየቅ (Claim) ሎጂክ
if (isset($_POST['claim_task_id'])) {
    $task_id = intval($_POST['claim_task_id']);

    // ታስኩን ማምጣት
    $stmt_t =$pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt_t->execute([$task_id]);
    $task =$stmt_t->fetch(PDO::FETCH_ASSOC);

    if ($task) {
        // ቀድሞ መወሰዱን ማረጋገጥ
        $stmt_chk =$pdo->prepare("SELECT id FROM user_claimed_tasks WHERE user_id = ? AND task_id = ?");
        $stmt_chk->execute([$user_id,$task_id]);

        if ($stmt_chk->rowCount() > 0) {$message = "ይህንን ሽልማት ቀድመው ወስደዋል!";
            $msg_type = "error";
        } elseif ($total_lv1_recharge < $task['target_amount']) {$message = "የዚህን ታስክ ግብ ገና አልደረሱበትም!";
            $msg_type = "error";
        } else {
            try {
                $pdo->beginTransaction();

                // ዎችን balance መጨመር
                $stmt_bal =$pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $stmt_bal->execute([$task['reward_amount'],$user_id]);

                // የተወሰደ መሆኑን መመዝገብ
                $stmt_ins =$pdo->prepare("INSERT INTO user_claimed_tasks (user_id, task_id) VALUES (?, ?)");
                $stmt_ins->execute([$user_id,$task_id]);

                $pdo->commit();$message = "እንኳን ደስ አለዎት! ሽልማቱ አካውንትዎ ገብቷል።";
                $msg_type = "success";
            } catch (Exception $e) {
                $pdo->rollBack();$message = "ስህተት ተፈጥሯል, እባክዎ እንደገና ይሞክሩ።";
                $msg_type = "error";
            }
        }
    }
}

// 4. ሁሉንም ታስኮች እና የተጠቃሚውን ክሌም ሁኔታ ማምጣት
$tasks_stmt =$pdo->query("SELECT * FROM tasks ORDER BY target_amount ASC");
$all_tasks =$tasks_stmt->fetchAll(PDO::FETCH_ASSOC);

$claimed_tasks_ids = [];
$stmt_cl =$pdo->prepare("SELECT task_id FROM user_claimed_tasks WHERE user_id = ?");
$stmt_cl->execute([$user_id]);
while ($row =$stmt_cl->fetch(PDO::FETCH_ASSOC)) {
    $claimed_tasks_ids[] =$row['task_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Task Rewards</title>
    <!-- Tailwind CSS for Modern Styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; -webkit-overflow-scrolling: touch; }
    </style>
    <script>
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
        
        <!-- Main Content -->
        <div class="flex-grow p-4 pb-32 overflow-y-auto">
            
            <!-- Header (VERA) -->
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-extrabold text-slate-200 tracking-wider">VERA</h2>
                    <p class="text-[11px] text-slate-400 font-medium">Invite Level 1 users to recharge and earn cash rewards</p>
                </div>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            </div>

            <?php if (!empty($message)): ?>
                <div class="p-3 mb-4 rounded-xl text-xs font-bold text-center <?= $msg_type == 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <!-- Tasks List -->
            <div class="space-y-4">
                <?php foreach ($all_tasks as$t): 
                    $target = floatval($t['target_amount']);
                    $reward = floatval($t['reward_amount']);
                    
                    // Progress percentage calculation
                    $percent = ($total_lv1_recharge / $target) * 100;
                    if ($percent > 100)$percent = 100;

                    $is_claimed = in_array($t['id'], $claimed_tasks_ids);$is_completed = $total_lv1_recharge >=$target;
                ?>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-lg">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="text-xs font-bold text-slate-200"><?= htmlspecialchars($t['title']) ?></h3>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-500 block font-medium">Mission reward</span>
                                <span class="text-xs font-black text-emerald-400">ETB <?= number_format($reward, 0) ?></span>
                            </div>
                        </div>

                        <!-- Progress Bar Info -->
                        <div class="flex justify-between text-[11px] text-slate-400 mb-1 font-medium">
                            <span>ETB <?= number_format($total_lv1_recharge, 0) ?></span>
                            <span>ETB <?= number_format($target, 0) ?></span>
                        </div>
                        <div class="w-full bg-slate-950 h-2 rounded-full overflow-hidden mb-2 border border-slate-800">
                            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" style="width: <?= $percent ?>%;"></div>
                        </div>
                        <div class="text-right text-[10px] font-bold text-slate-500 mb-3">
                            <?= number_format($percent, 2) ?>%
                        </div>

                        <!-- Action Button -->
                        <form action="task.php" method="POST">
                            <input type="hidden" name="claim_task_id" value="<?= $t['id'] ?>">
                            <?php if ($is_claimed): ?>
                                <button type="button" disabled class="w-full bg-slate-950 text-slate-600 text-xs font-bold py-2.5 rounded-xl cursor-not-allowed border border-slate-800">
                                    Claimed
                                </button>
                            <?php elseif ($is_completed): ?>
                                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold py-2.5 rounded-xl transition shadow-lg shadow-emerald-500/10">
                                    Claim Reward
                                </button>
                            <?php else: ?>
                                <button type="button" disabled class="w-full bg-slate-950 text-slate-600 text-xs font-bold py-2.5 rounded-xl cursor-not-allowed border border-slate-800">
                                    Locked
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-between items-center text-[10px] font-bold text-slate-400 z-50">
            <a href="home.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-house text-base"></i>
                <span>Home</span>
            </a>
            <a href="my_product.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-layer-group text-base"></i>
                <span>Product</span>
            </a>
            <a href="task.php" onclick="navigate(event, this.href)" class="flex flex-col items-center text-emerald-400 transition gap-1">
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
