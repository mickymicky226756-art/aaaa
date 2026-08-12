<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. የተጠቃሚውን መረጃ እና የራሱን የሪፈራል ኮድ እናምጣ
$stmt = $pdo->prepare("SELECT referral_code, balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. ተጠቃሚው ገና የሪፈራል ኮድ ከሌለው አዲስ እንፍጠርለት
if (empty($user['referral_code'])) {
    $new_ref_code = "VERA" . $user_id . rand(100, 999);
    $update = $pdo->prepare("UPDATE users SET referral_code = ? WHERE id = ?");
    $update->execute([$new_ref_code, $user_id]);
    $ref_code = $new_ref_code;
} else {
    $ref_code = $user['referral_code'];
}

// 3. አዲሱን ዶሜይን በመጠቀም ሊንኩን እናዘጋጅ
$referral_link = "https://ww.is-best.net/signup.php?ref=" . urlencode($ref_code);

// 4. የማልቲ ሌቭል (Multi-level) ስታቲስቲክስ እና ኮሚሽን ስሌት
$total_members = 0;
$active_members = 0;
$team_recharge = 0.00;

$lv1_count = 0; $lv1_commission = 0.00;
$lv2_count = 0; $lv2_commission = 0.00;
$lv3_count = 0; $lv3_commission = 0.00;

try {
    // --- Level 1 (Direct Referrals) ---
    $stmt_lv1 = $pdo->prepare("SELECT id, referral_code FROM users WHERE referred_by = ?");
    $stmt_lv1->execute([$ref_code]);
    $lv1_users_data = $stmt_lv1->fetchAll(PDO::FETCH_ASSOC);
    $lv1_count = count($lv1_users_data);

    if ($lv1_count > 0) {
        $lv1_ids = [];
        $lv1_codes = [];
        foreach ($lv1_users_data as $row) {
            $lv1_ids[] = $row['id'];
            if (!empty($row['referral_code'])) {
                $lv1_codes[] = $row['referral_code'];
            }
        }

        // Lv1 Active users & commission (20%)
        if (!empty($lv1_ids)) {
            $placeholders = implode(',', array_fill(0, count($lv1_ids), '?'));
            $stmt_lv1_act = $pdo->prepare("
                SELECT mp.user_id, SUM(mp.price) as total_spent 
                FROM my_products mp 
                WHERE mp.user_id IN ($placeholders) 
                GROUP BY mp.user_id
            ");
            $stmt_lv1_act->execute($lv1_ids);
            $lv1_buyers = $stmt_lv1_act->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($lv1_buyers as $buyer) {
                $active_members++;
                $team_recharge += $buyer['total_spent'];
                $lv1_commission += ($buyer['total_spent'] * 0.20);
            }
        }

        // --- Level 2 ---
        if (!empty($lv1_codes)) {
            $ph_codes_1 = implode(',', array_fill(0, count($lv1_codes), '?'));
            $stmt_l2 = $pdo->prepare("SELECT id, referral_code FROM users WHERE referred_by IN ($ph_codes_1)");
            $stmt_l2->execute($lv1_codes);
            $lv2_users_data = $stmt_l2->fetchAll(PDO::FETCH_ASSOC);
            $lv2_count = count($lv2_users_data);

            $lv2_ids = [];
            $lv2_codes = [];
            foreach ($lv2_users_data as $row) {
                $lv2_ids[] = $row['id'];
                if (!empty($row['referral_code'])) {
                    $lv2_codes[] = $row['referral_code'];
                }
            }

            if (!empty($lv2_ids)) {
                $ph_l2 = implode(',', array_fill(0, count($lv2_ids), '?'));
                $stmt_lv2_act = $pdo->prepare("
                    SELECT mp.user_id, SUM(mp.price) as total_spent 
                    FROM my_products mp 
                    WHERE mp.user_id IN ($ph_l2) 
                    GROUP BY mp.user_id
                ");
                $stmt_lv2_act->execute($lv2_ids);
                $lv2_buyers = $stmt_lv2_act->fetchAll(PDO::FETCH_ASSOC);

                foreach ($lv2_buyers as $buyer) {
                    $active_members++;
                    $team_recharge += $buyer['total_spent'];
                    $lv2_commission += ($buyer['total_spent'] * 0.03);
                }

                // --- Level 3 ---
                if (!empty($lv2_codes)) {
                    $ph_codes_2 = implode(',', array_fill(0, count($lv2_codes), '?'));
                    $stmt_l3 = $pdo->prepare("SELECT id FROM users WHERE referred_by IN ($ph_codes_2)");
                    $stmt_l3->execute($lv2_codes);
                    $lv3_users_data = $stmt_l3->fetchAll(PDO::FETCH_ASSOC);
                    $lv3_count = count($lv3_users_data);

                    $lv3_ids = [];
                    foreach ($lv3_users_data as $row) {
                        $lv3_ids[] = $row['id'];
                    }

                    if (!empty($lv3_ids)) {
                        $ph_l3 = implode(',', array_fill(0, count($lv3_ids), '?'));
                        $stmt_lv3_act = $pdo->prepare("
                            SELECT mp.user_id, SUM(mp.price) as total_spent 
                            FROM my_products mp 
                            WHERE mp.user_id IN ($ph_l3) 
                            GROUP BY mp.user_id
                        ");
                        $stmt_lv3_act->execute($lv3_ids);
                        $lv3_buyers = $stmt_lv3_act->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($lv3_buyers as $buyer) {
                            $active_members++;
                            $team_recharge += $buyer['total_spent'];
                            $lv3_commission += ($buyer['total_spent'] * 0.02);
                        }
                    }
                }
            }
        }
    }

    $total_members = $lv1_count + $lv2_count + $lv3_count;
    $total_commission = $lv1_commission + $lv2_commission + $lv3_commission;

    // 5. ኮሚሽኑን ወደ ዋናው ባላንስ በራስ-ሰር መጨመር
    $stmt_check = $pdo->prepare("SELECT total_credited_commission FROM users WHERE id = ?");
    $stmt_check->execute([$user_id]);
    $db_user_info = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $already_credited = $db_user_info['total_credited_commission'] ?? 0.00;

    if ($total_commission > $already_credited) {
        $new_earnings = $total_commission - $already_credited;

        $update_bal = $pdo->prepare("UPDATE users SET balance = balance + ?, total_credited_commission = ? WHERE id = ?");
        $update_bal->execute([$new_earnings, $total_commission, $user_id]);
    }

} catch (Exception $e) {
    // Error handling silent
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Team & Referral</title>
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

        function showCustomAlert(message) {
            const modal = document.getElementById('customAlertModal');
            document.getElementById('customAlertMessage').innerText = message;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function closeCustomAlert() {
            const modal = document.getElementById('customAlertModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        function copyLink() {
            var copyText = document.getElementById("refLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); // ለሞባይል ስልኮች የሚረዳ

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(copyText.value).then(() => {
                    showCustomAlert("ስኬታማ! የሪፈራል ሊንኩ ተቀድቷል።");
                }).catch(err => {
                    fallbackCopyText(copyText.value);
                });
            } else {
                fallbackCopyText(copyText.value);
            }
        }

        function fallbackCopyText(text) {
            var textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";  
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCustomAlert("ስኬታማ! የሪፈራል ሊንኩ ተቀድቷል።");
                } else {
                    showCustomAlert("ሊንኩን መቅዳት አልተቻለም፣ እባክዎ በእጅ ይቅዱት።");
                }
            } catch (err) {
                showCustomAlert("ሊንኩን መቅዳት አልተቻለም፣ እባክዎ በእጅ ይቅዱት።");
            }
            document.body.removeChild(textArea);
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900">
        
        <div class="flex-grow p-4 pb-32 overflow-y-auto">
            
            <div class="mb-4">
                <h2 class="text-base font-extrabold text-slate-200">My Team</h2>
                <p class="text-[11px] text-slate-400 mb-3">Invite friends and earn multi-level commissions</p>
                
                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg">
                    <p class="text-[11px] text-slate-300 mb-1.5 font-semibold">Your Referral Link & Code</p>
                    <div class="flex gap-2">
                        <input type="text" id="refLink" readonly value="<?php echo htmlspecialchars($referral_link); ?>" class="bg-slate-950 border border-slate-800 text-xs text-emerald-400 font-mono px-3 py-2.5 rounded-xl w-full outline-none select-all shadow-inner">
                        <button onclick="copyLink()" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-4 text-xs font-extrabold rounded-xl transition shadow-lg shadow-emerald-500/10 cursor-pointer">Copy</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-3 text-center">
                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg">
                    <p class="text-[11px] text-slate-400 font-semibold">Total Team</p>
                    <h4 class="text-base font-black text-emerald-400 mt-1"><?= $total_members ?></h4>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg">
                    <p class="text-[11px] text-slate-400 font-semibold">Active Team</p>
                    <h4 class="text-base font-black text-blue-400 mt-1"><?= $active_members ?></h4>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-5 text-center">
                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg">
                    <p class="text-[11px] text-slate-400 font-semibold">Team Recharge</p>
                    <h4 class="text-xs font-extrabold text-amber-400 mt-1"><?= number_format($team_recharge, 2) ?> ETB</h4>
                </div>
                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg">
                    <p class="text-[11px] text-slate-400 font-semibold">Total Commission</p>
                    <h4 class="text-xs font-extrabold text-purple-400 mt-1"><?= number_format($total_commission, 2) ?> ETB</h4>
                </div>
            </div>

            <div class="space-y-3 mb-4">
                <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center">
                    <i class="fa-solid fa-layer-group text-emerald-400 mr-2"></i> Level Breakdown & Earnings
                </h3>

                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg flex items-center justify-between">
                    <div>
                        <span class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">Lv1 (20%)</span>
                        <p class="text-[11px] text-slate-400 mt-1.5">Direct Referrals: <span class="font-bold text-slate-200"><?= $lv1_count ?></span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 block">Earned Commission</span>
                        <span class="text-xs font-black text-emerald-400">+<?= number_format($lv1_commission, 2) ?> ETB</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg flex items-center justify-between">
                    <div>
                        <span class="bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">Lv2 (3%)</span>
                        <p class="text-[11px] text-slate-400 mt-1.5">Indirect Referrals: <span class="font-bold text-slate-200"><?= $lv2_count ?></span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 block">Earned Commission</span>
                        <span class="text-xs font-black text-blue-400">+<?= number_format($lv2_commission, 2) ?> ETB</span>
                    </div>
                </div>

                <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl shadow-lg flex items-center justify-between">
                    <div>
                        <span class="bg-purple-500/10 border border-purple-500/20 text-purple-400 text-[10px] font-bold px-2.5 py-1 rounded-lg">Lv3 (2%)</span>
                        <p class="text-[11px] text-slate-400 mt-1.5">Sub-indirect: <span class="font-bold text-slate-200"><?= $lv3_count ?></span></p>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-500 block">Earned Commission</span>
                        <span class="text-xs font-black text-purple-400">+<?= number_format($lv3_commission, 2) ?> ETB</span>
                    </div>
                </div>
            </div>

        </div>

        <div id="customAlertModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden justify-center items-center z-50 p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-xs text-center shadow-2xl">
                <h3 class="text-sm font-bold text-slate-200 mb-1">Notification</h3>
                <p id="customAlertMessage" class="text-xs text-slate-400 mb-5"></p>
                <button onclick="closeCustomAlert()" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-bold py-2.5 rounded-xl transition cursor-pointer">OK</button>
            </div>
        </div>

        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-between items-center text-[10px] font-bold text-slate-400 z-50">
            <a href="home.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-house text-base"></i>
                <span>Home</span>
            </a>
            <a href="my_product.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-layer-group text-base"></i>
                <span>Product</span>
            </a>
            <a href="task.php" onclick="navigate(event, this.href)" class="flex flex-col items-center hover:text-emerald-400 transition gap-1">
                <i class="fa-solid fa-tasks text-base"></i>
                <span>Task</span>
            </a>
            <a href="team.php" onclick="navigate(event, this.href)" class="flex flex-col items-center text-emerald-400 transition gap-1">
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
