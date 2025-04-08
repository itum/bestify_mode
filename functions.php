<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
function ActiveVoucher($ev_number, $ev_code){
    global $connect;
    $Payer_Account = select("PaySetting", "ValuePay", "NamePay", 'perfectmoney_Payer_Account',"select")['ValuePay'];
    $AccountID = select("PaySetting", "ValuePay", "NamePay", 'perfectmoney_AccountID',"select")['ValuePay'];
    $PassPhrase = select("PaySetting", "ValuePay", "NamePay", 'perfectmoney_PassPhrase',"select")['ValuePay'];
    $opts = array(
        'socket' => array(
            'bindto' => 'ip',
        )
    );

    $context = stream_context_create($opts);

    $voucher = file_get_contents("https://perfectmoney.com/acct/ev_activate.asp?AccountID=" . $AccountID . "&PassPhrase=" . $PassPhrase . "&Payee_Account=" . $Payer_Account . "&ev_number=" . $ev_number . "&ev_code=" . $ev_code);
    return $voucher;
}
function update($table, $field, $newValue, $whereField = null, $whereValue = null) {
    global $pdo,$user;

    if ($whereField !== null) {
        $stmt = $pdo->prepare("SELECT $field FROM $table WHERE $whereField = ? FOR UPDATE");
        $stmt->execute([$whereValue]);
        $currentValue = $stmt->fetchColumn();
        $stmt = $pdo->prepare("UPDATE $table SET $field = ? WHERE $whereField = ?");
        $stmt->execute([$newValue, $whereValue]);
    } else {
        $stmt = $pdo->prepare("UPDATE $table SET $field = ?");
        $stmt->execute([$newValue]);
    }
}
function step($step, $from_id){
    global $pdo;
    $stmt = $pdo->prepare('UPDATE user SET step = ? WHERE id = ?');
    $stmt->execute([$step, $from_id]);


}
function select($table, $field, $whereField = null, $whereValue = null, $type = "select") {
    global $pdo;

    $query = "SELECT $field FROM $table";

    if ($whereField !== null) {
        $query .= " WHERE $whereField = :whereValue";
    }

    try {
        $stmt = $pdo->prepare($query);

        if ($whereField !== null) {
            $stmt->bindParam(':whereValue', $whereValue , PDO::PARAM_STR);
        }

        $stmt->execute();

        if ($type == "count") {
            return $stmt->rowCount();
        } elseif ($type == "FETCH_COLUMN") {
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }elseif ($type == "fetchAll") {
            return $stmt->fetchAll();
        } else {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        die("Query failed: " . $e->getMessage());
    }
}

function generateUUID() {
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

    return $uuid;
}
function tronratee(){
    $tronrate = [];
    $requeststron = json_decode(file_get_contents('https://api.nobitex.ir/v2/orderbook/TRXIRT'), true);
    $requestsusd = json_decode(file_get_contents('https://api.nobitex.ir/v2/orderbook/USDTIRT'), true);
    $tronrate['result']['USD'] = $requestsusd['lastTradePrice']*0.1;
    $tronrate['result']['TRX'] = $requeststron['lastTradePrice']*0.1;
    return $tronrate;
}
function nowPayments($payment, $price_amount, $order_id, $order_description){
    $apinowpayments = select("PaySetting", "ValuePay", "NamePay", 'apinowpayment',"select")['ValuePay'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.nowpayments.io/v1/' . $payment,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 4500,
        CURLOPT_ENCODING => '',
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => array(
            'x-api-key:' . $apinowpayments,
            'Content-Type: application/json'
        ),
    ));
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode([
        'price_amount' => $price_amount,
        'price_currency' => 'usd',
        'pay_currency' => 'trx',
        'order_id' => $order_id,
        'order_description' => $order_description,
    ]));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response);
}
function StatusPayment($paymentid){
    $apinowpayments = select("PaySetting", "ValuePay", "NamePay", 'apinowpayment',"select")['ValuePay'];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.nowpayments.io/v1/payment/' . $paymentid,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'x-api-key:' . $apinowpayments
        ),
    ));
    $response = curl_exec($curl);
    $response = json_decode($response, true);
    curl_close($curl);
    return $response;
}
function formatBytes($bytes, $precision = 2): string
{
    global $textbotlang;
    $base = log($bytes, 1024);
    $power = $bytes > 0 ? floor($base) : 0;
    $suffixes = [$textbotlang['users']['format']['byte'],$textbotlang['users']['format']['kilobyte'],$textbotlang['users']['format']['MBbyte'], $textbotlang['users']['format']['GBbyte'],$textbotlang['users']['format']['TBbyte']];
    return round(pow(1024, $base - $power), $precision) . ' ' . $suffixes[$power];
}
#---------------------[ ]--------------------------#
function generateUsername($from_id,$Metode,$username,$randomString,$text)
{
    global $connect,$textbotlang;
    $setting = select("setting", "*");
    global $connect;
    $generatedUsername = "";
    
    if($Metode == $textbotlang['users']['customidAndRandom']){
        $generatedUsername = $from_id."_".$randomString;
    }
    elseif($Metode == $textbotlang['users']['customusernameandorder']){
        $generatedUsername = $username."_".$randomString;
    }
    elseif($Metode == $textbotlang['users']['customusernameorder']){
        $statistics = mysqli_fetch_assoc(mysqli_query($connect, "SELECT COUNT(id_user)  FROM invoice WHERE id_user = '$from_id'"));
        $countInvoice = intval($statistics['COUNT(id_user)']) + 1 ;
        $generatedUsername = $username."_".$countInvoice;
    }
    elseif($Metode == $textbotlang['users']['customusername']){
        $generatedUsername = $text;
    }
    elseif($Metode == $textbotlang['users']['customtextandrandom']){
        $generatedUsername = $setting['namecustome']."_".$randomString;
    }
    
    // Validate and format the username to meet Marzban requirements
    return validateMarzbanUsername($generatedUsername);
}

