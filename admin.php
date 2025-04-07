<?php

#----------------[  admin section  ]------------------#
$textadmin = ["panel", "/panel",$textbotlang['Admin']['commendadminmanagment'], $textbotlang['Admin']['commendadmin']];
if (!in_array($from_id, $admin_ids)) {
    if (in_array($text, $textadmin)) {
        sendmessage($from_id, $textbotlang['users']['Invalid-comment'], null, 'HTML');
        foreach ($admin_ids as $admin) {
            $textadmin = sprintf($textbotlang['Admin']['Unauthorized-entry'],$username,$from_id,$first_name);
            sendmessage($admin, $textadmin, null, 'HTML');
        }
    }
    return;
}
if (in_array($text, $textadmin)) {
    $text_admin = sprintf($textbotlang['Admin']['login-admin'],$version);
    sendmessage($from_id, $text_admin, $keyboardadmin, 'HTML');
}
if ($text == $textbotlang['Admin']['Back-Adminment']) {
    sendmessage($from_id, $textbotlang['Admin']['Back-Admin'], $keyboardadmin, 'HTML');
    step('home', $from_id);
    return;
}
elseif ($text == "📣 تنظیم کانال جوین اجباری") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['changechannel'] . $channels['link'], $backadmin, 'HTML');
    step('addchannel', $from_id);
} elseif ($user['step'] == "addchannel") {
    sendmessage($from_id, $textbotlang['Admin']['channel']['setchannel'], $channelkeyboard, 'HTML');
    step('home', $from_id);
    $channels_ch = select("channels", "link", null, null, "count");
    $Check_filde = $connect->query("SHOW COLUMNS FROM channels LIKE 'Channel_lock'");
    if (mysqli_num_rows($Check_filde) == 1) {
        $connect->query("ALTER TABLE channels DROP COLUMN Channel_lock;");
        $stmt->execute();
    }
    if ($channels_ch == 0) {
        $stmt = $pdo->prepare("INSERT INTO channels (link) VALUES (?)");
        $stmt->bindParam(1, $text, PDO::PARAM_STR);

        $stmt->execute();
    } else {
        update("channels", "link", $text);
    }
}
elseif ($text == "💸 تنظیمات شارژ دوبرابر") {
    $setting = select("setting", "*");
    $status = ($setting['double_charge_status'] == 'on') ? '✅ فعال' : '❌ غیرفعال';
    $min_purchase = $setting['double_charge_min_purchase'];
    
    $purchase_guide = "";
    if ($min_purchase == 0) {
        $purchase_guide = "تمامی کاربران (هیچ محدودیتی برای حداقل خرید وجود ندارد)";
    } else {
        $purchase_guide = "حداقل $min_purchase خرید";
    }
    
    $text_double_charge = "💎 مدیریت شارژ دوبرابر

▫️ وضعیت فعلی: $status
▫️ حداقل تعداد خرید لازم: $purchase_guide
▫️ توضیحات: با این قابلیت، کاربرانی که به حد نصاب خرید رسیده باشند می‌توانند یکبار از امکان شارژ دوبرابر استفاده کنند.
▫️ کاربران نماینده مشمول این طرح نمی‌شوند.
▫️ راهنما: برای تغییر حداقل تعداد خرید مورد نیاز، از دکمه زیر استفاده کنید. اگر مقدار 0 را وارد کنید، تمامی کاربران بدون محدودیت می‌توانند از این ویژگی استفاده کنند.";
    
    $double_charge_keyboard = json_encode([
        'keyboard' => [
            [['text' => "✅ فعال کردن شارژ دوبرابر"], ['text' => "❌ غیرفعال کردن شارژ دوبرابر"]],
            [['text' => "🔢 تنظیم حداقل تعداد خرید"]],
            [['text' => "📋 لیست کاربران مشمول شارژ دوبرابر"]],
            [['text' => "⏰ یادآوری به کاربران نزدیک به پایان مهلت"]],
            [['text' => $textbotlang['Admin']['Back-Adminment']]]
        ],
        'resize_keyboard' => true
    ]);
    
    sendmessage($from_id, $text_double_charge, $double_charge_keyboard, 'HTML');
}
elseif ($text == "✅ فعال کردن شارژ دوبرابر") {
    update("setting", "double_charge_status", "on");
    sendmessage($from_id, "✅ ویژگی شارژ دوبرابر با موفقیت فعال شد.", $setting_panel, 'HTML');
}
elseif ($text == "❌ غیرفعال کردن شارژ دوبرابر") {
    update("setting", "double_charge_status", "off");
    sendmessage($from_id, "❌ ویژگی شارژ دوبرابر با موفقیت غیرفعال شد.", $setting_panel, 'HTML');
}
elseif ($text == "🔢 تنظیم حداقل تعداد خرید") {
    sendmessage($from_id, "🔢 لطفاً حداقل تعداد خرید لازم برای احراز شرایط شارژ دوبرابر را وارد کنید.\n\n👈 اگر می‌خواهید همه کاربران بدون محدودیت مشمول شوند، عدد 0 را وارد کنید.", $backuser, 'HTML');
    step('set_double_charge_min_purchase', $from_id);
}
elseif ($user['step'] == "set_double_charge_min_purchase") {
    // بررسی ورودی عددی
    if (!is_numeric($text) || intval($text) < 0) {
        sendmessage($from_id, "❌ لطفاً یک عدد صحیح بزرگتر یا مساوی صفر وارد کنید.", null, 'HTML');
        return;
    }
    
    $min_purchase = intval($text);
    update("setting", "double_charge_min_purchase", $min_purchase);
    
    $message = "✅ حداقل تعداد خرید لازم برای شارژ دوبرابر با موفقیت به $min_purchase تغییر یافت.";
    if ($min_purchase == 0) {
        $message .= "\n\n👈 حالا تمام کاربران (به جز نمایندگان) می‌توانند از شارژ دوبرابر استفاده کنند.";
    }
    
    sendmessage($from_id, $message, $double_charge_keyboard, 'HTML');
    step('none', $from_id);
}
if ($text == $textbotlang['Admin']['Addedadmin']) {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin, 'HTML');
    step('addadmin', $from_id);
}
if ($user['step'] == "addadmin") {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['addadminset'], $keyboardadmin, 'HTML');
    step('home', $from_id);
    $stmt = $pdo->prepare("INSERT INTO admin (id_admin) VALUES (?)");
    $stmt->bindParam(1, $text);
    $stmt->execute();
}
if ($text == $textbotlang['Admin']['Removeedadmin']) {
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['getid'], $backadmin, 'HTML');
    step('deleteadmin', $from_id);
} elseif ($user['step'] == "deleteadmin") {
    if(intval($text) == $adminnumber){
        sendmessage($from_id,$textbotlang['Admin']['manageadmin']['InfoAdd'], null, 'HTML');
        return;
    }
    if (!is_numeric($text) || !in_array($text, $admin_ids))
        return;
    sendmessage($from_id, $textbotlang['Admin']['manageadmin']['removedadmin'], $keyboardadmin, 'HTML');
    $stmt = $pdo->prepare("DELETE FROM admin WHERE id_admin = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    step('home', $from_id);
}
elseif (preg_match('/limitusertest_(.*)/', $datain, $dataget)) {
    $id_user = $dataget[1];
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['getid'], $backadmin, 'HTML');
    update("user", "Processing_value", $id_user, "id", $from_id);
    step('get_number_limit', $from_id);
} elseif ($user['step'] == "get_number_limit") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setlimit'], $keyboardadmin, 'HTML');
    $id_user_set = $text;
    step('home', $from_id);
    update("user", "limit_usertest", $text, "id", $user['Processing_value']);
}
if ($text == $textbotlang['Admin']['getlimitusertest']['setlimitallbtn']) {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['limitall'], $backadmin, 'HTML');
    step('limit_usertest_allusers', $from_id);
} elseif ($user['step'] == "limit_usertest_allusers") {
    sendmessage($from_id, $textbotlang['Admin']['getlimitusertest']['setlimitall'], $keyboard_usertest, 'HTML');
    step('home', $from_id);
    update("setting", "limit_usertest_all", $text);
    update("user", "limit_usertest", $text);
}
if ($text == $textbotlang['Admin']['channel']['setting']) {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $channelkeyboard, 'HTML');
}
#-------------------------#
if ($text == $textbotlang['Admin']['Statistics']['titlebtn']) {
    $current_date_time = time();
    $datefirst = $current_date_time - 86400;
    $desired_date_time_start = $current_date_time - 3600;
    $month_date_time_start = $current_date_time - 2592000;
    $datefirstday = time() - 86400;
    $dateacc = jdate('Y/m/d');
    $sql = "SELECT * FROM invoice WHERE  (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR status = 'sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dayListSell = $stmt->rowCount();
    $Balanceall =  select("user","SUM(Balance)",null,null,"select");
    $statistics = select("user","*",null,null,"count");
    $sumpanel = select("marzban_panel","*",null,null,"count");
    $sqlinvoice = "SELECT *  FROM invoice WHERE (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR Status = 'sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sqlinvoice);
    $stmt->execute();
    $invoice =$stmt->rowCount();
    $sql = "SELECT SUM(price_product)  FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $invoicesum =$stmt->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'];
    $sql = "SELECT SUM(price_product) FROM invoice WHERE time_sell > :time_sell AND (Status = 'active' OR Status = 'end_of_time'  OR Status = 'end_of_volume' OR status = 'sendedwarn') AND name_product != 'usertest'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':time_sell', $datefirstday);
    $stmt->execute();
    $dayListSell = $stmt->rowCount();
    $count_usertest = select("invoice","*","name_product","usertest","count");
    $ping = sys_getloadavg();
    $ping = number_format(floatval($ping[0]),2);
    $timeacc = jdate('H:i:s', time());
    $statisticsall = sprintf($textbotlang['Admin']['Statistics']['info'],$statistics,$Balanceall['SUM(Balance)'],$ping,$count_usertest,$invoice ,$invoicesum,$dayListSell,$sumpanel);
    sendmessage($from_id, $statisticsall, null, 'HTML');
}

