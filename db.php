<?php
$host = 'sql111.infinityfree.com';
$dbname = 'if0_42113561_vera';
$username = 'if0_42113561';
$password = 'Kssp2Ugf4NuY';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "የዳታቤዝ ግንኙነት ስህተት: " . $e->getMessage();
    exit();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Africa/Addis_Ababa');

// የተጠቃሚውን የ 24 ሰዓት የዕለት ገቢ ብቻ የሚያስተካክለው ክፍል (ለሰርቨሩ ቀላል የሆነ)
if (isset($_SESSION['user_id'])) {
    $global_user_id = $_SESSION['user_id'];
    
    try {
        $stmt_glob_prods = $pdo->prepare("SELECT * FROM my_products WHERE user_id = ?");
        $stmt_glob_prods->execute([$global_user_id]);
        $global_products = $stmt_glob_prods->fetchAll(PDO::FETCH_ASSOC);

        $current_time = time();

        foreach ($global_products as $prod) {
            $last_income_time = strtotime($prod['last_income_date']);
            
            if (($current_time - $last_income_time) >= 86400) {
                $daily_income = floatval($prod['daily_income']);
                $product_id = $prod['id'];

                $pdo->beginTransaction();

                $update_bal = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                $update_bal->execute([$daily_income, $global_user_id]);

                $new_income_date = date('Y-m-d H:i:s');
                $update_p = $pdo->prepare("UPDATE my_products SET last_income_date = ? WHERE id = ?");
                $update_p->execute([$new_income_date, $product_id]);

                $pdo->exec("CREATE TABLE IF NOT EXISTS income_history (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT,
                    amount DECIMAL(10,2),
                    income_date DATETIME
                )");

                $insert_hist = $pdo->prepare("INSERT INTO income_history (user_id, amount, income_date) VALUES (?, ?, ?)");
                $insert_hist->execute([$global_user_id, $daily_income, $new_income_date]);

                $pdo->commit();
            }
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}
?>
