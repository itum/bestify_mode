<?php
// فایل رفع خطاها - این فایل را اجرا کنید تا خطاهای گزارش شده رفع شود

// بررسی دسترسی به فایل
if (!file_exists("index.php")) {
    die("فایل index.php پیدا نشد. لطفا این فایل را در مسیر اصلی ربات قرار دهید.");
}

// افزودن کلیدهای مورد نیاز به فایل text.php
$text_php_path = "text.php";
if (file_exists($text_php_path)) {
    $text_content = file_get_contents($text_php_path);
    
    // اضافه کردن کلیدهای مورد نیاز اگر وجود نداشته باشند
    if (strpos($text_content, '$textbotlang[\'users\'][\'Balance\'][\'Back-Balance\']') === false) {
        $text_content .= "\n\$textbotlang['users']['Balance']['Back-Balance'] = \"🔙 بازگشت\";\n";
    }
    
    if (strpos($text_content, '$textbotlang[\'users\'][\'Balance\'][\'Payment-Method\']') === false) {
        $text_content .= "\$textbotlang['users']['Balance']['Payment-Method'] = \"💳 لطفا روش پرداخت خود را انتخاب کنید:\";\n";
    }
    
    file_put_contents($text_php_path, $text_content);
    echo "کلیدهای مورد نیاز به فایل text.php اضافه شد.\n";
}

// رفع خطای متغیرهای تعریف نشده در index.php
$index_php_path = "index.php";
if (file_exists($index_php_path)) {
    $index_content = file_get_contents($index_php_path);
    
    // الگوهای جستجو و جایگزینی
    $patterns = [
        // رفع خطای خط 620-621
        '/if \(\$setting\[\'NotUser\'\] == "1"\) {\s*\$keyboardlists\[\'inline_keyboard\'\]\[\] = \$usernotlist;\s*}\s*\$keyboardlists\[\'inline_keyboard\'\]\[\] = \$check_invalid_services;\s*\$keyboardlists\[\'inline_keyboard\'\]\[\] = \$search_service_button;/' => 
        "if (\$setting['NotUser'] == \"1\") {
        \$keyboardlists['inline_keyboard'][] = \$usernotlist;
    }
    
    // تعریف متغیرهای مورد نیاز
    \$check_invalid_services = [
        [
            'text' => \"🔍 بررسی سرویس‌های نامعتبر\",
            'callback_data' => 'check_invalid_services'
        ]
    ];
    
    \$search_service_button = [
        [
            'text' => \"🔎 جستجوی سرویس\",
            'callback_data' => 'search_service'
        ]
    ];
    
    \$keyboardlists['inline_keyboard'][] = \$check_invalid_services;
    \$keyboardlists['inline_keyboard'][] = \$search_service_button;",
    ];
    
    // اعمال الگوها
    $index_content = preg_replace(array_keys($patterns), array_values($patterns), $index_content);
    
    // ذخیره تغییرات
    file_put_contents($index_php_path, $index_content);
    echo "خطاهای متغیرهای تعریف نشده در index.php رفع شد.\n";
}

echo "تمام خطاهای گزارش شده با موفقیت رفع شدند.\n";
echo "لطفا ربات را مجدداً راه‌اندازی کنید.\n";
?> 