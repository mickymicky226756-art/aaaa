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

// Fetch user data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch packages from database dynamically
try {
    $packages = $pdo->query("SELECT * FROM packages ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $packages = [];
}

// Fallback images array for the 10 packages if needed
$package_images = [
    1 => "https://images.unsplash.com/photo-1621416894569-0f39ed31d247?q=80&w=200&auto=format&fit=crop",
    2 => "https://images.unsplash.com/photo-1639762681485-074b7f938ba0?q=80&w=200&auto=format&fit=crop",
    3 => "https://images.unsplash.com/photo-1642543492481-44e81e3914a7?q=80&w=200&auto=format&fit=crop",
    4 => "https://images.unsplash.com/photo-1642104704074-907c0698cbd9?q=80&w=200&auto=format&fit=crop",
    5 => "https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?q=80&w=200&auto=format&fit=crop",
    6 => "https://images.unsplash.com/photo-1620712943543-bcc4688e7485?q=80&w=200&auto=format&fit=crop",
    7 => "https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?q=80&w=200&auto=format&fit=crop",
    8 => "https://images.unsplash.com/photo-1626544827763-d516dce335e2?q=80&w=200&auto=format&fit=crop",
    9 => "https://images.unsplash.com/photo-1639762681485-074b7f938ba0?q=80&w=200&auto=format&fit=crop",
    10 => "https://images.unsplash.com/photo-1642543492481-44e81e3914a7?q=80&w=200&auto=format&fit=crop"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VERA - Home Packages</title>
    <!-- Tailwind CSS for Modern Styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; touch-action: manipulation; -webkit-overflow-scrolling: touch; }

        /* Custom Floating Animation for Support Button */
        @keyframes floatUpDown {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }
        .floating-support {
            animation: floatUpDown 2.5s ease-in-out infinite;
        }

        /* Banner Slider Fade Animation */
        .slide-item {
            display: none;
        }
        .slide-item.active {
            display: block;
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(1.02); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Popup Scale In Animation */
        @keyframes popupScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .popup-content {
            animation: popupScale 0.3s ease-out forwards;
        }
    </style>
    <script>
        // Back ቁልፍ ሲነካ የተጠቃሚውን አሰሳ መቆጣጠር
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });

        function navigate(event, url) {
            event.preventDefault();
            window.location.replace(url);
        }

        // Buy Package Function via AJAX
        function buyPackage(planId) {
            fetch('buy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'plan_id=' + planId
            })
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('alertModal');
                const title = document.getElementById('alertTitle');
                const msg = document.getElementById('alertMessage');
                const iconContainer = document.getElementById('alertIconContainer');
                
                title.innerText = data.status === 'success' ? 'Successful!' : 'Insufficient Balance!';
                msg.innerText = data.message;
                
                if (data.status === 'success') {
                    iconContainer.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 text-2xl"></i>';
                } else {
                    iconContainer.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-amber-400 text-2xl"></i>';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Daily Check-In Function via AJAX
        function handleCheckIn() {
            fetch('check_in.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                const modal = document.getElementById('alertModal');
                const title = document.getElementById('alertTitle');
                const msg = document.getElementById('alertMessage');
                const iconContainer = document.getElementById('alertIconContainer');
                
                title.innerText = data.status === 'success' ? 'Check-In Successful!' : 'Notice';
                msg.innerText = data.message;
                
                if (data.status === 'success') {
                    iconContainer.innerHTML = '<i class="fa-solid fa-calendar-check text-emerald-400 text-2xl"></i>';
                } else {
                    iconContainer.innerHTML = '<i class="fa-solid fa-circle-info text-amber-400 text-2xl"></i>';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        function closeAlertModal() {
            const modal = document.getElementById('alertModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
            window.location.reload();
        }

        // Automatic Banner Slider Script
        let currentSlide = 0;
        function showSlides() {
            const slides = document.querySelectorAll('.slide-item');
            if (slides.length === 0) return;
            
            slides.forEach(slide => slide.classList.remove('active'));
            currentSlide = (currentSlide + 1) % slides.length;
            slides[currentSlide].classList.add('active');
        }

        // Welcome Announcement Popup Management 
        function closeWelcomePopup() {
            const popup = document.getElementById('welcomePopup');
            popup.classList.remove('flex');
            popup.classList.add('hidden');
        }
        
        window.addEventListener('DOMContentLoaded', () => {
            setInterval(showSlides, 4000); // Changes every 4 seconds

            // Popup Logic Implementation
            const popup = document.getElementById('welcomePopup');
            if (popup) {
                let isNewSession = !sessionStorage.getItem('vera_visited');

                if (isNewSession) {
                    sessionStorage.setItem('vera_visited', 'true');
                    sessionStorage.setItem('vera_refresh_count', '0');
                    
                    popup.classList.remove('hidden');
                    popup.classList.add('flex');
                } else {
                    let refreshCount = parseInt(sessionStorage.getItem('vera_refresh_count') || '0');

                    if (refreshCount < 2) {
                        refreshCount++;
                        sessionStorage.setItem('vera_refresh_count', refreshCount.toString());

                        popup.classList.remove('hidden');
                        popup.classList.add('flex');
                    }
                }
            }
        });
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <!-- Mobile Container Frame -->
    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900">

        <!-- Main Content Area -->
        <div class="flex-grow p-4 pb-32 overflow-y-auto relative">
            
            <!-- Dynamic Rotating Banner Slider Component about Vera Company (English Only) -->
            <div class="relative w-full h-44 rounded-2xl overflow-hidden mb-5 shadow-xl border border-slate-800 bg-slate-900 mt-2">
                
                <!-- Slide 1 -->
                <div class="slide-item active absolute inset-0 w-full h-full">
                    <img src="https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?q=80&w=600&auto=format&fit=crop" alt="Vera Platform" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent flex flex-col justify-end p-4">
                        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest mb-0.5"><i class="fa-solid fa-shield-halved mr-1"></i> Trusted Platform</span>
                        <h3 class="text-xs font-bold text-white">Welcome to VERA! Secure and fast daily profit generation system.</h3>
                    </div>
                </div>

                <!-- Slide 2 -->
                <div class="slide-item absolute inset-0 w-full h-full">
                    <img src="https://images.unsplash.com/photo-1621416894569-0f39ed31d247?q=80&w=600&auto=format&fit=crop" alt="VIP Investment" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent flex flex-col justify-end p-4">
                        <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest mb-0.5"><i class="fa-solid fa-crown mr-1"></i> VIP Packages</span>
                        <h3 class="text-xs font-bold text-white">Choose upgraded investment packages and start your daily income today.</h3>
                    </div>
                </div>

                <!-- Slide 3 -->
                <div class="slide-item absolute inset-0 w-full h-full">
                    <img src="https://images.unsplash.com/photo-1553877522-43269d4ea984?q=80&w=600&auto=format&fit=crop" alt="Team Work" class="w-full h-full object-cover opacity-60">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent flex flex-col justify-end p-4">
                        <span class="text-[10px] font-bold text-blue-400 uppercase tracking-widest mb-0.5"><i class="fa-solid fa-users mr-1"></i> Team Reward</span>
                        <h3 class="text-xs font-bold text-white">Invite your friends to build a team and earn amazing extra bonuses together.</h3>
                    </div>
                </div>

            </div>

            <!-- Action Buttons Grid (Recharge, Withdraw, Check-In, My Team) -->
            <div class="grid grid-cols-4 gap-2 mb-6">
                <a href="recharge.php" onclick="navigate(event, this.href)" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 p-3 rounded-2xl flex flex-col items-center justify-center transition shadow-lg group">
                    <i class="fa-solid fa-wallet text-amber-400 text-lg mb-1.5 group-hover:scale-110 transition"></i>
                    <span class="text-[11px] font-bold text-slate-300">Recharge</span>
                </a>
                <a href="withdraw.php" onclick="navigate(event, this.href)" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 p-3 rounded-2xl flex flex-col items-center justify-center transition shadow-lg group">
                    <i class="fa-solid fa-money-bill-transfer text-emerald-400 text-lg mb-1.5 group-hover:scale-110 transition"></i>
                    <span class="text-[11px] font-bold text-slate-300">Withdraw</span>
                </a>
                <button onclick="handleCheckIn()" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 p-3 rounded-2xl flex flex-col items-center justify-center transition shadow-lg group cursor-pointer">
                    <i class="fa-solid fa-calendar-check text-blue-400 text-lg mb-1.5 group-hover:scale-110 transition"></i>
                    <span class="text-[11px] font-bold text-slate-300">Check In</span>
                </button>
                <a href="team.php" onclick="navigate(event, this.href)" class="bg-slate-900 hover:bg-slate-800 border border-slate-800 p-3 rounded-2xl flex flex-col items-center justify-center transition shadow-lg group">
                    <i class="fa-solid fa-user-group text-purple-400 text-lg mb-1.5 group-hover:scale-110 transition"></i>
                    <span class="text-[11px] font-bold text-slate-300">My Team</span>
                </a>
            </div>

            <!-- Section Title: Available VIP Packages -->
            <div class="flex items-center space-x-2 mb-4">
                <i class="fa-solid fa-fire text-orange-500 animate-pulse text-base"></i>
                <h3 class="text-sm font-extrabold text-slate-200 tracking-wide">Available VIP Packages</h3>
            </div>

            <!-- Packages Grid (2 Columns Layout: Dynamic from Database) -->
            <div class="grid grid-cols-2 gap-3">
                <?php if (empty($packages)): ?>
                    <div class="col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 text-center text-slate-400 text-xs">
                        No packages available.
                    </div>
                <?php else: ?>
                    <?php foreach ($packages as $p): 
                        $img_url = $package_images[$p['id']] ?? "https://images.unsplash.com/photo-1621416894569-0f39ed31d247?q=80&w=200&auto=format&fit=crop";
                        $is_coming_soon = (($p['status'] ?? 'buy_now') === 'coming_soon');
                    ?>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-3.5 shadow-lg flex flex-col justify-between hover:border-slate-700 transition">
                            <div>
                                <div class="w-full h-24 rounded-xl overflow-hidden mb-2.5 border border-slate-800 bg-slate-950">
                                    <img src="<?= $img_url ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="w-full h-full object-cover opacity-90">
                                </div>
                                <h4 class="text-xs font-bold text-slate-200 mb-1.5"><?= htmlspecialchars($p['name']) ?></h4>
                                <div class="space-y-1 text-[10px] text-slate-400 font-medium">
                                    <p>Price: <span class="text-emerald-400 font-bold"><?= number_format($p['price'], 2) ?> ETB</span></p>
                                    <p>Cycle: <span class="text-slate-300"><?= $p['cycle_days'] ?> Days</span></p>
                                    <p>Daily: <span class="text-emerald-400 font-bold"><?= number_format($p['daily_income'], 2) ?> ETB</span></p>
                                    <p>Total: <span class="text-emerald-400 font-bold"><?= number_format($p['total_income'], 2) ?> ETB</span></p>
                                </div>
                            </div>

                            <?php if ($is_coming_soon): ?>
                                <button disabled class="mt-3.5 w-full bg-slate-800 text-amber-400 text-[11px] font-black py-2 rounded-xl border border-amber-500/20 text-center block cursor-not-allowed uppercase tracking-wider">
                                    Coming Soon
                                </button>
                            <?php else: ?>
                                <button onclick="buyPackage(<?= $p['id'] ?>)" class="mt-3.5 w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-black py-2 rounded-xl transition shadow-lg shadow-emerald-500/10 text-center block">
                                    Buy Now
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Floating Support Button -->
            <a href="https://t.me/vera_customer" target="_blank" class="fixed right-5 bottom-20 z-40 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-xl shadow-cyan-500/30 border border-white/25 floating-support transition-transform hover:scale-110">
                <i class="fa-solid fa-headset text-xl"></i>
            </a>

        </div>

        <!-- Welcome Announcement Popup Modal -->
        <div id="welcomePopup" class="fixed inset-0 bg-slate-950/80 backdrop-blur-md hidden justify-center items-center z-50 p-4">
            <div class="popup-content bg-gradient-to-b from-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 w-full max-w-xs text-center shadow-2xl relative overflow-hidden">
                
                <div class="absolute -top-12 -right-12 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="w-16 h-16 mx-auto bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-2xl flex items-center justify-center shadow-lg shadow-emerald-500/20 mb-4 border border-white/20">
                    <i class="fa-solid fa-bullhorn text-slate-950 text-2xl"></i>
                </div>
                
                <h2 class="text-xl font-black text-white tracking-wide mb-4">VERA</h2>

                <div class="bg-slate-900/80 border border-slate-800/80 rounded-2xl p-4 mb-5 space-y-2.5 text-left text-xs shadow-inner">
                    <div class="flex justify-between items-center border-b border-slate-800/60 pb-2">
                        <span class="text-slate-400 flex items-center"><i class="fa-solid fa-gift text-emerald-400 mr-2 w-4"></i> Welcome Bonus</span>
                        <span class="font-black text-emerald-400">250 ETB</span>
                    </div>
                    <div class="flex justify-between items-center border-b border-slate-800/60 pb-2">
                        <span class="text-slate-400 flex items-center"><i class="fa-solid fa-wallet text-amber-400 mr-2 w-4"></i> Min. Recharge</span>
                        <span class="font-bold text-slate-200">750 ETB</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-slate-400 flex items-center"><i class="fa-solid fa-money-bill-transfer text-blue-400 mr-2 w-4"></i> Min. Withdraw</span>
                        <span class="font-bold text-slate-200">250 ETB</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5 mb-4">
                    <a href="https://t.me/veraoficialchannel" target="_blank" class="bg-slate-900 hover:bg-slate-800 border border-slate-700 text-cyan-400 hover:text-cyan-300 text-xs font-bold py-2.5 px-3 rounded-xl transition shadow flex items-center justify-center space-x-1.5">
                        <i class="fa-brands fa-telegram text-sm"></i>
                        <span>Channel</span>
                    </a>
                    <a href="https://t.me/veraoficialgroup" target="_blank" class="bg-slate-900 hover:bg-slate-800 border border-slate-700 text-blue-400 hover:text-blue-300 text-xs font-bold py-2.5 px-3 rounded-xl transition shadow flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-users text-sm"></i>
                        <span>Group</span>
                    </a>
                </div>

                <button onclick="closeWelcomePopup()" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 text-xs font-black py-3 rounded-xl transition shadow-lg shadow-emerald-500/20 uppercase tracking-wider">
                    Confirm
                </button>

            </div>
        </div>

        <!-- Dynamic Buy Alert Popup Modal -->
        <div id="alertModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm hidden justify-center items-center z-50 p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-xs text-center shadow-2xl">
                <div id="alertIconContainer" class="w-14 h-14 mx-auto bg-slate-800 border border-slate-700 rounded-full flex items-center justify-center mb-4"></div>
                <h3 id="alertTitle" class="text-base font-bold text-slate-200 mb-1">Notification</h3>
                <p id="alertMessage" class="text-xs text-slate-400 mb-5">Message text here</p>
                <button onclick="closeAlertModal()" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black py-2.5 rounded-xl transition shadow-lg shadow-emerald-500/10">
                    Confirm
                </button>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-slate-950 border-t border-slate-900 py-3 px-6 flex justify-between items-center text-[10px] font-bold text-slate-400 z-50">
            <a href="home.php" onclick="navigate(event, this.href)" class="flex flex-col items-center text-emerald-400 transition gap-1">
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
