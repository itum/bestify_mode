#!/bin/bash

# Checking Root Access
if [[ $EUID -ne 0 ]]; then
    echo -e "\033[31m[ERROR]\033[0m Please run this script as \033[1mroot\033[0m."
    exit 1
fi

# Display Logo
function show_logo() {
    clear
    echo -e "\033[1;34m"
    echo "========================================"
    echo "           MIRZA INSTALL SCRIPT         "
    echo "========================================"
    echo -e "\033[0m"
    echo ""
}

# Display Menu
function show_menu() {
    show_logo
    echo -e "\033[1;36m1)\033[0m Install Mirza Bot"
    echo -e "\033[1;36m2)\033[0m Update Mirza Bot"
    echo -e "\033[1;36m3)\033[0m Remove Mirza Bot"
    echo -e "\033[1;36m4)\033[0m Export Database"
    echo -e "\033[1;36m5)\033[0m Import Database"
    echo -e "\033[1;36m6)\033[0m Configure Automated Backup"
    echo -e "\033[1;36m7)\033[0m Renew SSL Certificates"
    echo -e "\033[1;36m8)\033[0m Change Domain"
    echo -e "\033[1;36m9)\033[0m Additional Bot Management"
    echo -e "\033[1;36m10)\033[0m Exit"
    echo ""
    read -p "Select an option [1-10]: " option
    case $option in
        1) install_bot ;;
        2) update_bot ;;
        3) remove_bot ;;
        4) export_database ;;
        5) import_database ;;
        6) auto_backup ;;
        7) renew_ssl ;;
        8) change_domain ;;
        9) manage_additional_bots ;;
        10)
            echo -e "\033[32mExiting...\033[0m"
            exit 0
            ;;
        *)
            echo -e "\033[31mInvalid option. Please try again.\033[0m"
            show_menu
            ;;
    esac
}

# Check if Marzban is installed
function check_marzban_installed() {
    if [ -f "/opt/marzban/docker-compose.yml" ]; then
        return 0  # Marzban installed
    else
        return 1  # Marzban not installed
    fi
}

# Detect database type for Marzban
function detect_database_type() {
    COMPOSE_FILE="/opt/marzban/docker-compose.yml"
    if grep -q "mysql:" "$COMPOSE_FILE"; then
        return 0  # MySQL detected
    else
        echo -e "\033[31m[ERROR] Marzban is installed but MySQL database is not present. Installation cannot proceed.\033[0m"
        exit 1
    fi
}

