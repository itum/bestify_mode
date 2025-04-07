<?php
require_once '../config.php';
require_once '../botapi.php';

// تنظیم time zone
date_default_timezone_set('Asia/Tehran');

// شروع لاگ
$logFile = '../logs/double_charge_reminder_' . date('Y-m-d') . '.log';
file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] شروع فرآیند ارسال یادآوری شارژ دوبرابر\n", FILE_APPEND);

try {
    // پیکربندی دیتابیس
    $pdoConnect = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbusername, $dbpassword);
    $pdoConnect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // دریافت تنظیمات
    $stmt = $pdoConnect->query("SELECT * FROM setting");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // بررسی وضعیت قابلیت شارژ دوبرابر
    if ($settings['double_charge_status'] !== 'on') {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] قابلیت شارژ دوبرابر غیرفعال است.\n", FILE_APPEND);
        exit;
    }
    
    // دریافت کاربرانی که مشمول هستند و کمتر از 12 ساعت به پایان مهلت آنها باقی مانده
    $query = "
        SELECT u.username, u.id, dcn.notified_at, dcn.expiry_at
        FROM users u
        JOIN double_charge_notifications dcn ON u.id = dcn.user_id
        LEFT JOIN double_charge_users dcu ON u.id = dcu.user_id
        WHERE 
            dcu.id IS NULL
            AND dcn.expiry_at > NOW() 
            AND dcn.expiry_at < DATE_ADD(NOW(), INTERVAL 12 HOUR)
            AND u.step NOT LIKE 'banned%'
            AND u.isAgent != 'agent'
    ";
    
    $stmt = $pdoConnect->query($query);
    $eligibleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalUsers = count($eligibleUsers);
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] تعداد کاربران مشمول یادآوری: $totalUsers\n", FILE_APPEND);
    
    if ($totalUsers == 0) {
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] هیچ کاربری نیاز به یادآوری ندارد.\n", FILE_APPEND);
        exit;
    }
    
    // ارسال پیام یادآوری به کاربران
    $successCount = 0;
    $failureCount = 0;
    
    // تعداد کل کاربران
    echo "در حال ارسال یادآوری به $totalUsers کاربر...\n";
    
    foreach ($eligibleUsers as $index => $user) {
        // محاسبه درصد پیشرفت
        $progress = round(($index + 1) / $totalUsers * 100);
        
        // نمایش پیشرفت
        echo "\rپیشرفت: $progress% ($index + 1/$totalUsers)";
        
        // محاسبه زمان باقی‌مانده
        $remainingTime = round((strtotime($user['expiry_at']) - time()) / 3600, 1);
        
        // پیام یادآوری
        $messageText = "⚠️ *یادآوری مهم* ⚠️\n\n";
        $messageText .= "کاربر گرامی {$user['username']}،\n\n";
        $messageText .= "🔔 *تنها " . $remainingTime . " ساعت* از فرصت استثنایی شارژ دوبرابر شما باقی مانده است!\n\n";
        $messageText .= "⏳ در صورت عدم استفاده، این فرصت ویژه را از دست خواهید داد.\n\n";
        $messageText .= "💡 یادآوری می‌کنیم با استفاده از این طرح، می‌توانید حساب خود را با هر مبلغی شارژ کنید و *دو برابر آن* را دریافت نمایید!\n\n";
        $messageText .= "🏃‍♂️ همین حالا اقدام کنید و از این فرصت استثنایی بهره‌مند شوید.";
        
        // استفاده از shell_exec برای اجرای در پس‌زمینه
        $chatId = $user['id'];
        $escapedMessage = escapeshellarg($messageText);
        $command = "php -r 'require_once \"../botapi.php\"; sendMessage($chatId, $escapedMessage, \"MarkDown\");' > /dev/null 2>&1 &";
        
        // اجرای دستور
        $result = shell_exec($command);
        
        // ثبت نتیجه در لاگ
        if ($result !== false) {
            $successCount++;
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] پیام یادآوری با موفقیت به کاربر {$user['username']} (ID: {$user['id']}) ارسال شد.\n", FILE_APPEND);
        } else {
            $failureCount++;
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] خطا در ارسال پیام یادآوری به کاربر {$user['username']} (ID: {$user['id']}).\n", FILE_APPEND);
        }
        
        // ایجاد تأخیر کوتاه برای جلوگیری از محدودیت‌های تلگرام
        usleep(200000); // 0.2 ثانیه تأخیر
    }
    
    echo "\n";
    echo "ارسال یادآوری‌ها به پایان رسید.\n";
    echo "موفق: $successCount | ناموفق: $failureCount\n";
    
    // پایان لاگ
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] پایان فرآیند ارسال یادآوری. موفق: $successCount | ناموفق: $failureCount\n", FILE_APPEND);
    
} catch (PDOException $e) {
    file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] خطا: " . $e->getMessage() . "\n", FILE_APPEND);
    echo "خطا: " . $e->getMessage() . "\n";
} 