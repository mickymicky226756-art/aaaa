<?php
// Prevent duplicate session start warnings
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ⚠️ ጠቃሚ ማስተካከያ፡-
// እዚህጋ የነበረው if (isset($_SESSION['user_id'])) የሚለው ኮድ ተነስተአል። 
// ምክንያቱም ተጠቃሚው ሎጊን አድርጎ ከወጣ በኋላ (Logout ካለ) ፕረዘር (Browser) 
// የድሮውን ሴክሽን ይዞ ራሱ ወደ home እንዳይወስደው እና ስልክ ቁጥርና ፓስወርድ አስገብቶ 
// በድጋሚ Login ሲጫን ብቻ እንዲገባ አድርጎታል።

include 'db.php';
$error = "";

// Check if remember-me cookies exist and pre-fill values
$saved_phone = isset($_COOKIE['vera_phone']) ? $_COOKIE['vera_phone'] : '';
$remember_checked = !empty($saved_phone) ? 'checked' : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $remember = isset($_POST['remember']) ? true : false;

    if (!empty($phone) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ተጠቃሚው የሞላው መረጃ ትክክል መሆኑ ሲረጋገጥ ብቻ
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['phone'] = $user['phone'];

            // Handle Remember Me Cookie (expires in 30 days)
            if ($remember) {
                setcookie('vera_phone', $phone, time() + (86400 * 30), "/");
            } else {
                setcookie('vera_phone', "", time() - 3600, "/");
            }

            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid phone number or password!";
        }
    } else {
        $error = "Please fill in all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Vera - Login</title>
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

        // Toggle Password Visibility Function
        function togglePassword() {
            const passwordField = document.getElementById('passwordField');
            const icon = document.getElementById('eyeIcon');
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 flex justify-center items-center min-h-screen m-0">

    <div class="w-full max-w-md bg-slate-950 min-h-screen shadow-2xl relative flex flex-col justify-between border-x border-slate-900 p-6">
        <div class="my-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-emerald-400 tracking-wider">VERA</h1>
                <p class="text-xs text-slate-400 mt-1 font-medium">Sign in to your account</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs p-3 rounded-xl mb-4 text-center font-medium">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST" autocomplete="off" class="space-y-4">
                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Phone Number</label>
                    <div class="flex items-center bg-slate-900 border border-slate-800 rounded-xl overflow-hidden">
                        <span class="px-3 text-xs font-bold text-slate-400 border-r border-slate-800">+251</span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($saved_phone); ?>" placeholder="........." required class="w-full bg-transparent px-3 py-2.5 text-xs text-slate-200 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Password</label>
                    <div class="relative">
                        <input type="password" id="passwordField" name="password" placeholder="••••••••" required class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2.5 text-xs text-slate-200 outline-none pr-10">
                        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200">
                            <i id="eyeIcon" class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me Checkbox -->
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <label class="flex items-center space-x-2 cursor-pointer select-none">
                        <input type="checkbox" name="remember" <?php echo $remember_checked; ?> class="w-4 h-4 rounded border-slate-800 bg-slate-900 text-emerald-500 focus:ring-0 cursor-pointer">
                        <span class="font-medium text-slate-300">Remember me</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black py-3 rounded-xl transition shadow-lg shadow-emerald-500/10">
                    Login
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-xs text-slate-400 font-medium">Don't have an account? <a href="signup.php" class="text-emerald-400 font-bold hover:underline">Sign Up</a></p>
            </div>
        </div>
        
        <div class="text-center text-[10px] text-slate-600 pb-2 font-medium">
            &copy; 2026 Vera. All rights reserved.
        </div>
    </div>

</body>
</html>
