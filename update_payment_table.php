<?php
// اضافه کردن ستون transaction_details به جدول Payment_report

// اتصال به دیتابیس - این مقادیر را با اطلاعات دیتابیس خود جایگزین کنید
require_once 'config.php'; // فرض می‌کنیم فایل config.php حاوی اطلاعات اتصال به دیتابیس است

try {
    // بررسی وجود ستون transaction_details
    $stmt = $pdo->prepare("SHOW COLUMNS FROM Payment_report LIKE 'transaction_details'");
    $stmt->execute();
    $column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$column_exists) {
        // اضافه کردن ستون transaction_details
        $stmt = $pdo->prepare("ALTER TABLE Payment_report ADD COLUMN transaction_details TEXT");
        $stmt->execute();
        echo "ستون transaction_details با موفقیت به جدول Payment_report اضافه شد.\n";
    } else {
        echo "ستون transaction_details قبلاً در جدول Payment_report وجود دارد.\n";
    }
    
    echo "عملیات به‌روزرسانی جدول با موفقیت انجام شد.\n";
} catch (PDOException $e) {
    echo "خطا در به‌روزرسانی جدول: " . $e->getMessage() . "\n";
}
?> 