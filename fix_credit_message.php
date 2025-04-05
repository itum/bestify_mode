<?php
// اصلاح پیام‌های خطای کمبود موجودی

// مسیر فایل اصلی
$indexFile = __DIR__ . '/text.php';

// خواندن محتوای فایل
$content = file_get_contents($indexFile);

// پیدا کردن و جایگزینی پترن %s با الگوی جدید
$oldPattern = '$textbotlang[\'users\'][\'sell\'][\'None-credit\'] = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: %s تومان
💲 مبلغ مورد نیاز: %s تومان
⚠️ کمبود اعتبار: %s تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";';

$newPattern = '$textbotlang[\'users\'][\'sell\'][\'None-credit\'] = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $product_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";';

// جایگزینی در محتوا
$content = str_replace($oldPattern, $newPattern, $content);

// ذخیره فایل
if (file_put_contents($indexFile, $content)) {
    echo "فایل text.php با موفقیت اصلاح شد.\n";
} else {
    echo "خطا در اصلاح فایل text.php\n";
}

// اکنون اصلاح بخش‌های مختلف index.php
$indexFile = __DIR__ . '/index.php';
$content = file_get_contents($indexFile);

// پیدا کردن و جایگزینی تمام موارد استفاده از sprintf برای پیام خطای کمبود موجودی
$oldPattern = '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $product_price, $shortage);';
$newPattern = '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $product_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";';

$content = str_replace($oldPattern, $newPattern, $content);

// جایگزینی برای موارد با volume_price
$oldPattern = '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $volume_price, $shortage);';
$newPattern = '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $volume_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";';

$content = str_replace($oldPattern, $newPattern, $content);

// جایگزینی برای موارد با price_format
$oldPattern = '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $price_format, $shortage);';
$newPattern = '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $price_format . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";';

$content = str_replace($oldPattern, $newPattern, $content);

// جایگزینی موارد استفاده مستقیم از متن خطا
$oldPattern = 'sendmessage($from_id, $textbotlang[\'users\'][\'sell\'][\'None-credit\'], $step_payment, \'HTML\');';
$newPattern = '// فرمت کردن مقادیر برای نمایش
        $user_balance = number_format($user[\'Balance\']);
        $product_price = number_format($final_price);
        $shortage = number_format($Balance_prim);
        
        $error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $product_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";
        
        sendmessage($from_id, $error_message, $step_payment, \'HTML\');';

$content = str_replace($oldPattern, $newPattern, $content);

// ذخیره فایل
if (file_put_contents($indexFile, $content)) {
    echo "فایل index.php با موفقیت اصلاح شد.\n";
} else {
    echo "خطا در اصلاح فایل index.php\n";
}

// اصلاح فایل cron/index.php
$cronFile = __DIR__ . '/cron/index.php';
if (file_exists($cronFile)) {
    $content = file_get_contents($cronFile);
    
    // اعمال همان تغییرات در فایل cron/index.php
    $content = str_replace(
        '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $product_price, $shortage);',
        '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $product_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";',
        $content
    );
    
    $content = str_replace(
        '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $volume_price, $shortage);',
        '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $volume_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";',
        $content
    );
    
    $content = str_replace(
        '$error_message = sprintf($textbotlang[\'users\'][\'sell\'][\'None-credit\'], $user_balance, $price_format, $shortage);',
        '$error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $price_format . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";',
        $content
    );
    
    $content = str_replace(
        'sendmessage($from_id, $textbotlang[\'users\'][\'sell\'][\'None-credit\'], $step_payment, \'HTML\');',
        '// فرمت کردن مقادیر برای نمایش
        $user_balance = number_format($user[\'Balance\']);
        $product_price = number_format($final_price);
        $shortage = number_format($Balance_prim);
        
        $error_message = "🚨 خطایی در هنگام پرداخت رخ داده است.
📝 دلیل خطا: موجودی حساب کاربری شما کافی نمی باشد

💰 موجودی فعلی شما: " . $user_balance . " تومان
💲 مبلغ مورد نیاز: " . $product_price . " تومان
⚠️ کمبود اعتبار: " . $shortage . " تومان

❌ برای شارژ حساب کاربری خود یکی از روش های پرداخت زیر را انتخاب کنید";
        
        sendmessage($from_id, $error_message, $step_payment, \'HTML\');',
        $content
    );
    
    // ذخیره فایل
    if (file_put_contents($cronFile, $content)) {
        echo "فایل cron/index.php با موفقیت اصلاح شد.\n";
    } else {
        echo "خطا در اصلاح فایل cron/index.php\n";
    }
} else {
    echo "فایل cron/index.php یافت نشد.\n";
}

echo "عملیات اصلاح پیام خطای کمبود موجودی با موفقیت انجام شد.\n"; 