# Find a free port between 3300 and 3330
function find_free_port() {
    for port in {3300..3330}; do
        if ! ss -tuln | grep -q ":$port "; then
            echo "$port"
            return 0
        fi
    done
    echo -e "\033[31m[ERROR] No free port found between 3300 and 3330.\033[0m"
    exit 1
}
# Install Function
function install_bot() {
    echo -e "\e[32mInstalling mirza script ... \033[0m\n"

    # Check if Marzban is installed and redirect to appropriate function
    if check_marzban_installed; then
        echo -e "\033[41m[IMPORTANT WARNING]\033[0m \033[1;33mMarzban detected. Proceeding with Marzban-compatible installation.\033[0m"
        install_bot_with_marzban "$@"  # Pass any arguments (e.g., -v beta)
        return 0
    fi

    # Function to add the Ondřej Surý PPA for PHP
    add_php_ppa() {
        sudo add-apt-repository -y ppa:ondrej/php || {
            echo -e "\e[91mError: Failed to add PPA ondrej/php.\033[0m"
            return 1
        }
    }

    # Function to add the Ondřej Surý PPA for PHP with locale override
    add_php_ppa_with_locale() {
        sudo LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php || {
            echo -e "\e[91mError: Failed to add PPA ondrej/php with locale override.\033[0m"
            return 1
        }
    }

    # Try adding the PPA with the system's default locale settings
    if ! add_php_ppa; then
        echo "Failed to add PPA with default locale, retrying with locale override..."
        if ! add_php_ppa_with_locale; then
            echo "Failed to add PPA even with locale override. Exiting..."
            exit 1
        fi
    fi

    sudo apt update && sudo apt upgrade -y || {
        echo -e "\e[91mError: Failed to update and upgrade packages.\033[0m"
        exit 1
    }
    echo -e "\e[92mThe server was successfully updated ...\033[0m\n"

    sudo apt install -y git unzip curl || {
        echo -e "\e[91mError: Failed to install required packages.\033[0m"
        exit 1
    }

    DEBIAN_FRONTEND=noninteractive sudo apt install -y php8.2 php8.2-fpm php8.2-mysql || {
        echo -e "\e[91mError: Failed to install PHP 8.2 and related packages.\033[0m"
        exit 1
    }

    # List of required packages
    PKG=(
        lamp-server^
        libapache2-mod-php
        mysql-server
        apache2
        php-mbstring
        php-zip
        php-gd
        php-json
        php-curl
    )

    # Installing required packages with error handling
    for i in "${PKG[@]}"; do
        dpkg -s $i &>/dev/null
        if [ $? -eq 0 ]; then
            echo "$i is already installed"
        else
            if ! DEBIAN_FRONTEND=noninteractive sudo apt install -y $i; then
                echo -e "\e[91mError installing $i. Exiting...\033[0m"
                exit 1
            fi
        fi
    done

    echo -e "\n\e[92mPackages Installed, Continuing ...\033[0m\n"

    # phpMyAdmin Configuration
    echo 'phpmyadmin phpmyadmin/dbconfig-install boolean true' | sudo debconf-set-selections
    echo 'phpmyadmin phpmyadmin/app-password-confirm password mirzahipass' | sudo debconf-set-selections
    echo 'phpmyadmin phpmyadmin/mysql/admin-pass password mirzahipass' | sudo debconf-set-selections
    echo 'phpmyadmin phpmyadmin/mysql/app-pass password mirzahipass' | sudo debconf-set-selections
    echo 'phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2' | sudo debconf-set-selections

    sudo apt-get install phpmyadmin -y || {
        echo -e "\e[91mError: Failed to install phpMyAdmin.\033[0m"
        exit 1
    }
    # Check and remove existing phpMyAdmin configuration
    if [ -f /etc/apache2/conf-available/phpmyadmin.conf ]; then
        sudo rm -f /etc/apache2/conf-available/phpmyadmin.conf && echo -e "\e[92mRemoved existing phpMyAdmin configuration.\033[0m"
    fi

    # Create symbolic link for phpMyAdmin configuration
    sudo ln -s /etc/phpmyadmin/apache.conf /etc/apache2/conf-available/phpmyadmin.conf || {
        echo -e "\e[91mError: Failed to create symbolic link for phpMyAdmin configuration.\033[0m"
        exit 1
    }

    sudo a2enconf phpmyadmin.conf || {
        echo -e "\e[91mError: Failed to enable phpMyAdmin configuration.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2 service.\033[0m"
        exit 1
    }

    # Additional package installations with error handling
    sudo apt-get install -y php-soap || {
        echo -e "\e[91mError: Failed to install php-soap.\033[0m"
        exit 1
    }

    sudo apt-get install libapache2-mod-php || {
        echo -e "\e[91mError: Failed to install libapache2-mod-php.\033[0m"
        exit 1
    }

    sudo systemctl enable mysql.service || {
        echo -e "\e[91mError: Failed to enable MySQL service.\033[0m"
        exit 1
    }
    sudo systemctl start mysql.service || {
        echo -e "\e[91mError: Failed to start MySQL service.\033[0m"
        exit 1
    }
    sudo systemctl enable apache2 || {
        echo -e "\e[91mError: Failed to enable Apache2 service.\033[0m"
        exit 1
    }
    sudo systemctl start apache2 || {
        echo -e "\e[91mError: Failed to start Apache2 service.\033[0m"
        exit 1
    }

    sudo apt-get install ufw -y || {
        echo -e "\e[91mError: Failed to install UFW.\033[0m"
        exit 1
    }
    ufw allow 'Apache' || {
        echo -e "\e[91mError: Failed to allow Apache in UFW.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2 service after UFW update.\033[0m"
        exit 1
    }

    sudo apt-get install -y git || {
        echo -e "\e[91mError: Failed to install Git.\033[0m"
        exit 1
    }
    sudo apt-get install -y wget || {
        echo -e "\e[91mError: Failed to install Wget.\033[0m"
        exit 1
    }
    sudo apt-get install -y unzip || {
        echo -e "\e[91mError: Failed to install Unzip.\033[0m"
        exit 1
    }
    sudo apt install curl -y || {
        echo -e "\e[91mError: Failed to install cURL.\033[0m"
        exit 1
    }
    sudo apt-get install -y php-ssh2 || {
        echo -e "\e[91mError: Failed to install php-ssh2.\033[0m"
        exit 1
    }
    sudo apt-get install -y libssh2-1-dev libssh2-1 || {
        echo -e "\e[91mError: Failed to install libssh2.\033[0m"
        exit 1
    }
    sudo apt install jq -y || {
        echo -e "\e[91mError: Failed to install jq.\033[0m"
        exit 1
    }

    sudo systemctl restart apache2.service || {
        echo -e "\e[91mError: Failed to restart Apache2 service.\033[0m"
        exit 1
    }

    # Check and remove existing directory before cloning Git repository
    BOT_DIR="/var/www/html/mirzabotconfig"
    if [ -d "$BOT_DIR" ]; then
        echo -e "\e[93mDirectory $BOT_DIR already exists. Removing...\033[0m"
        sudo rm -rf "$BOT_DIR" || {
            echo -e "\e[91mError: Failed to remove existing directory $BOT_DIR.\033[0m"
            exit 1
        }
    fi

    # Create bot directory
    sudo mkdir -p "$BOT_DIR"
    if [ ! -d "$BOT_DIR" ]; then
        echo -e "\e[91mError: Failed to create directory $BOT_DIR.\033[0m"
        exit 1
    fi

    # Default to latest release
    ZIP_URL=$(curl -s https://api.github.com/repos/itum/bestify_mode/releases/latest | grep "zipball_url" | cut -d '"' -f 4)

# Check for version flag
if [[ "$1" == "-v" && "$2" == "beta" ]] || [[ "$1" == "-beta" ]] || [[ "$1" == "-" && "$2" == "beta" ]]; then
    ZIP_URL="https://github.com/itum/bestify_mode/archive/refs/heads/main.zip"
elif [[ "$1" == "-v" && -n "$2" ]]; then
    ZIP_URL="https://github.com/itum/bestify_mode/archive/refs/tags/$2.zip"
fi

    # Download and extract the repository
    TEMP_DIR="/tmp/mirzabot"
    mkdir -p "$TEMP_DIR"
    wget -O "$TEMP_DIR/bot.zip" "$ZIP_URL" || {
        echo -e "\e[91mError: Failed to download the specified version.\033[0m"
        exit 1
    }

    unzip "$TEMP_DIR/bot.zip" -d "$TEMP_DIR"
    EXTRACTED_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d)
    mv "$EXTRACTED_DIR"/* "$BOT_DIR" || {
        echo -e "\e[91mError: Failed to move extracted files.\033[0m"
        exit 1
    }
    rm -rf "$TEMP_DIR"

    sudo chown -R www-data:www-data "$BOT_DIR"
    sudo chmod -R 755 "$BOT_DIR"

    echo -e "\n\033[33mMirza config and script have been installed successfully.\033[0m"


wait
if [ ! -d "/root/confmirza" ]; then
    sudo mkdir /root/confmirza || {
        echo -e "\e[91mError: Failed to create /root/confmirza directory.\033[0m"
        exit 1
    }

    sleep 1

    touch /root/confmirza/dbrootmirza.txt || {
        echo -e "\e[91mError: Failed to create dbrootmirza.txt.\033[0m"
        exit 1
    }
    sudo chmod -R 777 /root/confmirza/dbrootmirza.txt || {
        echo -e "\e[91mError: Failed to set permissions for dbrootmirza.txt.\033[0m"
        exit 1
    }
    sleep 1

    randomdbpasstxt=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)

    ASAS="$"

    echo "${ASAS}user = 'root';" >> /root/confmirza/dbrootmirza.txt
    echo "${ASAS}pass = '${randomdbpasstxt}';" >> /root/confmirza/dbrootmirza.txt
    echo "${ASAS}path = '${RANDOM_NUMBER}';" >> /root/confmirza/dbrootmirza.txt

    sleep 1

    passs=$(cat /root/confmirza/dbrootmirza.txt | grep '$pass' | cut -d"'" -f2)
    userrr=$(cat /root/confmirza/dbrootmirza.txt | grep '$user' | cut -d"'" -f2)

    sudo mysql -u $userrr -p$passs -e "alter user '$userrr'@'localhost' identified with mysql_native_password by '$passs';FLUSH PRIVILEGES;" || {
        echo -e "\e[91mError: Failed to alter MySQL user. Attempting recovery...\033[0m"

        # Enable skip-grant-tables at the end of the file
        sudo sed -i '$ a skip-grant-tables' /etc/mysql/mysql.conf.d/mysqld.cnf
        sudo systemctl restart mysql

        # Access MySQL to reset the root user
        sudo mysql <<EOF
DROP USER IF EXISTS 'root'@'localhost';
CREATE USER 'root'@'localhost' IDENTIFIED BY '${passs}';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

        # Disable skip-grant-tables
        sudo sed -i '/skip-grant-tables/d' /etc/mysql/mysql.conf.d/mysqld.cnf
        sudo systemctl restart mysql

        # Retry MySQL login with the new credentials
        echo "SELECT 1" | mysql -u$userrr -p$passs 2>/dev/null || {
            echo -e "\e[91mError: Recovery failed. MySQL login still not working.\033[0m"
            exit 1
        }
    }

    echo "Folder created successfully!"
else
    echo "Folder already exists."
fi


clear

echo " "
echo -e "\e[32m SSL \033[0m\n"

read -p "Enter the domain: " domainname
while [[ ! "$domainname" =~ ^[a-zA-Z0-9.-]+$ ]]; do
    echo -e "\e[91mInvalid domain format. Please try again.\033[0m"
    read -p "Enter the domain: " domainname
done
    DOMAIN_NAME="$domainname"
    PATHS=$(cat /root/confmirza/dbrootmirza.txt | grep '$path' | cut -d"'" -f2)
    sudo ufw allow 80 || {
        echo -e "\e[91mError: Failed to allow port 80 in UFW.\033[0m"
        exit 1
    }
    sudo ufw allow 443 || {
        echo -e "\e[91mError: Failed to allow port 443 in UFW.\033[0m"
        exit 1
    }

    echo -e "\033[33mDisable apache2\033[0m"
    wait

    sudo systemctl stop apache2 || {
        echo -e "\e[91mError: Failed to stop Apache2.\033[0m"
        exit 1
    }
    sudo systemctl disable apache2 || {
        echo -e "\e[91mError: Failed to disable Apache2.\033[0m"
        exit 1
    }
    sudo apt install letsencrypt -y || {
        echo -e "\e[91mError: Failed to install letsencrypt.\033[0m"
        exit 1
    }
    sudo systemctl enable certbot.timer || {
        echo -e "\e[91mError: Failed to enable certbot timer.\033[0m"
        exit 1
    }
    sudo certbot certonly --standalone --agree-tos --preferred-challenges http -d $DOMAIN_NAME || {
        echo -e "\e[91mError: Failed to generate SSL certificate.\033[0m"
        exit 1
    }
    sudo apt install python3-certbot-apache -y || {
        echo -e "\e[91mError: Failed to install python3-certbot-apache.\033[0m"
        exit 1
    }
    sudo certbot --apache --agree-tos --preferred-challenges http -d $DOMAIN_NAME || {
        echo -e "\e[91mError: Failed to configure SSL with Certbot.\033[0m"
        exit 1
    }

    echo " "
    echo -e "\033[33mEnable apache2\033[0m"
    wait
    sudo systemctl enable apache2 || {
        echo -e "\e[91mError: Failed to enable Apache2.\033[0m"
        exit 1
    }
    sudo systemctl start apache2 || {
        echo -e "\e[91mError: Failed to start Apache2.\033[0m"
        exit 1
    }
            clear

        printf "\e[33m[+] \e[36mBot Token: \033[0m"
        read YOUR_BOT_TOKEN
        while [[ ! "$YOUR_BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
            echo -e "\e[91mInvalid bot token format. Please try again.\033[0m"
            printf "\e[33m[+] \e[36mBot Token: \033[0m"
            read YOUR_BOT_TOKEN
        done

        printf "\e[33m[+] \e[36mChat id: \033[0m"
        read YOUR_CHAT_ID
        while [[ ! "$YOUR_CHAT_ID" =~ ^-?[0-9]+$ ]]; do
            echo -e "\e[91mInvalid chat ID format. Please try again.\033[0m"
            printf "\e[33m[+] \e[36mChat id: \033[0m"
            read YOUR_CHAT_ID
        done

        YOUR_DOMAIN="$DOMAIN_NAME"

    while true; do
        printf "\e[33m[+] \e[36musernamebot: \033[0m"
        read YOUR_BOTNAME
        if [ "$YOUR_BOTNAME" != "" ]; then
            break
        else
            echo -e "\e[91mError: Bot username cannot be empty. Please enter a valid username.\033[0m"
        fi
    done

    ROOT_PASSWORD=$(cat /root/confmirza/dbrootmirza.txt | grep '$pass' | cut -d"'" -f2)
    ROOT_USER="root"
    echo "SELECT 1" | mysql -u$ROOT_USER -p$ROOT_PASSWORD 2>/dev/null || {
        echo -e "\e[91mError: MySQL connection failed.\033[0m"
        exit 1
    }

    if [ $? -eq 0 ]; then
        wait

        randomdbpass=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)

        randomdbdb=$(openssl rand -base64 10 | tr -dc 'a-zA-Z' | cut -c1-8)

        if [[ $(mysql -u root -p$ROOT_PASSWORD -e "SHOW DATABASES LIKE 'mirzabot'") ]]; then
            clear
            echo -e "\n\e[91mYou have already created the database\033[0m\n"
        else
            dbname=mirzabot
            clear
            echo -e "\n\e[32mPlease enter the database username!\033[0m"
            printf "[+] Default user name is \e[91m${randomdbdb}\e[0m ( let it blank to use this user name ): "
            read dbuser
            if [ "$dbuser" = "" ]; then
                dbuser=$randomdbdb
            fi

            echo -e "\n\e[32mPlease enter the database password!\033[0m"
            printf "[+] Default password is \e[91m${randomdbpass}\e[0m ( let it blank to use this password ): "
            read dbpass
            if [ "$dbpass" = "" ]; then
                dbpass=$randomdbpass
            fi

            mysql -u root -p$ROOT_PASSWORD -e "CREATE DATABASE $dbname;" -e "CREATE USER '$dbuser'@'%' IDENTIFIED WITH mysql_native_password BY '$dbpass';GRANT ALL PRIVILEGES ON * . * TO '$dbuser'@'%';FLUSH PRIVILEGES;" -e "CREATE USER '$dbuser'@'localhost' IDENTIFIED WITH mysql_native_password BY '$dbpass';GRANT ALL PRIVILEGES ON * . * TO '$dbuser'@'localhost';FLUSH PRIVILEGES;" || {
                echo -e "\e[91mError: Failed to create database or user.\033[0m"
                exit 1
            }

            echo -e "\n\e[95mDatabase Created.\033[0m"

            clear



            ASAS="$"

            wait

            sleep 1

            file_path="/var/www/html/mirzabotconfig/config.php"

            if [ -f "$file_path" ]; then
              rm "$file_path" || {
                echo -e "\e[91mError: Failed to delete old config.php.\033[0m"
                exit 1
              }
              echo -e "File deleted successfully."
            else
              echo -e "File not found."
            fi

            sleep 1

            secrettoken=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)

            echo -e "<?php" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}APIKEY = '${YOUR_BOT_TOKEN}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}usernamedb = '${dbuser}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}passworddb = '${dbpass}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}dbname = '${dbname}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}domainhosts = '${YOUR_DOMAIN}/mirzabotconfig';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}adminnumber = '${YOUR_CHAT_ID}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}usernamebot = '${YOUR_BOTNAME}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}secrettoken = '${secrettoken}';" >> /var/www/html/mirzabotconfig/config.php
            echo -e "${ASAS}connect = mysqli_connect('localhost', \$usernamedb, \$passworddb, \$dbname);" >> /var/www/html/mirzabotconfig/config.php
            echo -e "if (${ASAS}connect->connect_error) {" >> /var/www/html/mirzabotconfig/config.php
            echo -e "die(' The connection to the database failed:' . ${ASAS}connect->connect_error);" >> /var/www/html/mirzabotconfig/config.php
            echo -e "}" >> /var/www/html/mirzabotconfig/config.php
            echo -e "mysqli_set_charset(${ASAS}connect, 'utf8mb4');" >> /var/www/html/mirzabotconfig/config.php
            text_to_save=$(cat <<EOF
\$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
\$dsn = "mysql:host=localhost;dbname=${ASAS}dbname;charset=utf8mb4";
try {
     \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);
} catch (\PDOException \$e) {
     throw new \PDOException(\$e->getMessage(), (int)\$e->getCode());
}
EOF
)
echo -e "$text_to_save" >> /var/www/html/mirzabotconfig/config.php
            echo -e "?>" >> /var/www/html/mirzabotconfig/config.php

            sleep 1

            curl -F "url=https://${YOUR_DOMAIN}/mirzabotconfig/index.php" \
     -F "secret_token=${secrettoken}" \
     "https://api.telegram.org/bot${YOUR_BOT_TOKEN}/setWebhook" || {
                echo -e "\e[91mError: Failed to set webhook for bot.\033[0m"
                exit 1
            }
            MESSAGE="✅ The bot is installed! for start the bot send /start command."
            curl -s -X POST "https://api.telegram.org/bot${YOUR_BOT_TOKEN}/sendMessage" -d chat_id="${YOUR_CHAT_ID}" -d text="$MESSAGE" || {
                echo -e "\e[91mError: Failed to send message to Telegram.\033[0m"
                exit 1
            }

            sleep 1
            sudo systemctl start apache2 || {
                echo -e "\e[91mError: Failed to start Apache2.\033[0m"
                exit 1
            }
            url="https://${YOUR_DOMAIN}/mirzabotconfig/table.php"
            curl $url || {
                echo -e "\e[91mError: Failed to fetch URL from domain.\033[0m"
                exit 1
            }

            clear

            echo " "

            echo -e "\e[102mDomain Bot: https://${YOUR_DOMAIN}\033[0m"
            echo -e "\e[104mDatabase address: https://${YOUR_DOMAIN}/phpmyadmin\033[0m"
            echo -e "\e[33mDatabase name: \e[36m${dbname}\033[0m"
            echo -e "\e[33mDatabase username: \e[36m${dbuser}\033[0m"
            echo -e "\e[33mDatabase password: \e[36m${dbpass}\033[0m"
            echo " "
            echo -e "Mirza Bot"
        fi


    elif [ "$ROOT_PASSWORD" = "" ] || [ "$ROOT_USER" = "" ]; then
        echo -e "\n\e[36mThe password is empty.\033[0m\n"
    else 

        echo -e "\n\e[36mThe password is not correct.\033[0m\n"

    fi


}


function install_bot_with_marzban() {
    # Display warning and confirmation
    echo -e "\033[41m[IMPORTANT WARNING]\033[0m \033[1;33mMarzban panel is detected on your server. Please make sure to backup the Marzban database before installing Mirza Bot.\033[0m"
    read -p "Are you sure you want to install Mirza Bot alongside Marzban? (y/n): " confirm
    if [[ "$confirm" != "y" && "$confirm" != "Y" ]]; then
        echo -e "\e[91mInstallation aborted by user.\033[0m"
        exit 0
    fi

    echo -e "\e[32mInstalling Mirza Bot alongside Marzban...\033[0m\n"

    # Check if port 80 is free before proceeding
    echo -e "\e[32mChecking port 80 availability...\033[0m"
    if sudo netstat -tuln | grep -q ":80 "; then
        echo -e "\e[91mError: Port 80 is already in use. Please free port 80 (e.g., stop any service using it like Marzban's HTTP) and run the script again.\033[0m"
        exit 1
    else
        echo -e "\e[92mPort 80 is free. Proceeding with installation...\033[0m"
    fi

    # Update system and upgrade packages
    sudo apt update && sudo apt upgrade -y || {
        echo -e "\e[91mError: Failed to update and upgrade system.\033[0m"
        exit 1
    }
    echo -e "\e[92mSystem updated successfully...\033[0m\n"

    # Add Ondřej Surý PPA for PHP 8.2
    sudo apt install -y software-properties-common || {
        echo -e "\e[91mError: Failed to install software-properties-common.\033[0m"
        exit 1
    }
    sudo add-apt-repository -y ppa:ondrej/php || {
        echo -e "\e[91mError: Failed to add PPA ondrej/php. Trying with locale override...\033[0m"
        sudo LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php || {
            echo -e "\e[91mError: Failed to add PPA even with locale override.\033[0m"
            exit 1
        }
    }
    sudo apt update || {
        echo -e "\e[91mError: Failed to update package list after adding PPA.\033[0m"
        exit 1
    }

    # Install all required packages
    sudo apt install -y git unzip curl wget jq || {
        echo -e "\e[91mError: Failed to install basic tools.\033[0m"
        exit 1
    }

    # Install Apache if not installed
    if ! dpkg -s apache2 &>/dev/null; then
        sudo apt install -y apache2 || {
            echo -e "\e[91mError: Failed to install Apache2.\033[0m"
            exit 1
        }
    fi

    # Install PHP 8.2 and all necessary modules (including PDO)
    DEBIAN_FRONTEND=noninteractive sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-zip php8.2-gd php8.2-curl php8.2-soap php8.2-ssh2 libssh2-1-dev libssh2-1 php8.2-pdo || {
        echo -e "\e[91mError: Failed to install PHP 8.2 and modules.\033[0m"
        exit 1
    }

    # Install additional Apache module
    sudo apt install -y libapache2-mod-php8.2 || {
        echo -e "\e[91mError: Failed to install libapache2-mod-php8.2.\033[0m"
        exit 1
    }

    # Install UFW if not present
    if ! dpkg -s ufw &>/dev/null; then
        sudo apt install -y ufw || {
            echo -e "\e[91mError: Failed to install UFW.\033[0m"
            exit 1
        }
    fi

    # Check Marzban and use its MySQL (Docker-based)
    ENV_FILE="/opt/marzban/.env"
    if [ ! -f "$ENV_FILE" ]; then
        echo -e "\e[91mError: Marzban .env file not found. Cannot proceed without Marzban configuration.\033[0m"
        exit 1
    fi

    # Get MySQL root password from .env
    MYSQL_ROOT_PASSWORD=$(grep "MYSQL_ROOT_PASSWORD=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '[:space:]' | sed 's/"//g')
    ROOT_USER="root"

    # Check if MYSQL_ROOT_PASSWORD is empty or invalid
    if [ -z "$MYSQL_ROOT_PASSWORD" ]; then
        echo -e "\e[93mWarning: Could not retrieve MySQL root password from Marzban .env file.\033[0m"
        read -s -p "Please enter the MySQL root password manually: " MYSQL_ROOT_PASSWORD
        echo
    fi

    # Dynamically find the MySQL container
    MYSQL_CONTAINER=$(docker ps -q --filter "name=mysql" --no-trunc)
    if [ -z "$MYSQL_CONTAINER" ]; then
        echo -e "\e[91mError: Could not find a running MySQL container. Ensure Marzban is running with Docker.\033[0m"
        echo -e "\e[93mRunning containers:\033[0m"
        docker ps
        exit 1
    fi

    # Test MySQL connection inside Docker container
    echo "Testing MySQL connection..."
    docker exec "$MYSQL_CONTAINER" bash -c "echo 'SELECT 1;' | mysql -u '$ROOT_USER' -p'$MYSQL_ROOT_PASSWORD'" 2>/dev/null || {
        echo -e "\e[91mError: Failed to connect to MySQL in Marzban Docker container.\033[0m"
        echo -e "\e[93mPlease ensure the MySQL root password is correct and the container is running.\033[0m"
        echo -e "\e[93mContainer found: $MYSQL_CONTAINER\033[0m"
        echo -e "\e[93mPassword used: $MYSQL_ROOT_PASSWORD\033[0m"
        exit 1
    }
    echo -e "\e[92mMySQL connection successful.\033[0m"

    # Ask for database username and password like Marzban
    clear
    echo -e "\e[33mConfiguring Mirza Bot database credentials...\033[0m"
    default_dbuser=$(openssl rand -base64 12 | tr -dc 'a-zA-Z' | head -c8)
    printf "\e[33m[+] \e[36mDatabase username (default: $default_dbuser): \033[0m"
    read dbuser
    if [ -z "$dbuser" ]; then
        dbuser="$default_dbuser"
    fi

    default_dbpass=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c12)
    printf "\e[33m[+] \e[36mDatabase password (default: $default_dbpass): \033[0m"
    read -s dbpass
    echo
    if [ -z "$dbpass" ]; then
        dbpass="$default_dbpass"
    fi
    dbname="mirzabot"

    # Create database and user inside Docker container
    docker exec "$MYSQL_CONTAINER" bash -c "mysql -u '$ROOT_USER' -p'$MYSQL_ROOT_PASSWORD' -e \"CREATE DATABASE IF NOT EXISTS $dbname; CREATE USER IF NOT EXISTS '$dbuser'@'%' IDENTIFIED BY '$dbpass'; GRANT ALL PRIVILEGES ON $dbname.* TO '$dbuser'@'%'; FLUSH PRIVILEGES;\"" || {
        echo -e "\e[91mError: Failed to create database or user in Marzban MySQL container.\033[0m"
        exit 1
    }
    echo -e "\e[92mDatabase '$dbname' created successfully.\033[0m"

    # Bot directory setup
    BOT_DIR="/var/www/html/mirzabotconfig"
    if [ -d "$BOT_DIR" ]; then
        echo -e "\e[93mDirectory $BOT_DIR already exists. Removing...\033[0m"
        sudo rm -rf "$BOT_DIR" || {
            echo -e "\e[91mError: Failed to remove existing directory $BOT_DIR.\033[0m"
            exit 1
        }
    fi
    sudo mkdir -p "$BOT_DIR" || {
        echo -e "\e[91mError: Failed to create directory $BOT_DIR.\033[0m"
        exit 1
    }

    # Download bot files
    ZIP_URL=$(curl -s https://api.github.com/repos/itum/bestify_mode/releases/latest | grep "zipball_url" | cut -d '"' -f 4)
    if [[ "$1" == "-v" && "$2" == "beta" ]] || [[ "$1" == "-beta" ]] || [[ "$1" == "-" && "$2" == "beta" ]]; then
        ZIP_URL="https://github.com/itum/bestify_mode/archive/refs/heads/main.zip"
    elif [[ "$1" == "-v" && -n "$2" ]]; then
        ZIP_URL="https://github.com/itum/bestify_mode/archive/refs/tags/$2.zip"
    fi

    TEMP_DIR="/tmp/mirzabot"
    mkdir -p "$TEMP_DIR"
    wget -O "$TEMP_DIR/bot.zip" "$ZIP_URL" || {
        echo -e "\e[91mError: Failed to download bot files.\033[0m"
        exit 1
    }
    unzip "$TEMP_DIR/bot.zip" -d "$TEMP_DIR" || {
        echo -e "\e[91mError: Failed to unzip bot files.\033[0m"
        exit 1
    }
    EXTRACTED_DIR=$(find "$TEMP_DIR" -mindepth 1 -maxdepth 1 -type d)
    mv "$EXTRACTED_DIR"/* "$BOT_DIR" || {
        echo -e "\e[91mError: Failed to move bot files.\033[0m"
        exit 1
    }
    rm -rf "$TEMP_DIR"

    sudo chown -R www-data:www-data "$BOT_DIR"
    sudo chmod -R 755 "$BOT_DIR"
    echo -e "\e[92mBot files installed in $BOT_DIR.\033[0m"
    sleep 3
    clear

    # Configure Apache to use port 80 temporarily and 88 for HTTPS
    echo -e "\e[32mConfiguring Apache ports...\033[0m"
    sudo bash -c "echo -n > /etc/apache2/ports.conf"  # Clear the file
    cat <<EOF | sudo tee /etc/apache2/ports.conf
# If you just change the port or add more ports here, you will likely also
# have to change the VirtualHost statement in
# /etc/apache2/sites-enabled/000-default.conf

Listen 80
Listen 88

# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
EOF
    if [ $? -ne 0 ]; then
        echo -e "\e[91mError: Failed to configure ports.conf.\033[0m"
        exit 1
    fi

    # Clear and configure VirtualHost for port 80
    sudo bash -c "echo -n > /etc/apache2/sites-available/000-default.conf"  # Clear the file
    cat <<EOF | sudo tee /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
EOF
    if [ $? -ne 0 ]; then
        echo -e "\e[91mError: Failed to configure 000-default.conf.\033[0m"
        exit 1
    fi

    # Enable Apache and apply port changes
    sudo systemctl enable apache2 || {
        echo -e "\e[91mError: Failed to enable Apache2.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2.\033[0m"
        exit 1
    }

    # SSL setup on port 88
    echo -e "\e[32mConfiguring SSL on port 88...\033[0m\n"
    sudo ufw allow 80 || {
        echo -e "\e[91mError: Failed to configure firewall for port 80.\033[0m"
        exit 1
    }
    sudo ufw allow 88 || {
        echo -e "\e[91mError: Failed to configure firewall for port 88.\033[0m"
        exit 1
    }
    clear
    printf "\e[33m[+] \e[36mEnter the domain (e.g., example.com): \033[0m"
    read domainname
    while [[ ! "$domainname" =~ ^[a-zA-Z0-9.-]+$ ]]; do
        echo -e "\e[91mInvalid domain format. Must be like 'example.com'. Please try again.\033[0m"
        printf "\e[33m[+] \e[36mEnter the domain (e.g., example.com): \033[0m"
        read domainname
    done
    DOMAIN_NAME="$domainname"
    echo -e "\e[92mDomain set to: $DOMAIN_NAME\033[0m"

    sudo apt install -y letsencrypt python3-certbot-apache || {
        echo -e "\e[91mError: Failed to install SSL tools.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2 before Certbot.\033[0m"
        exit 1
    }
    sudo certbot --apache --agree-tos --preferred-challenges http -d "$DOMAIN_NAME" --https-port 88 --no-redirect || {
        echo -e "\e[91mError: Failed to configure SSL with Certbot on port 88.\033[0m"
        exit 1
    }

    # Ensure SSL VirtualHost uses port 88 with correct settings
    sudo bash -c "echo -n > /etc/apache2/sites-available/000-default-le-ssl.conf"  # Clear any existing file
    cat <<EOF | sudo tee /etc/apache2/sites-available/000-default-le-ssl.conf
<IfModule mod_ssl.c>
<VirtualHost *:88>
    ServerAdmin webmaster@localhost
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/html
    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite HIGH:!aNULL:!MD5
</VirtualHost>
</IfModule>
EOF
    if [ $? -ne 0 ]; then
        echo -e "\e[91mError: Failed to create SSL VirtualHost configuration.\033[0m"
        exit 1
    fi
    sudo a2enmod ssl || {
        echo -e "\e[91mError: Failed to enable SSL module.\033[0m"
        exit 1
    }
    sudo a2ensite 000-default-le-ssl.conf || {
        echo -e "\e[91mError: Failed to enable SSL site.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2 after SSL configuration.\033[0m"
        exit 1
    }

    # Disable port 80 after SSL is configured
    echo -e "\e[32mDisabling port 80 as it's no longer needed...\033[0m"
    sudo bash -c "echo -n > /etc/apache2/ports.conf"  # Clear the file again
    cat <<EOF | sudo tee /etc/apache2/ports.conf
# If you just change the port or add more ports here, you will likely also
# have to change the VirtualHost statement in
# /etc/apache2/sites-enabled/000-default.conf

Listen 88

# vim: syntax=apache ts=4 sw=4 sts=4 sr noet
EOF
    if [ $? -ne 0 ]; then
        echo -e "\e[91mError: Failed to reconfigure ports.conf.\033[0m"
        exit 1
    fi

    # Remove port 80 VirtualHost from all config files
    sudo sed -i '/<VirtualHost \*:80>/,/<\/VirtualHost>/d' /etc/apache2/sites-available/* || {
        echo -e "\e[91mError: Failed to remove VirtualHost for port 80 from all config files.\033[0m"
        exit 1
    }
    sudo ufw delete allow 80 || {
        echo -e "\e[91mError: Failed to remove port 80 from firewall.\033[0m"
        exit 1
    }
    sudo systemctl restart apache2 || {
        echo -e "\e[91mError: Failed to restart Apache2 after SSL.\033[0m"
        exit 1
    }
    echo -e "\e[92mSSL configured successfully on port 88. Port 80 disabled.\033[0m"
    sleep 3
    clear

    # Bot token, chat ID, and username
    printf "\e[33m[+] \e[36mBot Token: \033[0m"
    read YOUR_BOT_TOKEN
    while [[ ! "$YOUR_BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
        echo -e "\e[91mInvalid bot token format. Please try again.\033[0m"
        printf "\e[33m[+] \e[36mBot Token: \033[0m"
        read YOUR_BOT_TOKEN
    done

    printf "\e[33m[+] \e[36mChat id: \033[0m"
    read YOUR_CHAT_ID
    while [[ ! "$YOUR_CHAT_ID" =~ ^-?[0-9]+$ ]]; do
        echo -e "\e[91mInvalid chat ID format. Please try again.\033[0m"
        printf "\e[33m[+] \e[36mChat id: \033[0m"
        read YOUR_CHAT_ID
    done

    YOUR_DOMAIN="$DOMAIN_NAME:88"  # Use port 88 for HTTPS
    printf "\e[33m[+] \e[36mUsernamebot: \033[0m"
    read YOUR_BOTNAME
    while [ -z "$YOUR_BOTNAME" ]; do
        echo -e "\e[91mError: Bot username cannot be empty.\033[0m"
        printf "\e[33m[+] \e[36mUsernamebot: \033[0m"
        read YOUR_BOTNAME
    done

    # Create config file with correct MySQL host and PDO
    ASAS="$"
    secrettoken=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
    cat <<EOF > "$BOT_DIR/config.php"
<?php
${ASAS}APIKEY = '$YOUR_BOT_TOKEN';
${ASAS}usernamedb = '$dbuser';
${ASAS}passworddb = '$dbpass';
${ASAS}dbname = '$dbname';
${ASAS}domainhosts = '$YOUR_DOMAIN/mirzabotconfig';
${ASAS}adminnumber = '$YOUR_CHAT_ID';
${ASAS}usernamebot = '$YOUR_BOTNAME';
${ASAS}secrettoken = '$secrettoken';

${ASAS}connect = mysqli_connect('localhost', \$usernamedb, \$passworddb, \$dbname);
if (\$connect->connect_error) {
    die('Database connection failed: ' . \$connect->connect_error);
}
mysqli_set_charset(\$connect, 'utf8mb4');

${ASAS}options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
${ASAS}dsn = "mysql:host=localhost;dbname=\$dbname;charset=utf8mb4";
try {
     \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);
} catch (\PDOException \$e) {
     throw new \PDOException(\$e->getMessage(), (int)\$e->getCode());
}
?>
EOF

    # Set webhook with port 88
    curl -F "url=https://${YOUR_DOMAIN}/mirzabotconfig/index.php" \
         -F "secret_token=${secrettoken}" \
         "https://api.telegram.org/bot${YOUR_BOT_TOKEN}/setWebhook" || {
        echo -e "\e[91mError: Failed to set webhook.\033[0m"
        exit 1
    }

    # Send Installation Confirmation
    MESSAGE="✅ The bot is installed! for start bot send comment /start"
    curl -s -X POST "https://api.telegram.org/bot${BOT_TOKEN}/sendMessage" -d chat_id="${CHAT_ID}" -d text="$MESSAGE" || {
        echo -e "\033[31mError: Failed to send message to Telegram.\033[0m"
        return 1
    }

    # Execute table creation script
    TABLE_SETUP_URL="https://${DOMAIN_NAME}/$BOT_NAME/table.php"
    echo -e "\033[33mSetting up database tables...\033[0m"
    curl $TABLE_SETUP_URL || {
        echo -e "\033[31mError: Failed to execute table creation script at $TABLE_SETUP_URL.\033[0m"
        return 1
    }

    # Output Bot Information
    echo -e "\033[32mBot installed successfully!\033[0m"
    echo -e "\033[102mDomain Bot: https://$DOMAIN_NAME\033[0m"
    echo -e "\033[104mDatabase address: https://$DOMAIN_NAME/phpmyadmin\033[0m"
    echo -e "\033[33mDatabase name: \033[36m$DB_NAME\033[0m"
    echo -e "\033[33mDatabase username: \033[36m$DB_USERNAME\033[0m"
    echo -e "\033[33mDatabase password: \033[36m$DB_PASSWORD\033[0m"
}

# Function to Update Additional Bot
function update_additional_bot() {
    clear
    echo -e "\033[36mAvailable Bots:\033[0m"

    # List directories in /var/www/html excluding mirzabotconfig
    BOT_DIRS=$(ls -d /var/www/html/*/ 2>/dev/null | grep -v "/var/www/html/mirzabotconfig" | xargs -n 1 basename)

    if [ -z "$BOT_DIRS" ]; then
        echo -e "\033[31mNo additional bots found in /var/www/html.\033[0m"
        return 1
    fi

    # Display list of bots
    echo "$BOT_DIRS" | nl -w 2 -s ") "

    # Prompt user to select a bot
    echo -ne "\033[36mSelect a bot by name: \033[0m"
    read SELECTED_BOT

    if [[ ! "$BOT_DIRS" =~ (^|[[:space:]])$SELECTED_BOT($|[[:space:]]) ]]; then
        echo -e "\033[31mInvalid bot name.\033[0m"
        return 1
    fi

    BOT_PATH="/var/www/html/$SELECTED_BOT"
    CONFIG_PATH="$BOT_PATH/config.php"
    TEMP_CONFIG_PATH="/root/${SELECTED_BOT}_config.php"

    echo -e "\033[33mUpdating $SELECTED_BOT...\033[0m"

    # Check and backup the config.php file
    if [ -f "$CONFIG_PATH" ]; then
        mv "$CONFIG_PATH" "$TEMP_CONFIG_PATH" || {
            echo -e "\033[31mFailed to backup config.php. Exiting...\033[0m"
            return 1
        }
    else
        echo -e "\033[31mconfig.php not found in $BOT_PATH. Exiting...\033[0m"
        return 1
    fi

    # Remove the old version of the bot
    if ! rm -rf "$BOT_PATH"; then
        echo -e "\033[31mFailed to remove old bot directory. Exiting...\033[0m"
        return 1
    fi

    # Clone the new version of the bot
    if ! git clone https://github.com/itum/bestify_mode.git "$BOT_PATH"; then
        echo -e "\033[31mFailed to clone the repository. Exiting...\033[0m"
        return 1
    fi

    # Restore configuration file
    if ! mv "$TEMP_CONFIG_PATH" "$CONFIG_PATH"; then
        echo -e "\033[31mFailed to restore config.php. Exiting...\033[0m"
        return 1
    fi

    # Set ownership and permissions
    sudo chown -R www-data:www-data "$BOT_PATH"
    sudo chmod -R 755 "$BOT_PATH"

    # Execute the table.php script
    URL=$(grep '\$domainhosts' "$CONFIG_PATH" | cut -d"'" -f2)
    if [ -z "$URL" ]; then
        echo -e "\033[31mFailed to extract domain URL from config.php. Exiting...\033[0m"
        return 1
    fi

    if ! curl -s "https://$URL/table.php"; then
        echo -e "\033[31mFailed to execute table.php. Exiting...\033[0m"
        return 1
    fi

    echo -e "\033[32m$SELECTED_BOT has been successfully updated!\033[0m"
}

