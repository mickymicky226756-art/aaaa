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
    <title>Vera - About Us & 5-Year Plan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
    <script>
        // Back ቁልፍ ሲነካ በቅጽበት ወደ home.php እንዲመለስ ማድረግ
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            window.location.replace('home.php');
        });
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl border border-slate-900 flex flex-col justify-between">
        
        <div class="flex-grow pb-10">
            <!-- Header -->
            <div class="p-4 border-b border-slate-800 flex items-center bg-slate-900/50 sticky top-0 z-50 backdrop-blur-md">
                <a href="home.php" class="text-slate-400 mr-3 hover:text-white transition"><i class="fa-solid fa-arrow-left"></i></a>
                <h2 class="font-bold text-sm text-slate-200">About Vera Investment</h2>
            </div>

            <div class="p-4 space-y-6">
                
                <!-- Hero Section -->
                <div class="bg-gradient-to-br from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/20 p-5 rounded-3xl text-center shadow-xl">
                    <div class="w-14 h-14 mx-auto bg-emerald-500/20 rounded-2xl flex items-center justify-center mb-3 border border-emerald-500/30 text-emerald-400 text-2xl shadow-lg">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h1 class="text-lg font-extrabold text-slate-100 mb-1">Welcome to Vera Global</h1>
                    <p class="text-[11px] text-emerald-400 font-semibold uppercase tracking-wider mb-3">Empowering Financial Growth</p>
                    <p class="text-xs text-slate-300 leading-relaxed">
                        Vera is a premier, large-scale investment ecosystem engineered to generate sustainable high-yield returns across diverse economic sectors. We bridge the gap between ambitious investors and high-growth profitable industries.
                    </p>
                </div>

                <!-- Diverse Investment Sectors -->
                <div>
                    <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-3 flex items-center">
                        <i class="fa-solid fa-layer-group text-emerald-400 mr-2"></i> Diverse Investment Sectors
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl">
                            <i class="fa-solid fa-bolt text-emerald-400 text-base mb-2"></i>
                            <h4 class="text-xs font-bold text-slate-200 mb-1">Green Energy</h4>
                            <p class="text-[10px] text-slate-400 leading-tight">Investing in sustainable solar, wind, and modern power infrastructures.</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl">
                            <i class="fa-solid fa-microchip text-emerald-400 text-base mb-2"></i>
                            <h4 class="text-xs font-bold text-slate-200 mb-1">Digital Tech</h4>
                            <p class="text-[10px] text-slate-400 leading-tight">Funding AI advancements, cloud data centers, and web infrastructure.</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl">
                            <i class="fa-solid fa-building text-emerald-400 text-base mb-2"></i>
                            <h4 class="text-xs font-bold text-slate-200 mb-1">Real Estate</h4>
                            <p class="text-[10px] text-slate-400 leading-tight">Developing commercial properties and smart residential complexes.</p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 p-3.5 rounded-2xl">
                            <i class="fa-solid fa-seedling text-emerald-400 text-base mb-2"></i>
                            <h4 class="text-xs font-bold text-slate-200 mb-1">Agro-Business</h4>
                            <p class="text-[10px] text-slate-400 leading-tight">Scaling modern commercial farming and food supply chains.</p>
                        </div>
                    </div>
                </div>

                <!-- Our 5-Year Strategic Master Plan -->
                <div class="bg-slate-900 border border-slate-800 p-4 rounded-3xl">
                    <h3 class="text-xs font-bold text-slate-200 uppercase tracking-wider mb-4 flex items-center">
                        <i class="fa-solid fa-flag-checkered text-emerald-400 mr-2"></i> Our 5-Year Strategic Master Plan
                    </h3>
                    
                    <div class="space-y-4 relative border-l border-slate-800 ml-2 pl-4">
                        <div class="relative">
                            <span class="absolute -left-[21px] top-0 w-3 h-3 bg-emerald-500 rounded-full border-2 border-slate-950"></span>
                            <h4 class="text-xs font-bold text-emerald-400">Phase 1 (Year 1): Foundation & Launch</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Platform stabilization, user acquisition, and capital deployment into fast-yield digital assets.</p>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-[21px] top-0 w-3 h-3 bg-slate-700 rounded-full border-2 border-slate-950"></span>
                            <h4 class="text-xs font-bold text-slate-200">Phase 2 (Year 2): Real Estate Expansion</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Acquiring high-value commercial lands and launching smart real estate construction projects.</p>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-[21px] top-0 w-3 h-3 bg-slate-700 rounded-full border-2 border-slate-950"></span>
                            <h4 class="text-xs font-bold text-slate-200">Phase 3 (Year 3): Green Energy Integration</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Transitioning portions of capital into long-term solar and green energy power generation.</p>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-[21px] top-0 w-3 h-3 bg-slate-700 rounded-full border-2 border-slate-950"></span>
                            <h4 class="text-xs font-bold text-slate-200">Phase 4 (Year 4): Global Market Scale</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Expanding our operational reach to international markets and multi-currency transactions.</p>
                        </div>
                        <div class="relative">
                            <span class="absolute -left-[21px] top-0 w-3 h-3 bg-slate-700 rounded-full border-2 border-slate-950"></span>
                            <h4 class="text-xs font-bold text-slate-200">Phase 5 (Year 5): Market Leadership</h4>
                            <p class="text-[10px] text-slate-400 mt-0.5">Establishing Vera as a leading diversified financial holding company globally.</p>
                        </div>
                    </div>
                </div>

                <!-- Security & Trust -->
                <div class="bg-slate-900/60 border border-slate-800 p-4 rounded-2xl flex items-center space-x-3">
                    <div class="text-emerald-400 text-2xl">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-slate-200">Secure & Transparent</h4>
                        <p class="text-[10px] text-slate-400">All investments are managed with strict security protocols, secure hashing, and real-time transaction reporting.</p>
                    </div>
                </div>

            </div>
        </div>

    </div>

</body>
</html>
