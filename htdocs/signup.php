<?php
session_start();
include 'db.php';

$message = "";

// Get referral code from URL if present
$ref_code_from_url = isset($_GET['ref']) ? trim($_GET['ref']) : '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referral_code = trim($_POST['referral_code']) ?: $ref_code_from_url;

    if (empty($phone) || empty($password) || empty($confirm_password)) {
        $message = "Please fill in all required fields!";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Check if phone number is already registered
        $check = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $check->execute([$phone]);
        if ($check->rowCount() > 0) {
            $message = "This phone number is already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $welcome_bonus = 250.00;
            
            // Insert new user into database with 250 ETB welcome bonus
            $stmt = $pdo->prepare("INSERT INTO users (phone, password, balance, referred_by) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$phone, $hashed_password, $welcome_bonus, $referral_code])) {
                // Automatically log the user in and redirect straight to home.php
                $new_user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_user_id;
                
                header("Location: home.php");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VERA - Sign Up</title>
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
        function togglePassword(fieldId, iconId) {
            const passwordField = document.getElementById(fieldId);
            const icon = document.getElementById(iconId);
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
    <div class="w-full max-w-md bg-slate-950 p-6 rounded-2xl shadow-2xl border border-slate-900 m-4">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-black text-emerald-400 tracking-wider">VERA</h1>
            <p class="text-xs text-slate-400 mt-1 font-medium">Modern Investment Platform</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="bg-rose-500/10 border border-rose-500/30 text-rose-400 text-xs p-3 rounded-xl mb-4 text-center font-medium">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form action="signup.php?ref=<?php echo urlencode($ref_code_from_url); ?>" method="POST" class="space-y-4">
            <h2 class="text-base font-bold mb-2 text-slate-200">Create an Account</h2>
            
            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Phone Number</label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-slate-800 bg-slate-900 text-slate-300 text-xs font-bold">+251</span>
                    <input type="tel" name="phone" placeholder=".........." required class="flex-1 rounded-r-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-xs text-slate-100 outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" placeholder="••••••••" required class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-xs text-slate-100 outline-none focus:border-emerald-500 pr-10">
                    <button type="button" onclick="togglePassword('password', 'eyeIcon1')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200">
                        <i id="eyeIcon1" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Confirm Password</label>
                <div class="relative">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" required class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-xs text-slate-100 outline-none focus:border-emerald-500 pr-10">
                    <button type="button" onclick="togglePassword('confirm_password', 'eyeIcon2')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200">
                        <i id="eyeIcon2" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-400 mb-1 uppercase tracking-wider">Referral Code (Optional)</label>
                <input type="text" name="referral_code" value="<?php echo htmlspecialchars($ref_code_from_url); ?>" placeholder="Enter code if you have one" class="w-full rounded-xl border border-slate-800 bg-slate-900 px-3 py-2.5 text-xs text-slate-100 outline-none focus:border-emerald-500">
            </div>

            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black py-3 rounded-xl transition shadow-lg shadow-emerald-500/10 mt-2">
                Sign Up
            </button>
        </form>

        <p class="text-center text-xs text-slate-400 mt-6 font-medium">
            Already have an account? <a href="login.php" class="text-emerald-400 font-bold hover:underline">Log In</a>
        </p>
    </div>
</body>
</html>
