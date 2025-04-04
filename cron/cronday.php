<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once '../config.php';
require_once '../botapi.php';
require_once '../panels.php';
require_once '../functions.php';
require_once '../text.php';
$ManagePanel = new ManagePanel();


// buy service 
$stmt = $pdo->prepare("SELECT * FROM invoice WHERE (status = 'active' OR status = 'end_of_volume') AND name_product != 'usertest' ORDER BY RAND() LIMIT 5");
$stmt->execute();
while ($line = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $resultss = $line;
    $marzban_list_get = select("marzban_panel","*","name_panel",$resultss['Service_location'],"select");
    $get_username_Check = $ManagePanel->DataUser($resultss['Service_location'],$resultss['username']);
    if($get_username_Check['status'] != "Unsuccessful"){
        if(in_array($get_username_Check['status'],['active','on_hold'])){
            $timeservice = $get_username_Check['expire'] - time();
            $day = floor($timeservice / 86400)+1;
            $output =  $get_username_Check['data_limit'] - $get_username_Check['used_traffic'];
            $textservice = select("textbot","text","id_text","text_Purchased_services","select")['text'];
            $RemainingVolume = formatBytes($output);
            $Response = json_encode([
                'inline_keyboard' => [
                    [
                        ['text' => "💊 تمدید سرویس", 'callback_data' => 'extend_' . $resultss['username']],
                    ],
                ]
            ]);
            if ($timeservice <= "167000" && $timeservice > 0) {
                $text = "با سلام خدمت شما کاربر گرامی 👋

🔰 سرویس فعال شما با نام کاربری {$resultss['username']} به زودی منقضی خواهد شد!
⏰ مدت اعتبار باقی مانده : $day روز
⌛️ حجم باقیمانده از سرویس : $RemainingVolume
    
در صورت تمایل می توانید اکانت خود را تمدید نمایید 👇";
                sendmessage($resultss['id_user'], $text, $Response, 'HTML');
            }
            if($get_username_Check && !in_array($get_username_Check['status'],['active','on_hold'])){
                update("invoice","status","disabled", "username",$line['username']);
            }
        }
    }
}