function outputlunk($text){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $text);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    $response = curl_exec($ch);
    if($response === false) {
        $error = curl_error($ch);
        return "";
    } else {
        return $response;
    }

    curl_close($ch);
}
function DirectPayment($order_id){
    global $pdo,$ManagePanel,$textbotlang,$keyboard,$from_id,$message_id,$callback_query_id;
    $setting = select("setting", "*");
    $admin_ids = select("admin", "id_admin",null,null,"FETCH_COLUMN");
    $Payment_report = select("Payment_report", "*", "id_order", $order_id,"select");
    $format_price_cart = number_format($Payment_report['price']);
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'],"select");
    $steppay = explode("|", $Payment_report['invoice']);
    if ($steppay[0] == "getconfigafterpay") {
        $stmt = $pdo->prepare("SELECT * FROM invoice WHERE username = '{$steppay[1]}' AND Status = 'unpaid' LIMIT 1");
        $stmt->execute();
        $get_invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("SELECT * FROM product WHERE name_product = '{$get_invoice['name_product']}' AND (Location = '{$get_invoice['Service_location']}'  or Location = '/all')");
        $stmt->execute();
        $info_product = $stmt->fetch(PDO::FETCH_ASSOC);
        $username_ac = $get_invoice['username'];

        // Validate username before attempting to create user
        $username_ac = validateMarzbanUsername($username_ac);
        if (!preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~', $username_ac)) {
            // Username is invalid even after validation attempt
            sendmessage($Balance_id['id'], "خطا در ساخت کانفیگ\n✍️ دلیل خطا : نام کاربری نامعتبر است. نام کاربری باید بین 3 تا 32 کاراکتر باشد و فقط شامل حروف کوچک، اعداد و زیرخط باشد.", $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'], "نام کاربری نامعتبر", $Balance_id['id'], $Balance_id['username']);
            foreach ($admin_ids as $admin) {
                sendmessage($admin, $texterros, null, 'HTML');
                step('home', $admin);
            }
            // Return payment to user's balance since config creation failed
            $Balance_prim = $Balance_id['Balance'] + $get_invoice['price_product'];
            update("user", "Balance", $Balance_prim, "id", $Balance_id['id']);
            update("Payment_report", "payment_Status", "refunded", "id_order", $order_id);
            return;
        }
        
        $randomString = bin2hex(random_bytes(2));
        $marzban_list_get = select("marzban_panel", "*", "name_panel", $get_invoice['Service_location'],"select");
        $date = strtotime("+" . $get_invoice['Service_time'] . "days");
        if(intval($get_invoice['Service_time']) == 0){
            $timestamp = 0;
        }else{
            $timestamp = strtotime(date("Y-m-d H:i:s", $date));
        }
        $datac = array(
            'expire' => $timestamp,
            'data_limit' => $get_invoice['Volume'] * pow(1024, 3),
        );
        $dataoutput = $ManagePanel->createUser($marzban_list_get['name_panel'],$username_ac,$datac);

        if ($dataoutput['username'] == null) {
            $dataoutput['msg'] = json_encode($dataoutput['msg']);
            sendmessage($Balance_id['id'], $textbotlang['users']['sell']['ErrorConfig'], $keyboard, 'HTML');
            $texterros = sprintf($textbotlang['users']['buy']['errorInCreate'],$dataoutput['msg'],$Balance_id['id'],$Balance_id['username']);
            foreach ($admin_ids as $admin) {
                sendmessage($admin, $texterros, null, 'HTML');
                step('home', $admin);
            }
            // Return payment to user's balance since config creation failed
            $Balance_prim = $Balance_id['Balance'] + $get_invoice['price_product'];
            update("user", "Balance", $Balance_prim, "id", $Balance_id['id']);
            update("Payment_report", "payment_Status", "refunded", "id_order", $order_id);
            return;
        }
        $output_config_link = "";
        $config = "";
        $Shoppinginfo = [
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['users']['help']['btninlinebuy'], 'callback_data' => "helpbtn"],
                ]
            ]
        ];
        if ($marzban_list_get['sublink'] == "onsublink") {
            $output_config_link = $dataoutput['subscription_url'];
        }
        if ($marzban_list_get['configManual'] == "onconfig") {
            if(isset($dataoutput['configs']) and count($dataoutput['configs']) !=0){
                foreach ($dataoutput['configs'] as $configs) {
                    $config .= "\n" . $configs;
                    $configqr .= $configs;
                }
            }else{
                $config .= "";
                $configqr .= "";
            }
        }
        $Shoppinginfo = json_encode($Shoppinginfo);
        $textcreatuser = sprintf($textbotlang['users']['buy']['createservice'],$dataoutput['username'],$get_invoice['name_product'],$marzban_list_get['name_panel'],$get_invoice['Service_time'],$get_invoice['Volume'],$config,$output_config_link);
        if ($marzban_list_get['configManual'] == "onconfig") {
            if (count($dataoutput['configs']) == 1) {
                $urlimage = "{$get_invoice['id_user']}$randomString.png";
                $writer = new PngWriter();
                $qrCode = QrCode::create($configqr)
                    ->setEncoding(new Encoding('UTF-8'))
                    ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                    ->setSize(400)
                    ->setMargin(0)
                    ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
                $result = $writer->write($qrCode, null, null);
                $result->saveToFile($urlimage);
                telegram('sendphoto', [
                    'chat_id' => $get_invoice['id_user'],
                    'photo' => new CURLFile($urlimage),
                    'reply_markup' => $Shoppinginfo,
                    'caption' => $textcreatuser,
                    'parse_mode' => "HTML",
                ]);
                unlink($urlimage);
            } else {
                sendmessage($get_invoice['id_user'], $textcreatuser, $Shoppinginfo, 'HTML');
            }
        }
        elseif ($marzban_list_get['sublink'] == "onsublink") {
            $urlimage = "{$get_invoice['id_user']}$randomString.png";
            $writer = new PngWriter();
            $qrCode = QrCode::create($output_config_link)
                ->setEncoding(new Encoding('UTF-8'))
                ->setErrorCorrectionLevel(ErrorCorrectionLevel::Low)
                ->setSize(400)
                ->setMargin(0)
                ->setRoundBlockSizeMode(RoundBlockSizeMode::Margin);
            $result = $writer->write($qrCode, null, null);
            $result->saveToFile($urlimage);
            telegram('sendphoto', [
                'chat_id' => $get_invoice['id_user'],
                'photo' => new CURLFile($urlimage),
                'reply_markup' => $Shoppinginfo,
                'caption' => $textcreatuser,
                'parse_mode' => "HTML",
            ]);
            unlink($urlimage);
        }
        $partsdic = explode("_", $Balance_id['Processing_value_four']);
        if ($partsdic[0] == "dis") {
            $SellDiscountlimit = select("DiscountSell", "*", "codeDiscount", $partsdic[1],"select");
            $value = intval($SellDiscountlimit['usedDiscount']) + 1;
            update("DiscountSell","usedDiscount",$value, "codeDiscount",$partsdic[1]);
            $stmt = $pdo->prepare("INSERT INTO Giftcodeconsumed (id_user,code) VALUES (:id_user,:code)");
            $stmt->bindParam(':id_user', $Balance_id['id']);
            $stmt->bindParam(':code', $partsdic[1]);
            $stmt->execute();
            $result = ($SellDiscountlimit['price'] / 100) * $get_invoice['price_product'];
            $pricediscount = $get_invoice['price_product'] - $result;
            $text_report = sprintf($textbotlang['users']['Report']['discountused'],$Balance_id['username'],$Balance_id['id'],$partsdic[1]);
            if (strlen($setting['Channel_Report']) > 0) {
                telegram('sendmessage',[
                    'chat_id' => $setting['Channel_Report'],
                    'text' => $text_report,
                ]);
            }
        }else{
            $pricediscount = null;
        }
        $affiliatescommission = select("affiliates", "*", null, null,"select");
        if ($affiliatescommission['status_commission'] == "oncommission" &&($Balance_id['affiliates'] !== null || $Balance_id['affiliates'] != 0)) {
            if($pricediscount == null){
                $result = ($get_invoice['price_product'] * $affiliatescommission['affiliatespercentage']) / 100;
            }else{
                $result = ($pricediscount * $affiliatescommission['affiliatespercentage']) / 100;
            }
            $user_Balance = select("user", "*", "id", $Balance_id['affiliates'],"select");
            if(isset($user_Balance)){
                $Balance_prim = $user_Balance['Balance'] + $result;
                update("user","Balance",$Balance_prim, "id",$Balance_id['affiliates']);
                $result = number_format($result);
                $textadd =sprintf($textbotlang['users']['affiliates']['porsantuser'],$result);
                sendmessage($Balance_id['affiliates'], $textadd, null, 'HTML');
            }
        }
        $Balance_prims = $Balance_id['Balance'] - $get_invoice['price_product'];
        if($Balance_prims <= 0) $Balance_prims = 0;
        update("user","Balance",$Balance_prims, "id",$Balance_id['id']);
        $Balance_id['Balance'] = select("user", "Balance", "id", $get_invoice['id_user'],"select")['Balance'];
        $balanceformatsell = number_format($Balance_id['Balance'], 0);
        $text_report = sprintf($textbotlang['users']['Report']['reportbuyafterpay'] ,$get_invoice['username'],$get_invoice['price_product'],$get_invoice['Volume'],$get_invoice['id_user'],$Balance_id['number'],$get_invoice['Service_location'],$balanceformatsell,$randomString,$Balance_id['username']);
        if (strlen($setting['Channel_Report']) > 0) {
            telegram('sendmessage',[
                'chat_id' => $setting['Channel_Report'],
                'text' => $text_report,
                'parse_mode' => "HTML"
            ]);
        }
        update("invoice","status","active","username",$get_invoice['username']);
        if($Payment_report['Payment_Method'] == "cart to cart"){
            update("invoice","Status","active","id_invoice",$get_invoice['id_invoice']);
            telegram('answerCallbackQuery', array(
                    'callback_query_id' => $callback_query_id,
                    'text' => $textbotlang['users']['moeny']['acceptedcart'],
                    'show_alert' => true,
                    'cache_time' => 5,
                )
            );
        }
    }else {
        // بررسی امکان شارژ دوبرابر
        $double_charge = false;
        $setting = select("setting", "*");
        
        try {
            // بررسی فعال بودن ویژگی شارژ دوبرابر
            if(isset($setting['double_charge_status']) && $setting['double_charge_status'] == 'on') {
                // بررسی اینکه کاربر نماینده نباشد
                $agency_exists = $pdo->prepare("SHOW TABLES LIKE 'agency'");
                $agency_exists->execute();
                $agency_user = false;
                
                if ($agency_exists->rowCount() > 0) {
                    $stmt_agency = $pdo->prepare("SELECT * FROM agency WHERE user_id = :user_id AND status = 'approved'");
                    $stmt_agency->bindParam(':user_id', $Payment_report['id_user']);
                    $stmt_agency->execute();
                    $agency_user = $stmt_agency->rowCount() > 0;
                }
                
                if(!$agency_user) {
                    // بررسی تنظیمات حداقل تعداد خرید
                    $min_purchase = isset($setting['double_charge_min_purchase']) ? intval($setting['double_charge_min_purchase']) : 3;
                    
                    // اگر min_purchase صفر باشد، نیازی به بررسی تعداد خرید نیست
                    $meets_purchase_requirement = ($min_purchase == 0);
                    
                    // اگر نیاز به بررسی تعداد خرید باشد
                    if (!$meets_purchase_requirement) {
                        // بررسی اینکه کاربر به حداقل تعداد خرید رسیده باشد
                        $stmt = $pdo->prepare("SELECT COUNT(*) as purchase_count FROM invoice WHERE id_user = :user_id AND Status = 'active'");
                        $stmt->bindParam(':user_id', $Payment_report['id_user']);
                        $stmt->execute();
                        $purchase_count = $stmt->fetch(PDO::FETCH_ASSOC)['purchase_count'];
                        
                        $meets_purchase_requirement = ($purchase_count >= $min_purchase);
                    }
                    
                    if($meets_purchase_requirement) {
                        // بررسی وجود جدول double_charge_users
                        $table_exists = $pdo->prepare("SHOW TABLES LIKE 'double_charge_users'");
                        $table_exists->execute();
                        
                        if ($table_exists->rowCount() == 0) {
                            // جدول وجود ندارد، آن را ایجاد می‌کنیم
                            $create_table = "CREATE TABLE IF NOT EXISTS double_charge_users (
                                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                user_id varchar(500) NOT NULL,
                                used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin";
                            $pdo->exec($create_table);
                        }
                        
                        // بررسی اینکه کاربر قبلاً از این ویژگی استفاده نکرده باشد
                        $stmt = $pdo->prepare("SELECT * FROM double_charge_users WHERE user_id = :user_id");
                        $stmt->bindParam(':user_id', $Payment_report['id_user']);
                        $stmt->execute();
                        
                        if($stmt->rowCount() == 0) {
                            // بررسی مهلت زمانی استفاده از طرح
                            $expiry_hours = isset($setting['double_charge_expiry_hours']) ? intval($setting['double_charge_expiry_hours']) : 72;
                            $within_time_limit = true; // پیش‌فرض: در محدوده زمانی مجاز است
                            
                            // اگر جدول اطلاع‌رسانی وجود دارد، محدودیت زمانی را بررسی می‌کنیم
                            $notification_table_exists = $pdo->prepare("SHOW TABLES LIKE 'double_charge_notifications'");
                            $notification_table_exists->execute();
                            
                            if ($notification_table_exists->rowCount() > 0) {
                                // بررسی اینکه آیا به کاربر اطلاع‌رسانی شده و آیا در محدوده زمانی مجاز است
                                $stmt = $pdo->prepare("SELECT * FROM double_charge_notifications WHERE user_id = :user_id");
                                $stmt->bindParam(':user_id', $Payment_report['id_user']);
                                $stmt->execute();
                                
                                if ($stmt->rowCount() > 0) {
                                    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                                    $expiry_at = strtotime($notification['expiry_at']);
                                    $now = time();
                                    
                                    // بررسی اینکه آیا زمان استفاده منقضی شده یا نه
                                    $within_time_limit = ($now <= $expiry_at);
                                } else {
                                    // اگر اطلاع‌رسانی نشده، نیازی به بررسی محدودیت زمانی نیست
                                    $within_time_limit = true;
                                }
                            }
                            
                            // کاربر واجد شرایط شارژ دوبرابر است (در صورتی که در محدوده زمانی مجاز باشد)
                            if ($within_time_limit) {
                                $double_charge = true;
                                
                                // ثبت استفاده کاربر از ویژگی شارژ دوبرابر
                                $stmt = $pdo->prepare("INSERT INTO double_charge_users (user_id) VALUES (:user_id)");
                                $stmt->bindParam(':user_id', $Payment_report['id_user']);
                                $stmt->execute();
                                
                                // حذف رکورد اطلاع‌رسانی پس از استفاده از طرح
                                if ($notification_table_exists->rowCount() > 0) {
                                    $stmt = $pdo->prepare("DELETE FROM double_charge_notifications WHERE user_id = :user_id");
                                    $stmt->bindParam(':user_id', $Payment_report['id_user']);
                                    $stmt->execute();
                                }
                            } else {
                                // مهلت استفاده از طرح تمام شده است
                                error_log("مهلت استفاده از شارژ دوبرابر برای کاربر {$Payment_report['id_user']} به پایان رسیده است.");
                            }
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            // در صورت خطا، لاگ می‌کنیم اما ادامه می‌دهیم تا پرداخت معمولی انجام شود
            error_log("خطا در بررسی شرایط شارژ دوبرابر: " . $e->getMessage());
            $double_charge = false;
        }
        
        // محاسبه مبلغ شارژ (عادی یا دوبرابر)
        $charge_amount = intval($Payment_report['price']);
        if($double_charge) {
            $charge_amount *= 2;
        }
        
        $Balance_confrim = intval($Balance_id['Balance']) + $charge_amount;
        update("user","Balance",$Balance_confrim, "id",$Payment_report['id_user']);
        update("Payment_report","payment_Status","paid","id_order",$Payment_report['id_order']);
        
        // ارسال پیام به کاربر
        if($double_charge) {
            $format_price_original = number_format($Payment_report['price'], 0);
            $format_price_doubled = number_format($charge_amount, 0);
            $textpay = "🎁 تبریک! شارژ دوبرابر\n✅ مبلغ {$format_price_original} تومان پرداخت کردید و {$format_price_doubled} تومان شارژ شد!\n🔰 شماره پیگیری: {$Payment_report['id_order']}";
        } else {
        $Payment_report['price'] = number_format($Payment_report['price'], 0);
        $format_price_cart = $Payment_report['price'];
            $textpay = sprintf($textbotlang['users']['moeny']['Charged.'],$Payment_report['price'],$Payment_report['id_order']);
        }
        
        if($Payment_report['Payment_Method'] == "cart to cart"){
            telegram('answerCallbackQuery', array(
                    'callback_query_id' => $callback_query_id,
                    'text' => $textbotlang['users']['moeny']['acceptedcart'],
                    'show_alert' => true,
                    'cache_time' => 5,
                )
            );
        }
        
        sendmessage($Payment_report['id_user'], $textpay, null, 'HTML');
    }
}
function savedata($type,$namefiled,$valuefiled){
    global $from_id;
    if($type == "clear"){
        $datauser = [];
        $datauser[$namefiled] = $valuefiled;
        $data = json_encode($datauser);
        update("user","Processing_value",$data,"id",$from_id);
    }elseif($type == "save"){
        $userdata = select("user","*","id",$from_id,"select");
        $dataperevieos = json_decode($userdata['Processing_value'],true);
        $dataperevieos[$namefiled] = $valuefiled;
        update("user","Processing_value",json_encode($dataperevieos),"id",$from_id);
    }
}
function sanitizeUserName($userName) {
    $forbiddenCharacters = [
        "'", "\"", "<", ">", "--", "#", ";", "\\", "%", "(", ")"
    ];

    foreach ($forbiddenCharacters as $char) {
        $userName = str_replace($char, "", $userName);
    }

    return $userName;
}

function validateMarzbanUsername($username) {
    // Check if username follows Marzban pattern: lowercase letters, numbers, and underscores
    // Username must start with a letter, be 3-32 characters long, and not end with underscore
    if (preg_match('~(?!_)^[a-z][a-z\d_]{2,32}(?<!_)$~', $username)) {
        return $username;
    } else {
        // Convert to valid format if possible
        $username = preg_replace('/[^a-z0-9_]/', '', strtolower($username));
        
        // Make sure it starts with a letter
        if (!preg_match('/^[a-z]/', $username)) {
            $username = 'u' . $username;
        }
        
        // Make sure it's at least 3 characters
        if (strlen($username) < 3) {
            $username .= random_int(100, 999);
        }
        
        // Make sure it's not more than 32 characters
        if (strlen($username) > 32) {
            $username = substr($username, 0, 32);
        }
        
        // Make sure it doesn't end with an underscore
        if (substr($username, -1) === '_') {
            $username = substr($username, 0, -1) . random_int(0, 9);
        }
        
        return $username;
    }
}

function checktelegramip(){

    $telegram_ip_ranges = [
        ['lower' => '149.154.160.0', 'upper' => '149.154.175.255'],
        ['lower' => '91.108.4.0',    'upper' => '91.108.7.255']
    ];
    $ip_dec = (float) sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
    $ok = false;
    foreach ($telegram_ip_ranges as $telegram_ip_range) if (!$ok) {
        $lower_dec = (float) sprintf("%u", ip2long($telegram_ip_range['lower']));
        $upper_dec = (float) sprintf("%u", ip2long($telegram_ip_range['upper']));
        if ($ip_dec >= $lower_dec and $ip_dec <= $upper_dec) $ok = true;
    }
    return $ok;

}
function generateAuthStr($length = 10) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle(str_repeat($characters, ceil($length / strlen($characters)))), 0, $length);
}
function delete($table, $field, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE $field = :value");
        $stmt->bindParam(':value', $value);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("خطا در حذف رکورد: " . $e->getMessage());
        return false;
    }
}
function channel($id_channel){
    global $from_id,$APIKEY;
    $channel_link = array();
    $response = telegram('getChatMember',[
        "chat_id" => "@$id_channel",
        "user_id" => $from_id,
    ]);
    if($response['ok']){
        if(!in_array($response['result']['status'], ['member', 'creator', 'administrator'])){
            $channel_link[] = $id_channel;
        }
    }
    if(count($channel_link) == 0){
        return [];
    }else{
        return $channel_link;
    }
}

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