if ($text == $textbotlang['Admin']['managepanel']['btnshowconnect']) {
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($marzban_list_get['type'] == "marzban") {
        $Check_token = token_panel($marzban_list_get['id']);
        if (isset ($Check_token['access_token'])) {
            $System_Stats = Get_System_Stats($user['Processing_value']);
            $active_users = $System_Stats['users_active'];
            $total_user = $System_Stats['total_user'];
            $mem_total = formatBytes($System_Stats['mem_total']);
            $mem_used = formatBytes($System_Stats['mem_used']);
            $bandwidth = formatBytes($System_Stats['outgoing_bandwidth'] + $System_Stats['incoming_bandwidth']);
            $Condition_marzban = "";
            $text_marzban = sprintf($textbotlang['Admin']['managepanel']['infomarzban'],$total_user,$active_users,$System_Stats['version'],$mem_total,$mem_used,$bandwidth);
            sendmessage($from_id, $text_marzban, null, 'HTML');
        } elseif (isset ($Check_token['detail']) && $Check_token['detail'] == "Incorrect username or password") {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['Incorrectinfo'], null, 'HTML');
        } else {
            $text_marzban = $textbotlang['Admin']['managepanel']['errorstateuspanel'] . json_encode($Check_token);
            sendmessage($from_id, $text_marzban, null, 'HTML');
        }
    }elseif ($marzban_list_get['type'] == "marzneshin") {
        $Check_token = token_panelm($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
        if (isset($Check_token['access_token'])) {
            $System_Stats = Get_System_Statsm($user['Processing_value']);
            $active_users = $System_Stats['active'];
            $total_user = $System_Stats['total'];
            $text_marzban = sprintf($textbotlang['Admin']['managepanel']['infomarzneshin'],$total_user,$active_users);
            sendmessage($from_id, $text_marzban, null, 'HTML');
        } elseif (isset ($Check_token['detail']) && $Check_token['detail'] == "Incorrect username or password") {
            $text_marzban = $textbotlang['Admin']['managepanel']['Incorrectinfo'];
            sendmessage($from_id, $text_marzban, null, 'HTML');
        } else {
            $text_marzban = $textbotlang['Admin']['managepanel']['errorstateuspanel'] . json_encode($Check_token);
            sendmessage($from_id, $text_marzban, null, 'HTML');
        }
    } elseif ($marzban_list_get['type'] == "x-ui_single") {
        $x_ui_check_connect = login($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
        if ($x_ui_check_connect['success']) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['connectx-ui'], null, 'HTML');
        } elseif ($x_ui_check_connect['msg'] == "Invalid username or password.") {
            $text_marzban = $textbotlang['Admin']['managepanel']['Incorrectinfo'];
            sendmessage($from_id, $text_marzban, null, 'HTML');
        } else {
            $text_marzban = $textbotlang['Admin']['managepanel']['errorstateuspanel'];
            sendmessage($from_id, $text_marzban, null, 'HTML');
        }
    }elseif ($marzban_list_get['type'] == "alireza") {
        $x_ui_check_connect = loginalireza($marzban_list_get['url_panel'], $marzban_list_get['username_panel'], $marzban_list_get['password_panel']);
        if ($x_ui_check_connect['success']) {
            sendmessage($from_id, $textbotlang['Admin']['managepanel']['connectx-ui'], null, 'HTML');
        } elseif ($x_ui_check_connect['msg'] == "Invalid username or password.") {
            $text_marzban = $textbotlang['Admin']['managepanel']['Incorrectinfo'];
            sendmessage($from_id, $text_marzban, null, 'HTML');
        } else {
            $text_marzban = $textbotlang['Admin']['managepanel']['errorstateuspanel'];
            sendmessage($from_id, $text_marzban, null, 'HTML');
        }
    }
    step('home', $from_id);
}
if ($text == $textbotlang['Admin']['manageadmin']['showlistbtn']) {
    $List_admin = null;
    $admin_ids = array_filter($admin_ids);
    foreach ($admin_ids as $admin) {
        $List_admin .= "$admin\n";
    }
    $list_admin_text = sprintf($textbotlang['Admin']['manageadmin']['showlist'],$List_admin);
    sendmessage($from_id, $list_admin_text, $admin_section_panel, 'HTML');
}
if ($text == $textbotlang['Admin']['keyboardadmin']['add_panel']) {
    $textx = "📌 نوع پنل را ارسال نمایید
    
⚠️ در صورت انتخاب پنل ثنایی پس از اضافه کردن پنل به بخش ویرایش پنل > تنظیم شناسه اینباند رفته و شناسه اینباند را ثبت کنید";
    sendmessage($from_id, $textx, $typepanel, 'HTML');
    step('gettyppepanel', $from_id);
}elseif($user['step'] == "gettyppepanel"){
    savedata("clear","type",$text);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addpanelname'], $backadmin, 'HTML');
    step('add_name_panel', $from_id);
} elseif ($user['step'] == "add_name_panel") {
    if (in_array($text, $marzban_list)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Repeatpanel'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addpanelurl'], $backadmin, 'HTML');
    savedata("save","name",$text);
    step('add_link_panel', $from_id);
} elseif ($user['step'] == "add_link_panel") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    savedata("save","url_panel",$text);
    $userdata = json_decode($user['Processing_value'],true);
    if($userdata['type'] == "s_ui"){
        sendmessage($from_id, "📌 توکن  را از پنل s-ui منوی ادمین ساخته و ارسال نمایید.", $backadmin, 'HTML');
        step('add_password_panel', $from_id);
        savedata("save","username_panel","none");
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['usernameset'], $backadmin, 'HTML');
    step('add_username_panel', $from_id);
} elseif ($user['step'] == "add_username_panel") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getpassword'], $backadmin, 'HTML');
    step('add_password_panel', $from_id);
    savedata("save","username_panel",$text);
}elseif ($user['step'] == "add_password_panel") {
    $userdata = json_decode($user['Processing_value'],true);
    $inboundid = "0";
    $sublink = "onsublink";
    $config = "offconfig";
    $valusername = "آیدی عددی + حروف و عدد رندوم";
    $valueteststatus = "ontestshowpanel";
    $stauts = "activepanel";
    $on_hold = "offonhold";
    $stmt = $pdo->prepare("INSERT INTO marzban_panel (name_panel,url_panel,username_panel,password_panel,type,inboundid,sublink,configManual,MethodUsername,statusTest,status,onholdstatus) VALUES (?, ?, ?, ?, ?,?,?,?,?,?,?,?)");
    $stmt->execute([$userdata['name'],$userdata['url_panel'],$userdata['username_panel'],$text,$userdata['type'],$inboundid, $sublink, $config,$valusername,$valueteststatus,$stauts,$on_hold]);
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['addedpanel'], $backadmin, 'HTML');
    sendmessage($from_id, "🥳", $keyboardadmin, 'HTML');
    if($userdata['type'] == "x-ui_single" or $userdata['type'] == "alireza") {
        sendmessage($from_id,"📌 نکات بعد اضافه کردن پنل :‌

۱ - از مدیریت پنل > تنظیم شناسه اینباند  شناسه اینباندی که میخواهید ساخته شود را تنظیم نمایید
۲ - از مدیریت پنل > دامنه لینک ساب دامنه لینک ساب را حتما تنظیم نمایید.", null, 'HTML');
    }elseif($userdata['type'] == "marzban" || $userdata['type'] == "s_ui" || $userdata['type'] == "marzneshin"){
        sendmessage($from_id,"📌 نکات بعد اضافه کردن پنل :‌

۱ -از مدیریت پنل > تنظیم پروتکل و اینباند یک نام کاربری موجود در پنل را ارسال نمایید.", null, 'HTML');
    }
    step('home', $from_id);
}
if ($text == "📨 ارسال پیام") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $sendmessageuser, 'HTML');
} elseif ($text == "✉️ ارسال همگانی") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetText'], $backadmin, 'HTML');
    step('getconfirmsendall', $from_id);
}elseif($user['step'] == "getconfirmsendall"){
    if(!$text){
        sendmessage($from_id, "فقط ارسال متن مجاز است", $backadmin, 'HTML');
        return;
    }
    savedata("clear","text",$text);
    savedata("save","id_admin",$from_id);
    sendmessage($from_id,"در صورت تایید متن زیر را ارسال نمایید
    تایید", $backadmin, 'HTML');
    step("gettextforsendall",$from_id);
} elseif ($user['step'] == "gettextforsendall") {
    $userdata  = json_decode($user['Processing_value'],true);
    if($text == "تایید"){
        step('home', $from_id);
        $result = select("user","id","User_Status","Active","fetchAll");
        $Respuseronse = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "لغو ارسال", 'callback_data' => 'cancel_sendmessage'],
                ],
            ]
        ]);
        file_put_contents('cron/users.json',json_encode($result));
        file_put_contents('cron/info',$user['Processing_value']);
        sendmessage($from_id, "📌 پیام شما  در صف ارسال قرار گرفت پس از ارسال پیام تایید برای شما ارسال می شود ( ارسال پیام ممکن است  حداکثر 8 ساعت زمان ببرد بدلیل محدودیت های تلگرام )", $Respuseronse, 'HTML');
    }
}elseif($datain == "cancel_sendmessage"){
    unlink('cron/users.json');
    unlink('cron/info');
    deletemessage($from_id, $message_id);
    sendmessage($from_id, "📌 ارسال پیام لغو گردید.", null, 'HTML');
} elseif ($text == "📤 فوروارد همگانی") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ForwardGetext'], $backadmin, 'HTML');
    step('gettextforwardMessage', $from_id);
} elseif ($user['step'] == "gettextforwardMessage") {
    sendmessage($from_id, "درحال ارسال پیام", $keyboardadmin, 'HTML');
    step('home', $from_id);
    $filename = 'user.txt';
    $stmt = $pdo->prepare("SELECT id FROM user");
    $stmt->execute();
    if ($result) {
        $ids = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $ids[] = $row['id'];
        }
        $idsText = implode("\n", $ids);
        file_put_contents($filename, $idsText);
    }
    $file = fopen($filename, 'r');
    if ($file) {
        while (($line = fgets($file)) !== false) {
            $line = trim($line);
            forwardMessage($from_id, $message_id, $line);
            usleep(2000000);
        }
        sendmessage($from_id, "✅ پیام به تمامی کاربران ارسال شد", $keyboardadmin, 'HTML');
        fclose($file);
    }
    unlink($filename);
}
//_________________________________________________
if ($text == "📝 تنظیم متن ربات") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $textbot, 'HTML');
} elseif ($text == "تنظیم متن شروع") {
    $textstart = $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_start'];
    sendmessage($from_id, $textstart, $backadmin, 'HTML');
    step('changetextstart', $from_id);
} elseif ($user['step'] == "changetextstart") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_start");
    step('home', $from_id);
} elseif ($text == "دکمه سرویس خریداری شده") {
    $textstart = $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_Purchased_services'];
    sendmessage($from_id, $textstart, $backadmin, 'HTML');
    step('changetextinfo', $from_id);
} elseif ($user['step'] == "changetextinfo") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_Purchased_services");
    step('home', $from_id);
} elseif ($text == "دکمه اکانت تست") {
    $textstart = $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_usertest'];
    sendmessage($from_id, $textstart, $backadmin, 'HTML');
    step('changetextusertest', $from_id);
} elseif ($user['step'] == "changetextusertest") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_usertest");
    step('home', $from_id);
} elseif ($text == "متن دکمه 📚 آموزش") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_help'], $backadmin, 'HTML');
    step('text_help', $from_id);
} elseif ($user['step'] == "text_help") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_help");
    step('home', $from_id);
} elseif ($text == "متن دکمه ☎️ پشتیبانی") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_support'], $backadmin, 'HTML');
    step('text_support', $from_id);
} elseif ($user['step'] == "text_support") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_support");
    step('home', $from_id);
} elseif ($text == "دکمه سوالات متداول") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_fq'], $backadmin, 'HTML');
    step('text_fq', $from_id);
} elseif ($user['step'] == "text_fq") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_fq");
    step('home', $from_id);
} elseif ($text == "📝 تنظیم متن توضیحات سوالات متداول") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_dec_fq'], $backadmin, 'HTML');
    step('text_dec_fq', $from_id);
} elseif ($user['step'] == "text_dec_fq") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_dec_fq");
    step('home', $from_id);
} elseif ($text == "📝 تنظیم متن توضیحات عضویت اجباری") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_channel'], $backadmin, 'HTML');
    step('text_channel', $from_id);
} elseif ($user['step'] == "text_channel") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_channel");
    step('home', $from_id);
} elseif ($text == "متن دکمه حساب کاربری") {
    $textstart = $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_account'];
    sendmessage($from_id, $textstart, $backadmin, 'HTML');
    step('text_account', $from_id);
} elseif ($user['step'] == "text_account") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_account");
    step('home', $from_id);
} elseif ($text == "دکمه افزایش موجودی") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_Add_Balance'], $backadmin, 'HTML');
    step('text_Add_Balance', $from_id);
} elseif ($user['step'] == "text_Add_Balance") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_Add_Balance");
    step('home', $from_id);
} elseif ($text == "متن دکمه خرید اشتراک") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_sell'], $backadmin, 'HTML');
    step('text_sell', $from_id);
} elseif ($user['step'] == "text_sell") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_sell");
    step('home', $from_id);
} elseif ($text == "متن دکمه لیست تعرفه") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_Tariff_list'], $backadmin, 'HTML');
    step('text_Tariff_list', $from_id);
} elseif ($user['step'] == "text_Tariff_list") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_Tariff_list");
    step('home', $from_id);
} elseif ($text == "متن توضیحات لیست تعرفه") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_dec_Tariff_list'], $backadmin, 'HTML');
    step('text_dec_Tariff_list', $from_id);
} elseif ($user['step'] == "text_dec_Tariff_list") {
    if (!$text) {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ErrorText'], $textbot, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_dec_Tariff_list");
    step('home', $from_id);
}
//_________________________________________________
if ($text == "✍️ ارسال پیام برای یک کاربر") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetText'], $backadmin, 'HTML');
    step('sendmessagetext', $from_id);
} elseif ($user['step'] == "sendmessagetext") {
    update("user", "Processing_value", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetIDMessage'], $backadmin, 'HTML');
    step('sendmessagetid', $from_id);
} elseif ($user['step'] == "sendmessagetid") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['not-user'], $backadmin, 'HTML');
        return;
    }
    $textsendadmin = "
                    👤 یک پیام از طرف ادمین ارسال شده است  
    متن پیام:
                {$user['Processing_value']}";
    sendmessage($text, $textsendadmin, null, 'HTML');
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['MessageSent'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
//_________________________________________________
if ($text == "📚 بخش آموزش") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardhelpadmin, 'HTML');
} elseif ($text == "📚 اضافه کردن آموزش") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['GetAddNameHelp'], $backadmin, 'HTML');
    step('add_name_help', $from_id);
} elseif ($user['step'] == "add_name_help") {
    $stmt = $pdo->prepare("INSERT IGNORE INTO help (name_os) VALUES (?)");
    $stmt->bindParam(1, $text, PDO::PARAM_STR);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Help']['GetAddDecHelp'], $backadmin, 'HTML');
    step('add_dec', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "add_dec") {
    if ($photo) {
        update("help", "Media_os", $photoid, "name_os", $user['Processing_value']);
        update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "photo", "name_os", $user['Processing_value']);
    } elseif ($text) {
        update("help", "Description_os", $text, "name_os", $user['Processing_value']);
    } elseif ($video) {
        update("help", "Media_os", $videoid, "name_os", $user['Processing_value']);
        update("help", "Description_os", $caption, "name_os", $user['Processing_value']);
        update("help", "type_Media_os", "video", "name_os", $user['Processing_value']);
    }
    sendmessage($from_id, $textbotlang['Admin']['Help']['SaveHelp'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif ($text == "❌ حذف آموزش") {
    sendmessage($from_id, $textbotlang['Admin']['Help']['SelectName'], $json_list_help, 'HTML');
    step('remove_help', $from_id);
} elseif ($user['step'] == "remove_help") {
    $stmt = $pdo->prepare("DELETE FROM help WHERE name_os = ?");
    $stmt->execute([$text]);
    sendmessage($from_id, $textbotlang['Admin']['Help']['RemoveHelp'], $keyboardhelpadmin, 'HTML');
    step('home', $from_id);
}
//_________________________________________________
if (preg_match('/Response_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "Processing_value", $iduser, "id", $from_id);
    step('getmessageAsAdmin', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['GetTextResponse'], $backadmin, 'HTML');
} elseif ($user['step'] == "getmessageAsAdmin") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SendMessageuser'], null, 'HTML');
    if ($text) {
        $textSendAdminToUser = "
📩 یک پیام از سمت مدیریت برای شما ارسال گردید.
                
        متن پیام : 
        $text";
        sendmessage($user['Processing_value'], $textSendAdminToUser, null, 'HTML');
    }
    if ($photo) {
        $textSendAdminToUser = "
📩 یک پیام از سمت مدیریت برای شما ارسال گردید.
                
        متن پیام : 
        $caption";
        telegram('sendphoto', [
            'chat_id' => $user['Processing_value'],
            'photo' => $photoid,
            'reply_markup' => $Response,
            'caption' => $textSendAdminToUser,
            'parse_mode' => "HTML",
        ]);
    }
    step('home', $from_id);
}
//_________________________________________________
if ($text == "👁‍🗨 وضعیت نمایش پنل") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['status'], 'callback_data' => $panel['status']],
            ],
        ]
    ]);
    sendmessage($from_id,"📌 در این بخش می توانید مشخص نمایید  که پنل در بخش خرید برای کاربر در دسترس باشد یا خیر", $view_Status, 'HTML');
}
if ($datain == "activepanel") {
    update("marzban_panel", "status", "disablepanel", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['status'], 'callback_data' => $panel['status']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, "خاموش گردید.", $view_Status);
} elseif ($datain == "disablepanel") {
    update("marzban_panel", "status", "activepanel", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['status'], 'callback_data' => $panel['status']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, "روشن گردید.", $view_Status);
}
//_________________________________________________
if ($text == "🎁 وضعیت اکانت تست") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['statusTest'], 'callback_data' => $panel['statusTest']],
            ],
        ]
    ]);
    sendmessage($from_id,"📌 در این بخش می توانید مشخص نمایید  که پنل در بخش اکانت تس برای کاربر در دسترس باشد یا خیر در صورت روشن کردن این قابلیت باید وضعیت نمایش پنل را خماوش کنید", $view_Status, 'HTML');
}
if ($datain == "ontestshowpanel") {
    update("marzban_panel", "statusTest", "offtestshowpanel", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['statusTest'], 'callback_data' => $panel['statusTest']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, "خاموش گردید.", $view_Status);
} elseif ($datain == "offtestshowpanel") {
    update("marzban_panel", "statusTest", "ontestshowpanel", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $view_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['statusTest'], 'callback_data' => $panel['statusTest']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, "روشن گردید.", $view_Status);
}
//_________________________________________________
elseif (preg_match('/banuserlist_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userblock = select("user", "*", "id", $iduser, "select");
    if ($userblock['User_Status'] == "block") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['BlockedUser'], $backadmin, 'HTML');
        return;
    }
    update("user", "Processing_value", $iduser, "id", $from_id);
    update("user", "User_Status", "block", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['BlockUser'], $backadmin, 'HTML');
    step('adddecriptionblock', $from_id);
} elseif ($user['step'] == "adddecriptionblock") {
    update("user", "description_blocking", $text, "id", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['DescriptionBlock'], $keyboardadmin, 'HTML');
    step('home', $from_id);
} elseif (preg_match('/unbanuserr_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunblock = select("user", "*", "id", $iduser, "select");
    if ($userunblock['User_Status'] == "Active") {
        sendmessage($from_id, $textbotlang['Admin']['ManageUser']['UserNotBlock'], $backadmin, 'HTML');
        return;
    }
    update("user", "User_Status", "Active", "id", $iduser);
    update("user", "description_blocking", "", "id", $iduser);
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['UserUnblocked'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
//_________________________________________________
elseif ($text == "⚖️ متن قانون") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ChangeTextGet'] . $datatextbot['text_roll'], $backadmin, 'HTML');
    step('text_roll', $from_id);
} elseif ($user['step'] == "text_roll") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SaveText'], $textbot, 'HTML');
    update("textbot", "text", $text, "id_text", "text_roll");
    step('home', $from_id);
}
//_________________________________________________
if ($text == "👤 خدمات کاربر") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $User_Services, 'HTML');
}
#-------------------------#
elseif (preg_match('/confirmnumber_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user", "number", "confrim number by admin", "id", $iduser);
    step('home', $iduser);
    sendmessage($from_id, $textbotlang['Admin']['phone']['active'], $User_Services, 'HTML');
}
if ($text == "📣 تنظیم کانال گزارش") {
    sendmessage($from_id, $textbotlang['Admin']['Channel']['ReportChannel'] . $setting['Channel_Report'], $backadmin, 'HTML');
    step('addchannelid', $from_id);
} elseif ($user['step'] == "addchannelid") {
    sendmessage($from_id, $textbotlang['Admin']['Channel']['SetChannelReport'], $keyboardadmin, 'HTML');
    update("setting", "Channel_Report", $text);
    step('home', $from_id);
    sendmessage($setting['Channel_Report'], $textbotlang['Admin']['Channel']['TestChannel'], null, 'HTML');
}
#-------------------------#
if ($text == "🏬 بخش فروشگاه") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $shopkeyboard, 'HTML');
} elseif ($text == "🛍 اضافه کردن محصول") {
    $locationproduct = select("marzban_panel", "*", null, null, "count");
    if ($locationproduct == 0) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['nullpaneladmin'], null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Product']['AddProductStepOne'], $backadmin, 'HTML');
    step('get_limit', $from_id);
} elseif ($user['step'] == "get_limit") {
    $randomString = bin2hex(random_bytes(2));
    $stmt = $pdo->prepare("INSERT IGNORE INTO product (name_product, code_product) VALUES (?, ?)");
    $stmt->bindParam(1, $text);
    $stmt->bindParam(2, $randomString);

    $stmt->execute();
    update("user", "Processing_value", $randomString, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['Service_location'], $json_list_marzban_panel, 'HTML');
    step('get_location', $from_id);
} elseif ($user['step'] == "get_location") {
    update("product", "Location", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['Getcategory'], KeyboardCategory(), 'HTML');
    step('get_category', $from_id);
} elseif ($user['step'] == "get_category") {
    $category = select("category","*","remark",$text,"select");
    if($category == false){
        sendmessage($from_id, "دسته بندی نامعتبر", $backadmin, 'HTML');
        return;
    }
    update("product", "category", $category['id'], "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['GetLimit'], $backadmin, 'HTML');
    step('get_time', $from_id);
} elseif ($user['step'] == "get_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    update("product", "Volume_constraint", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['GettIime'], $backadmin, 'HTML');
    step('get_price', $from_id);
} elseif ($user['step'] == "get_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    update("product", "Service_time", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['GetPrice'], $backadmin, 'HTML');
    step('endstep', $from_id);
} elseif ($user['step'] == "endstep") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidPrice'], $backadmin, 'HTML');
        return;
    }
    update("product", "price_product", $text, "code_product", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Product']['SaveProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "👨‍🔧 بخش ادمین") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $admin_section_panel, 'HTML');
}
#-------------------------#
if ($text == "⚙️ تنظیمات") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $setting_panel, 'HTML');
}
#-------------------------#
if ($text == "🔑 تنظیمات اکانت تست") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboard_usertest, 'HTML');
}
#-------------------------#
if (preg_match('/Confirm_pay_(\w+)/', $datain, $dataget)) {
    $order_id = $dataget[1];
    $Payment_report = select("Payment_report", "*", "id_order", $order_id, "select");
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'], "select");
    if ($Payment_report['payment_Status'] == "paid" || $Payment_report['payment_Status'] == "reject") {
        telegram('answerCallbackQuery', array(
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['Admin']['Payment']['reviewedpayment'],
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    DirectPayment($order_id);
    update("user","Processing_value","0", "id",$Balance_id['id']);
    update("user","Processing_value_one","0", "id",$Balance_id['id']);
    update("user","Processing_value_tow","0", "id",$Balance_id['id']);
    update("Payment_report","payment_Status","paid","id_order",$order_id);
    $text_report = "📣 یک ادمین رسید پرداخت کارت به کارت را تایید کرد.
    
    اطلاعات :
    👤آیدی عددی  ادمین تایید کننده : $from_id
    💰 مبلغ پرداخت : {$Payment_report['price']}
    ";
    if (isset($setting['Channel_Report']) &&strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
}
#-------------------------#
if (preg_match('/reject_pay_(\w+)/', $datain, $datagetr)) {
    $id_order = $datagetr[1];
    $Payment_report = select("Payment_report", "*", "id_order", $id_order, "select");
    update("user", "Processing_value", $Payment_report['id_user'], "id", $from_id);
    update("user", "Processing_value_one", $id_order, "id", $from_id);
    if ($Payment_report['payment_Status'] == "reject" || $Payment_report['payment_Status'] == "paid") {
        telegram('answerCallbackQuery', array(
                'callback_query_id' => $callback_query_id,
                'text' => $textbotlang['Admin']['Payment']['reviewedpayment'],
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    update("Payment_report", "payment_Status", "reject", "id_order", $id_order);
    sendmessage($from_id, $textbotlang['Admin']['Payment']['Reasonrejecting'], $backadmin, 'HTML');
    step('reject-dec', $from_id);
    Editmessagetext($from_id, $message_id, $text_callback, null);
} elseif ($user['step'] == "reject-dec") {
    update("Payment_report", "dec_not_confirmed", $text, "id_order", $user['Processing_value_one']);
    $text_reject = "❌ کاربر گرامی پرداخت شما به دلیل زیر رد گردید.
✍️ $text
🛒 کد پیگیری پرداخت: {$user['Processing_value_one']}
            ";
    sendmessage($from_id, $textbotlang['Admin']['Payment']['Rejected'], $keyboardadmin, 'HTML');
    sendmessage($user['Processing_value'], $text_reject, null, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "❌ حذف محصول") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['Rmove_location'], $json_list_marzban_panel, 'HTML');
    step('selectloc', $from_id);
} elseif ($user['step'] == "selectloc") {
    update("user", "Processing_value", $text, "id", $from_id);
    step('remove-product', $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectRemoveProduct'], $json_list_product_list_admin, 'HTML');
} elseif ($user['step'] == "remove-product") {
    if (!in_array($text, $name_product)) {
        sendmessage($from_id, $textbotlang['users']['sell']['error-product'], null, 'HTML');
        return;
    }
    $ydf = '/all';
    $stmt = $pdo->prepare("DELETE FROM product WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmt->execute([$text, $user['Processing_value'], $ydf]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['RemoveedProduct'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "✏️ ویرایش محصول") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['Rmove_location'], $json_list_marzban_panel, 'HTML');
    step('selectlocedite', $from_id);
} elseif ($user['step'] == "selectlocedite") {
    update("user", "Processing_value_one", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectEditProduct'], $json_list_product_list_admin, 'HTML');
    step('change_filde', $from_id);
} elseif ($user['step'] == "change_filde") {
    if (!in_array($text, $name_product)) {
        sendmessage($from_id, $textbotlang['users']['sell']['error-product'], null, 'HTML');
        return;
    }
    update("user", "Processing_value", $text, "id", $from_id);
    sendmessage($from_id, $textbotlang['Admin']['Product']['selectfieldProduct'], $change_product, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "قیمت") {
    sendmessage($from_id, "قیمت جدید را ارسال کنید", $backadmin, 'HTML');
    step('change_price', $from_id);
} elseif ($user['step'] == "change_price") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidPrice'], $backadmin, 'HTML');
        return;
    }
    $location = '/all';
    $stmtFirst = $pdo->prepare("UPDATE product SET price_product = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmtFirst->execute([$text, $user['Processing_value'], $user['Processing_value_one'], $location]);
    $stmtSecond = $pdo->prepare("UPDATE invoice SET price_product = ? WHERE name_product = ? AND Service_location = ?");
    $stmtSecond->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, "✅ قیمت محصول بروزرسانی شد", $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "دسته بندی") {
    sendmessage($from_id, "دسته بندی جدید را ارسال کنید", KeyboardCategory(), 'HTML');
    step('change_category', $from_id);
} elseif ($user['step'] == "change_category") {
    $category = select("category","*","remark",$text,"select");
    if($category == false){
        sendmessage($from_id, "دسته بندی نامعتبر", $backadmin, 'HTML');
        return;
    }
    $location = "/all";
    $stmtFirst = $pdo->prepare("UPDATE product SET category = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmtFirst->execute([$category['id'], $user['Processing_value'], $user['Processing_value_one'], $location]);
    sendmessage($from_id, "✅ دسته بندی محصول بروزرسانی شد", $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "نام محصول") {
    sendmessage($from_id, "نام جدید را ارسال کنید", $backadmin, 'HTML');
    step('change_name', $from_id);
} elseif ($user['step'] == "change_name") {
    $value = "/all";
    $stmtFirst = $pdo->prepare("UPDATE product SET name_product = ? WHERE name_product = ? AND (Location = ? OR Location = ?)");
    $stmtFirst->execute([$text, $user['Processing_value'], $user['Processing_value_one'], $value]);
    $sqlSecond = "UPDATE invoice SET name_product = ? WHERE name_product = ? AND Service_location = ?";
    $stmtSecond = $pdo->prepare($sqlSecond);
    $stmtSecond->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, "✅نام محصول بروزرسانی شد", $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "حجم") {
    sendmessage($from_id, "حجم جدید را ارسال کنید", $backadmin, 'HTML');
    step('change_val', $from_id);
} elseif ($user['step'] == "change_val") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    $sqlInvoice = "UPDATE invoice SET Volume = ? WHERE name_product = ? AND Service_location = ?";
    $stmtInvoice = $pdo->prepare($sqlInvoice);
    $stmtInvoice->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    $sqlProduct = "UPDATE product SET Volume_constraint = ? WHERE name_product = ? AND Location = ?";
    $stmtProduct = $pdo->prepare($sqlProduct);
    $stmtProduct->execute([$text, $user['Processing_value'], $user['Processing_value_one']]);
    sendmessage($from_id, $textbotlang['Admin']['Product']['volumeUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "زمان") {
    sendmessage($from_id, $textbotlang['Admin']['Product']['NewTime'], $backadmin, 'HTML');
    step('change_time', $from_id);
} elseif ($user['step'] == "change_time") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    $stmtInvoice = $pdo->prepare("UPDATE invoice SET Service_time = ? WHERE name_product = ? AND Service_location = ?");
    $stmtInvoice->bindParam(1, $text);
    $stmtInvoice->bindParam(2, $user['Processing_value']);
    $stmtInvoice->bindParam(3, $user['Processing_value_one']);
    $stmtInvoice->execute();
    $stmtProduct = $pdo->prepare("UPDATE product SET Service_time = ? WHERE name_product = ? AND Location = ?");
    $stmtProduct->bindParam(1, $text);
    $stmtProduct->bindParam(2, $user['Processing_value']);
    $stmtProduct->bindParam(3, $user['Processing_value_one']);
    $stmtProduct->execute();
    sendmessage($from_id, $textbotlang['Admin']['Product']['TimeUpdated'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "⏳ زمان سرویس تست") {
    sendmessage($from_id, "🕰 مدت زمان سرویس تست را ارسال کنید.
زمان فعلی: {$setting['time_usertest']} ساعت
⚠️ زمان بر حسب ساعت است.", $backadmin, 'HTML');
    step('updatetime', $from_id);
} elseif ($user['step'] == "updatetime") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['InvalidTime'], $backadmin, 'HTML');
        return;
    }
    update("setting", "time_usertest", $text);
    sendmessage($from_id, $textbotlang['Admin']['Usertest']['TimeUpdated'], $keyboard_usertest, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "💾 حجم اکانت تست") {
    sendmessage($from_id, "حجم سرویس تست را ارسال کنید.
حجم فعلی: {$setting['val_usertest']} مگابایت
⚠️ حجم بر حسب مگابایت است.", $backadmin, 'HTML');
    step('val_usertest', $from_id);
} elseif ($user['step'] == "val_usertest") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Product']['Invalidvolume'], $backadmin, 'HTML');
        return;
    }
    update("setting", "val_usertest", $text);
    sendmessage($from_id, $textbotlang['Admin']['Usertest']['VolumeUpdated'], $keyboard_usertest, 'HTML');
    step('home', $from_id);
}
#-------------------------#
elseif (preg_match('/addbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user","Processing_value",$iduser, "id",$from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['PriceBalance'], $backadmin, 'HTML');
    step('get_price_add', $from_id);
} elseif ($user['step'] == "get_price_add") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    if(intval($text) > 100000000){
        sendmessage($from_id, "حداکثر ۱۰۰ میلیون تومان می باشد", $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['AddBalanceUser'], $User_Services, 'HTML');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_add_user = $Balance_user['Balance'] + $text;
    update("user", "Balance", $Balance_add_user, "id", $user['Processing_value']);
    $text = number_format($text);
    $textadd = "💎 کاربر عزیز مبلغ $text تومان به موجودی کیف پول تان اضافه گردید.";
    sendmessage($user['Processing_value'], $textadd, null, 'HTML');
    step('home', $from_id);
}
#-------------------------#
elseif (preg_match('/lowbalanceuser_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    update("user","Processing_value",$iduser, "id",$from_id);
    sendmessage($from_id, $textbotlang['Admin']['Balance']['PriceBalancek'], $backadmin, 'HTML');
    step('get_price_Negative', $from_id);
} elseif ($user['step'] == "get_price_Negative") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    if(intval($text) > 100000000){
        sendmessage($from_id, "حداکثر ۱۰۰ میلیون تومان می باشد", $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['NegativeBalanceUser'], $User_Services, 'HTML');
    $Balance_user = select("user", "*", "id", $user['Processing_value'], "select");
    $Balance_Low_user = $Balance_user['Balance'] - $text;
    update("user", "Balance", $Balance_Low_user, "id", $user['Processing_value']);
    $text = number_format($text);
    $textkam = "❌ کاربر عزیز مبلغ $text تومان از  موجودی کیف پول تان کسر گردید.";
    sendmessage($user['Processing_value'], $textkam, null, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "🎁 ساخت کد هدیه") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['GetCode'], $backadmin, 'HTML');
    step('get_code', $from_id);
} elseif ($user['step'] == "get_code") {
    if (!preg_match('/^[A-Za-z]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['ErrorCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("INSERT INTO Discount (code) VALUES (?)");
    $stmt->bindParam(1, $text);
    $stmt->execute();

    sendmessage($from_id, $textbotlang['Admin']['Discount']['PriceCode'], null, 'HTML');
    step('get_price_code', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "get_price_code") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    update("Discount", "price", $text, "code", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['SaveCode'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "🔗 ارسال لینک سابسکرایبشن") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['sublink'] == null) {
        update("marzban_panel", "sublink", "onsublink", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    if ($panel['configManual'] == "onconfig") {
        sendmessage($from_id, "ابتدا  ارسال کانفیگ را خاموش کنید", null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Status']['subTitle'], $sublinkkeyboard, 'HTML');
}
if ($datain == "onsublink") {
    update("marzban_panel", "sublink", "offsublink", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['subStatusOff'], $sublinkkeyboard);

} elseif ($datain == "offsublink") {
    update("marzban_panel", "sublink", "onsublink", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $sublinkkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['sublink'], 'callback_data' => $panel['sublink']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['subStatuson'], $sublinkkeyboard);
}
#-------------------------#
if ($text == "⚙️ارسال کانفیگ") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['configManual'] == null) {
        update("marzban_panel", "configManual", "offconfig", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    if ($panel['sublink'] == "onsublink") {
        sendmessage($from_id, "ابتدا لینک اشتراک را خاموش کنید", null, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Status']['configTitle'], $configkeyboard, 'HTML');
}
if ($datain == "onconfig") {
    update("marzban_panel", "configManual", "offconfig", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['configStatusOff'], $configkeyboard);
} elseif ($datain == "offconfig") {
    update("marzban_panel", "configManual", "onconfig", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $configkeyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['configManual'], 'callback_data' => $panel['configManual']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['configStatuson'], $configkeyboard);
}
#----------------[  view order user  ]------------------#
if ($text == "🛍 مشاهده سفارشات کاربر") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['ViewOrder'], $backadmin, 'HTML');
    step('GetIdAndOrdedrs', $from_id);
} elseif ($user['step'] == "GetIdAndOrdedrs") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['not-user'], $backadmin, 'HTML');
        return;
    }
    $OrderUsers = select("invoice", "*", "id_user", $text, "fetchAll");
    foreach ($OrderUsers as $OrderUser) {
        $timeacc = jdate('Y/m/d H:i:s', $OrderUser['time_sell']);
        $text_order = "
🛒 شماره سفارش  :  <code>{$OrderUser['id_invoice']}</code>
وضعیت سفارش : <code>{$OrderUser['Status']}</code>
🙍‍♂️ شناسه کاربر : <code>{$OrderUser['id_user']}</code>
👤 نام کاربری اشتراک :  <code>{$OrderUser['username']}</code> 
📍 لوکیشن سرویس :  {$OrderUser['Service_location']}
🛍 نام محصول :  {$OrderUser['name_product']}
💰 قیمت پرداختی سرویس : {$OrderUser['price_product']} تومان
⚜️ حجم سرویس خریداری شده : {$OrderUser['Volume']}
⏳ زمان سرویس خریداری شده : {$OrderUser['Service_time']} روزه
📆 تاریخ خرید : $timeacc
                ";
        sendmessage($from_id, $text_order, null, 'HTML');
    }
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['SendOrder'], $User_Services, 'HTML');
    step('home', $from_id);
}
#----------------[  remove Discount   ]------------------#
if ($text == "❌ حذف کد هدیه") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemoveCode'], $json_list_Discount_list_admin, 'HTML');
    step('remove-Discount', $from_id);
} elseif ($user['step'] == "remove-Discount") {
    if (!in_array($text, $code_Discount)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['NotCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM Discount WHERE code = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemovedCode'], $shopkeyboard, 'HTML');
}
if ($text == "❌ حذف سرویس کاربر") {
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['RemoveService'], $backadmin, 'HTML');
    step('removeservice', $from_id);
} elseif ($user['step'] == "removeservice") {
    $info_product = select("invoice", "*", "username", $text, "select");
    $marzban_list_get = select("marzban_panel", "*", "name_panel", $info_product['Service_location'], "select");
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $text);
    if (isset ($DataUserOut['status'])) {
        $ManagePanel->RemoveUser($marzban_list_get['name_panel'], $text);
    }
    $stmt = $pdo->prepare("DELETE FROM invoice WHERE username = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['ManageUser']['RemovedService'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
if ($text == "💡 روش ساخت نام کاربری") {
    $text_username = "⭕️ روش ساخت نام کاربری برای اکانت ها را از دکمه زیر انتخاب نمایید.
    
    ⚠️ در صورتی که کاربری نام کاربری نداشته باشه کلمه NOT_USERNAME جای نام کاربری اعمال خواهد شد.
    
    ⚠️ در صورتی که نام کاربری وجود داشته باشه یک عدد رندوم به نام کاربری اضافه خواهد شد";
    sendmessage($from_id, $text_username, $MethodUsername, 'HTML');
    step('updatemethodusername', $from_id);
} elseif ($user['step'] == "updatemethodusername") {
    update("marzban_panel", "MethodUsername", $text, "name_panel", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['AlgortimeUsername']['SaveData'], $keyboardadmin, 'HTML');
    if ($text == "متن دلخواه + عدد رندوم") {
        step('getnamecustom', $from_id);
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['customnamesend'], $backuser, 'HTML');
        return;
    }
    step('home', $from_id);
} elseif ($user['step'] == "getnamecustom") {
    // Validate and sanitize the custom name using the Marzban username rules
    $valid_username = validateMarzbanUsername($text);
    if ($valid_username !== $text) {
        // If username was modified, inform the admin
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['invalidname'] . "\n" . 
                   "نام به فرمت صحیح تبدیل شد: " . $valid_username, $backadmin, 'html');
        $text = $valid_username;
    }
    update("setting", "namecustome", $text);
    step('home', $from_id);
    $listpanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    update("user", "Processing_value", $text, "id", $from_id);
    if ($listpanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['savedname'], $optionMarzban, 'HTML');
    } elseif ($listpanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['savedname'], $optionMarzneshin, 'HTML');
    }elseif ($listpanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['savedname'], $optionX_ui_single, 'HTML');
    }elseif ($listpanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['savedname'], $optionX_ui_single, 'HTML');
    }else{
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['savedname'], $optionMarzban, 'HTML');
    }
}
#----------------[  MANAGE PAYMENT   ]------------------#

if ($text == "💵 مالی") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardpaymentManage, 'HTML');
}
if ($text == "💳 تنظبمات درگاه آفلاین") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $CartManage, 'HTML');
}
if ($text == "💳 تنظیم شماره کارت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "CartDescription", "select");
    $textcart = "💳 شماره کارت خود را ارسال کنید
    
    ⭕️ همراه با شماره کارت می توانید نام صاحب کارت هم ارسال نمایید.
    
    💳 شماره کارت فعلی شما : {$PaySetting['ValuePay']}";
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('changecard', $from_id);
} elseif ($user['step'] == "changecard") {
    sendmessage($from_id, $textbotlang['Admin']['SettingPayment']['Savacard'], $CartManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "CartDescription");
    step('home', $from_id);
}
if ($text == "🔌 وضعیت درگاه آفلاین") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "Cartstatus", "select")['ValuePay'];
    $card_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['cardTitle'], $card_Status, 'HTML');
}
if ($datain == "oncard") {
    update("PaySetting", "ValuePay", "offcard", "NamePay", "Cartstatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['cardStatusOff'], null);
} elseif ($datain == "offcard") {
    update("PaySetting", "ValuePay", "oncard", "NamePay", "Cartstatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['cardStatuson'], null);
}
if ($text == "💵 تنظیمات nowpayment") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $NowPaymentsManage, 'HTML');
}
if ($text == "🧩 api nowpayment") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "apinowpayment", "select")['ValuePay'];
    $textcart = "⚙️ api سایت nowpayments.io را ارسال نمایید
    
    api nowpayment :$PaySetting";
    sendmessage($from_id, $textcart, $backadmin, 'HTML');
    step('apinowpayment', $from_id);
} elseif ($user['step'] == "apinowpayment") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['Savaapi'], $NowPaymentsManage, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "apinowpayment");
    step('home', $from_id);
}
if ($text == "🔌 وضعیت درگاه nowpayments") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "nowpaymentstatus", "select")['ValuePay'];
    $now_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['nowpaymentsTitle'], $now_Status, 'HTML');
}
if ($datain == "onnowpayment") {
    update("PaySetting", "ValuePay", "offnowpayment", "NamePay", "nowpaymentstatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['nowpaymentsStatusOff'], null);
} elseif ($datain == "offnowpayment") {
    update("PaySetting", "ValuePay", "onnowpayment", "NamePay", "nowpaymentstatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['nowpaymentsStatuson'], null);
}
if ($text == "💎 درگاه ارزی ریالی") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "digistatus", "select")['ValuePay'];
    $digi_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['digiTitle'], $digi_Status, 'HTML');
}
if ($datain == "offdigi") {
    update("PaySetting", "ValuePay", "ondigi", "NamePay", "digistatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['digiStatuson'], null);
} elseif ($datain == "ondigi") {
    update("PaySetting", "ValuePay", "offdigi", "NamePay", "digistatus");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['digiStatusOff'], null);
}
if ($text == "🟡  درگاه زرین پال") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $zarinpal, 'HTML');
}
if ($text == "تنظیم مرچنت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_id", "select");
    $textzarinpal = "💳 مرچنت کد خود را از زرین پال دریافت و در این قسمت وارد کنید
    
    مرچنت کد فعلی شما : {$PaySetting['ValuePay']}";
    sendmessage($from_id, $textzarinpal, $backadmin, 'HTML');
    step('merchant_id', $from_id);
} elseif ($user['step'] == "merchant_id") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['Savaapi'], $zarinpal, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "merchant_id");
    step('home', $from_id);
}
if ($text == "وضعیت درگاه زرین پال") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "statuszarinpal", "select")['ValuePay'];
    $zarinpal_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['zarinpalTitle'], $zarinpal_Status, 'HTML');
}
if ($datain == "offzarinpal") {
    update("PaySetting", "ValuePay", "onzarinpal", "NamePay", "statuszarinpal");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['zarinpalStatuson'], null);
} elseif ($datain == "onzarinpal") {
    update("PaySetting", "ValuePay", "offzarinpal", "NamePay", "statuszarinpal");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['zarrinpalStatusOff'], null);
}
if ($text == "🔵 درگاه آقای پرداخت") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $aqayepardakht, 'HTML');
}
if ($text == "تنظیم مرچنت آقای پرداخت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "merchant_id_aqayepardakht", "select");
    $textaqayepardakht = "💳 مرچنت کد خود را ازآقای پرداخت دریافت و در این قسمت وارد کنید
    
    مرچنت کد فعلی شما : {$PaySetting['ValuePay']}";
    sendmessage($from_id, $textaqayepardakht, $backadmin, 'HTML');
    step('merchant_id_aqayepardakht', $from_id);
} elseif ($user['step'] == "merchant_id_aqayepardakht") {
    sendmessage($from_id, $textbotlang['Admin']['SettingnowPayment']['Savaapi'], $aqayepardakht, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "merchant_id_aqayepardakht");
    step('home', $from_id);
}
if ($text == "وضعیت درگاه آقای پرداخت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "statusaqayepardakht", "select")['ValuePay'];
    $aqayepardakht_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['aqayepardakhtTitle'], $aqayepardakht_Status, 'HTML');
}
if ($datain == "offaqayepardakht") {
    update("PaySetting", "ValuePay", "onaqayepardakht", "NamePay", "statusaqayepardakht");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['aqayepardakhtStatuson'], null);
} elseif ($datain == "onaqayepardakht") {
    update("PaySetting", "ValuePay", "offaqayepardakht", "NamePay", "statusaqayepardakht");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['aqayepardakhtStatusOff'], null);
}
if ($text == "✏️ مدیریت پنل") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getloc'], $json_list_marzban_panel, 'HTML');
    step('GetLocationEdit', $from_id);
} elseif ($user['step'] == "GetLocationEdit") {
    $listpanel = select("marzban_panel", "*", "name_panel", $text, "select");
    update("user", "Processing_value", $text, "id", $from_id);
    if ($listpanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionMarzban, 'HTML');
    }elseif($listpanel['type'] == "s_ui"){
        sendmessage($from_id, $textbotlang['users']['selectoption'], $options_ui, 'HTML');
    }elseif ($listpanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionMarzneshin, 'HTML');
    } elseif ($listpanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionX_ui_single, 'HTML');
    } elseif ($listpanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionX_ui_single, 'HTML');
    }else{
        sendmessage($from_id, $textbotlang['users']['selectoption'], $optionMarzban, 'HTML');
    }
    step('home', $from_id);
} elseif ($text == "✍️ نام پنل") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['GetNameNew'], $backadmin, 'HTML');
    step('GetNameNew', $from_id);
} elseif ($user['step'] == "GetNameNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($typepanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedNmaePanel'], $optionMarzban, 'HTML');
    }elseif ($typepanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedNmaePanel'], $optionMarzneshin, 'HTML');
    } elseif ($typepanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedNmaePanel'], $optionX_ui_single, 'HTML');
    } elseif ($typepanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedNmaePanel'], $optionX_ui_single, 'HTML');
    }elseif ($typepanel['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedNmaePanel'], $options_ui, 'HTML');
    }
    update("marzban_panel", "name_panel", $text, "name_panel", $user['Processing_value']);
    update("invoice", "Service_location", $text, "Service_location", $user['Processing_value']);
    update("product", "Location", $text, "Location", $user['Processing_value']);
    update("user", "Processing_value", $text, "id", $from_id);
    step('home', $from_id);
} elseif ($text == "🔗 ویرایش آدرس پنل") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['geturlnew'], $backadmin, 'HTML');
    step('GeturlNew', $from_id);
} elseif ($user['step'] == "GeturlNew") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($typepanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $optionMarzban, 'HTML');
    } elseif ($typepanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $optionX_ui_single, 'HTML');
    } elseif ($typepanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $optionX_ui_single, 'HTML');
    }elseif ($typepanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $optionMarzneshin, 'HTML');
    }elseif ($typepanel['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $options_ui, 'HTML');
    }
    update("marzban_panel", "url_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == "👤 ویرایش نام کاربری") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getusernamenew'], $backadmin, 'HTML');
    step('GetusernameNew', $from_id);
} elseif ($user['step'] == "GetusernameNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($typepanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedusernamePanel'], $optionMarzban, 'HTML');
    } elseif ($typepanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedusernamePanel'], $optionX_ui_single, 'HTML');
    }elseif ($typepanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedusernamePanel'], $optionX_ui_single, 'HTML');
    }elseif ($typepanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedusernamePanel'], $optionMarzneshin, 'HTML');
    }elseif ($typepanel['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedusernamePanel'], $options_ui, 'HTML');
    }
    update("marzban_panel", "username_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == "🔐 ویرایش رمز عبور") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['getpasswordnew'], $backadmin, 'HTML');
    step('GetpaawordNew', $from_id);
} elseif ($user['step'] == "GetpaawordNew") {
    $typepanel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($typepanel['type'] == "marzban") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionMarzban, 'HTML');
    } elseif ($typepanel['type'] == "x-ui_single") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionX_ui_single, 'HTML');
    } elseif ($typepanel['type'] == "alireza") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionX_ui_single, 'HTML');
    }elseif ($typepanel['type'] == "marzneshin") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionMarzneshin, 'HTML');
    }elseif ($typepanel['type'] == "s_ui") {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $options_ui, 'HTML');
    }
    update("marzban_panel", "password_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == "💎 تنظیم شناسه اینباند") {
    sendmessage($from_id, "📌 شناسه اینباندی که می خواهید کانفیگ از آن ساخته شود را ارسال نمایید.", $backadmin, 'HTML');
    step('getinboundiid', $from_id);
} elseif ($user['step'] == "getinboundiid") {
    sendmessage($from_id, "✅ شناسه اینباند با موفقیت ذخیره گردید", $optionX_ui_single, 'HTML');
    update("marzban_panel", "inboundid", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
} elseif ($text == "🔗 دامنه لینک ساب") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['geturlnew'], $backadmin, 'HTML');
    step('GeturlNewx', $from_id);
} elseif ($user['step'] == "GeturlNewx") {
    if (!filter_var($text, FILTER_VALIDATE_URL)) {
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['Invalid-domain'], $backadmin, 'HTML');
        return;
    }
    $panel = select("marzban_panel","*","name_panel",$user['Processing_value'],"select");
    if($panel['type'] == "x-ui_single"){
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $optionX_ui_single, 'HTML');
    }elseif($panel['type'] == "s_ui"){
        sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedurlPanel'], $options_ui, 'HTML');
    }
    update("marzban_panel", "linksubx", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
}elseif ($user['step'] == "GetpaawordNew") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['ChangedpasswordPanel'], $optionMarzban, 'HTML');
    update("marzban_panel", "password_panel", $text, "name_panel", $user['Processing_value']);
    step('home', $from_id);
}
if ($text == "❌ حذف پنل") {
    sendmessage($from_id, $textbotlang['Admin']['managepanel']['RemovedPanel'], $keyboardadmin, 'HTML');
    $stmt = $pdo->prepare("DELETE FROM marzban_panel WHERE name_panel = ?");
    $stmt->bindParam(1, $user['Processing_value']);
    $stmt->execute();
}
if ($text == "➕ تنظیم قیمت حجم اضافه") {
    sendmessage($from_id, $textbotlang['users']['Extra_volume']['SetPrice'] . $setting['Extra_volume'], $backadmin, 'HTML');
    step('GetPriceExtra', $from_id);
} elseif ($user['step'] == "GetPriceExtra") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    update("setting", "Extra_volume", $text);
    sendmessage($from_id, $textbotlang['users']['Extra_volume']['ChangedPrice'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
#-------------------------#
if ($text == "👥 شارژ همگانی") {
    sendmessage($from_id, $textbotlang['Admin']['Balance']['addallbalance'], $backadmin, 'HTML');
    step('add_Balance_all', $from_id);
} elseif ($user['step'] == "add_Balance_all") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, $textbotlang['Admin']['Balance']['AddBalanceUsers'], $User_Services, 'HTML');
    $Balance_user = select("user", "*", null, null, "fetchAll");
    foreach ($Balance_user as $balance) {
        $Balance_add_user = $balance['Balance'] + $text;
        update("user", "Balance", $Balance_add_user, "id", $balance['id']);
    }
    step('home', $from_id);
}
if ($text == "🔴 درگاه پرفکت مانی") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $perfectmoneykeyboard, 'HTML');
} elseif ($text == "تنظیم شماره اکانت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "perfectmoney_AccountID", "select")['ValuePay'];
    sendmessage($from_id, "⭕️ شماره اکانت پرفکت مانی خود را ارسال کنید
    مثال : 93293828
    شماره اکانت فعلی : $PaySetting", $backadmin, 'HTML');
    step('setnumberaccount', $from_id);
} elseif ($user['step'] == "setnumberaccount") {
    sendmessage($from_id, $textbotlang['Admin']['perfectmoney']['setnumberacount'], $perfectmoneykeyboard, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "perfectmoney_AccountID");
    step('home', $from_id);
}
if ($text == "تنظیم شماره کیف پول") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "perfectmoney_Payer_Account", "select")['ValuePay'];
    sendmessage($from_id, "⭕️ شماره کیف پولی که میخواهید ووچر پرفکت مانی به آن واریز شود را ارسال کنید 
    مثال : u234082394
    شماره کیف پول فعلی : $PaySetting", $backadmin, 'HTML');
    step('perfectmoney_Payer_Account', $from_id);
} elseif ($user['step'] == "perfectmoney_Payer_Account") {
    sendmessage($from_id, $textbotlang['Admin']['perfectmoney']['setnumberacount'], $perfectmoneykeyboard, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "perfectmoney_Payer_Account");
    step('home', $from_id);
}
if ($text == "تنظیم رمز اکانت") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "perfectmoney_PassPhrase", "select")['ValuePay'];
    sendmessage($from_id, "⭕️ رمز اکانت پرفکت مانی خود را ارسال کنید
    رمز عبور فعلی : $PaySetting", $backadmin, 'HTML');
    step('perfectmoney_PassPhrase', $from_id);
} elseif ($user['step'] == "perfectmoney_PassPhrase") {
    sendmessage($from_id, $textbotlang['Admin']['perfectmoney']['setnumberacount'], $perfectmoneykeyboard, 'HTML');
    update("PaySetting", "ValuePay", $text, "NamePay", "perfectmoney_PassPhrase");
    step('home', $from_id);
}
if ($text == "وضعیت پرفکت مانی") {
    $PaySetting = select("PaySetting", "ValuePay", "NamePay", "status_perfectmoney", "select")['ValuePay'];
    $status_perfectmoney = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $PaySetting, 'callback_data' => $PaySetting],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['perfectmoneyTitle'], $status_perfectmoney, 'HTML');
}
if ($datain == "offperfectmoney") {
    update("PaySetting", "ValuePay", "onperfectmoney", "NamePay", "status_perfectmoney");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['perfectmoneyStatuson'], null);
} elseif ($datain == "onperfectmoney") {
    update("PaySetting", "ValuePay", "offperfectmoney", "NamePay", "status_perfectmoney");
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['perfectmoneyStatusOff'], null);
}
if ($text == "🎁 ساخت کد تخفیف") {
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['GetCode'], $backadmin, 'HTML');
    step('get_codesell', $from_id);
} elseif ($user['step'] == "get_codesell") {
    if (in_array($text, $SellDiscount)) {
        sendmessage($from_id, "❌ این کد تخفیف وجود دارد لطفا از کد تخفیف دیگری استفاده کنید", $backadmin, 'HTML');
        return;
    }
    if (!preg_match('/^[A-Za-z\d]+$/', $text)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['ErrorCode'], null, 'HTML');
        return;
    }
    $values = "0";
    $stmt = $pdo->prepare("INSERT INTO DiscountSell (codeDiscount, usedDiscount, price, limitDiscount, usefirst) VALUES (?, ?, ?, ?,?)");
    $stmt->bindParam(1, $text);
    $stmt->bindParam(2, $values);
    $stmt->bindParam(3, $values);
    $stmt->bindParam(4, $values);
    $stmt->bindParam(5, $values);
    $stmt->execute();

    sendmessage($from_id, $textbotlang['Admin']['Discount']['PriceCodesell'], null, 'HTML');
    step('get_price_codesell', $from_id);
    update("user", "Processing_value", $text, "id", $from_id);
} elseif ($user['step'] == "get_price_codesell") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, $textbotlang['Admin']['Balance']['Invalidprice'], $backadmin, 'HTML');
        return;
    }
    update("DiscountSell", "price", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discountsell']['getlimit'], $backadmin, 'HTML');
    step('getlimitcode', $from_id);
} elseif ($user['step'] == "getlimitcode") {
    update("DiscountSell", "limitDiscount", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, "📌 کد تخفیف برای خرید اول باشد یا همه خرید ها
    0 : همه خرید ها
    1 : خرید اول ", $backadmin, 'HTML');
    step('getusefirst', $from_id);
} elseif ($user['step'] == "getusefirst") {
    update("DiscountSell", "usefirst", $text, "codeDiscount", $user['Processing_value']);
    sendmessage($from_id, $textbotlang['Admin']['Discount']['SaveCode'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
if ($text == "❌ حذف کد تخفیف") {
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemoveCode'], $json_list_Discount_list_admin_sell, 'HTML');
    step('remove-Discountsell', $from_id);
} elseif ($user['step'] == "remove-Discountsell") {
    if (!in_array($text, $SellDiscount)) {
        sendmessage($from_id, $textbotlang['Admin']['Discount']['NotCode'], null, 'HTML');
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM DiscountSell WHERE codeDiscount = ?");
    $stmt->bindParam(1, $text);
    $stmt->execute();
    sendmessage($from_id, $textbotlang['Admin']['Discount']['RemovedCode'], $shopkeyboard, 'HTML');
    step('home', $from_id);
}
if ($text == "👥 تنظیمات زیر مجموعه گیری") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $affiliates, 'HTML');
} elseif ($text == "🎁 وضعیت زیرمجموعه گیری") {
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    $keyboardaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $affiliatesvalue, 'callback_data' => $affiliatesvalue],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['affiliates'], $keyboardaffiliates, 'HTML');
} elseif ($datain == "onaffiliates") {
    update("affiliates", "affiliatesstatus", "offaffiliates");
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    $keyboardaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $affiliatesvalue, 'callback_data' => $affiliatesvalue],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['affiliatesStatusOff'], $keyboardaffiliates);
} elseif ($datain == "offaffiliates") {
    update("affiliates", "affiliatesstatus", "onaffiliates");
    $affiliatesvalue = select("affiliates", "*", null, null, "select")['affiliatesstatus'];
    $keyboardaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $affiliatesvalue, 'callback_data' => $affiliatesvalue],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['affiliatesStatuson'], $keyboardaffiliates);
}
if ($text == "🧮 تنظیم درصد زیرمجموعه") {
    sendmessage($from_id, $textbotlang['users']['affiliates']['setpercentage'], $backadmin, 'HTML');
    step('setpercentage', $from_id);
} elseif ($user['step'] == "setpercentage") {
    sendmessage($from_id, $textbotlang['users']['affiliates']['changedpercentage'], $affiliates, 'HTML');
    update("affiliates", "affiliatespercentage", $text);
    step('home', $from_id);
} elseif ($text == "🏞 تنظیم بنر زیرمجموعه گیری") {
    sendmessage($from_id, $textbotlang['users']['affiliates']['banner'], $backadmin, 'HTML');
    step('setbanner', $from_id);
} elseif ($user['step'] == "setbanner") {
    if (!$photo) {
        sendmessage($from_id, $textbotlang['users']['affiliates']['invalidbanner'], $backadmin, 'HTML');
        return;
    }
    update("affiliates", "description", $caption);
    update("affiliates", "id_media", $photoid);
    sendmessage($from_id, $textbotlang['users']['affiliates']['insertbanner'], $affiliates, 'HTML');
    step('home', $from_id);
} elseif ($text == "🎁 پورسانت بعد از خرید") {
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['commission'], $keyboardcommission, 'HTML');
} elseif ($datain == "oncommission") {
    update("affiliates", "status_commission", "offcommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionStatusOff'], $keyboardcommission);
} elseif ($datain == "offcommission") {
    update("affiliates", "status_commission", "oncommission");
    $marzbancommission = select("affiliates", "*", null, null, "select");
    $keyboardcommission = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbancommission['status_commission'], 'callback_data' => $marzbancommission['status_commission']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['commissionStatuson'], $keyboardcommission);
} elseif ($text == "🎁 دریافت هدیه") {
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['Discountaffiliates'], $keyboardDiscountaffiliates, 'HTML');
} elseif ($datain == "onDiscountaffiliates") {
    update("affiliates", "Discount", "offDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['DiscountaffiliatesStatusOff'], $keyboardDiscountaffiliates);
} elseif ($datain == "offDiscountaffiliates") {
    update("affiliates", "Discount", "onDiscountaffiliates");
    $marzbanDiscountaffiliates = select("affiliates", "*", null, null, "select");
    $keyboardDiscountaffiliates = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $marzbanDiscountaffiliates['Discount'], 'callback_data' => $marzbanDiscountaffiliates['Discount']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['DiscountaffiliatesStatuson'], $keyboardDiscountaffiliates);
}
if ($text == "🌟 مبلغ هدیه استارت") {
    sendmessage($from_id, $textbotlang['users']['affiliates']['priceDiscount'], $backadmin, 'HTML');
    step('getdiscont', $from_id);
} elseif ($user['step'] == "getdiscont") {
    sendmessage($from_id, $textbotlang['users']['affiliates']['changedpriceDiscount'], $affiliates, 'HTML');
    update("affiliates", "price_Discount", $text);
    step('home', $from_id);
} elseif (preg_match('/rejectremoceserviceadmin-(\w+)/', $datain, $dataget)) {
    $usernamepanel = $dataget[1];
    $requestcheck = select("cancel_service", "*", "username", $usernamepanel, "select");
    if ($requestcheck['status'] == "accept" || $requestcheck['status'] == "reject") {
        telegram('answerCallbackQuery', array(
                'callback_query_id' => $callback_query_id,
                'text' => "این درخواست توسط ادمین دیگری بررسی شده است",
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    step("descriptionsrequsts", $from_id);
    update("user", "Processing_value", $usernamepanel, "id", $from_id);
    sendmessage($from_id, "📌 درخواست رد کردن حذف با موفقیت ثبت شد دلیل عدم تایید را ارسال کنید", $backuser, 'HTML');

} elseif ($user['step'] == "descriptionsrequsts") {
    sendmessage($from_id, "✅ با موفقیت ثبت گردید", $keyboardadmin, 'HTML');
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    update("cancel_service", "status", "reject", "username", $user['Processing_value']);
    update("cancel_service", "description", $text, "username", $user['Processing_value']);
    step("home", $from_id);
    sendmessage($nameloc['id_user'], "❌ کاربری گرامی درخواست حذف شما با نام کاربری  {$user['Processing_value']} موافقت نگردید.
            
            دلیل عدم تایید : $text", null, 'HTML');

} elseif (preg_match('/remoceserviceadmin-(\w+)/', $datain, $dataget)) {
    $username = $dataget[1];
    $requestcheck = select("cancel_service", "*", "username", $username, "select");
    if ($requestcheck['status'] == "accept" || $requestcheck['status'] == "reject") {
        telegram('answerCallbackQuery', array(
                'callback_query_id' => $callback_query_id,
                'text' => "این درخواست توسط ادمین دیگری بررسی شده است",
                'show_alert' => true,
                'cache_time' => 5,
            )
        );
        return;
    }
    step("getpricerequests", $from_id);
    update("user", "Processing_value", $username, "id", $from_id);
    sendmessage($from_id, "💰 مقدار مبلغی که میخواهید به موجودی کاربر اضافه شود را ارسال کنید.", $backuser, 'HTML');

} elseif ($user['step'] == "getpricerequests") {
    if (!ctype_digit($text)) {
        sendmessage($from_id, "⭕️ ورودی نا معتبر", null, 'HTML');
    }
    $nameloc = select("invoice", "*", "username", $user['Processing_value'], "select");
    if ($nameloc['price_product'] < $text) {
        sendmessage($from_id, "❌ مبلغ بازگشتی بزرگ تر از مبلغ محصول است!", $backuser, 'HTML');
        return;
    }
    sendmessage($from_id, "✅ با موفقیت ثبت گردید", $keyboardadmin, 'HTML');
    step("home", $from_id);
    $marzban_list_get = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM marzban_panel WHERE name_panel = '{$nameloc['Service_location']}'"));
    $DataUserOut = $ManagePanel->DataUser($marzban_list_get['name_panel'], $user['Processing_value']);
    if (isset ($DataUserOut['status'])) {
        $ManagePanel->RemoveUser($marzban_list_get['name_panel'], $user['Processing_value']);
    }
    update("cancel_service", "status", "accept", "username", $user['Processing_value']);
    update("invoice", "status", "removedbyadmin", "username", $user['Processing_value']);
    step("home", $from_id);
    sendmessage($nameloc['id_user'], "✅ کاربری گرامی درخواست حذف شما با نام کاربری  {$user['Processing_value']} موافقت گردید.", null, 'HTML');
    $pricecancel = number_format(intval($text));
    if (intval($text) != 0) {
        $Balance_id_cancel = mysqli_fetch_assoc(mysqli_query($connect, "SELECT * FROM user WHERE id = '{$nameloc['id_user']}' LIMIT 1"));
        $Balance_id_cancel_fee = intval($Balance_id_cancel['Balance']) + intval($text);
        update("user", "Balance", $Balance_id_cancel_fee, "id", $nameloc['id_user']);
        sendmessage($nameloc['id_user'], "💰کاربر گرامی مبلغ $pricecancel تومان به موجودی شما اضافه گردید.", null, 'HTML');
    }
    $text_report = "⭕️ یک ادمین سرویس کاربر که درخواست حذف داشت را تایید کرد
            
            اطلاعات کاربر تایید کننده  : 
            
            🪪 آیدی عددی : <code>$from_id</code>
            💰 مبلغ بازگشتی : $pricecancel تومان
            👤 نام کاربری : $username
            آیدی عددی درخواست کننده کنسل کردن : {$nameloc['id_user']}";
    if (isset($setting['Channel_Report']) &&strlen($setting['Channel_Report']) > 0) {
        sendmessage($setting['Channel_Report'], $text_report, null, 'HTML');
    }
}
if ($text == "⏳ قابلیت اولین اتصال") {
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    if ($panel['onholdstatus'] == null) {
        update("marzban_panel", "onholdstatus", "offonhold", "name_panel", $user['Processing_value']);
    }
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['onhold'], $onhold_Status, 'HTML');
}
if ($datain == "ononhold") {
    update("marzban_panel", "onholdstatus", "offonhold", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['offstatus'], $onhold_Status);
} elseif ($datain == "offonhold") {
    update("marzban_panel", "onholdstatus", "ononhold", "name_panel", $user['Processing_value']);
    $panel = select("marzban_panel", "*", "name_panel", $user['Processing_value'], "select");
    $onhold_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $panel['onholdstatus'], 'callback_data' => $panel['onholdstatus']],
            ],
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['onstatus'], $onhold_Status);
}
if ($text == "🕚 تنظیمات کرون جاب") {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardcronjob, 'HTML');
}
if($text == "فعال شدن کرون تست"){
    sendmessage($from_id, "✅ کرون جاب فعال گردید این کرون هر 15 دقیقه اجرا می شود", null, 'HTML');
    $phpFilePath = "https://$domainhosts/cron/configtest.php";
    $cronCommand = "*/15 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}
if($text == "غیر فعال شدن کرون تست"){
    sendmessage($from_id, "کرون جاب غیرفعال گردید", null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/15 * * * * curl https://$domainhosts/cron/configtest.php";
    $newCronJobs = preg_replace('/'.preg_quote($jobToRemove, '/').'/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}
if($text == "فعال شدن کرون حجم"){
    sendmessage($from_id, "✅ کرون جاب فعال گردید این کرون هر 1 دقیقه اجرا می شود", null, 'HTML');
    $phpFilePath = "https://$domainhosts/cron/cronvolume.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}
if($text == "غیر فعال شدن کرون حجم"){
    sendmessage($from_id, "کرون جاب غیرفعال گردید", null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/cronvolume.php";
    $newCronJobs = preg_replace('/'.preg_quote($jobToRemove, '/').'/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}
if($text == "فعال شدن کرون زمان"){
    sendmessage($from_id, "✅ کرون جاب فعال گردید این کرون هر 1 دقیقه اجرا می شود", null, 'HTML');
    $phpFilePath = "https://$domainhosts/cron/cronday.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}
if($text == "غیر فعال شدن کرون زمان"){
    sendmessage($from_id, "کرون جاب غیرفعال گردید", null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/cronday.php";
    $newCronJobs = preg_replace('/'.preg_quote($jobToRemove, '/').'/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}
if($text == "فعال شدن کرون حذف"){
    sendmessage($from_id, "✅ کرون جاب فعال گردید این کرون هر 1 دقیقه اجرا می شود", null, 'HTML');
    $phpFilePath = "https://$domainhosts/cron/removeexpire.php";
    $cronCommand = "*/1 * * * * curl $phpFilePath";
    $existingCronCommands = shell_exec('crontab -l');
    if (strpos($existingCronCommands, $cronCommand) === false) {
        $command = "(crontab -l ; echo '$cronCommand') | crontab -";
        shell_exec($command);
    }
}
if($text == "غیر فعال شدن کرون حذف"){
    sendmessage($from_id, "کرون جاب غیرفعال گردید", null, 'HTML');
    $currentCronJobs = shell_exec("crontab -l");
    $jobToRemove = "*/1 * * * * curl https://$domainhosts/cron/removeexpire.php";
    $newCronJobs = preg_replace('/'.preg_quote($jobToRemove, '/').'/', '', $currentCronJobs);
    file_put_contents('/tmp/crontab.txt', $newCronJobs);
    shell_exec('crontab /tmp/crontab.txt');
    unlink('/tmp/crontab.txt');
}
if ($text == "👁‍🗨 جستجو کاربر") {
    sendmessage($from_id, "📌 آیدی عددی کاربر را ارسال نمایید", $backadmin, 'HTML');
    step('show_infos', $from_id);
} elseif ($user['step'] == "show_infos") {
    if (!in_array($text, $users_ids)) {
        sendmessage($from_id, $textbotlang['Admin']['not-user'], $backadmin, 'HTML');
        return;
    }
    $date = date("Y-m-d");
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn') AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $dayListSell = $stmt->rowCount();
    $stmt = $pdo->prepare("SELECT SUM(price) FROM Payment_report WHERE payment_Status = 'paid' AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $balanceall = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(price)'];
    $stmt = $pdo->prepare("SELECT SUM(price_product) FROM invoice WHERE (status = 'active' OR status = 'end_of_time'  OR status = 'end_of_volume' OR status = 'sendedwarn') AND id_user = :id_user");
    $stmt->bindParam(':id_user', $text);
    $stmt->execute();
    $subbuyuser = $stmt->fetch(PDO::FETCH_ASSOC)['SUM(price_product)'];
    $user = select("user","*","id",$text,"select");
    $roll_Status = [
        '1' => $textbotlang['Admin']['ManageUser']['Acceptedphone'],
        '0' => $textbotlang['Admin']['ManageUser']['Failedphone'],
    ][$user['roll_Status']];
    if($subbuyuser == null )$subbuyuser = 0;
    $keyboardmanage = [
        'inline_keyboard' => [
            [['text' => $textbotlang['Admin']['ManageUser']['addbalanceuser'], 'callback_data' => "addbalanceuser_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['lowbalanceuser'], 'callback_data' => "lowbalanceuser_" . $text],],
            [['text' => $textbotlang['Admin']['ManageUser']['banuserlist'], 'callback_data' => "banuserlist_" . $text], ['text' => $textbotlang['Admin']['ManageUser']['unbanuserlist'], 'callback_data' => "unbanuserr_" . $text]],
            [['text' => $textbotlang['Admin']['ManageUser']['confirmnumber'], 'callback_data' => "confirmnumber_" . $text]],
            [['text' => "➕ محدودیت ساخت اکانت تست", 'callback_data' => "limitusertest_" . $text]],
            [['text' => "احراز هویت ", 'callback_data' => "verify_" . $text],['text' => "حذف احراز هویت ", 'callback_data' => "verifyun_" . $text]],
        ]
    ];
    $keyboardmanage = json_encode($keyboardmanage);
    $user['Balance'] = number_format($user['Balance']);
    $lastmessage = jdate('Y/m/d H:i:s',$user['last_message_time']);
    $textinfouser = "👀 اطلاعات کاربر:

⭕️ وضعیت کاربر : {$user['User_Status']}
⭕️ نام کاربری کاربر : @{$user['username']}
⭕️ آیدی عددی کاربر :  <a href = \"tg://user?id=$text\">$text</a>
⭕️ آخرین زمان  استفاده کاربر از ربات : $lastmessage
⭕️ محدودیت اکانت تست :  {$user['limit_usertest']} 
⭕️ وضعیت تایید قانون : $roll_Status
⭕️ شماره موبایل : <code>{$user['number']}</code>
⭕️ موجودی کاربر : {$user['Balance']}
⭕️ تعداد خرید کل کاربر : $dayListSell
⭕️ مبلغ کل پرداختی  :  $balanceall
⭕️ جمع کل خرید : $subbuyuser
⭕️ تعداد زیرمجموعه کاربر : {$user['affiliatescount']}
⭕  معرف کاربر : {$user['affiliates']}
⭕  وضعیت احراز کاربرر : {$user['verify']}
";
    sendmessage($from_id, $textinfouser, $keyboardmanage, 'HTML');
    sendmessage($from_id, $textbotlang['users']['selectoption'], $keyboardadmin, 'HTML');
    step('home', $from_id);
}
if($text == "زمان حذف اکانت"){
    sendmessage($from_id, "زمان خود را برای حذف اکانت های اکسپایر شده ارسال کنید.
نکته : این بخش سرویس هایی که x روز از زمان انقضا شان گذشته باشد حذف می کند", $backadmin, 'HTML');
    step("gettimeremove",$from_id);
}elseif($user['step'] == "gettimeremove"){
    if (!ctype_digit($text)) {
        sendmessage($from_id, "زمان ناعمتبر است", $backadmin, 'HTML');
        return;
    }
    sendmessage($from_id, "زمان با موفقیت تنظیم شد", $keyboardcronjob, 'HTML');
    step("home",$from_id);
    update("setting","removedayc",$text,null,null);
}
if ($text == "⚙️ تنظیمات سرویس") {
    $textsetservice = "📌 برای تنظیم سرویس یک کانفیگ در پنل خود ساخته و  سرویس هایی که میخواهید فعال باشند. را داخل پنل فعال کرده و نام کاربری کانفیگ را ارسال نمایید";
    sendmessage($from_id, $textsetservice, $backadmin, 'HTML');
    step('getservceid',$from_id);
} elseif ($user['step'] == "getservceid") {
    $userdata = getuserm($text,$user['Processing_value']);
    if(isset($userdata['detail']) and $userdata['detail'] == "User not found"){
        sendmessage($from_id,"کاربر در پنل وجود ندارد", null, 'HTML');
        return;
    }
    update("marzban_panel","proxies",json_encode($userdata['service_ids']),"name_panel",$user['Processing_value']);
    step("home",$from_id);
    sendmessage($from_id,"✅ اطلاعات با موفقیت تنظیم گردید", $optionMarzneshin, 'HTML');
}
elseif($text == "✏️ ویرایش آموزش"){
    sendmessage($from_id,"📌 یک آموزش را انتخاب کنید.", $json_list_help, 'HTML');
    step("getnameforedite",$from_id);
}elseif($user['step'] == "getnameforedite"){
    sendmessage($from_id, $textbotlang['users']['selectoption'], $helpedit, 'HTML');
    update("user","Processing_value",$text, "id",$from_id);
    step("home",$from_id);

}
elseif($text == "ویرایش نام") {
    sendmessage($from_id, "نام جدید را ارسال کنید", $backadmin, 'HTML');
    step('changenamehelp', $from_id);
}elseif($user['step'] == "changenamehelp") {
    if(strlen($text) >= 150){
        sendmessage($from_id, "❌ نام آموزش باید کمتر از 150 کاراکتر باشد", null, 'HTML');
        return;
    }
    update("help","name_os",$text,"name_os",$user['Processing_value']);
    sendmessage($from_id, "✅ نام آموزش بروزرسانی شد", $json_list_helpkey, 'HTML');
    step('home', $from_id);
}elseif($text == "ویرایش توضیحات") {
    sendmessage($from_id, "توضیحات جدید را ارسال کنید", $backadmin, 'HTML');
    step('changedeshelp', $from_id);
}elseif($user['step'] == "changedeshelp") {
    update("help","Description_os",$text,"name_os",$user['Processing_value']);
    sendmessage($from_id, "✅ توضیحات  آموزش بروزرسانی شد", $helpedit, 'HTML');
    step('home', $from_id);
}
elseif($text == "ویرایش رسانه") {
    sendmessage($from_id, "تصویر یا فیلم جدید را ارسال کنید", $backadmin, 'HTML');
    step('changemedia', $from_id);
}elseif($user['step'] == "changemedia") {
    if ($photo) {
        if(isset($photoid))update("help","Media_os",$photoid, "name_os",$user['Processing_value']);
        update("help","type_Media_os","photo", "name_os",$user['Processing_value']);
    }elseif($video) {
        if(isset($videoid))update("help","Media_os",$videoid, "name_os",$user['Processing_value']);
        update("help","type_Media_os","video", "name_os",$user['Processing_value']);
    }
    sendmessage($from_id, "✅ توضیحات  آموزش بروزرسانی شد", $helpedit, 'HTML');
    step('home', $from_id);
}elseif($text == "⚙️ تنظیم پروتکل و اینباند"){
    $textsetprotocol = "📌 برای تنظیم اینباند  و پروتکل باید یک کانفیگ در پنل خود ساخته و  پروتکل و اینباند هایی که میخواهید فعال باشند. را داخل پنل فعال کرده و نام کاربری کانفیگ را ارسال نمایید";
    sendmessage($from_id, $textsetprotocol, $backadmin, 'HTML');
    step("setinboundandprotocol",$from_id);
}elseif($user['step'] == "setinboundandprotocol"){
    $panel = select("marzban_panel","*","name_panel",$user['Processing_value'],"select");
    if($panel['type'] == "marzban"){
        $DataUserOut = getuser($text,$user['Processing_value']);
        if ((isset($DataUserOut['msg']) && $DataUserOut['msg'] == "User not found") or !isset($DataUserOut['proxies'])) {
            sendmessage($from_id,$textbotlang['users']['stateus']['usernotfound'], null, 'html');
            return;
        }
        foreach ($DataUserOut['proxies'] as $key => &$value){
            if($key == "shadowsocks"){
                unset($DataUserOut['proxies'][$key]['password']);
            }
            elseif($key == "trojan"){
                unset($DataUserOut['proxies'][$key]['password']);
            }
            else{
                unset($DataUserOut['proxies'][$key]['id']);
            }
            if(count($DataUserOut['proxies'][$key]) == 0){
                $DataUserOut['proxies'][$key] = new stdClass();
            }
        }
        update("marzban_panel","inbounds",json_encode($DataUserOut['inbounds']),"name_panel",$user['Processing_value']);
        update("marzban_panel","proxies",json_encode($DataUserOut['proxies']),"name_panel",$user['Processing_value']);
    }else{
        $data = GetClientsS_UI($text,$panel['name_panel']);{
            if(count($data) == 0){
                sendmessage($from_id, "❌ یوزر در پنل وجود ندارد.", $options_ui, 'HTML');
                return;
            }
            $servies = [];
            foreach ($data['inbounds'] as $service){
                $servies[] = $service;
            }
        }
        update("marzban_panel","proxies",json_encode($servies,true),"name_panel",$user['Processing_value']);
    }
    sendmessage($from_id, "✅ اینباند و پروتکل های شما با موفقیت تنظیم گردیدند.", $optionMarzban, 'HTML');
    step("home",$from_id);
}elseif($text == "⚙️ وضعیت قابلیت ها") {
    if($setting['Bot_Status'] == "✅  ربات روشن است") {
        update("setting","Bot_Status","1");
    }elseif($setting['Bot_Status'] == "❌ ربات خاموش است") {
        update("setting","Bot_Status","0");
    }

    if($setting['roll_Status'] == "✅ تایید قانون روشن است") {
        update("setting","roll_Status","1");
    }elseif($setting['roll_Status'] == "❌ تایید قوانین خاموش است") {
        update("setting","roll_Status","0");
    }

    if($setting['NotUser'] == "onnotuser") {
        update("setting","NotUser","1");
    }elseif($setting['NotUser'] == "offnotuser") {
        update("setting","NotUser","0");
    }

    if($setting['help_Status'] == "✅ آموزش فعال است") {
        update("setting","help_Status","1");
    }elseif($setting['help_Status'] == "❌ آموزش غیرفعال است") {
        update("setting","help_Status","0");
    }

    if($setting['get_number'] == "✅ تایید شماره موبایل روشن است") {
        update("setting","get_number","1");
    }elseif($setting['get_number'] == "❌ احرازهویت شماره تماس غیرفعال است") {
        update("setting","get_number","0");
    }

    if($setting['iran_number'] == "✅ احرازشماره ایرانی روشن است") {
        update("setting","iran_number","1");
    }elseif($setting['iran_number'] == "❌ بررسی شماره ایرانی غیرفعال است") {
        update("setting","iran_number","0");
    }
    $setting = select("setting", "*");
    $name_status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $roll_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['roll_Status']];
    $NotUser_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $help_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['help_Status']];
    $get_number_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $get_number_iran   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['iran_number']];
    $statusv_verify   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_verify']];
    $statusv_category  = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statussubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['stautsbot'], 'callback_data' => "statusbot"],
            ],[
                ['text' => $roll_Status, 'callback_data' => "editstsuts-roll_Status-{$setting['roll_Status']}"],
                ['text' => "♨️ بخش قوانین", 'callback_data' => "roll_Status"],
            ],[
                ['text' => $NotUser_Status, 'callback_data' => "editstsuts-NotUser-{$setting['NotUser']}"],
                ['text' => "👤 دکمه نام کاربری", 'callback_data' => "NotUser"],
            ],[
                ['text' => $help_Status, 'callback_data' => "editstsuts-help_Status-{$setting['help_Status']}"],
                ['text' => "💡 وضعیت بخش آموزش", 'callback_data' => "help_Status"],
            ],[
                ['text' => $get_number_Status, 'callback_data' => "editstsuts-get_number-{$setting['get_number']}"],
                ['text' => "احراز هویت شماره", 'callback_data' => "get_number"],
            ],[
                ['text' => $get_number_iran, 'callback_data' => "editstsuts-iran_number-{$setting['iran_number']}"],
                ['text' => "تایید شماره ایرانی 🇮🇷", 'callback_data' => "iran_number"],
            ],[
                ['text' => $statusv_verify, 'callback_data' => "editstsuts-verify-{$setting['status_verify']}"],
                ['text' => "👤 احراز هویت", 'callback_data' => "status_verify"],
            ],[
                ['text' => $statusv_category, 'callback_data' => "editstsuts-category-{$setting['statuscategory']}"],
                ['text' => "🕹 دسته بندی", 'callback_data' => "statuscategory"],
            ]
        ]
    ]);
    sendmessage($from_id, $textbotlang['Admin']['Status']['BotTitle'], $Bot_Status, 'HTML');
}
elseif(preg_match('/^editstsuts-(.*)-(.*)/', $datain, $dataget)) {
    $type = $dataget[1];
    $value = $dataget[2];
    if($type == "statusbot"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","Bot_Status",$valuenew);
    }elseif($type == "roll_Status"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","roll_Status",$valuenew);
    }elseif($type == "NotUser"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","NotUser",$valuenew);
    }elseif($type == "help_Status"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","help_Status",$valuenew);
    }elseif($type == "get_number"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","get_number",$valuenew);
    }elseif($type == "iran_number"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","iran_number",$valuenew);
    }elseif($type == "verify"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","status_verify",$valuenew);
    }elseif($type == "category"){
        if($value == "1"){
            $valuenew = "0";
        }else{
            $valuenew = "1";
        }
        update("setting","statuscategory",$valuenew);
    }
    $setting = select("setting", "*");
    $name_status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['Bot_Status']];
    $roll_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['roll_Status']];
    $NotUser_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['NotUser']];
    $help_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['help_Status']];
    $get_number_Status   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['get_number']];
    $get_number_iran   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['iran_number']];
    $statusv_verify   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['status_verify']];
    $statusv_category   = [
        '1' => $textbotlang['Admin']['Status']['statuson'],
        '0' => $textbotlang['Admin']['Status']['statusoff']
    ][$setting['statuscategory']];
    $Bot_Status = json_encode([
        'inline_keyboard' => [
            [
                ['text' => $textbotlang['Admin']['Status']['statussubject'], 'callback_data' => "subjectde"],
                ['text' => $textbotlang['Admin']['Status']['subject'], 'callback_data' => "subject"],
            ],
            [
                ['text' => $name_status, 'callback_data' => "editstsuts-statusbot-{$setting['Bot_Status']}"],
                ['text' => $textbotlang['Admin']['Status']['stautsbot'], 'callback_data' => "statusbot"],
            ],[
                ['text' => $roll_Status, 'callback_data' => "editstsuts-roll_Status-{$setting['roll_Status']}"],
                ['text' => "♨️ بخش قوانین", 'callback_data' => "roll_Status"],
            ],[
                ['text' => $NotUser_Status, 'callback_data' => "editstsuts-NotUser-{$setting['NotUser']}"],
                ['text' => "👤 دکمه نام کاربری", 'callback_data' => "NotUser"],
            ],[
                ['text' => $help_Status, 'callback_data' => "editstsuts-help_Status-{$setting['help_Status']}"],
                ['text' => "💡 وضعیت بخش آموزش", 'callback_data' => "help_Status"],
            ],[
                ['text' => $get_number_Status, 'callback_data' => "editstsuts-get_number-{$setting['get_number']}"],
                ['text' => "احراز هویت شماره", 'callback_data' => "get_number"],
            ],[
                ['text' => $get_number_iran, 'callback_data' => "editstsuts-iran_number-{$setting['iran_number']}"],
                ['text' => "تایید شماره ایرانی 🇮🇷", 'callback_data' => "iran_number"],
            ],[
                ['text' => $statusv_verify, 'callback_data' => "editstsuts-verify-{$setting['status_verify']}"],
                ['text' => "👤 احراز هویت", 'callback_data' => "status_verify"],
            ],[
                ['text' => $statusv_category, 'callback_data' => "editstsuts-category-{$setting['statuscategory']}"],
                ['text' => "🕹 دسته بندی", 'callback_data' => "statuscategory"],
            ]
        ]
    ]);
    Editmessagetext($from_id, $message_id, $textbotlang['Admin']['Status']['BotTitle'], $Bot_Status);
}elseif (preg_match('/verify_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunverify = select("user", "*", "id", $iduser, "select");
    if ($userunverify['verify'] == "1") {
        sendmessage($from_id, "کاربر از قبل احراز شده است", $backadmin, 'HTML');
        return;
    }
    update("user", "verify", "1", "id", $iduser);
    sendmessage($from_id,"✅ کاربر با موفقیت احراز گردید.", $keyboardadmin, 'HTML');
    step('home', $from_id);
}elseif (preg_match('/verifyun_(\w+)/', $datain, $dataget)) {
    $iduser = $dataget[1];
    $userunverify = select("user", "*", "id", $iduser, "select");
    if ($userunblock['verify'] == "0") {
        sendmessage($from_id, "کاربر از قبل احراز نبوده است", $backadmin, 'HTML');
        return;
    }
    update("user", "verify", "0", "id", $iduser);
    sendmessage($from_id,"✅ کاربر با موفقیت از احراز خارج گردید.", $keyboardadmin, 'HTML');
    step('home', $from_id);
}elseif($text == "🛒 اضافه کردن دسته بندی"){
    sendmessage($from_id,"📌 نام دسته بندی را ارسال کنید ", $backadmin, 'HTML');
    step("getremarkcategory",$from_id);
}elseif($user['step'] == "getremarkcategory"){
    sendmessage($from_id,"✅ دسته بندی با موفقیت اضافه گردید.", $shopkeyboard, 'HTML');
    step("home",$from_id);
    $stmt = $pdo->prepare("INSERT INTO category (remark) VALUES (?)");
    $stmt->bindParam(1, $text);
    $stmt->execute();
}elseif($text == "❌ حذف دسته بندی"){
    sendmessage($from_id,"📌 دسته بندی خود را جهت حذف انتخاب کنید",KeyboardCategory(), 'HTML');
    step("removecategory",$from_id);
}elseif($user['step'] == "removecategory"){
    sendmessage($from_id,"✅ دسته بندی با موفقیت حذف گردید.", $shopkeyboard, 'HTML');
    step("home",$from_id);
    $stmt = $pdo->prepare("DELETE FROM category WHERE remark = :remark ");
    $stmt->bindParam(':remark', $text);
    $stmt->execute();
}

// Agency Management Section
if ($text == $textbotlang['Admin']['agency']['list_button']) {
    // نمایش لیست نمایندگان به صورت جدول
    $agencies = select("agency", "*", "status", "approved", "fetchAll");
    
    // حتی اگر هیچ نماینده‌ای وجود نداشته باشد، یک پیام مناسب نمایش می‌دهیم
    if (!$agencies || (is_array($agencies) && count($agencies) == 0)) {
        sendmessage($from_id, $textbotlang['Admin']['agency']['no_agencies'], $agencyManageKeyboard, 'HTML');
        exit();
    }
    
    $message = "🔍 لیست نمایندگان:\nجهت مشاهده آمار فروش نمایندگان روی اسم نماینده کلیک کنید، همچنین میتوانید برای نماینده درصد تعیین کنید یا آن را از لیست نمایندگان خارج کنید\n\n";
    
    // ساخت اینلاین کیبورد
    $inline_keyboard = [
        // هدر جدول به عنوان اولین ردیف
        [
            ['text' => "آیدی عددی", 'callback_data' => "null"],
            ['text' => "اسم نماینده", 'callback_data' => "null"],
            ['text' => "تاریخ نمایندگی", 'callback_data' => "null"],
            ['text' => "درصد تخفیف", 'callback_data' => "null"],
            ['text' => "حذف", 'callback_data' => "null"]
        ]
    ];
    
    // اضافه کردن ردیف‌های جدول به اینلاین کیبورد
    foreach ($agencies as $agency) {
        if (!isset($agency['user_id']) || !$agency['user_id']) {
            continue; // اگر user_id نامعتبر است، این نماینده را نادیده می‌گیریم
        }
        
        $user_info = select("user", "*", "id", $agency['user_id'], "select");
        $name = "کاربر"; // نام پیش‌فرض
        
        // بررسی اطلاعات کاربر
        if ($user_info && isset($user_info['name']) && $user_info['name']) {
            $name = $user_info['name'];
        } elseif (isset($agency['username']) && $agency['username']) {
            $name = $agency['username'];
        }
        
        // تبدیل تایمستمپ به تاریخ شمسی
        $date = "نامشخص"; // تاریخ پیش‌فرض
        if (isset($agency['created_at']) && $agency['created_at']) {
            if (function_exists('jdate')) {
                $date = jdate('Y/m/d H:i', strtotime($agency['created_at']));
            } else {
                $date = date('Y/m/d H:i', strtotime($agency['created_at']));
            }
        }
        
        // هر نماینده یک ردیف در اینلاین کیبورد است
        $inline_keyboard[] = [
            ['text' => $agency['user_id'], 'callback_data' => "agency_info_" . $agency['user_id']],
            ['text' => $name, 'callback_data' => "agency_info_" . $agency['user_id']],
            ['text' => $date, 'callback_data' => "agency_date_" . $agency['user_id']],
            ['text' => "⚙️", 'callback_data' => "edit_agency_discount_" . $agency['user_id']],
            ['text' => "❌", 'callback_data' => "remove_agency_" . $agency['user_id']]
        ];
    }
    
    // اگر هیچ ردیف‌ای به اینلاین کیبورد اضافه نشده، پیام مناسب نمایش می‌دهیم
    if (count($inline_keyboard) <= 1) {
        sendmessage($from_id, $textbotlang['Admin']['agency']['no_agencies'], $agencyManageKeyboard, 'HTML');
        exit();
    }
    
    $agency_keyboard = json_encode(['inline_keyboard' => $inline_keyboard]);
    sendmessage($from_id, $message, $agency_keyboard, 'HTML');
} elseif ($text == $textbotlang['Admin']['agency']['list_button'] && $text != $textbotlang['Admin']['keyboardadmin']['affiliate_settings']) {
    // نمایش لیست نمایندگان
    $agencies = select("agency", "*", "status", "approved", "fetchAll");
    
    if (!$agencies || count($agencies) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['agency']['no_agencies'], $agencyManageKeyboard, 'HTML');
        exit();
    }
    
    $message = $textbotlang['Admin']['agency']['list_title'] . "\n\n";
    
    foreach ($agencies as $agency) {
        $user_info = select("user", "*", "id", $agency['user_id'], "select");
        $name = $user_info ? $user_info['name'] : "کاربر ناشناس";
        
        $message .= sprintf(
            $textbotlang['Admin']['agency']['list_item'],
            $agency['username'],
            $agency['user_id'],
            number_format($agency['income']),
            $agency['discount_percent']
        );
        
        $keyboard_agency = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['Admin']['agency']['edit_discount'], 'callback_data' => "edit_agency_discount_" . $agency['user_id']],
                    ['text' => $textbotlang['Admin']['agency']['remove_btn'], 'callback_data' => "remove_agency_" . $agency['user_id']]
                ]
            ]
        ]);
        
        sendmessage($from_id, $message, $keyboard_agency, 'HTML');
        $message = ""; // پاک کردن پیام برای نماینده بعدی
    }
} elseif (preg_match('/approve_agency_(\d+)/', $datain, $matches)) {
    $user_id = $matches[1];
    
    // ذخیره اطلاعات کاربر برای مرحله بعدی
    savedata("clear", "agency_user_id", $user_id);
    
    // درخواست درصد تخفیف
    sendmessage($from_id, $textbotlang['Admin']['agency']['set_discount'], $backadmin, 'HTML');
    update("user", "step", "set_agency_discount", "id", $from_id);
} elseif ($user['step'] == "set_agency_discount") {
    if (!is_numeric($text) || $text < 0 || $text > 100) {
        sendmessage($from_id, "لطفا یک عدد بین 0 تا 100 وارد کنید.", $backadmin, 'HTML');
        exit();
    }
    
    $discount_percent = intval($text);
    $user_id = json_decode($user['Processing_value'], true)['agency_user_id'];
    
    // آپدیت وضعیت نمایندگی
    update("agency", "status", "approved", "user_id", $user_id);
    update("agency", "discount_percent", $discount_percent, "user_id", $user_id);
    
    // اطلاع‌رسانی به کاربر
    sendmessage($user_id, sprintf($textbotlang['users']['agency']['approved'], $discount_percent), null, 'HTML');
    
    // تایید به ادمین
    sendmessage($from_id, sprintf($textbotlang['Admin']['agency']['approved'], $discount_percent), $agencyManageKeyboard, 'HTML');
    update("user", "step", "none", "id", $from_id);
} elseif (preg_match('/reject_agency_(\d+)/', $datain, $matches)) {
    $user_id = $matches[1];
    
    // آپدیت وضعیت نمایندگی
    update("agency", "status", "rejected", "user_id", $user_id);
    
    // اطلاع‌رسانی به کاربر
    sendmessage($user_id, $textbotlang['users']['agency']['rejected'], null, 'HTML');
    
    // تایید به ادمین
    sendmessage($from_id, $textbotlang['Admin']['agency']['rejected'], $agencyManageKeyboard, 'HTML');
} elseif (preg_match('/edit_agency_discount_(\d+)/', $datain, $matches)) {
    $user_id = $matches[1];
    $agency = select("agency", "*", "user_id", $user_id, "select");
    
    if (!$agency) {
        Editmessagetext($from_id, $message_id, "❌ اطلاعات نماینده یافت نشد.", null);
        return;
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && $agency['username'] ? $agency['username'] : "کاربر با شناسه " . $user_id;
    
    // ذخیره اطلاعات کاربر برای مرحله بعدی
    savedata("clear", "agency_edit_user_id", $user_id);
    
    // نمایش فرم ویرایش درصد تخفیف
    $discount = isset($agency['discount_percent']) ? $agency['discount_percent'] : "0";
    $message = sprintf($textbotlang['Admin']['agency']['change_discount'], $username) . "\n";
    $message .= "درصد تخفیف فعلی: " . $discount . "%\n";
    $message .= "لطفا درصد تخفیف جدید را وارد کنید (عدد بین 0 تا 100):";
    
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "10%", 'callback_data' => "set_discount_" . $user_id . "_10"],
                ['text' => "20%", 'callback_data' => "set_discount_" . $user_id . "_20"],
                ['text' => "30%", 'callback_data' => "set_discount_" . $user_id . "_30"],
                ['text' => "40%", 'callback_data' => "set_discount_" . $user_id . "_40"],
                ['text' => "50%", 'callback_data' => "set_discount_" . $user_id . "_50"]
            ],
            [
                ['text' => "🔙 بازگشت", 'callback_data' => "agency_info_" . $user_id]
            ]
        ]
    ]);
    
    Editmessagetext($from_id, $message_id, $message, $keyboard);
    update("user", "step", "edit_agency_discount", "id", $from_id);

} elseif (preg_match('/set_discount_(\d+)_(\d+)/', $datain, $matches)) {
    // تنظیم سریع درصد تخفیف با کلیک روی دکمه
    $user_id = $matches[1];
    $discount_percent = intval($matches[2]);
    $agency = select("agency", "*", "user_id", $user_id, "select");
    
    if (!$agency) {
        Editmessagetext($from_id, $message_id, "❌ اطلاعات نماینده یافت نشد.", null);
        return;
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && $agency['username'] ? $agency['username'] : "کاربر با شناسه " . $user_id;
    
    // آپدیت درصد تخفیف
    update("agency", "discount_percent", $discount_percent, "user_id", $user_id);
    
    // اطلاع‌رسانی به کاربر
    sendmessage($user_id, sprintf($textbotlang['users']['agency']['approved'], $discount_percent), null, 'HTML');
    
    // تایید به ادمین و بازگشت به صفحه اطلاعات نماینده
    $success_message = sprintf($textbotlang['Admin']['agency']['discount_changed'], $username, $discount_percent);
    
    // ایجاد یک message_id جدید برای تایید تغییرات
    $confirm_keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "🔙 بازگشت به اطلاعات نماینده", 'callback_data' => "agency_info_" . $user_id]
            ]
        ]
    ]);
    
    Editmessagetext($from_id, $message_id, $success_message, $confirm_keyboard);
    update("user", "step", "none", "id", $from_id);

} elseif (preg_match('/remove_agency_(\d+)/', $datain, $matches)) {
    $user_id = $matches[1];
    $agency = select("agency", "*", "user_id", $user_id, "select");
    
    if (!$agency) {
        Editmessagetext($from_id, $message_id, "❌ اطلاعات نماینده یافت نشد.", null);
        return;
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && $agency['username'] ? $agency['username'] : "کاربر با شناسه " . $user_id;
    
    // تایید حذف نماینده
    $keyboard_confirm = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✅ بله، حذف شود", 'callback_data' => "confirm_remove_agency_" . $user_id],
                ['text' => "❌ خیر", 'callback_data' => "agency_info_" . $user_id]
            ]
        ]
    ]);
    
    Editmessagetext($from_id, $message_id, sprintf($textbotlang['Admin']['agency']['remove_confirm'], $username), $keyboard_confirm);
} elseif (preg_match('/confirm_remove_agency_(\d+)/', $datain, $matches)) {
    $agency_user_id = $matches[1];
    
    // بررسی وجود نماینده
    $agency = select("agency", "*", "user_id", $agency_user_id, "select");
    if (!$agency) {
        $message = "❌ نماینده مورد نظر یافت نشد!";
        Editmessagetext($from_id, $message_id, $message, null);
        return;
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && !empty($agency['username']) ? $agency['username'] : 'بدون نام';
    
    // ساخت پیام تأیید حذف
    $message = sprintf("⚠️ آیا از حذف نماینده %s اطمینان دارید؟", $username);
    
    // دکمه‌های تأیید یا لغو
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✅ بله، حذف شود", 'callback_data' => "remove_agency_" . $agency_user_id],
                ['text' => "❌ خیر، لغو شود", 'callback_data' => "agency_list"]
            ]
        ]
    ]);
    
    // ارسال پیام تأیید
    Editmessagetext($from_id, $message_id, $message, $keyboard);
} elseif (preg_match('/remove_agency_(\d+)/', $datain, $matches)) {
    $agency_user_id = $matches[1];
    
    // بررسی وجود نماینده
    $agency = select("agency", "*", "user_id", $agency_user_id, "select");
    if (!$agency) {
        $message = "❌ نماینده مورد نظر یافت نشد!";
        Editmessagetext($from_id, $message_id, $message, null);
        return;
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && !empty($agency['username']) ? $agency['username'] : 'بدون نام';
    
    try {
        // حذف نماینده با استفاده از پرس و جوی مستقیم SQL
        $sql = "DELETE FROM agency WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $agency_user_id, PDO::PARAM_STR);
        $result = $stmt->execute();
        
        if (!$result) {
            throw new Exception("خطا در حذف نماینده");
        }
        
        // ساخت پیام موفقیت
        $message = sprintf("✅ نماینده %s با موفقیت حذف شد.", $username);
        
        // دکمه بازگشت به لیست
        $keyboard = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => "🔙 بازگشت به لیست نمایندگان", 'callback_data' => "back_to_agency_list"]
                ]
            ]
        ]);
        
        // ارسال پیام موفقیت
        Editmessagetext($from_id, $message_id, $message, $keyboard);
    } catch (Exception $e) {
        $message = "❌ خطایی در حذف نماینده رخ داد: " . $e->getMessage();
        Editmessagetext($from_id, $message_id, $message, null);
        return;
    }
}

