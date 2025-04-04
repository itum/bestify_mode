# 🚀 Bestify Mode

Advanced VPN Service Management System with Multi-Panel Support

<p align="center">
    <img src="https://img.shields.io/badge/Version-1.0.0-blue?style=flat-square" alt="Version"/>
    <img src="https://img.shields.io/badge/Status-Private-red?style=flat-square" alt="Status"/>
    <img src="https://img.shields.io/badge/PHP-8.2+-green?style=flat-square" alt="PHP Version"/>
</p>


---

## 📑 Table of Contents

- [✨ Overview](#-overview)
- [⚙️ Features](#️-features)
- [🚀 Installation](#-installation)
  - [Prerequisites](#-prerequisites)
  - [Setup](#-setup)
  - [Updates](#-updates)
  - [Uninstallation](#-uninstallation)
- [👨‍💻 Private Repository Access](#-private-repository-access)
- [🔄 Version Management](#-version-management)

---

## ✨ Overview

**Bestify Mode** is an advanced management system for VPN services designed to simplify user management, payment processing, and service delivery. The system is compatible with various panels such as **Marzban** and **X-UI**, providing an integrated interface for managing all aspects of your VPN business.

---

## ⚙️ Features

### 🔹 **Core Features**

- ✅ Integrated user management
- ✅ Multiple payment system support
- ✅ Various panel compatibility
- ✅ Automatic configuration generation
- ✅ Subscription management and auto-renewal
- ✅ Analytical and statistical reporting
- ✅ Advanced admin interface
- ✅ User support management
- ✅ Ticket and support system
- ✅ Reseller and sub-user system
- ✅ Bandwidth management
- ✅ Alert and notification system
- ✅ Fully customizable text and interface

---

## 🚀 Installation

### 📋 Prerequisites

To install Bestify Mode, you'll need:
- 🖥️ **Ubuntu Server 20.04 or higher**
- 🌐 **A domain or subdomain**
- 🔑 **Access to the private repository**

### 💻 Setup

To install, run the following command in your server terminal:

```bash
bash <(curl -s https://example.com/path/to/install.sh)
```

**Note**: Installing this system requires access to the private repository. For guidance on setting up SSH keys, refer to the [Private Repository Access](#-private-repository-access) section.

---

### 🔄 Updates

To update the system to the latest version, run:

```bash
cd /var/www/bestify_mode && bash install.sh -update
```

You can also set up automatic updates by selecting the relevant option in the installation menu.

---

### ❌ Uninstallation

To completely remove the system from your server, run:

```bash
cd /var/www/bestify_mode && bash install.sh
```

Then select option 5 (Remove Bestify Mode).

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
ssh -T git@github.com
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

## 🔄 Version Management

Bestify Mode uses Git tags to provide different versions. To view available versions:

```bash
cd /var/www/bestify_mode && bash install.sh -version
```

To switch to a specific version, select option 3 from the installation menu and enter the desired version number.

### Release Versions

- **v1.0.0**: Initial stable release
- **v1.1.0**: Performance improvements and new features
- **v1.2.0**: Bug fixes and security enhancements

---

Developed by Bestify Mode Team &copy; 2023-2024
