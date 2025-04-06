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
                            // کاربر واجد شرایط شارژ دوبرابر است
                            $double_charge = true;
                            
                            // ثبت استفاده کاربر از ویژگی شارژ دوبرابر
                            $stmt = $pdo->prepare("INSERT INTO double_charge_users (user_id) VALUES (:user_id)");
                            $stmt->bindParam(':user_id', $Payment_report['id_user']);
                            $stmt->execute();
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

// تابع تبدیل تاریخ میلادی به شمسی اگر وجود نداشته باشد
if (!function_exists('jdate')) {
    function jdate($format, $timestamp = '', $none = '', $time_zone = 'Asia/Tehran', $tr_num = 'fa') {
        $T_sec = 0; /* تنظیم ثانیه */

        if ($time_zone != 'local') date_default_timezone_set($time_zone);
        $ts = $T_sec + (($timestamp === '') ? time() : $timestamp);
        $date = explode('_', date('H_i_j_n_O_P_s_w_Y', $ts));
        list($j_y, $j_m, $j_d) = gregorian_to_jalali($date[8], $date[3], $date[2]);
        $doy = ($j_m < 7) ? (($j_m - 1) * 31) + $j_d : (($j_m - 7) * 30) + $j_d + 186;
        $kab = (((($j_y % 33) % 4) - 1) == ((int) (($j_y % 33) * 0.05))) ? 1 : 0;
        $sl = strlen($format);
        $out = '';
        for ($i = 0; $i < $sl; $i++) {
            $sub = substr($format, $i, 1);
            if ($sub == '\\') {
                $out .= substr($format, ++$i, 1);
                continue;
            }
            switch ($sub) {
                case 'E':
                case 'R':
                case 'x':
                case 'X':
                    $out .= 'http://jdf.scr.ir';
                    break;
                case 'B':
                case 'e':
                case 'g':
                case 'G':
                case 'h':
                case 'I':
                case 'T':
                case 'u':
                case 'Z':
                    $out .= date($sub, $ts);
                    break;
                case 'a':
                    $out .= ($date[0] < 12) ? 'ق.ظ' : 'ب.ظ';
                    break;
                case 'A':
                    $out .= ($date[0] < 12) ? 'قبل از ظهر' : 'بعد از ظهر';
                    break;
                case 'b':
                    $out .= (int) ($j_m / 3.1) + 1;
                    break;
                case 'c':
                    $out .= $j_y . '/' . $j_m . '/' . $j_d . ' ،' . $date[0] . ':' . $date[1] . ':' . $date[6];
                    break;
                case 'C':
                    $out .= (int) (($j_y + 99) / 100);
                    break;
                case 'd':
                    $out .= ($j_d < 10) ? '0' . $j_d : $j_d;
                    break;
                case 'D':
                    $out .= jdate_words(array('kh' => $date[7]), ' ');
                    break;
                case 'f':
                    $out .= jdate_words(array('ff' => $j_m), ' ');
                    break;
                case 'F':
                    $out .= jdate_words(array('mm' => $j_m), ' ');
                    break;
                case 'H':
                    $out .= $date[0];
                    break;
                case 'i':
                    $out .= $date[1];
                    break;
                case 'j':
                    $out .= $j_d;
                    break;
                case 'J':
                    $out .= jdate_words(array('rr' => $j_d), ' ');
                    break;
                case 'k';
                    $out .= tr_num(100 - (int) ($doy / ($kab + 365) * 1000) / 10, $tr_num);
                    break;
                case 'K':
                    $out .= tr_num((int) ($doy / ($kab + 365) * 1000) / 10, $tr_num);
                    break;
                case 'l':
                    $out .= jdate_words(array('rh' => $date[7]), ' ');
                    break;
                case 'L':
                    $out .= $kab;
                    break;
                case 'm':
                    $out .= ($j_m > 9) ? $j_m : '0' . $j_m;
                    break;
                case 'M':
                    $out .= jdate_words(array('km' => $j_m), ' ');
                    break;
                case 'n':
                    $out .= $j_m;
                    break;
                case 'N':
                    $out .= $date[7] + 1;
                    break;
                case 'o':
                    $jdw = ($date[7] == 6) ? 0 : $date[7] + 1;
                    $dny = 364 + $kab - $doy;
                    $out .= ($jdw > ($doy + 3) and $doy < 3) ? $j_y - 1 : (((3 - $dny) > $jdw and $dny < 3) ? $j_y + 1 : $j_y);
                    break;
                case 'O':
                    $out .= $date[4];
                    break;
                case 'p':
                    $out .= jdate_words(array('mb' => $j_m), ' ');
                    break;
                case 'P':
                    $out .= $date[5];
                    break;
                case 'q':
                    $out .= jdate_words(array('sh' => $j_y), ' ');
                    break;
                case 'Q':
                    $out .= $kab + 364 - $doy;
                    break;
                case 'r':
                    $key = jdate_words(array('rh' => $date[7], 'mm' => $j_m));
                    $out .= $date[0] . ':' . $date[1] . ':' . $date[6] . ' ' . $date[4]
                        . ' ' . $key['rh'] . '، ' . $j_d . ' ' . $key['mm'] . ' ' . $j_y;
                    break;
                case 's':
                    $out .= $date[6];
                    break;
                case 'S':
                    $out .= 'ام';
                    break;
                case 't':
                    $out .= ($j_m != 12) ? (31 - (int) ($j_m / 6.5)) : ($kab + 29);
                    break;
                case 'U':
                    $out .= $ts;
                    break;
                case 'v':
                    $out .= jdate_words(array('ss' => ($j_y % 100)), ' ');
                    break;
                case 'V':
                    $out .= jdate_words(array('ss' => $j_y), ' ');
                    break;
                case 'w':
                    $out .= ($date[7] == 6) ? 0 : $date[7] + 1;
                    break;
                case 'W':
                    $avs = (($date[7] == 6) ? 0 : $date[7] + 1) - ($doy % 7);
                    if ($avs < 0) $avs += 7;
                    $num = (int) (($doy + $avs) / 7);
                    if ($avs < 4) {
                        $num++;
                    } elseif ($num < 1) {
                        $num = ($avs == 4 or $avs == ((((($j_y % 33) % 4) - 2) == ((int) (($j_y % 33) * 0.05))) ? 5 : 4)) ? 53 : 52;
                    }
                    $aks = $avs + $kab;
                    if ($aks == 7) $aks = 0;
                    $out .= (($kab + 363 - $doy) < $aks and $aks < 3) ? '01' : (($num < 10) ? '0' . $num : $num);
                    break;
                case 'y':
                    $out .= substr($j_y, 2, 2);
                    break;
                case 'Y':
                    $out .= $j_y;
                    break;
                case 'z':
                    $out .= $doy;
                    break;
                default:
                    $out .= $sub;
            }
        }
        return ($tr_num != 'en') ? tr_num($out, 'fa', '.') : $out;
    }
}

