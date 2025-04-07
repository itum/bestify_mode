<?php
require_once 'config.php';

// اتصال به دیتابیس با استفاده از متغیرهای موجود در config.php
try {
    // استفاده از اتصال از پیش ایجاد شده در config.php ($connect)
    
    // بررسی وجود ستون double_charge_balance در جدول user
    $result = $connect->query("SHOW COLUMNS FROM `user` LIKE 'double_charge_balance'");
    
    if ($result->num_rows == 0) {
        // اگر ستون وجود نداشت، آن را اضافه کنیم
        $connect->query("ALTER TABLE `user` ADD COLUMN `double_charge_balance` int(255) NOT NULL DEFAULT 0");
        echo "✅ ستون double_charge_balance با موفقیت به جدول user اضافه شد.<br>";
    } else {
        echo "⚠️ ستون double_charge_balance از قبل در جدول user وجود دارد.<br>";
    }
    
    // بررسی وجود ستون payment_method در جدول invoice
    $result = $connect->query("SHOW COLUMNS FROM `invoice` LIKE 'payment_method'");
    
    if ($result->num_rows == 0) {
        // اگر ستون وجود نداشت، آن را اضافه کنیم
        $connect->query("ALTER TABLE `invoice` ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'balance'");
        echo "✅ ستون payment_method با موفقیت به جدول invoice اضافه شد.<br>";
    } else {
        echo "⚠️ ستون payment_method از قبل در جدول invoice وجود دارد.<br>";
    }
    
    echo "<br>✅ به‌روزرسانی پایگاه داده با موفقیت انجام شد.";
    
} catch (Exception $e) {
    echo "❌ خطا در به‌روزرسانی پایگاه داده: " . $e->getMessage();
} 