function generate_random_amount($base_amount) {
    // تولید عدد تصادفی سه رقمی
    $random_digits = rand(100, 999);
    // اضافه کردن عدد تصادفی به مبلغ پایه
    $final_amount = $base_amount + $random_digits;
    return $final_amount;
}

function check_payment_status($order_id, $expected_amount) {
    $api_url = "https://api.ariana-ielts.com/pay-api.php";
    
    try {
        $response = file_get_contents($api_url);
        $data = json_decode($response, true);
        
        if ($data && isset($data['transactions'])) {
            foreach ($data['transactions'] as $transaction) {
                // تبدیل مبلغ از ریال به تومان (تقسیم بر 10)
                $transaction_amount_rial = isset($transaction['transactionAmountCredit']) ? $transaction['transactionAmountCredit'] : 0;
                $transaction_amount_toman = $transaction_amount_rial / 10;
                
                // بررسی دقیق مبلغ با تبدیل صحیح از ریال به تومان
                if (abs($transaction_amount_toman - $expected_amount) < 1) {  // تقریبی برای مقایسه اعداد اعشاری
                    // تراکنش یافت شد - پرداخت تایید می‌شود
                    return [
                        'status' => true,
                        'transaction' => $transaction
                    ];
                }
            }
        }
        // هیچ تراکنشی با مبلغ مورد نظر یافت نشد
        return [
            'status' => false,
            'message' => 'تراکنشی با این مبلغ یافت نشد'
        ];
    } catch (Exception $e) {
        error_log("Error checking payment status: " . $e->getMessage());
        return [
            'status' => false,
            'message' => 'خطا در ارتباط با سرور: ' . $e->getMessage()
        ];
    }
}

