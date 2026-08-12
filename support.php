<?php
session_start();
include 'db.php';

// Check if user is logged in
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
    <title>Vera - Customer Support</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 flex flex-col justify-between">
        
        <div>
            <!-- Header -->
            <div class="px-4 py-3 border-b border-slate-800 flex items-center bg-slate-900/50">
                <a href="home.php" class="text-slate-400 mr-3"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="font-bold text-sm text-slate-200">Customer Support</h2>
            </div>

            <div class="p-4 space-y-4">
                
                <!-- Live Telegram Support Card -->
                <div class="bg-gradient-to-r from-blue-900/40 to-slate-900 border border-blue-500/30 p-4 rounded-2xl flex items-center justify-between shadow-lg">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white text-lg shadow-md shadow-blue-600/30">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-slate-200">Live Telegram Support</h4>
                            <p class="text-[10px] text-slate-400">Chat directly with our admin team</p>
                        </div>
                    </div>
                    <a href="https://t.me/vera_customer" target="_blank" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition shadow-md">
                        Open Chat
                    </a>
                </div>

                <!-- Support Info Section -->
                <div class="bg-slate-900/60 border border-slate-800/80 p-5 rounded-2xl space-y-4 shadow-inner">
                    <div class="flex items-center space-x-2 text-emerald-400">
                        <i class="fa-solid fa-headset text-base"></i>
                        <h3 class="text-xs font-extrabold tracking-wide uppercase">We Are Here For You!</h3>
                    </div>

                    <p class="text-[11px] text-slate-300 leading-relaxed">
                        The **Vera** customer support team is always ready to assist you with any questions, feedback, or technical issues you might encounter.
                    </p>

                    <div class="border-t border-slate-800/80 pt-3 space-y-2.5">
                        <div class="flex items-start space-x-2.5">
                            <div class="text-emerald-400 mt-0.5"><i class="fa-solid fa-clock text-[10px]"></i></div>
                            <p class="text-[10px] text-slate-400"><strong class="text-slate-200">24/7 Availability:</strong> We provide full support 7 days a week, 24 hours a day.</p>
                        </div>

                        <div class="flex items-start space-x-2.5">
                            <div class="text-emerald-400 mt-0.5"><i class="fa-solid fa-bolt text-[10px]"></i></div>
                            <p class="text-[10px] text-slate-400"><strong class="text-slate-200">Fast Response:</strong> Get quick assistance regarding deposits, withdrawals, or referral inquiries.</p>
                        </div>

                        <div class="flex items-start space-x-2.5">
                            <div class="text-emerald-400 mt-0.5"><i class="fa-solid fa-shield-halved text-[10px]"></i></div>
                            <p class="text-[10px] text-slate-400"><strong class="text-slate-200">Secure & Reliable:</strong> Communicate directly with us to keep your account safe and secure.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Info -->
        <div class="p-4">
            <div class="bg-slate-900 border border-slate-800 p-3 rounded-xl text-[10px] text-slate-400 text-center space-y-1">
                <p>📌 Official Telegram Channel: <a href="https://t.me/vera_customer" target="_blank" class="text-blue-400 font-bold underline">@vera_customer</a></p>
            </div>
        </div>

    </div>

</body>
</html>
