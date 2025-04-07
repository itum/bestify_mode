<?php
require_once 'config.php';

// اتصال به دیتابیس
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // بررسی وجود ستون double_charge_balance در جدول user
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `user` LIKE 'double_charge_balance'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // اگر ستون وجود نداشت، آن را اضافه کنیم
        $pdo->exec("ALTER TABLE `user` ADD COLUMN `double_charge_balance` int(255) NOT NULL DEFAULT 0");
        echo "✅ ستون double_charge_balance با موفقیت به جدول user اضافه شد.<br>";
    } else {
        echo "⚠️ ستون double_charge_balance از قبل در جدول user وجود دارد.<br>";
    }
    
    // بررسی وجود ستون payment_method در جدول invoice
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `invoice` LIKE 'payment_method'");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // اگر ستون وجود نداشت، آن را اضافه کنیم
        $pdo->exec("ALTER TABLE `invoice` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'balance'");
        echo "✅ ستون payment_method با موفقیت به جدول invoice اضافه شد.<br>";
    } else {
        echo "⚠️ ستون payment_method از قبل در جدول invoice وجود دارد.<br>";
    }
    
    echo "<br>✅ به‌روزرسانی پایگاه داده با موفقیت انجام شد.";
    
} catch (PDOException $e) {
    echo "❌ خطا در به‌روزرسانی پایگاه داده: " . $e->getMessage();
} 