function convert_to_persian_words($number) {
    $ones = array(
        0 => '', 1 => 'یک', 2 => 'دو', 3 => 'سه', 4 => 'چهار', 5 => 'پنج',
        6 => 'شش', 7 => 'هفت', 8 => 'هشت', 9 => 'نه', 10 => 'ده',
        11 => 'یازده', 12 => 'دوازده', 13 => 'سیزده', 14 => 'چهارده', 15 => 'پانزده',
        16 => 'شانزده', 17 => 'هفده', 18 => 'هجده', 19 => 'نوزده'
    );
    $tens = array(
        2 => 'بیست', 3 => 'سی', 4 => 'چهل', 5 => 'پنجاه',
        6 => 'شصت', 7 => 'هفتاد', 8 => 'هشتاد', 9 => 'نود'
    );
    $hundreds = array(
        1 => 'صد', 2 => 'دویست', 3 => 'سیصد', 4 => 'چهارصد', 5 => 'پانصد',
        6 => 'ششصد', 7 => 'هفتصد', 8 => 'هشتصد', 9 => 'نهصد'
    );
    $thousands = array(
        1 => 'هزار', 2 => 'میلیون', 3 => 'میلیارد'
    );

    if ($number == 0) return 'صفر';
    
    $number = (int)str_replace(',', '', $number);
    if ($number < 20) return $ones[$number];
    
    $words = array();
    $level = 0;
    
    while ($number > 0) {
        $chunk = $number % 1000;
        if ($chunk > 0) {
            $chunk_words = array();
            
            // صدگان
            $hundreds_digit = floor($chunk / 100);
            if ($hundreds_digit > 0) {
                $chunk_words[] = $hundreds[$hundreds_digit];
            }
            
            // دهگان و یکان
            $remainder = $chunk % 100;
            if ($remainder > 0) {
                if ($remainder < 20) {
                    $chunk_words[] = $ones[$remainder];
                } else {
                    $tens_digit = floor($remainder / 10);
                    $ones_digit = $remainder % 10;
                    $chunk_words[] = $tens[$tens_digit] . ($ones_digit > 0 ? ' و ' . $ones[$ones_digit] : '');
                }
            }
            
            $chunk_text = implode(' و ', $chunk_words);
            if ($level > 0 && !empty($chunk_text)) {
                $chunk_text .= ' ' . $thousands[$level];
            }
            array_unshift($words, $chunk_text);
        }
        $number = floor($number / 1000);
        $level++;
    }
    
    return implode(' و ', array_filter($words));
}