if (isset($datain) && $datain == "cancel_remove_agency") {
    Editmessagetext($from_id, $message_id, "❌ عملیات حذف نماینده لغو شد.", null);
}

if (preg_match('/agency_info_(\d+)/', $datain, $matches)) {
    // نمایش جزئیات آمار نماینده
    $user_id = $matches[1];
    $agency = select("agency", "*", "user_id", $user_id, "select");
    
    if (!$agency) {
        Editmessagetext($from_id, $message_id, "❌ اطلاعات نماینده یافت نشد.", null);
        return;
    }
    
    $user_info = select("user", "*", "id", $user_id, "select");
    
    // تعیین نام نماینده
    $name = "کاربر";
    if ($user_info && isset($user_info['name']) && $user_info['name']) {
        $name = $user_info['name'];
    } elseif (isset($agency['username']) && $agency['username']) {
        $name = $agency['username'];
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && $agency['username'] ? $agency['username'] : "تنظیم نشده";
    
    // تعیین درآمد و درصد تخفیف
    $income = isset($agency['income']) ? number_format($agency['income']) : "0";
    $discount = isset($agency['discount_percent']) ? $agency['discount_percent'] : "0";
    
    // تبدیل تایمستمپ به تاریخ شمسی
    $date = "نامشخص";
    if (isset($agency['created_at']) && $agency['created_at']) {
        if (function_exists('jdate')) {
            $date = jdate('Y/m/d H:i', strtotime($agency['created_at']));
        } else {
            $date = date('Y/m/d H:i', strtotime($agency['created_at']));
        }
    }
    
    // اطلاعات جدید مورد نیاز
    
    // 1. تعداد خرید (از جدول invoice)
    $stmt = $pdo->prepare("SELECT COUNT(*) as purchase_count FROM invoice WHERE id_user = ? AND Status = 'active'");
    $stmt->execute([$user_id]);
    $purchase_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $purchase_count = $purchase_info ? $purchase_info['purchase_count'] : 0;
    
    // 2. بیشترین محصول خریداری شده
    $stmt = $pdo->prepare("
        SELECT name_product, COUNT(*) as count 
        FROM invoice 
        WHERE id_user = ? AND Status = 'active' 
        GROUP BY name_product 
        ORDER BY count DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $top_product_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $top_product = ($top_product_info && $top_product_info['count'] > 0) ? 
        $top_product_info['name_product'] . " (" . $top_product_info['count'] . " بار)" : 
        "خریدی ثبت نشده";
    
    // 3. آخرین محصول خریداری شده
    $stmt = $pdo->prepare("
        SELECT name_product, time_sell 
        FROM invoice 
        WHERE id_user = ? AND Status = 'active' 
        ORDER BY time_sell DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $last_product_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $last_product = "خریدی ثبت نشده";
    $last_purchase_date = "";
    
    if ($last_product_info) {
        $last_product = $last_product_info['name_product'];
        // تبدیل زمان به فرمت مناسب
        if (function_exists('jdate')) {
            $last_purchase_date = jdate('Y/m/d H:i', $last_product_info['time_sell']);
        } else {
            $last_purchase_date = date('Y/m/d H:i', $last_product_info['time_sell']);
        }
    }
    
    // 4. مجموع پرداختی با تخفیف‌ها
    $stmt = $pdo->prepare("
        SELECT SUM(price_product) as total_payment 
        FROM invoice 
        WHERE id_user = ? AND Status = 'active'
    ");
    $stmt->execute([$user_id]);
    $payment_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_payment = $payment_info ? number_format($payment_info['total_payment']) : "0";
    
    // 5. موجودی کیف پول
    $balance = isset($user_info['Balance']) ? number_format($user_info['Balance']) : "0";
    
    $message = "📊 اطلاعات جامع نماینده:\n\n";
    $message .= "👤 نام: " . $name . "\n";
    $message .= "🆔 یوزرنیم: " . $username . "\n";
    $message .= "🔢 آیدی عددی: " . $user_id . "\n";
    $message .= "📅 تاریخ نمایندگی: " . $date . "\n\n";
    
    $message .= "💹 درصد تخفیف: " . $discount . "%\n";
    $message .= "💰 مجموع درآمد نماینده: " . $income . " تومان\n\n";
    
    $message .= "📈 آمار خرید:\n";
    $message .= "🛒 تعداد کل خرید: " . $purchase_count . "\n";
    $message .= "🥇 پرفروش‌ترین محصول: " . $top_product . "\n";
    
    if (!empty($last_purchase_date)) {
        $message .= "🆕 آخرین خرید: " . $last_product . " (" . $last_purchase_date . ")\n";
    } else {
        $message .= "🆕 آخرین خرید: " . $last_product . "\n";
    }
    
    $message .= "💵 مجموع پرداختی: " . $total_payment . " تومان\n";
    $message .= "👝 موجودی کیف پول: " . $balance . " تومان";
    
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => "✏️ ویرایش درصد تخفیف", 'callback_data' => "edit_agency_discount_" . $user_id],
                ['text' => "❌ حذف نماینده", 'callback_data' => "confirm_remove_agency_" . $user_id]
            ],
            [
                ['text' => "🔙 بازگشت به لیست", 'callback_data' => "back_to_agency_list"]
            ]
        ]
    ]);
    
    Editmessagetext($from_id, $message_id, $message, $keyboard);
} elseif ($datain == "back_to_agency_list") {
    // برگشت به لیست نمایندگان
    // نمایش لیست نمایندگان به صورت جدول
    $agencies = select("agency", "*", "status", "approved", "fetchAll");
    
    if (!$agencies || count($agencies) == 0) {
        Editmessagetext($from_id, $message_id, $textbotlang['Admin']['agency']['no_agencies'], null);
        return;
    }
    
    $message = "🔍 لیست نمایندگان:\nجهت مشاهده آمار فروش نمایندگان روی اسم نماینده کلیک کنید، همچنین میتوانید برای نماینده درصد تعیین کنید یا آن را از لیست نمایندگان خارج کنید\n\n";
    
    // ساخت اینلاین کیبورد
    $inline_keyboard = [
        // هدر جدول به عنوان اولین ردیف
        [
            ['text' => "آیدی عددی", 'callback_data' => "null"],
            ['text' => "اسم نماینده", 'callback_data' => "null"],
            ['text' => "تاریخ نمایندگی", 'callback_data' => "null"],
            ['text' => "درصد تخفیف", 'callback_data' => "null"],
            ['text' => "حذف", 'callback_data' => "null"]
        ]
    ];
    
    // اضافه کردن ردیف‌های جدول به اینلاین کیبورد
    foreach ($agencies as $agency) {
        $user_info = select("user", "*", "id", $agency['user_id'], "select");
        $name = $user_info ? $user_info['name'] : $agency['username'];
        
        // تبدیل تایمستمپ به تاریخ شمسی
        if (function_exists('jdate')) {
            $date = jdate('Y/m/d H:i', strtotime($agency['created_at']));
        } else {
            $date = date('Y/m/d H:i', strtotime($agency['created_at']));
        }
        
        // هر نماینده یک ردیف در اینلاین کیبورد است
        $inline_keyboard[] = [
            ['text' => $agency['user_id'], 'callback_data' => "agency_info_" . $agency['user_id']],
            ['text' => $name, 'callback_data' => "agency_info_" . $agency['user_id']],
            ['text' => $date, 'callback_data' => "agency_date_" . $agency['user_id']],
            ['text' => "⚙️", 'callback_data' => "edit_agency_discount_" . $agency['user_id']],
            ['text' => "❌", 'callback_data' => "remove_agency_" . $agency['user_id']]
        ];
    }
    
    $agency_keyboard = json_encode(['inline_keyboard' => $inline_keyboard]);
    Editmessagetext($from_id, $message_id, $message, $agency_keyboard);
} elseif ($text == $textbotlang['Admin']['agency']['pending_requests']) {
    // نمایش درخواست‌های در انتظار
    $pendingRequests = select("agency", "*", "status", "pending", "fetchAll");
    
    if (!$pendingRequests || count($pendingRequests) == 0) {
        sendmessage($from_id, $textbotlang['Admin']['agency']['no_pending'], $agencyManageKeyboard, 'HTML');
        exit();
    }
    
    $message = $textbotlang['Admin']['agency']['pending_requests'] . ":\n\n";
    
    foreach ($pendingRequests as $request) {
        $user_info = select("user", "*", "id", $request['user_id'], "select");
        $name = $user_info ? $user_info['name'] : "کاربر ناشناس";
        
        $message .= "👤 نام: " . $name . "\n";
        $message .= "🆔 شناسه: " . $request['user_id'] . "\n";
        $message .= "👤 نام کاربری: " . $request['username'] . "\n";
        $message .= "⏰ تاریخ درخواست: " . $request['created_at'] . "\n\n";
        
        $keyboard_agency = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $textbotlang['Admin']['agency']['approve_btn'], 'callback_data' => "approve_agency_" . $request['user_id']],
                    ['text' => $textbotlang['Admin']['agency']['reject_btn'], 'callback_data' => "reject_agency_" . $request['user_id']]
                ]
            ]
        ]);
        
        sendmessage($from_id, $message, $keyboard_agency, 'HTML');
        $message = ""; // پاک کردن پیام برای درخواست بعدی
    }
}