# Function to Remove Additional Bot
function remove_additional_bot() {
    clear
    echo -e "\033[36mAvailable Bots:\033[0m"

    # List directories in /var/www/html excluding mirzabotconfig
    BOT_DIRS=$(ls -d /var/www/html/*/ 2>/dev/null | grep -v "/var/www/html/mirzabotconfig" | xargs -n 1 basename)

    if [ -z "$BOT_DIRS" ]; then
        echo -e "\033[31mNo additional bots found in /var/www/html.\033[0m"
        return 1
    fi

    # Display list of bots
    echo "$BOT_DIRS" | nl -w 2 -s ") "

    # Prompt user to select a bot
    echo -ne "\033[36mSelect a bot by name: \033[0m"
    read SELECTED_BOT

    if [[ ! "$BOT_DIRS" =~ (^|[[:space:]])$SELECTED_BOT($|[[:space:]]) ]]; then
        echo -e "\033[31mInvalid bot name.\033[0m"
        return 1
    fi

    BOT_PATH="/var/www/html/$SELECTED_BOT"
    CONFIG_PATH="$BOT_PATH/config.php"

    # Confirm removal
    echo -ne "\033[36mAre you sure you want to remove $SELECTED_BOT? (yes/no): \033[0m"
    read CONFIRM_REMOVE
    if [[ "$CONFIRM_REMOVE" != "yes" ]]; then
        echo -e "\033[33mAborted.\033[0m"
        return 1
    fi

    # Check database backup
    echo -ne "\033[36mHave you backed up the database? (yes/no): \033[0m"
    read BACKUP_CONFIRM
    if [[ "$BACKUP_CONFIRM" != "yes" ]]; then
        echo -e "\033[33mAborted. Please backup the database first.\033[0m"
        return 1
    fi

    # Get database credentials
    ROOT_CREDENTIALS_FILE="/root/confmirza/dbrootmirza.txt"
    if [ -f "$ROOT_CREDENTIALS_FILE" ]; then
        ROOT_USER=$(grep '\$user =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
        ROOT_PASS=$(grep '\$pass =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
    else
        echo -ne "\033[36mRoot credentials file not found. Enter MySQL root password: \033[0m"
        read -s ROOT_PASS
        echo
        ROOT_USER="root"
    fi

    DOMAIN_NAME=$(grep '\$domainhosts' "$CONFIG_PATH" | cut -d"'" -f2 | cut -d"/" -f1)
    DB_NAME=$(awk -F"'" '/\$dbname = / {print $2}' "$CONFIG_PATH")
    DB_USER=$(awk -F"'" '/\$usernamedb = / {print $2}' "$CONFIG_PATH")

    # Debugging variables
    echo "ROOT_USER: $ROOT_USER" > /tmp/remove_bot_debug.log
    echo "ROOT_PASS: $ROOT_PASS" >> /tmp/remove_bot_debug.log
    echo "DB_NAME: $DB_NAME" >> /tmp/remove_bot_debug.log
    echo "DB_USER: $DB_USER" >> /tmp/remove_bot_debug.log

    # Delete database
    echo -e "\033[33mRemoving database $DB_NAME...\033[0m"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;" 2>/tmp/db_remove_error.log
    if [ $? -eq 0 ]; then
        echo -e "\033[32mDatabase $DB_NAME removed successfully.\033[0m"
    else
        echo -e "\033[31mFailed to remove database $DB_NAME.\033[0m"
        cat /tmp/db_remove_error.log >> /tmp/remove_bot_debug.log
    fi

    # Delete user
    echo -e "\033[33mRemoving user $DB_USER...\033[0m"
    mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "DROP USER IF EXISTS '$DB_USER'@'localhost';" 2>/tmp/user_remove_error.log
    if [ $? -eq 0 ]; then
        echo -e "\033[32mUser $DB_USER removed successfully.\033[0m"
    else
        echo -e "\033[31mFailed to remove user $DB_USER.\033[0m"
        cat /tmp/user_remove_error.log >> /tmp/remove_bot_debug.log
    fi

    # Remove bot directory
    echo -e "\033[33mRemoving bot directory $BOT_PATH...\033[0m"
    if ! rm -rf "$BOT_PATH"; then
        echo -e "\033[31mFailed to remove bot directory.\033[0m"
        return 1
    fi

    # Remove Apache configuration
    APACHE_CONF="/etc/apache2/sites-available/$DOMAIN_NAME.conf"
    if [ -f "$APACHE_CONF" ]; then
        echo -e "\033[33mRemoving Apache configuration for $DOMAIN_NAME...\033[0m"
        sudo a2dissite "$DOMAIN_NAME.conf"
        rm -f "$APACHE_CONF"
        rm -f "/etc/apache2/sites-enabled/$DOMAIN_NAME.conf"
        sudo systemctl reload apache2
    else
        echo -e "\033[31mApache configuration for $DOMAIN_NAME not found.\033[0m"
    fi

    echo -e "\033[32m$SELECTED_BOT has been successfully removed.\033[0m"
}

    #Function to export additional bot database
function export_additional_bot_database() {
    clear
    echo -e "\033[36mAvailable Bots:\033[0m"

    # List all directories in /var/www/html excluding mirzabotconfig
    BOT_DIRS=$(ls -d /var/www/html/*/ 2>/dev/null | grep -v "/var/www/html/mirzabotconfig" | xargs -n 1 basename)

    # Check if there are no additional bots available
    if [ -z "$BOT_DIRS" ]; then
        echo -e "\033[31mNo additional bots found in /var/www/html.\033[0m"
        return 1
    fi

    # Display the list of bot directories with numbering
    echo "$BOT_DIRS" | nl -w 2 -s ") "

    # Prompt the user to select a bot by entering its name
    echo -ne "\033[36mEnter the bot name: \033[0m"
    read SELECTED_BOT

    # Verify the selected bot exists in the list
    if [[ ! "$BOT_DIRS" =~ (^|[[:space:]])$SELECTED_BOT($|[[:space:]]) ]]; then
        echo -e "\033[31mInvalid bot name.\033[0m"
        return 1
    fi

    BOT_PATH="/var/www/html/$SELECTED_BOT"  # Define the bot's directory path
    CONFIG_PATH="$BOT_PATH/config.php"      # Define the config.php file path

    # Check if the config.php file exists for the selected bot
    if [ ! -f "$CONFIG_PATH" ]; then
        echo -e "\033[31mconfig.php not found for $SELECTED_BOT.\033[0m"
        return 1
    fi

    # Check for root credentials file
    ROOT_CREDENTIALS_FILE="/root/confmirza/dbrootmirza.txt"
    if [ -f "$ROOT_CREDENTIALS_FILE" ]; then
        ROOT_USER=$(grep '\$user =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
        ROOT_PASS=$(grep '\$pass =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
    else
        echo -e "\033[31mRoot credentials file not found.\033[0m"
        echo -ne "\033[36mEnter MySQL root password: \033[0m"
        read -s ROOT_PASS
        echo

        if [ -z "$ROOT_PASS" ]; then
            echo -e "\033[31mPassword cannot be empty. Exiting...\033[0m"
            return 1
        fi

        ROOT_USER="root"

        # Verify root credentials
        echo "SELECT 1" | mysql -u "$ROOT_USER" -p"$ROOT_PASS" 2>/dev/null
        if [ $? -ne 0 ]; then
            echo -e "\033[31mInvalid root credentials. Exiting...\033[0m"
            return 1
        fi
    fi

    # Extract database credentials from the config.php file
    DB_USER=$(grep '^\$usernamedb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_PASS=$(grep '^\$passworddb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_NAME=$(grep '^\$dbname' "$CONFIG_PATH" | awk -F"'" '{print $2}')

    # Validate that all necessary credentials were extracted
    if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_NAME" ]; then
        echo -e "\033[31m[ERROR]\033[0m Failed to extract database credentials from $CONFIG_PATH."
        return 1
    fi

    # Check if the specified database exists and credentials are correct
    echo -e "\033[33mVerifying database existence...\033[0m"
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        echo -e "\033[31m[ERROR]\033[0m Database $DB_NAME does not exist or credentials are incorrect."
        return 1
    fi

    # Define the backup file path and create a backup of the database
    BACKUP_FILE="/root/${DB_NAME}_backup.sql"
    echo -e "\033[33mCreating backup at $BACKUP_FILE...\033[0m"
    if ! mysqldump -u "$ROOT_USER" -p"$ROOT_PASS" "$DB_NAME" > "$BACKUP_FILE"; then
        echo -e "\033[31m[ERROR]\033[0m Failed to create database backup."
        return 1
    fi

    # Confirm successful creation of the backup file
    echo -e "\033[32mBackup successfully created at $BACKUP_FILE.\033[0m"
}

#function to import additional bot database
function import_additional_bot_database() {
    clear
    echo -e "\033[36mStarting Import Database Process...\033[0m"

    # Check for root credentials file
    ROOT_CREDENTIALS_FILE="/root/confmirza/dbrootmirza.txt"
    if [ -f "$ROOT_CREDENTIALS_FILE" ]; then
        ROOT_USER=$(grep '\$user =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
        ROOT_PASS=$(grep '\$pass =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
    else
        echo -e "\033[31mRoot credentials file not found.\033[0m"
        echo -ne "\033[36mEnter MySQL root password: \033[0m"
        read -s ROOT_PASS
        echo

        if [ -z "$ROOT_PASS" ]; then
            echo -e "\033[31mPassword cannot be empty. Exiting...\033[0m"
            return 1
        fi

        ROOT_USER="root"

        # Verify root credentials
        echo "SELECT 1" | mysql -u "$ROOT_USER" -p"$ROOT_PASS" 2>/dev/null
        if [ $? -ne 0 ]; then
            echo -e "\033[31mInvalid root credentials. Exiting...\033[0m"
            return 1
        fi
    fi

    # List available .sql files in /root
    SQL_FILES=$(find /root -maxdepth 1 -type f -name "*.sql")
    if [ -z "$SQL_FILES" ]; then
        echo -e "\033[31mNo .sql files found in /root. Please provide a valid .sql file.\033[0m"
        return 1
    fi

    echo -e "\033[36mAvailable .sql files:\033[0m"
    echo "$SQL_FILES" | nl -w 2 -s ") "

    # Prompt the user to select or provide a file path
    echo -ne "\033[36mEnter the number of the file or provide a full path: \033[0m"
    read FILE_SELECTION

    if [[ "$FILE_SELECTION" =~ ^[0-9]+$ ]]; then
        SELECTED_FILE=$(echo "$SQL_FILES" | sed -n "${FILE_SELECTION}p")
    else
        SELECTED_FILE="$FILE_SELECTION"
    fi

    if [ ! -f "$SELECTED_FILE" ]; then
        echo -e "\033[31mSelected file does not exist. Exiting...\033[0m"
        return 1
    fi

    # List all available bots
    echo -e "\033[36mAvailable Bots:\033[0m"
    BOT_DIRS=$(ls -d /var/www/html/*/ 2>/dev/null | grep -v "/var/www/html/mirzabotconfig" | xargs -n 1 basename)

    if [ -z "$BOT_DIRS" ]; then
        echo -e "\033[31mNo additional bots found in /var/www/html.\033[0m"
        return 1
    fi

    echo "$BOT_DIRS" | nl -w 2 -s ") "

    # Prompt the user to select a bot
    echo -ne "\033[36mSelect a bot by name: \033[0m"
    read SELECTED_BOT

    if [[ ! "$BOT_DIRS" =~ (^|[[:space:]])$SELECTED_BOT($|[[:space:]]) ]]; then
        echo -e "\033[31mInvalid bot name.\033[0m"
        return 1
    fi

    BOT_PATH="/var/www/html/$SELECTED_BOT"  # Define the bot's directory path
    CONFIG_PATH="$BOT_PATH/config.php"      # Define the config.php file path

    # Check if the config.php file exists for the selected bot
    if [ ! -f "$CONFIG_PATH" ]; then
        echo -e "\033[31mconfig.php not found for $SELECTED_BOT.\033[0m"
        return 1
    fi

    # Extract database credentials from the config.php file
    DB_USER=$(grep '^\$usernamedb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_PASS=$(grep '^\$passworddb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_NAME=$(grep '^\$dbname' "$CONFIG_PATH" | awk -F"'" '{print $2}')

    # Validate that all necessary credentials were extracted
    if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_NAME" ]; then
        echo -e "\033[31m[ERROR]\033[0m Failed to extract database credentials from $CONFIG_PATH."
        return 1
    fi

    # Verify database existence
    echo -e "\033[33mVerifying database existence...\033[0m"
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        echo -e "\033[31m[ERROR]\033[0m Database $DB_NAME does not exist or credentials are incorrect."
        return 1
    fi

    # Import the selected .sql file into the selected bot's database
    echo -e "\033[33mImporting database from $SELECTED_FILE into $DB_NAME...\033[0m"
    if ! mysql -u "$ROOT_USER" -p"$ROOT_PASS" "$DB_NAME" < "$SELECTED_FILE"; then
        echo -e "\033[31m[ERROR]\033[0m Failed to import database."
        return 1
    fi

    echo -e "\033[32mDatabase successfully imported from $SELECTED_FILE into $DB_NAME.\033[0m"
}
#function to configure backup additional bot
function configure_backup_additional_bot() {
    clear
    echo -e "\033[36mConfiguring Automated Backup for Additional Bot...\033[0m"

    # List all available bots in /var/www/html excluding the main configuration directory
    echo -e "\033[36mAvailable Bots:\033[0m"
    BOT_DIRS=$(ls -d /var/www/html/*/ 2>/dev/null | grep -v "/var/www/html/mirzabotconfig" | xargs -n 1 basename)

    if [ -z "$BOT_DIRS" ]; then
        echo -e "\033[31mNo additional bots found in /var/www/html.\033[0m"
        return 1
    fi

    echo "$BOT_DIRS" | nl -w 2 -s ") "

    # Prompt user to select a bot
    echo -ne "\033[36mSelect a bot by name: \033[0m"
    read SELECTED_BOT

    if [[ ! "$BOT_DIRS" =~ (^|[[:space:]])$SELECTED_BOT($|[[:space:]]) ]]; then
        echo -e "\033[31mInvalid bot name.\033[0m"
        return 1
    fi

    BOT_PATH="/var/www/html/$SELECTED_BOT"
    CONFIG_PATH="$BOT_PATH/config.php"

    # Check if the config.php file exists
    if [ ! -f "$CONFIG_PATH" ]; then
        echo -e "\033[31mconfig.php not found for $SELECTED_BOT.\033[0m"
        return 1
    fi

    # Extract database and Telegram credentials from config.php
    DB_NAME=$(grep '^\$dbname' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_USER=$(grep '^\$usernamedb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    DB_PASS=$(grep '^\$passworddb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    TELEGRAM_TOKEN=$(grep '^\$APIKEY' "$CONFIG_PATH" | awk -F"'" '{print $2}')
    TELEGRAM_CHAT_ID=$(grep '^\$adminnumber' "$CONFIG_PATH" | awk -F"'" '{print $2}')

    if [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
        echo -e "\033[31m[ERROR]\033[0m Failed to extract database credentials from $CONFIG_PATH."
        return 1
    fi

    if [ -z "$TELEGRAM_TOKEN" ] || [ -z "$TELEGRAM_CHAT_ID" ]; then
        echo -e "\033[31m[ERROR]\033[0m Telegram token or chat ID not found in $CONFIG_PATH."
        return 1
    fi

    # Prompt user to select backup frequency
    while true; do
        echo -e "\033[36mChoose backup frequency:\033[0m"
        echo -e "\033[36m1) Every minute\033[0m"
        echo -e "\033[36m2) Every hour\033[0m"
        echo -e "\033[36m3) Every day\033[0m"
        read -p "Enter your choice (1-3): " frequency

        case $frequency in
            1) cron_time="* * * * *" ; break ;;
            2) cron_time="0 * * * *" ; break ;;
            3) cron_time="0 0 * * *" ; break ;;
            *)
                echo -e "\033[31mInvalid option. Please try again.\033[0m"
                ;;
        esac
    done

    # Create a backup script specific to the selected bot
    BACKUP_SCRIPT="/root/${SELECTED_BOT}_auto_backup.sh"
    cat <<EOF > "$BACKUP_SCRIPT"
#!/bin/bash

DB_NAME="$DB_NAME"
DB_USER="$DB_USER"
DB_PASS="$DB_PASS"
TELEGRAM_TOKEN="$TELEGRAM_TOKEN"
TELEGRAM_CHAT_ID="$TELEGRAM_CHAT_ID"

BACKUP_FILE="/root/\${DB_NAME}_\$(date +"%Y%m%d_%H%M%S").sql"
if mysqldump -u "\$DB_USER" -p"\$DB_PASS" "\$DB_NAME" > "\$BACKUP_FILE"; then
    curl -F document=@"\$BACKUP_FILE" "https://api.telegram.org/bot\$TELEGRAM_TOKEN/sendDocument" -F chat_id="\$TELEGRAM_CHAT_ID"
    rm "\$BACKUP_FILE"
else
    echo -e "\033[31m[ERROR]\033[0m Failed to create database backup."
fi
EOF

    # Grant execution permission to the backup script
    chmod +x "$BACKUP_SCRIPT"

    # Add a cron job to execute the backup script at the selected frequency
    (crontab -l 2>/dev/null; echo "$cron_time bash $BACKUP_SCRIPT") | crontab -

    echo -e "\033[32mAutomated backup configured successfully for $SELECTED_BOT.\033[0m"
}

# Main Execution
process_arguments() {
    local version=""
    case "$1" in
        -v*)
            version="${1#-v}"
            if [ -n "$version" ]; then
                install_bot "-v" "$version"
            else
                if [ -n "$2" ]; then
                    install_bot "-v" "$2"
                else
                    echo -e "\033[31m[ERROR]\033[0m Please specify a version with -v (e.g., -v 4.11.1)"
                    exit 1
                fi
            fi
            ;;
        -beta)
            install_bot "-beta"
            ;;
        --beta)  
            install_bot "-beta"
            ;;
        -update)
            update_bot "$2"
            ;;
        *)
            show_menu
            ;;
    esac
}

process_arguments "$1" "$2"
