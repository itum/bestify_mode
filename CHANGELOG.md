# Changelog

## V1.3.2 (1402/06/02)

### رفع باگ‌ها
- رفع مشکل ارسال پیام‌های تکراری به کاربران برای شارژ دوبرابر
- رفع مشکل «لیست کاربران مشمول در دسترس نیست» با استفاده از دیتابیس به جای سشن
- رفع خطای عدم دسترسی به فایل admin.php با استفاده از مسیر مطلق و بررسی وجود فایل
- رفع خطای Undefined array key username در index.php
- رفع خطای دسترسی به آرایه false در فایل panels.php
- بازیابی فایل marzneshin.php برای رفع خطای require_once

### بهبودها و تغییرات
- اضافه شدن فایل wgdashboard.php برای مدیریت بهتر پنل داشبورد
- بهینه‌سازی کدهای panels.php برای عملکرد بهتر
- به‌روزرسانی فایل‌های API پنل با تغییرات جدید

### فایل‌های تغییر یافته
- admin.php
- apipanel.php
- cron/index.php
- functions.php
- index.php
- panels.php
- اضافه شدن: wgdashboard.php
- حذف: functions.php.bak و modify_commands.json 