if ($user['step'] == "edit_agency_discount") {
    if (!is_numeric($text) || $text < 0 || $text > 100) {
        sendmessage($from_id, "لطفا یک عدد بین 0 تا 100 وارد کنید.", $backadmin, 'HTML');
        exit();
    }
    
    $discount_percent = intval($text);
    $processing_value = json_decode($user['Processing_value'], true);
    
    if (!isset($processing_value['agency_edit_user_id'])) {
        sendmessage($from_id, "❌ خطا در پردازش اطلاعات. لطفا دوباره تلاش کنید.", $backadmin, 'HTML');
        update("user", "step", "none", "id", $from_id);
        exit();
    }
    
    $user_id = $processing_value['agency_edit_user_id'];
    $agency = select("agency", "*", "user_id", $user_id, "select");
    
    if (!$agency) {
        sendmessage($from_id, "❌ اطلاعات نماینده یافت نشد.", $backadmin, 'HTML');
        update("user", "step", "none", "id", $from_id);
        exit();
    }
    
    // تعیین نام کاربری
    $username = isset($agency['username']) && $agency['username'] ? $agency['username'] : "کاربر با شناسه " . $user_id;
    
    // آپدیت درصد تخفیف
    update("agency", "discount_percent", $discount_percent, "user_id", $user_id);
    
    // اطلاع‌رسانی به کاربر
    sendmessage($user_id, sprintf($textbotlang['users']['agency']['approved'], $discount_percent), null, 'HTML');
    
    // تایید به ادمین
    sendmessage($from_id, sprintf($textbotlang['Admin']['agency']['discount_changed'], $username, $discount_percent), $agencyManageKeyboard, 'HTML');
    update("user", "step", "none", "id", $from_id);
}