// تابع تبدیل میلادی به شمسی
if (!function_exists('gregorian_to_jalali')) {
    function gregorian_to_jalali($gy, $gm, $gd, $mod = '') {
        $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = (365 * $gy) + ((int) (($gy2 + 3) / 4)) - ((int) (($gy2 + 99) / 100)) + ((int) (($gy2 + 399) / 400)) - 80 + $gd + $g_d_m[$gm - 1];
        $jy += 33 * ((int) ($days / 12053));
        $days %= 12053;
        $jy += 4 * ((int) ($days / 1461));
        $days %= 1461;
        if ($days > 365) {
            $jy += (int) (($days - 1) / 365);
            $days = ($days - 1) % 365;
        }
        $jm = ($days < 186) ? 1 + (int) ($days / 31) : 7 + (int) (($days - 186) / 30);
        $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
        return ($mod == '') ? array($jy, $jm, $jd) : $jy . $mod . $jm . $mod . $jd;
    }
}

// تابع کلمات تاریخ فارسی
if (!function_exists('jdate_words')) {
    function jdate_words($array, $mod = '') {
        foreach ($array as $type => $num) {
            $num = (int) $num;
            switch ($type) {
                case 'ss':
                    $sl = strlen($num);
                    $xy3 = substr($num, 2 - $sl, 1);
                    $h3 = $h34 = $h4 = '';
                    if ($xy3 == 1) {
                        $p34 = '';
                        $k34 = array('ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده');
                        $h34 = $k34[substr($num, 2 - $sl, 2) - 10];
                    } else {
                        $xy4 = substr($num, 3 - $sl, 1);
                        $p34 = ($xy3 == 0 or $xy4 == 0) ? '' : ' و ';
                        $k3 = array('', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود');
                        $h3 = $k3[$xy3];
                        $k4 = array('', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه');
                        $h4 = $k4[$xy4];
                    }
                    $array[$type] = (($num > 99) ? str_replace(array('12', '13', '14', '19', '20')
                                , array('هزار و دویست', 'هزار و سیصد', 'هزار و چهارصد', 'هزار و نهصد', 'دوهزار')
                                , substr($num, 0, 2)) . ((substr($num, 2, 2) == '00') ? '' : ' و ') : '') . $h3 . $p34 . $h34 . $h4;
                    break;
                case 'mm':
                    $key = array('فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند');
                    $array[$type] = $key[$num - 1];
                    break;
                case 'rr':
                    $key = array('یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه', 'ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده', 'بیست', 'بیست و یک', 'بیست و دو', 'بیست و سه', 'بیست و چهار', 'بیست و پنج', 'بیست و شش', 'بیست و هفت', 'بیست و هشت', 'بیست و نه', 'سی', 'سی و یک');
                    $array[$type] = $key[$num - 1];
                    break;
                case 'rh':
                    $key = array('یکشنبه', 'دوشنبه', 'سه شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه');
                    $array[$type] = $key[$num];
                    break;
                case 'sh':
                    $key = array('مار', 'اسب', 'گوسفند', 'میمون', 'مرغ', 'سگ', 'خوک', 'موش', 'گاو', 'پلنگ', 'خرگوش', 'نهنگ');
                    $array[$type] = $key[$num % 12];
                    break;
                case 'mb':
                    $key = array('حمل', 'ثور', 'جوزا', 'سرطان', 'اسد', 'سنبله', 'میزان', 'عقرب', 'قوس', 'جدی', 'دلو', 'حوت');
                    $array[$type] = $key[$num - 1];
                    break;
                case 'ff':
                    $key = array('بهار', 'تابستان', 'پاییز', 'زمستان');
                    $array[$type] = $key[(int) ($num / 3.1)];
                    break;
                case 'km':
                    $key = array('فر', 'ار', 'خر', 'تی', 'مر', 'شه', 'مه', 'آب', 'آذ', 'دی', 'به', 'اس');
                    $array[$type] = $key[$num - 1];
                    break;
                case 'kh':
                    $key = array('ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش');
                    $array[$type] = $key[$num];
                    break;
                default:
                    $array[$type] = $num;
            }
        }
        return ($mod === '') ? $array : implode($mod, $array);
    }
}

// تابع تبدیل اعداد انگلیسی به فارسی
if (!function_exists('tr_num')) {
    function tr_num($str, $mod = 'en', $mf = '٫') {
        $num_a = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.');
        $key_a = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', $mf);
        return ($mod == 'fa') ? str_replace($num_a, $key_a, $str) : str_replace($key_a, $num_a, $str);
    }
}