// تمدید خودکار سرویس‌های منقضی شده که قابلیت تمدید خودکار آنها فعال است
// بررسی وجود ستون auto_renewal در جدول invoice
try {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM invoice LIKE 'auto_renewal'");
    $stmt->execute();
    $column_exists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // اگر ستون وجود نداشت، آن را اضافه کنیم
    if (!$column_exists) {
        $pdo->exec("ALTER TABLE invoice ADD COLUMN auto_renewal VARCHAR(20) DEFAULT 'inactive'");
    }
    
    // پیدا کردن سرویس‌های منقضی شده که تمدید خودکار آنها فعال است
    $stmt = $pdo->prepare("
        SELECT i.*, u.Balance, p.price_product, p.Service_time, p.Volume_constraint 
        FROM invoice i 
        JOIN user u ON i.id_user = u.id
        JOIN product p ON i.price_product = p.price_product AND i.category = p.category
        WHERE i.auto_renewal = 'active' 
        AND i.status IN ('active', 'expired', 'end_of_volume', 'end_of_time')
        AND i.name_product != 'usertest'
    ");
    $stmt->execute();
    $expired_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($expired_services as $service) {
        $id_user = $service['id_user'];
        $username = $service['username'];
        $product_name = $service['name_product'];
        $service_location = $service['Service_location'];
        
        // دریافت اطلاعات سرویس از پنل
        $panel_data = $ManagePanel->DataUser($service_location, $username);
        
        // اگر سرویس منقضی شده یا حجم آن تمام شده
        if (
            $panel_data && 
            (
                $panel_data['status'] == 'expired' || 
                $panel_data['status'] == 'limited' ||
                (time() > $panel_data['expire'] && $panel_data['expire'] != 0) ||
                ($panel_data['used_traffic'] >= $panel_data['data_limit'] && $panel_data['data_limit'] != 0)
            )
        ) {
            // بررسی موجودی کاربر
            $user_balance = $service['Balance'];
            $service_price = $service['price_product'];
            
            // بررسی آیا کاربر نماینده است و تخفیف دارد
            $stmt = $pdo->prepare("SELECT * FROM agency WHERE user_id = ? AND status = 'approved'");
            $stmt->execute([$id_user]);
            $agency = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $discounted_price = $service_price;
            $is_agent = false;
            $discount_percent = 0;
            
            if ($agency) {
                $is_agent = true;
                $discount_percent = $agency['discount_percent'];
                $discounted_price = $service_price - ($service_price * $discount_percent / 100);
            }
            
            // قیمت نهایی که باید پرداخت شود (با یا بدون تخفیف)
            $final_price = $is_agent ? $discounted_price : $service_price;
            
            // اگر کاربر موجودی کافی داشت، سرویس را تمدید کنیم
            if ($user_balance >= $final_price) {
                // کسر موجودی از کیف پول کاربر
                $new_balance = $user_balance - $final_price;
                $stmt = $pdo->prepare("UPDATE user SET Balance = ? WHERE id = ?");
                $stmt->execute([$new_balance, $id_user]);
                
                // تمدید سرویس در پنل
                $marzban_list_get = select("marzban_panel", "*", "name_panel", $service_location, "select");
                if ($marzban_list_get['type'] == "marzban") {
                    if (intval($service['Service_time']) == 0) {
                        $newDate = 0;
                    } else {
                        $date = strtotime("+" . $service['Service_time'] . "day");
                        $newDate = strtotime(date("Y-m-d H:i:s", $date));
                    }
                    $data_limit = intval($service['Volume_constraint']) * pow(1024, 3);
                    $datam = array(
                        "expire_date" => $newDate,
                        "data_limit" => $data_limit
                    );
                    $ManagePanel->Modifyuser($username, $service_location, $datam);
                } elseif ($marzban_list_get['type'] == "x-ui_single") {
                    $date = strtotime("+" . $service['Service_time'] . "day");
                    $newDate = strtotime(date("Y-m-d H:i:s", $date)) * 1000;
                    $data_limit = intval($service['Volume_constraint']) * pow(1024, 3);
                    $config = array(
                        'id' => intval($marzban_list_get['inboundid']),
                        'settings' => json_encode(
                            array(
                                'clients' => array(
                                    array(
                                        "totalGB" => $data_limit,
                                        "expiryTime" => $newDate,
                                        "enable" => true,
                                    )
                                ),
                            )
                        ),
                    );
                    $ManagePanel->Modifyuser($username, $service_location, $config);
                }
                
                // به‌روزرسانی وضعیت سرویس در دیتابیس
                $stmt = $pdo->prepare("UPDATE invoice SET status = 'active' WHERE username = ?");
                $stmt->execute([$username]);
                
                // ارسال پیام تمدید موفق به کاربر
                if ($is_agent) {
                    $success_message = "✅ تمدید خودکار سرویس با موفقیت انجام شد

🔰 اطلاعات تمدید:
👤 نام کاربری: <code>$username</code>
📦 نام محصول: $product_name
⏱ مدت زمان: {$service['Service_time']} روز
💾 حجم: {$service['Volume_constraint']} گیگابایت
💰 قیمت اصلی: " . number_format($service_price) . " تومان
🎁 تخفیف نمایندگی: $discount_percent درصد
💵 مبلغ پرداختی: " . number_format($final_price) . " تومان

📌 این تمدید به صورت خودکار انجام شده و مبلغ آن از کیف پول شما کسر شده است.";
                } else {
                    $success_message = "✅ تمدید خودکار سرویس با موفقیت انجام شد

🔰 اطلاعات تمدید:
👤 نام کاربری: <code>$username</code>
📦 نام محصول: $product_name
⏱ مدت زمان: {$service['Service_time']} روز
💾 حجم: {$service['Volume_constraint']} گیگابایت
💰 مبلغ پرداختی: " . number_format($service_price) . " تومان

📌 این تمدید به صورت خودکار انجام شده و مبلغ آن از کیف پول شما کسر شده است.";
                }
                
                $keyboard_back = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => "🔄 مشاهده اطلاعات سرویس", 'callback_data' => "product_" . $username],
                            ['text' => "🏠 منوی اصلی", 'callback_data' => "backuser"]
                        ]
                    ]
                ]);
                
                sendmessage($id_user, $success_message, $keyboard_back, 'HTML');
            } else {
                // اگر موجودی کافی نبود، به کاربر اطلاع دهیم
                $shortage = $final_price - $user_balance;
                
                $payment_info = $is_agent ? 
                    "💰 موجودی فعلی شما: " . number_format($user_balance) . " تومان
💲 مبلغ مورد نیاز: " . number_format($final_price) . " تومان (با اعمال $discount_percent% تخفیف نمایندگی)
⚠️ کمبود اعتبار: " . number_format($shortage) . " تومان" :
                    "💰 موجودی فعلی شما: " . number_format($user_balance) . " تومان
💲 مبلغ مورد نیاز: " . number_format($service_price) . " تومان
⚠️ کمبود اعتبار: " . number_format($shortage) . " تومان";
                
                $message = "⚠️ سرویس «$username» شما منقضی شده و نیاز به تمدید دارد.

📌 با توجه به اینکه تمدید خودکار برای این سرویس فعال است، سیستم قصد داشت آن را به‌طور خودکار تمدید کند، اما موجودی کیف پول شما کافی نیست.

$payment_info

لطفاً کیف پول خود را شارژ کنید تا سرویس شما تمدید شود.";
                
                $keyboard_charge = json_encode([
                    'inline_keyboard' => [
                        [
                            ['text' => "💰 شارژ کیف پول", 'callback_data' => "menu_pay"]
                        ]
                    ]
                ]);
                
                sendmessage($id_user, $message, $keyboard_charge, 'HTML');
            }
        }
    }
} catch (PDOException $e) {
    error_log("خطا در تمدید خودکار سرویس‌ها: " . $e->getMessage());
}