if ($text == $textbotlang['Admin']['keyboardadmin']['settings']) {
    sendmessage($from_id, $textbotlang['users']['selectoption'], $setting_panel, 'HTML');
}

// اضافه کردن کد جدید برای لیست کاربران مشمول شارژ دوبرابر
elseif ($text == "📋 لیست کاربران مشمول شارژ دوبرابر") {
    $setting = select("setting", "*");
    $min_purchase = intval($setting['double_charge_min_purchase']);
    
    // شرط بررسی فعال بودن ویژگی
    if ($setting['double_charge_status'] != 'on') {
        sendmessage($from_id, "❌ ویژگی شارژ دوبرابر در حال حاضر غیرفعال است. ابتدا آن را فعال کنید.", $double_charge_keyboard, 'HTML');
        return;
    }
    
    try {
        // بررسی کاربرانی که مشمول شارژ دوبرابر می‌شوند
        $eligible_users = [];
        $total_eligible = 0;
        
        // کوئری برای یافتن تمام کاربران
        $users_query = $pdo->query("SELECT * FROM user WHERE User_Status = 'Active'");
        $users = $users_query->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as $user) {
            $user_id = $user['id'];
            
            // بررسی اینکه کاربر نماینده نباشد
            $agency_user = false;
            $check_agency_table = $pdo->query("SHOW TABLES LIKE 'agency'");
            if ($check_agency_table && $check_agency_table->rowCount() > 0) {
                $stmt_agency = $pdo->prepare("SELECT * FROM agency WHERE user_id = :user_id AND status = 'approved'");
                $stmt_agency->bindParam(':user_id', $user_id);
                $stmt_agency->execute();
                $agency_user = $stmt_agency->rowCount() > 0;
            }
            
            if (!$agency_user) {
                // بررسی تعداد خرید کاربر
                $meets_purchase_requirement = ($min_purchase == 0); // اگر min_purchase صفر باشد، نیازی به بررسی تعداد خرید نیست
                
                if (!$meets_purchase_requirement) {
                    // بررسی اینکه کاربر به حداقل تعداد خرید رسیده باشد
                    $stmt = $pdo->prepare("SELECT COUNT(*) as purchase_count FROM invoice WHERE id_user = :user_id AND Status = 'active'");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->execute();
                    $purchase_count = $stmt->fetch(PDO::FETCH_ASSOC)['purchase_count'];
                    
                    $meets_purchase_requirement = ($purchase_count >= $min_purchase);
                }
                
                if ($meets_purchase_requirement) {
                    // بررسی اینکه کاربر قبلاً از این ویژگی استفاده نکرده باشد
                    $check_table = $pdo->query("SHOW TABLES LIKE 'double_charge_users'");
                    if ($check_table && $check_table->rowCount() > 0) {
                        $stmt = $pdo->prepare("SELECT * FROM double_charge_users WHERE user_id = :user_id");
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() == 0) {
                            // اضافه کردن کاربر به لیست
                            $user_info = [
                                'id' => $user_id,
                                'username' => !empty($user['username']) ? $user['username'] : 'بدون نام کاربری'
                            ];
                            $eligible_users[] = $user_info;
                            $total_eligible++;
                        }
                    }
                }
            }
        }
        
        // ارسال نتایج به ادمین
        if ($total_eligible > 0) {
            $list_text = "📋 لیست کاربران مشمول شارژ دوبرابر:\n\n";
            $list_text .= "🔹 تعداد کل: $total_eligible کاربر\n\n";
            
            $counter = 1;
            foreach ($eligible_users as $user) {
                $list_text .= "$counter. شناسه: {$user['id']} | نام: {$user['username']}\n";
                $counter++;
                
                // ارسال پیام به صورت بخش‌بندی شده برای جلوگیری از خطای پیام طولانی
                if ($counter % 20 == 0 || $counter > $total_eligible) {
                    sendmessage($from_id, $list_text, null, 'HTML');
                    $list_text = "";
                }
            }
            
            if (!empty($list_text)) {
                sendmessage($from_id, $list_text, null, 'HTML');
            }
            
            // منوی اطلاع‌رسانی به کاربران
            $notify_keyboard = json_encode([
                'keyboard' => [
                    [['text' => "📢 اطلاع‌رسانی به کاربران مشمول"]],
                    [['text' => $textbotlang['Admin']['Back-Adminment']]]
                ],
                'resize_keyboard' => true
            ]);
            
            sendmessage($from_id, "آیا می‌خواهید به کاربران مشمول اطلاع‌رسانی کنید؟", $notify_keyboard, 'HTML');
            step('notify_double_charge_users', $from_id);
            
            // ذخیره لیست کاربران در متغیر سشن
            $_SESSION['eligible_users'] = $eligible_users;
        } else {
            sendmessage($from_id, "❌ هیچ کاربری واجد شرایط شارژ دوبرابر نیست.", $double_charge_keyboard, 'HTML');
        }
    } catch (PDOException $e) {
        sendmessage($from_id, "خطا در بررسی کاربران: " . $e->getMessage(), $double_charge_keyboard, 'HTML');
    }
}

