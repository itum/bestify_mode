<?php
// مسیر فایل admin.php
$adminFilePath = '/var/www/html/mirzabotconfig/admin.php';

// خواندن محتوای فایل
$content = file_get_contents($adminFilePath);

// جستجو و حذف تابع convert_numbers_to_english
$pattern = '/function convert_numbers_to_english\(\$string\)[\s\S]*?\}[\s\n]*/';
$replacement = "// تابع convert_numbers_to_english به functions.php منتقل شده است\n\n";
$newContent = preg_replace($pattern, $replacement, $content);

// نوشتن محتوای جدید بدون تابع
if (file_put_contents($adminFilePath, $newContent)) {
    echo "✅ تابع convert_numbers_to_english با موفقیت از فایل admin.php حذف شد.\n";
} else {
    echo "❌ خطا در حذف تابع. لطفاً مجوزهای دسترسی فایل را بررسی کنید.\n";
}

// اطمینان از وجود تابع در functions.php
$functionsFilePath = '/var/www/html/mirzabotconfig/functions.php';
$functionsContent = file_get_contents($functionsFilePath);

// بررسی وجود تابع در functions.php
if (strpos($functionsContent, 'function convert_numbers_to_english') === false) {
    // اضافه کردن تابع به انتهای فایل functions.php
    $functionCode = <<<'EOD'

/**
 * تبدیل اعداد فارسی و عربی به انگلیسی
 * 
 * @param string $string متن حاوی اعداد
 * @return string متن با اعداد انگلیسی
 */
function convert_numbers_to_english($string) {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    $string = str_replace($persian, $english, $string);
    $string = str_replace($arabic, $english, $string);
    
    return $string;
}
EOD;
    
    // افزودن تابع به انتهای فایل functions.php
    if (file_put_contents($functionsFilePath, $functionsContent . $functionCode)) {
        echo "✅ تابع convert_numbers_to_english به فایل functions.php اضافه شد.\n";
    } else {
        echo "❌ خطا در افزودن تابع به functions.php. لطفاً مجوزهای دسترسی فایل را بررسی کنید.\n";
    }
} else {
    echo "✅ تابع convert_numbers_to_english در فایل functions.php وجود دارد.\n";
}

echo "\n✅ عملیات با موفقیت انجام شد. حالا باید خطای 'Cannot redeclare function convert_numbers_to_english()' برطرف شده باشد.\n";

require_once 'config.php';
require_once 'functions.php';

// اتصال به دیتابیس
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ساخت جدول auto_payment_checks
$sql = "CREATE TABLE IF NOT EXISTS auto_payment_checks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_id VARCHAR(10) NOT NULL,
    user_id INT NOT NULL,
    amount INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL DEFAULT 'pending',
    completed_at DATETIME DEFAULT NULL,
    message_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "جدول auto_payment_checks با موفقیت ساخته شد\n";
} else {
    echo "خطا در ساخت جدول auto_payment_checks: " . $conn->error . "\n";
}

// بستن اتصال
$conn->close();
?> 