# 🚀 Bestify Mode

سیستم مدیریت پیشرفته فروش خدمات VPN با پشتیبانی از چندین پنل

<p align="center">
    <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version"/>
    <img src="https://img.shields.io/badge/Status-Private-red?style=flat-square" alt="Status"/>
    <img src="https://img.shields.io/badge/PHP-8.2+-green?style=flat-square" alt="PHP Version"/>
</p>


---

## 📑 فهرست مطالب

- [✨ معرفی](#-معرفی)
- [⚙️ امکانات](#️-امکانات)
- [🚀 نصب و راه‌اندازی](#-نصب-و-راه‌اندازی)
  - [پیش‌نیازها](#-پیش‌نیازها)
  - [نصب برنامه](#-نصب-برنامه)
  - [به‌روزرسانی](#-به‌روزرسانی)
  - [حذف کامل](#-حذف-کامل)
- [👨‍💻 Private Repository Access](#-private-repository-access)
- [🔄 مدیریت نسخه‌ها](#-مدیریت-نسخه‌ها)

---

## ✨ معرفی

**Bestify Mode** یک سیستم مدیریت پیشرفته برای فروش خدمات VPN است که با هدف ساده‌سازی فرایند‌های مدیریت کاربران، پرداخت‌ها، و ارائه خدمات طراحی شده است. این سیستم با پنل‌های مختلف مانند **Marzban** و **X-UI** سازگار است و یک رابط کاربری یکپارچه برای مدیریت تمام جنبه‌های کسب‌وکار VPN ارائه می‌دهد.

---

## ⚙️ امکانات

### 🔹 **ویژگی‌های اصلی**

- ✅ مدیریت یکپارچه کاربران
- ✅ سیستم پرداخت چندگانه
- ✅ پشتیبانی از پنل‌های متنوع
- ✅ ساخت خودکار تنظیمات
- ✅ مدیریت اشتراک‌ها و تمدید خودکار
- ✅ گزارش‌های تحلیلی و آماری
- ✅ رابط کاربری ادمین پیشرفته
- ✅ سیستم مدیریت پشتیبانی کاربران
- ✅ سیستم تیکت و پشتیبانی
- ✅ سیستم نمایندگی و زیرمجموعه‌ها
- ✅ مدیریت پهنای باند
- ✅ سیستم هشدار و اطلاع‌رسانی
- ✅ امکان شخصی‌سازی کامل متون و رابط کاربری

---

## 🚀 نصب و راه‌اندازی

### 📋 پیش‌نیازها

برای نصب Bestify Mode، موارد زیر مورد نیاز است:
- 🖥️ **سرور Ubuntu 20.04 یا بالاتر**
- 🌐 **یک دامنه یا ساب‌دامنه**
- 🔑 **دسترسی به ریپوزیتوری خصوصی**

### 💻 نصب برنامه

برای نصب، دستور زیر را در ترمینال سرور خود اجرا کنید:

```bash
bash <(curl -s https://example.com/path/to/install.sh)
```

**توجه**: نصب این سیستم نیازمند دسترسی به ریپوزیتوری خصوصی است. برای دریافت راهنمایی درباره تنظیم کلید SSH، به بخش [Private Repository Access](#-private-repository-access) مراجعه کنید.

---

### 🔄 به‌روزرسانی

برای به‌روزرسانی سیستم به آخرین نسخه، دستور زیر را اجرا کنید:

```bash
cd /var/www/bestify_mode && bash install.sh -update
```

همچنین می‌توانید برای به‌روزرسانی خودکار، گزینه مربوطه را در منوی نصب انتخاب کنید.

---

### ❌ حذف کامل

برای حذف کامل سیستم از سرور، دستور زیر را اجرا کنید:

```bash
cd /var/www/bestify_mode && bash install.sh
```

سپس گزینه 5 (حذف Bestify Mode) را انتخاب کنید.

---

## 👨‍💻 Private Repository Access

Bestify Mode is a private project that requires SSH key access to the repository. Follow these steps to set up access:

### 1. Generate SSH Key

Create a new SSH key on your server:

```bash
# Create SSH directory if it doesn't exist
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Generate new SSH key
ssh-keygen -t rsa -b 4096 -f ~/.ssh/id_rsa_bestify -N ""

# Set proper permissions
chmod 600 ~/.ssh/id_rsa_bestify
```

### 2. Display and Copy Public Key

Show your public key and copy it:

```bash
cat ~/.ssh/id_rsa_bestify.pub
```

### 3. Add Key to GitHub

Add the public key to the repository:

- Go to the GitHub repository Settings
- Navigate to "Deploy keys"
- Click "Add deploy key"
- Paste your public key and give it a name
- Check "Allow write access" if needed
- Click "Add key"

### 4. Configure SSH

Create or update your SSH config file:

```bash
echo -e "Host github.com\n  IdentityFile ~/.ssh/id_rsa_bestify\n  User git" >> ~/.ssh/config
chmod 600 ~/.ssh/config
```

### 5. Add GitHub to Known Hosts

```bash
ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts
```

### 6. Test Connection

```bash
ssh-T git@github.com
```

You should see a message confirming successful authentication.

### 7. Clone Repository

After setting up SSH access, you can clone the repository:

```bash
sudo mkdir -p /var/www
sudo GIT_SSH_COMMAND="ssh -i ~/.ssh/id_rsa_bestify" git clone git@github.com:itum/bestify_mode.git /var/www/bestify_mode
```

Contact the repository administrator to request access if needed.

---

## 🔄 مدیریت نسخه‌ها

Bestify Mode با استفاده از سیستم تگ‌های Git، نسخه‌های مختلفی ارائه می‌دهد. برای مشاهده نسخه‌های موجود:

```bash
cd /var/www/bestify_mode && bash install.sh -version
```

برای تغییر به یک نسخه خاص، از طریق منوی نصب، گزینه 3 را انتخاب کرده و شماره نسخه مورد نظر را وارد کنید.

### انتشار نسخه‌ها

- **v1.0.0**: نسخه اولیه (پایدار)
- **v1.1.0**: بهبود عملکرد و افزودن ویژگی‌های جدید
- **v1.2.0**: رفع باگ‌ها و بهبود امنیت

---

توسعه‌داده شده توسط تیم Bestify Mode &copy; 2023-2024