// رسیدگی به مرحله اطلاع‌رسانی
elseif ($user['step'] == "notify_double_charge_users") {
    if ($text == "📢 اطلاع‌رسانی به کاربران مشمول") {
        if (isset($_SESSION['eligible_users']) && count($_SESSION['eligible_users']) > 0) {
            $count = 0;
            $success = 0;
            
            // دریافت مهلت زمانی از تنظیمات
            $setting = select("setting", "*");
            $expiry_hours = isset($setting['double_charge_expiry_hours']) ? $setting['double_charge_expiry_hours'] : 72;
            
            foreach ($_SESSION['eligible_users'] as $user_info) {
                $count++;
                $user_id = $user_info['id'];
                $username = $user_info['username'];
                
                // پیام اطلاع‌رسانی با اضافه کردن مهلت زمانی
                $notification_message = "🎉 خبر خوب {$username} عزیز!

💰 شما واجد شرایط استفاده از طرح ویژه شارژ دوبرابر هستید!

با استفاده از این فرصت استثنایی، می‌توانید یک‌بار حساب خود را با هر مبلغی شارژ کنید و دو برابر آن را دریافت نمایید!

مثال: اگر 200 هزار تومان شارژ کنید، 400 هزار تومان به حساب شما افزوده می‌شود!

⏱ توجه: مهلت استفاده از این طرح فقط {$expiry_hours} ساعت است!

🔴 نکته مهم: این فرصت فقط یک‌بار قابل استفاده است، پس حتماً از آن به بهترین شکل بهره‌مند شوید.

برای شارژ حساب، کافیست به منوی اصلی بات مراجعه کرده و گزینه «💰 شارژ حساب» را انتخاب نمایید.

🚀 موفق باشید!";
                
                // ارسال پیام به کاربر
                $result = telegram('sendMessage', [
                    'chat_id' => $user_id,
                    'text' => $notification_message,
                    'parse_mode' => 'HTML'
                ]);
                
                if (isset($result['ok']) && $result['ok']) {
                    $success++;
                    
                    // ثبت زمان ارسال اطلاعیه برای کاربر
                    try {
                        // بررسی وجود جدول
                        $check_table = $pdo->query("SHOW TABLES LIKE 'double_charge_notifications'");
                        
                        if ($check_table && $check_table->rowCount() == 0) {
                            // جدول وجود ندارد، آن را ایجاد می‌کنیم
                            $pdo->exec("CREATE TABLE IF NOT EXISTS double_charge_notifications (
                                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                                user_id varchar(500) NOT NULL,
                                notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                expiry_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                expiry_hours INT(11) NOT NULL
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
                        }
                        
                        // محاسبه زمان انقضا
                        $notified_at = date('Y-m-d H:i:s');
                        $expiry_at = date('Y-m-d H:i:s', strtotime("+{$expiry_hours} hours"));
                        
                        // ثبت رکورد جدید یا بروزرسانی رکورد موجود
                        $stmt = $pdo->prepare("INSERT INTO double_charge_notifications (user_id, notified_at, expiry_at, expiry_hours) 
                                           VALUES (:user_id, :notified_at, :expiry_at, :expiry_hours)
                                           ON DUPLICATE KEY UPDATE 
                                           notified_at = :notified_at,
                                           expiry_at = :expiry_at,
                                           expiry_hours = :expiry_hours");
                        
                        $stmt->bindParam(':user_id', $user_id);
                        $stmt->bindParam(':notified_at', $notified_at);
                        $stmt->bindParam(':expiry_at', $expiry_at);
                        $stmt->bindParam(':expiry_hours', $expiry_hours);
                        $stmt->execute();
                        
                    } catch (PDOException $e) {
                        error_log("خطا در ثبت اطلاع‌رسانی شارژ دوبرابر: " . $e->getMessage());
                    }
                }
                
                // کمی صبر برای جلوگیری از محدودیت تلگرام
                sleep(1);
            }
            
            sendmessage($from_id, "✅ اطلاع‌رسانی انجام شد!\n\n📊 آمار ارسال:\n▪️ تعداد کل: $count\n▪️ ارسال موفق: $success\n▪️ ارسال ناموفق: " . ($count - $success) . "\n\n⏱ مهلت استفاده: {$expiry_hours} ساعت", $double_charge_keyboard, 'HTML');
            step('none', $from_id);
            
            // پاک کردن سشن
            unset($_SESSION['eligible_users']);
        } else {
            sendmessage($from_id, "❌ لیست کاربران مشمول در دسترس نیست. لطفاً دوباره لیست را بررسی کنید.", $double_charge_keyboard, 'HTML');
            step('none', $from_id);
        }
    } else {
        sendmessage($from_id, "به منوی مدیریت بازگشتید.", $keyboard, 'HTML');
        step('none', $from_id);
    }
}
// اضافه کردن بخش تنظیم مهلت استفاده
elseif ($text == "⏱ تنظیم مهلت استفاده") {
    $setting = select("setting", "*");
    $expiry_hours = isset($setting['double_charge_expiry_hours']) ? $setting['double_charge_expiry_hours'] : 72;
    
    sendmessage($from_id, "⏱ لطفاً مهلت استفاده از طرح شارژ دوبرابر را بر حسب ساعت وارد کنید.\n\n👈 مقدار فعلی: $expiry_hours ساعت\n\n🔔 این مدت از زمان ارسال اطلاع‌رسانی به کاربر محاسبه می‌شود.", $backuser, 'HTML');
    step('set_double_charge_expiry', $from_id);
}

elseif ($user['step'] == "set_double_charge_expiry") {
    // بررسی ورودی عددی
    if (!is_numeric($text) || intval($text) <= 0) {
        sendmessage($from_id, "❌ لطفاً یک عدد صحیح بزرگتر از صفر وارد کنید.", null, 'HTML');
        return;
    }
    
    $expiry_hours = intval($text);
    
    // بررسی وجود ستون در دیتابیس
    try {
        $check_column = $pdo->query("SHOW COLUMNS FROM setting LIKE 'double_charge_expiry_hours'");
        
        if ($check_column && $check_column->rowCount() == 0) {
            // ستون وجود ندارد، آن را اضافه می‌کنیم
            $pdo->exec("ALTER TABLE setting ADD COLUMN double_charge_expiry_hours INT(11) DEFAULT 72");
        }
        
        // بروزرسانی مقدار
        update("setting", "double_charge_expiry_hours", $expiry_hours);
        
        sendmessage($from_id, "✅ مهلت استفاده از طرح شارژ دوبرابر با موفقیت به $expiry_hours ساعت تغییر یافت.", $double_charge_keyboard, 'HTML');
        step('none', $from_id);
    } catch (PDOException $e) {
        sendmessage($from_id, "❌ خطا در بروزرسانی تنظیمات: " . $e->getMessage(), $double_charge_keyboard, 'HTML');
        step('none', $from_id);
    }
}

// اضافه کردن بخش یادآوری به کاربران نزدیک به پایان مهلت
elseif ($text == "⏰ یادآوری به کاربران نزدیک به پایان مهلت") {
    try {
        // بررسی وجود جدول double_charge_notifications
        $check_table = $pdo->query("SHOW TABLES LIKE 'double_charge_notifications'");
        
        if ($check_table && $check_table->rowCount() == 0) {
            // ایجاد جدول اگر وجود نداشته باشد
            $pdo->exec("CREATE TABLE IF NOT EXISTS double_charge_notifications (
                id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id varchar(500) NOT NULL,
                notified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expiry_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expiry_hours INT(11) NOT NULL,
                UNIQUE KEY unique_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_bin");
            
            sendmessage($from_id, "❌ هیچ کاربری برای یادآوری وجود ندارد. ابتدا باید به کاربران اطلاع‌رسانی کنید.", $double_charge_keyboard, 'HTML');
            return;
        }
        
        $reminder_options = json_encode([
            'keyboard' => [
                [['text' => "📤 ارسال دستی یادآوری"]],
                [['text' => "⚙️ ساخت فایل کرون جاب برای ارسال خودکار"]],
                [['text' => $textbotlang['Admin']['Back-Adminment']]]
            ],
            'resize_keyboard' => true
        ]);
        
        sendmessage($from_id, "📣 سیستم یادآوری شارژ دوبرابر

لطفاً نحوه ارسال یادآوری به کاربران را انتخاب کنید:

📤 ارسال دستی: بررسی کاربران نزدیک به پایان مهلت و ارسال پیام یادآوری به آنها در همین لحظه

⚙️ ساخت فایل کرون جاب: ایجاد یک فایل PHP که می‌توانید آن را در سرور خود به عنوان کرون جاب تنظیم کنید تا به صورت خودکار هر روز اجرا شود و به کاربرانی که مهلت آنها کمتر از 12 ساعت مانده یادآوری ارسال کند.", $reminder_options, 'HTML');
        
        step('double_charge_reminder_option', $from_id);
        return;
    } catch (PDOException $e) {
        sendmessage($from_id, "❌ خطا در بررسی جدول: " . $e->getMessage(), $double_charge_keyboard, 'HTML');
    }
}

elseif ($user['step'] == "double_charge_reminder_option") {
    if ($text == "📤 ارسال دستی یادآوری") {
        try {
            $current_time = time();
            $reminder_limit = 12 * 3600; // 12 ساعت به ثانیه
            $count = 0;
            $success = 0;
            
            // پیدا کردن کاربرانی که مهلت آنها کمتر از 12 ساعت باقی مانده
            $stmt = $pdo->prepare("
                SELECT n.*, u.username 
                FROM double_charge_notifications n
                JOIN user u ON n.user_id = u.id
                WHERE UNIX_TIMESTAMP(n.expiry_at) - ? <= ?
                AND UNIX_TIMESTAMP(n.expiry_at) > ?
            ");
            
            $stmt->execute([$current_time, $reminder_limit, $current_time]);
            $users_to_remind = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users_to_remind) == 0) {
                sendmessage($from_id, "❌ هیچ کاربری نزدیک به پایان مهلت استفاده از شارژ دوبرابر نیست.", $double_charge_keyboard, 'HTML');
                step('none', $from_id);
                return;
            }
            
            // ارسال پیام یادآوری به کاربران
            foreach ($users_to_remind as $user) {
                $count++;
                $user_id = $user['user_id'];
                $username = !empty($user['username']) ? $user['username'] : 'کاربر';
                $expiry_at = strtotime($user['expiry_at']);
                $remaining_hours = round(($expiry_at - $current_time) / 3600, 1);
                
                // بررسی اینکه کاربر استفاده نکرده باشد
                $stmt = $pdo->prepare("SELECT * FROM double_charge_users WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                if ($stmt->rowCount() == 0) { // کاربر هنوز استفاده نکرده
                    // پیام یادآوری
                    $reminder_message = "⚠️ {$username} عزیز هنوز از فرصت شارژ دوبرابر استفاده نکرده‌اید!

⏰ تنها {$remaining_hours} ساعت از مهلت استفاده شما باقی مانده است!

💰 با استفاده از این فرصت استثنایی، می‌توانید یک‌بار حساب خود را با هر مبلغی شارژ کنید و دو برابر آن را دریافت نمایید!

🔴 توجه: این فرصت فقط یک‌بار قابل استفاده است و پس از پایان مهلت، تکرار نخواهد شد.

برای شارژ حساب، کافیست به منوی اصلی بات مراجعه کرده و گزینه «💰 شارژ حساب» را انتخاب نمایید.

🚀 عجله کنید!";
                    
                    // ارسال پیام به کاربر
                    $result = telegram('sendMessage', [
                        'chat_id' => $user_id,
                        'text' => $reminder_message,
                        'parse_mode' => 'HTML'
                    ]);
                    
                    if (isset($result['ok']) && $result['ok']) {
                        $success++;
                    }
                    
                    // کمی صبر برای جلوگیری از محدودیت تلگرام
                    sleep(1);
                }
            }
            
            if ($success > 0) {
                sendmessage($from_id, "✅ یادآوری با موفقیت انجام شد!\n\n📊 آمار ارسال:\n▪️ تعداد کل کاربران نزدیک به پایان مهلت: $count\n▪️ یادآوری‌های ارسال شده: $success\n▪️ ارسال ناموفق: " . ($count - $success), $double_charge_keyboard, 'HTML');
            } else {
                sendmessage($from_id, "❌ هیچ یادآوری‌ای ارسال نشد. احتمالاً همه کاربران قبلاً از شارژ دوبرابر استفاده کرده‌اند.", $double_charge_keyboard, 'HTML');
            }
            
            step('none', $from_id);
            
        } catch (PDOException $e) {
            sendmessage($from_id, "❌ خطا در بررسی و ارسال یادآوری: " . $e->getMessage(), $double_charge_keyboard, 'HTML');
            step('none', $from_id);
        }
    } 
    elseif ($text == "⚙️ ساخت فایل کرون جاب برای ارسال خودکار") {
        try {
            // ایجاد فایل کرون جاب
            $cron_file_content = '<?php
// فایل کرون جاب برای ارسال خودکار یادآوری به کاربران نزدیک به پایان مهلت شارژ دوبرابر
// این فایل را باید به عنوان کرون جاب در سرور خود تنظیم کنید تا هر روز یکبار اجرا شود

require_once "config.php";
require_once "vendor/autoload.php";
require_once "functions.php";

try {
    $current_time = time();
    $reminder_limit = 12 * 3600; // 12 ساعت به ثانیه
    $count = 0;
    $success = 0;
    
    // لاگ فایل
    $log_file = "cron_double_charge_reminder.log";
    file_put_contents($log_file, "=== شروع اجرای کرون جاب یادآوری شارژ دوبرابر در " . date("Y-m-d H:i:s") . " ===\n", FILE_APPEND);
    
    // پیدا کردن کاربرانی که مهلت آنها کمتر از 12 ساعت باقی مانده
    $stmt = $pdo->prepare("
        SELECT n.*, u.username 
        FROM double_charge_notifications n
        JOIN user u ON n.user_id = u.id
        WHERE UNIX_TIMESTAMP(n.expiry_at) - ? <= ?
        AND UNIX_TIMESTAMP(n.expiry_at) > ?
    ");
    
    $stmt->execute([$current_time, $reminder_limit, $current_time]);
    $users_to_remind = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    file_put_contents($log_file, "تعداد کاربران نزدیک به پایان مهلت: " . count($users_to_remind) . "\n", FILE_APPEND);
    
    if (count($users_to_remind) == 0) {
        file_put_contents($log_file, "هیچ کاربری نزدیک به پایان مهلت نیست. پایان اجرا.\n", FILE_APPEND);
        exit;
    }
    
    // ارسال پیام یادآوری به کاربران
    foreach ($users_to_remind as $user) {
        $count++;
        $user_id = $user["user_id"];
        $username = !empty($user["username"]) ? $user["username"] : "کاربر";
        $expiry_at = strtotime($user["expiry_at"]);
        $remaining_hours = round(($expiry_at - $current_time) / 3600, 1);
        
        // بررسی اینکه کاربر استفاده نکرده باشد
        $stmt = $pdo->prepare("SELECT * FROM double_charge_users WHERE user_id = :user_id");
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) { // کاربر هنوز استفاده نکرده
            // پیام یادآوری
            $reminder_message = "⚠️ {$username} عزیز هنوز از فرصت شارژ دوبرابر استفاده نکرده‌اید!

⏰ تنها {$remaining_hours} ساعت از مهلت استفاده شما باقی مانده است!

💰 با استفاده از این فرصت استثنایی، می‌توانید یک‌بار حساب خود را با هر مبلغی شارژ کنید و دو برابر آن را دریافت نمایید!

🔴 توجه: این فرصت فقط یک‌بار قابل استفاده است و پس از پایان مهلت، تکرار نخواهد شد.

برای شارژ حساب، کافیست به منوی اصلی بات مراجعه کرده و گزینه «💰 شارژ حساب» را انتخاب نمایید.

🚀 عجله کنید!";
            
            // ارسال پیام به کاربر
            $result = telegram("sendMessage", [
                "chat_id" => $user_id,
                "text" => $reminder_message,
                "parse_mode" => "HTML"
            ]);
            
            if (isset($result["ok"]) && $result["ok"]) {
                $success++;
                file_put_contents($log_file, "✅ یادآوری با موفقیت به کاربر {$user_id} ({$username}) ارسال شد. مهلت باقیمانده: {$remaining_hours} ساعت\n", FILE_APPEND);
            } else {
                file_put_contents($log_file, "❌ خطا در ارسال یادآوری به کاربر {$user_id}: " . json_encode($result) . "\n", FILE_APPEND);
            }
            
            // کمی صبر برای جلوگیری از محدودیت تلگرام
            sleep(1);
        } else {
            file_put_contents($log_file, "⚠️ کاربر {$user_id} قبلاً از شارژ دوبرابر استفاده کرده است.\n", FILE_APPEND);
        }
    }
    
    file_put_contents($log_file, "=== پایان اجرای کرون جاب یادآوری شارژ دوبرابر ===\n", FILE_APPEND);
    file_put_contents($log_file, "📊 آمار: کل کاربران: {$count} | ارسال موفق: {$success} | ارسال ناموفق: " . ($count - $success) . "\n\n", FILE_APPEND);
    
} catch (Exception $e) {
    file_put_contents($log_file, "❌❌❌ خطای کرون جاب: " . $e->getMessage() . "\n", FILE_APPEND);
}
';

            // نوشتن محتوا در فایل
            $cron_file_path = 'cron_double_charge_reminder.php';
            file_put_contents($cron_file_path, $cron_file_content);
            
            $instructions = "✅ فایل کرون جاب با موفقیت ایجاد شد!

📋 **دستورالعمل‌های نصب**:

1. فایل `cron_double_charge_reminder.php` در مسیر اصلی بات ایجاد شده است.

2. برای تنظیم کرون جاب در سرور لینوکس، می‌توانید از دستور زیر استفاده کنید:
```
0 8 * * * cd /path/to/bot && php cron_double_charge_reminder.php
```
این دستور هر روز ساعت 8 صبح اجرا می‌شود. می‌توانید زمان را تغییر دهید.

3. برای اضافه کردن کرون جاب:
```
crontab -e
```
سپس دستور بالا را اضافه کنید و ذخیره نمایید.

4. نتایج اجرای کرون جاب در فایل `cron_double_charge_reminder.log` ذخیره می‌شود.

این کرون جاب به صورت خودکار به کاربرانی که مهلت استفاده از شارژ دوبرابر آنها کمتر از 12 ساعت باقی مانده است، پیام یادآوری ارسال می‌کند.";

            sendmessage($from_id, $instructions, $double_charge_keyboard, 'HTML');
            step('none', $from_id);
            
        } catch (Exception $e) {
            sendmessage($from_id, "❌ خطا در ایجاد فایل کرون جاب: " . $e->getMessage(), $double_charge_keyboard, 'HTML');
            step('none', $from_id);
        }
    }
    else {
        sendmessage($from_id, "به منوی مدیریت بازگشتید.", $double_charge_keyboard, 'HTML');
        step('none', $from_id);
    }
}
