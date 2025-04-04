#!/bin/bash

# Bestify Mode Installation Script
# For private repository access and management

# Colors for terminal output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Checking Root Access
if [[ $EUID -ne 0 ]]; then
    echo -e "${RED}[ERROR]${NC} Please run this script as ${BLUE}root${NC}."
    exit 1
fi

# Display Logo
function show_logo() {
    clear
    echo -e "${BLUE}"
    echo "========================================"
    echo "          BESTIFY MODE INSTALLER        "
    echo "========================================"
    echo -e "${NC}"
    echo ""
}

# Configuration variables
REPO_URL="git@github.com:username/bestify_mode.git"
INSTALL_DIR="/var/www/bestify_mode"
SSH_KEY_DIR="/root/.ssh"
SSH_KEY_FILE="${SSH_KEY_DIR}/id_rsa_bestify"
KNOWN_HOSTS_FILE="${SSH_KEY_DIR}/known_hosts"
CONFIG_FILE="${SSH_KEY_DIR}/config"

# Check dependencies
function check_dependencies() {
    echo -e "${BLUE}[INFO]${NC} Checking dependencies..."
    
    DEPS=(git curl wget unzip)
    MISSING=()
    
    for DEP in "${DEPS[@]}"; do
        if ! command -v $DEP &> /dev/null; then
            MISSING+=($DEP)
        fi
    done
    
    if [ ${#MISSING[@]} -ne 0 ]; then
        echo -e "${YELLOW}[WARNING]${NC} Missing dependencies: ${MISSING[*]}"
        echo -e "${BLUE}[INFO]${NC} Installing missing dependencies..."
        apt update
        apt install -y ${MISSING[*]}
    else
        echo -e "${GREEN}[SUCCESS]${NC} All dependencies are installed."
    fi
}

# Setup SSH key for private repository access
function setup_ssh_key() {
    echo -e "${BLUE}[INFO]${NC} Setting up SSH for private repository access..."
    
    # Create SSH directory if it doesn't exist
    mkdir -p $SSH_KEY_DIR
    chmod 700 $SSH_KEY_DIR
    
    # Check if SSH key already exists
    if [ ! -f "$SSH_KEY_FILE" ]; then
        echo -e "${YELLOW}[INPUT NEEDED]${NC} SSH key for repository access not found."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Do you want to:"
        echo "1) Generate a new SSH key"
        echo "2) Enter an existing private key"
        read -p "Select option [1-2]: " ssh_option
        
        case $ssh_option in
            1)
                echo -e "${BLUE}[INFO]${NC} Generating new SSH key for repository access..."
                ssh-keygen -t rsa -b 4096 -f $SSH_KEY_FILE -N ""
                echo -e "${GREEN}[SUCCESS]${NC} SSH key generated. Please add this public key to your GitHub repository:"
                cat "${SSH_KEY_FILE}.pub"
                echo ""
                echo -e "${YELLOW}[ACTION REQUIRED]${NC} After adding the key to GitHub, press Enter to continue..."
                read
                ;;
            2)
                echo -e "${YELLOW}[INPUT NEEDED]${NC} Please paste your private SSH key (press Ctrl+D when done):"
                cat > $SSH_KEY_FILE
                chmod 600 $SSH_KEY_FILE
                ;;
            *)
                echo -e "${RED}[ERROR]${NC} Invalid option. Exiting..."
                exit 1
                ;;
        esac
    else
        echo -e "${GREEN}[SUCCESS]${NC} SSH key already exists."
    fi
    
    # Set proper permissions
    chmod 600 $SSH_KEY_FILE
    
    # Add GitHub to known hosts if not already added
    if ! grep -q "github.com" $KNOWN_HOSTS_FILE 2>/dev/null; then
        ssh-keyscan -t rsa github.com >> $KNOWN_HOSTS_FILE
    fi
    
    # Create or update SSH config
    if [ ! -f "$CONFIG_FILE" ]; then
        echo "Host github.com" > $CONFIG_FILE
        echo "  IdentityFile $SSH_KEY_FILE" >> $CONFIG_FILE
        echo "  User git" >> $CONFIG_FILE
    else
        if ! grep -q "Host github.com" $CONFIG_FILE; then
            echo "" >> $CONFIG_FILE
            echo "Host github.com" >> $CONFIG_FILE
            echo "  IdentityFile $SSH_KEY_FILE" >> $CONFIG_FILE
            echo "  User git" >> $CONFIG_FILE
        fi
    fi
    
    chmod 600 $CONFIG_FILE
    
    echo -e "${GREEN}[SUCCESS]${NC} SSH configuration completed."
}

# Clone the repository
function clone_repository() {
    echo -e "${BLUE}[INFO]${NC} Cloning repository..."
    
    if [ -d "$INSTALL_DIR" ]; then
        echo -e "${YELLOW}[WARNING]${NC} Installation directory already exists."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Do you want to:"
        echo "1) Remove existing installation and reinstall"
        echo "2) Update existing installation"
        echo "3) Exit"
        read -p "Select option [1-3]: " clone_option
        
        case $clone_option in
            1)
                echo -e "${BLUE}[INFO]${NC} Removing existing installation..."
                rm -rf $INSTALL_DIR
                echo -e "${BLUE}[INFO]${NC} Cloning repository to $INSTALL_DIR..."
                GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git clone $REPO_URL $INSTALL_DIR
                ;;
            2)
                echo -e "${BLUE}[INFO]${NC} Updating existing installation..."
                cd $INSTALL_DIR
                GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git pull
                ;;
            3)
                echo -e "${YELLOW}[INFO]${NC} Exiting..."
                exit 0
                ;;
            *)
                echo -e "${RED}[ERROR]${NC} Invalid option. Exiting..."
                exit 1
                ;;
        esac
    else
        echo -e "${BLUE}[INFO]${NC} Cloning repository to $INSTALL_DIR..."
        GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git clone $REPO_URL $INSTALL_DIR
    fi
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}[SUCCESS]${NC} Repository cloned/updated successfully."
    else
        echo -e "${RED}[ERROR]${NC} Failed to clone/update repository. Please check your SSH key and repository access."
        exit 1
    fi
}

# List available releases
function list_releases() {
    cd $INSTALL_DIR
    echo -e "${BLUE}[INFO]${NC} Fetching available releases..."
    GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git fetch --tags
    
    echo -e "${BLUE}[INFO]${NC} Available releases:"
    git tag -l | sort -V
    
    echo ""
    echo -e "${BLUE}[INFO]${NC} Current version:"
    git describe --tags --abbrev=0
}

# Switch to a specific release
function switch_release() {
    cd $INSTALL_DIR
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter the release version to install (e.g., v1.0.0):"
    read release_version
    
    echo -e "${BLUE}[INFO]${NC} Switching to release $release_version..."
    GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git fetch --tags
    
    # Check if the release exists
    if git rev-parse "$release_version" >/dev/null 2>&1; then
        git checkout $release_version
        echo -e "${GREEN}[SUCCESS]${NC} Switched to release $release_version."
    else
        echo -e "${RED}[ERROR]${NC} Release $release_version not found."
        list_releases
        exit 1
    fi
}

# Install dependencies
function install_dependencies() {
    echo -e "${BLUE}[INFO]${NC} Installing required packages..."
    
    apt update
    
    # PHP and Apache
    apt install -y php php-fpm php-mysql lamp-server^ libapache2-mod-php mysql-server apache2 php-mbstring php-zip php-gd php-json php-curl
    
    # Enable and start services
    systemctl enable mysql.service
    systemctl start mysql.service
    systemctl enable apache2
    systemctl start apache2
    
    echo -e "${GREEN}[SUCCESS]${NC} Required packages installed."
}

# Configure the application
function configure_application() {
    echo -e "${BLUE}[INFO]${NC} Configuring application..."
    
    # Setup database
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter database name:"
    read db_name
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter database username:"
    read db_user
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter database password:"
    read -s db_pass
    echo ""
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS ${db_name};"
    mysql -e "CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${db_name}.* TO '${db_user}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Update config file
    if [ -f "$INSTALL_DIR/config.php" ]; then
        sed -i "s/\$dbname = \"databasename\"/\$dbname = \"$db_name\"/" $INSTALL_DIR/config.php
        sed -i "s/\$usernamedb = \"username\"/\$usernamedb = \"$db_user\"/" $INSTALL_DIR/config.php
        sed -i "s/\$passworddb = \"password\"/\$passworddb = \"$db_pass\"/" $INSTALL_DIR/config.php
        
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot token:"
        read bot_token
        sed -i "s/\$APIKEY = \"\*\*TOKEN\*\*\"/\$APIKEY = \"$bot_token\"/" $INSTALL_DIR/config.php
        
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter admin user ID:"
        read admin_id
        sed -i "s/\$adminnumber = \"5522424631\"/\$adminnumber = \"$admin_id\"/" $INSTALL_DIR/config.php
        
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter domain (e.g., example.com/bot):"
        read domain_host
        sed -i "s/\$domainhosts = \"domain.com\/bot\"/\$domainhosts = \"$domain_host\"/" $INSTALL_DIR/config.php
        
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot username (without @):"
        read bot_username
        sed -i "s/\$usernamebot = \"marzbaninfobot\"/\$usernamebot = \"$bot_username\"/" $INSTALL_DIR/config.php
    else
        echo -e "${RED}[ERROR]${NC} Config file not found. Installation may be incomplete."
        exit 1
    fi
    
    # Set permissions
    chown -R www-data:www-data $INSTALL_DIR
    chmod -R 755 $INSTALL_DIR
    
    echo -e "${GREEN}[SUCCESS]${NC} Application configured successfully."
}

# Configure cron jobs
function setup_cron() {
    echo -e "${BLUE}[INFO]${NC} Setting up cron jobs..."
    
    crontab -l > mycron || true
    
    # Check if the cron job already exists
    if ! grep -q "$INSTALL_DIR/cron/cronday.php" mycron; then
        echo "0 0 * * * php $INSTALL_DIR/cron/cronday.php" >> mycron
    fi
    
    if ! grep -q "$INSTALL_DIR/cron/cronvolume.php" mycron; then
        echo "*/10 * * * * php $INSTALL_DIR/cron/cronvolume.php" >> mycron
    fi
    
    if ! grep -q "$INSTALL_DIR/cron/removeexpire.php" mycron; then
        echo "*/10 * * * * php $INSTALL_DIR/cron/removeexpire.php" >> mycron
    fi
    
    if ! grep -q "$INSTALL_DIR/cron/sendmessage.php" mycron; then
        echo "*/10 * * * * php $INSTALL_DIR/cron/sendmessage.php" >> mycron
    fi
    
    crontab mycron
    rm mycron
    
    echo -e "${GREEN}[SUCCESS]${NC} Cron jobs set up."
}

# Set up auto-updates (optional)
function setup_auto_updates() {
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Do you want to set up automatic updates? (y/n)"
    read auto_update_choice
    
    if [[ $auto_update_choice == "y" || $auto_update_choice == "Y" ]]; then
        crontab -l > mycron || true
        
        # Check if the update cron job already exists
        if ! grep -q "$INSTALL_DIR/install.sh" mycron; then
            echo "0 3 * * 0 cd $INSTALL_DIR && bash install.sh -update" >> mycron
        fi
        
        crontab mycron
        rm mycron
        
        echo -e "${GREEN}[SUCCESS]${NC} Auto-updates scheduled for every Sunday at 3 AM."
    else
        echo -e "${BLUE}[INFO]${NC} Skipping auto-updates setup."
    fi
}

# Update function
function update_bestify() {
    cd $INSTALL_DIR
    
    echo -e "${BLUE}[INFO]${NC} Backing up configuration..."
    cp config.php config.php.bak
    
    echo -e "${BLUE}[INFO]${NC} Checking for updates..."
    GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git fetch --tags
    
    # Get current version
    current_version=$(git describe --tags --abbrev=0)
    echo -e "${BLUE}[INFO]${NC} Current version: $current_version"
    
    # Get latest version
    latest_version=$(GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git tag -l | sort -V | tail -n 1)
    echo -e "${BLUE}[INFO]${NC} Latest version: $latest_version"
    
    if [ "$current_version" != "$latest_version" ]; then
        echo -e "${BLUE}[INFO]${NC} Updating to version $latest_version..."
        GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git checkout $latest_version
        
        echo -e "${BLUE}[INFO]${NC} Restoring configuration..."
        cp config.php.bak config.php
        
        echo -e "${GREEN}[SUCCESS]${NC} Updated to version $latest_version."
    else
        echo -e "${GREEN}[SUCCESS]${NC} Already at the latest version."
    fi
}

# Remove function
function remove_bestify() {
    echo -e "${YELLOW}[WARNING]${NC} This will completely remove Bestify Mode from your server."
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Are you sure you want to proceed? (y/n)"
    read remove_choice
    
    if [[ $remove_choice == "y" || $remove_choice == "Y" ]]; then
        echo -e "${BLUE}[INFO]${NC} Removing Bestify Mode..."
        
        # Delete the installation directory
        rm -rf $INSTALL_DIR
        
        # Remove cron jobs
        crontab -l | grep -v "$INSTALL_DIR" > mycron
        crontab mycron
        rm mycron
        
        echo -e "${GREEN}[SUCCESS]${NC} Bestify Mode has been removed."
    else
        echo -e "${BLUE}[INFO]${NC} Removal cancelled."
    fi
}

# Main menu
function show_menu() {
    show_logo
    echo -e "${BLUE}1)${NC} Install Bestify Mode"
    echo -e "${BLUE}2)${NC} Update Bestify Mode"
    echo -e "${BLUE}3)${NC} Switch to specific release"
    echo -e "${BLUE}4)${NC} View available releases"
    echo -e "${BLUE}5)${NC} Remove Bestify Mode"
    echo -e "${BLUE}6)${NC} Exit"
    echo ""
    read -p "Select an option [1-6]: " option
    
    case $option in
        1)
            check_dependencies
            setup_ssh_key
            clone_repository
            install_dependencies
            configure_application
            setup_cron
            setup_auto_updates
            echo -e "${GREEN}[SUCCESS]${NC} Bestify Mode installed successfully!"
            ;;
        2)
            check_dependencies
            setup_ssh_key
            update_bestify
            ;;
        3)
            check_dependencies
            setup_ssh_key
            list_releases
            switch_release
            ;;
        4)
            check_dependencies
            setup_ssh_key
            list_releases
            ;;
        5)
            remove_bestify
            ;;
        6)
            echo -e "${GREEN}Exiting...${NC}"
            exit 0
            ;;
        *)
            echo -e "${RED}[ERROR]${NC} Invalid option. Please try again."
            show_menu
            ;;
    esac
}

# Process command line arguments
if [[ "$1" == "-update" ]]; then
    check_dependencies
    setup_ssh_key
    update_bestify
    exit 0
elif [[ "$1" == "-version" ]]; then
    check_dependencies
    setup_ssh_key
    list_releases
    exit 0
else
    show_menu
fi

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
    ZIP_URL=$(curl -s https://api.github.com/repos/mahdiMGF2/botmirzapanel/releases/latest | grep "zipball_url" | cut -d '"' -f 4)

# Check for version flag
if [[ "$1" == "-v" && "$2" == "beta" ]] || [[ "$1" == "-beta" ]] || [[ "$1" == "-" && "$2" == "beta" ]]; then
    ZIP_URL="https://github.com/mahdiMGF2/botmirzapanel/archive/refs/heads/main.zip"
elif [[ "$1" == "-v" && -n "$2" ]]; then
    ZIP_URL="https://github.com/mahdiMGF2/botmirzapanel/archive/refs/tags/$2.zip"
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
    ZIP_URL=$(curl -s https://api.github.com/repos/mahdiMGF2/botmirzapanel/releases/latest | grep "zipball_url" | cut -d '"' -f 4)
    if [[ "$1" == "-v" && "$2" == "beta" ]] || [[ "$1" == "-beta" ]] || [[ "$1" == "-" && "$2" == "beta" ]]; then
        ZIP_URL="https://github.com/mahdiMGF2/botmirzapanel/archive/refs/heads/main.zip"
    elif [[ "$1" == "-v" && -n "$2" ]]; then
        ZIP_URL="https://github.com/mahdiMGF2/botmirzapanel/archive/refs/tags/$2.zip"
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
    
    # Set permissions
    chown -R www-data:www-data $INSTALL_DIR
    chmod -R 755 $INSTALL_DIR
    
    # Run setup script
    URL=$(grep '\$domainhosts' "$CONFIG_PATH" | cut -d"'" -f2)
    curl -s "https://$URL/table.php" || {
        echo -e "\e[91mSetup script execution failed!\033[0m"
    }
    
    # Cleanup
    rm -rf "$TEMP_DIR"
    
    echo -e "\n\e[92mMirza Bot updated to latest version successfully!\033[0m"
}

    # Remove user if DB_USER is available
    if [ -n "$DB_USER" ]; then
        echo -e "\e[33mRemoving database user $DB_USER...\033[0m" | tee -a "$LOG_FILE"
        docker exec "$MYSQL_CONTAINER" bash -c "mysql -u '$ROOT_USER' -p'$MYSQL_ROOT_PASSWORD' -e \"DROP USER IF EXISTS '$DB_USER'@'%'; FLUSH PRIVILEGES;\"" && {
            echo -e "\e[92mUser $DB_USER removed successfully.\033[0m" | tee -a "$LOG_FILE"
        } || {
            echo -e "\e[91mFailed to remove user $DB_USER.\033[0m" | tee -a "$LOG_FILE"
        }
    else
        echo -e "\e[93mWarning: No database user specified. Checking for non-default users...\033[0m" | tee -a "$LOG_FILE"
        # Check for non-default users
        MIRZA_USERS=$(docker exec "$MYSQL_CONTAINER" bash -c "mysql -u '$ROOT_USER' -p'$MYSQL_ROOT_PASSWORD' -e \"SELECT User FROM mysql.user WHERE User NOT IN ('root', 'mysql.infoschema', 'mysql.session', 'mysql.sys', 'marzban');\"" | grep -v "User" | awk '{print $1}')
        if [ -n "$MIRZA_USERS" ]; then
            for user in $MIRZA_USERS; do
                echo -e "\e[33mRemoving detected non-default user: $user...\033[0m" | tee -a "$LOG_FILE"
                docker exec "$MYSQL_CONTAINER" bash -c "mysql -u '$ROOT_USER' -p'$MYSQL_ROOT_PASSWORD' -e \"DROP USER IF EXISTS '$user'@'%'; FLUSH PRIVILEGES;\"" && {
                    echo -e "\e[92mUser $user removed successfully.\033[0m" | tee -a "$LOG_FILE"
                } || {
                    echo -e "\e[91mFailed to remove user $user.\033[0m" | tee -a "$LOG_FILE"
                }
            done
        else
            echo -e "\e[93mNo non-default users found.\033[0m" | tee -a "$LOG_FILE"
        fi
    fi

    # Remove Apache
    echo -e "\e[33mRemoving Apache...\033[0m" | tee -a "$LOG_FILE"
    sudo systemctl stop apache2 || {
        echo -e "\e[91mFailed to stop Apache. Continuing anyway...\033[0m" | tee -a "$LOG_FILE"
    }
    sudo systemctl disable apache2 || {
        echo -e "\e[91mFailed to disable Apache. Continuing anyway...\033[0m" | tee -a "$LOG_FILE"
    }
    sudo apt-get purge -y apache2 apache2-utils apache2-bin apache2-data libapache2-mod-php* || {
        echo -e "\e[91mFailed to purge Apache packages.\033[0m" | tee -a "$LOG_FILE"
    }
    sudo apt-get autoremove --purge -y
    sudo apt-get autoclean -y
    sudo rm -rf /etc/apache2 /var/www/html

    # Reset Firewall (only remove Apache rule, keep SSL)
    echo -e "\e[33mResetting firewall rules (keeping SSL)...\033[0m" | tee -a "$LOG_FILE"
    sudo ufw delete allow 'Apache' || {
        echo -e "\e[91mFailed to remove Apache rule from UFW.\033[0m" | tee -a "$LOG_FILE"
    }
    sudo ufw reload

    echo -e "\e[92mMirza Bot has been removed alongside Marzban. SSL certificates remain intact.\033[0m" | tee -a "$LOG_FILE"
}

# Extract database credentials from config.php
function extract_db_credentials() {
    CONFIG_PATH="/var/www/html/mirzabotconfig/config.php"
    if [ -f "$CONFIG_PATH" ]; then
        DB_USER=$(grep '^\$usernamedb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
        DB_PASS=$(grep '^\$passworddb' "$CONFIG_PATH" | awk -F"'" '{print $2}')
        DB_NAME=$(grep '^\$dbname' "$CONFIG_PATH" | awk -F"'" '{print $2}')
        TELEGRAM_TOKEN=$(grep '^\$APIKEY' "$CONFIG_PATH" | awk -F"'" '{print $2}')
        TELEGRAM_CHAT_ID=$(grep '^\$adminnumber' "$CONFIG_PATH" | awk -F"'" '{print $2}')
        if [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_NAME" ] || [ -z "$TELEGRAM_TOKEN" ] || [ -z "$TELEGRAM_CHAT_ID" ]; then
            echo -e "\033[31m[ERROR]\033[0m Failed to extract required credentials from $CONFIG_PATH."
            return 1
        fi
        return 0
    else
        echo -e "\033[31m[ERROR]\033[0m config.php not found at $CONFIG_PATH."
        return 1
    fi
}

# Translate cron schedule to human-readable format
function translate_cron() {
    local cron_line="$1"
    local schedule=""
    case "$cron_line" in
        "* * * * *"*) schedule="Every Minute" ;;
        "0 * * * *"*) schedule="Every Hour" ;;
        "0 0 * * *"*) schedule="Every Day" ;;
        "0 0 * * 0"*) schedule="Every Week" ;;
        *) schedule="Custom Schedule ($cron_line)" ;;
    esac
    echo "$schedule"
}

# Export Database Function
function export_database() {
    echo -e "\033[33mChecking database configuration...\033[0m"

    if ! extract_db_credentials; then
        return 1
    fi

    # Check if Marzban is installed
    if check_marzban_installed; then
        echo -e "\033[31m[ERROR]\033[0m Exporting database is not supported when Marzban is installed due to database being managed by Docker."
        return 1
    fi

    echo -e "\033[33mVerifying database existence...\033[0m"

    if ! mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        echo -e "\033[31m[ERROR]\033[0m Database $DB_NAME does not exist or credentials are incorrect."
        return 1
    fi

    BACKUP_FILE="/root/${DB_NAME}_backup.sql"
    echo -e "\033[33mCreating backup at $BACKUP_FILE...\033[0m"

    if ! mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"; then
        echo -e "\033[31m[ERROR]\033[0m Failed to create database backup."
        return 1
    fi

    echo -e "\033[32mBackup successfully created at $BACKUP_FILE.\033[0m"
}
# Import Database Function
function import_database() {
    echo -e "\033[33mChecking database configuration...\033[0m"

    if ! extract_db_credentials; then
        return 1
    fi

    # Check if Marzban is installed
    if check_marzban_installed; then
        echo -e "\033[31m[ERROR]\033[0m Importing database is not supported when Marzban is installed due to database being managed by Docker."
        return 1
    fi

    echo -e "\033[33mVerifying database existence...\033[0m"

    if ! mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
        echo -e "\033[31m[ERROR]\033[0m Database $DB_NAME does not exist or credentials are incorrect."
        return 1
    fi

    while true; do
        read -p "Enter the path to the backup file [default: /root/${DB_NAME}_backup.sql]: " BACKUP_FILE
        BACKUP_FILE=${BACKUP_FILE:-/root/${DB_NAME}_backup.sql}

        if [[ -f "$BACKUP_FILE" && "$BACKUP_FILE" =~ \.sql$ ]]; then
            break
        else
            echo -e "\033[31m[ERROR]\033[0m Invalid file path or format. Please provide a valid .sql file."
        fi
    done

    echo -e "\033[33mImporting backup from $BACKUP_FILE...\033[0m"

    if ! mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$BACKUP_FILE"; then
        echo -e "\033[31m[ERROR]\033[0m Failed to import database from backup file."
        return 1
    fi

    echo -e "\033[32mDatabase successfully imported from $BACKUP_FILE.\033[0m"
}

# Function for automated backup
function auto_backup() {
    echo -e "\033[36mConfigure Automated Backup\033[0m"

    # Check if Mirza Bot is installed
    BOT_DIR="/var/www/html/mirzabotconfig"
    if [ ! -d "$BOT_DIR" ]; then
        echo -e "\033[31m[ERROR]\033[0m Mirza Bot is not installed ($BOT_DIR not found)."
        echo -e "\033[33mExiting...\033[0m"
        sleep 2
        return 1
    fi

    # Extract credentials
    if ! extract_db_credentials; then
        return 1
    fi

    # Determine backup script based on Marzban presence
    if check_marzban_installed; then
        echo -e "\033[41m[NOTICE]\033[0m \033[33mMarzban detected. Using Marzban-compatible backup.\033[0m"
        BACKUP_SCRIPT="/root/backup_mirza_marzban.sh"
        MYSQL_CONTAINER=$(docker ps -q --filter "name=mysql" --no-trunc)
        if [ -z "$MYSQL_CONTAINER" ]; then
            echo -e "\033[31m[ERROR]\033[0m No running MySQL container found for Marzban."
            return 1
        fi
        # Create Marzban backup script
        cat <<EOF > "$BACKUP_SCRIPT"
#!/bin/bash
BACKUP_FILE="/root/\${DB_NAME}_\$(date +\"%Y%m%d_%H%M%S\").sql"
docker exec $MYSQL_CONTAINER mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "\$BACKUP_FILE"
if [ \$? -eq 0 ]; then
    curl -F document=@"\$BACKUP_FILE" "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendDocument" -F chat_id="$TELEGRAM_CHAT_ID"
    rm "\$BACKUP_FILE"
else
    echo -e "\033[31m[ERROR]\033[0m Failed to create Marzban database backup."
fi
EOF
    else
        echo -e "\033[33mUsing standard backup.\033[0m"
        BACKUP_SCRIPT="/root/mirza_backup.sh"
        # Verify database existence
        if ! mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME;" 2>/dev/null; then
            echo -e "\033[31m[ERROR]\033[0m Database $DB_NAME does not exist or credentials are incorrect."
            return 1
        fi
        # Create standard backup script
        cat <<EOF > "$BACKUP_SCRIPT"
#!/bin/bash
BACKUP_FILE="/root/\${DB_NAME}_\$(date +\"%Y%m%d_%H%M%S\").sql"
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "\$BACKUP_FILE"
if [ \$? -eq 0 ]; then
    curl -F document=@"\$BACKUP_FILE" "https://api.telegram.org/bot$TELEGRAM_TOKEN/sendDocument" -F chat_id="$TELEGRAM_CHAT_ID"
    rm "\$BACKUP_FILE"
else
    echo -e "\033[31m[ERROR]\033[0m Failed to create database backup."
fi
EOF
    fi

    # Make the script executable
    chmod +x "$BACKUP_SCRIPT"

    # Check current cron and translate it
    CURRENT_CRON=$(crontab -l 2>/dev/null | grep "$BACKUP_SCRIPT" | grep -v "^#")
    if [ -n "$CURRENT_CRON" ]; then
        SCHEDULE=$(translate_cron "$CURRENT_CRON")
        echo -e "\033[33mCurrent Backup Schedule:\033[0m $SCHEDULE"
    else
        echo -e "\033[33mNo active backup schedule found.\033[0m"
    fi

    # Show backup frequency options
    echo -e "\033[36m1) Every Minute\033[0m"
    echo -e "\033[36m2) Every Hour\033[0m"
    echo -e "\033[36m3) Every Day\033[0m"
    echo -e "\033[36m4) Every Week\033[0m"
    echo -e "\033[36m5) Disable Backup\033[0m"
    echo -e "\033[36m6) Back to Menu\033[0m"
    echo ""
    read -p "Select an option [1-6]: " backup_option

    # Function to update cron
    update_cron() {
        local cron_line="$1"
        if [ -n "$CURRENT_CRON" ]; then
            crontab -l 2>/dev/null | grep -v "$BACKUP_SCRIPT" | crontab - && {
                echo -e "\033[92mRemoved previous backup schedule.\033[0m"
            } || {
                echo -e "\033[31mFailed to remove existing cron.\033[0m"
            }
        fi
        if [ -n "$cron_line" ]; then
            (crontab -l 2>/dev/null; echo "$cron_line") | crontab - && {
                echo -e "\033[92mBackup scheduled: $(translate_cron "$cron_line")\033[0m"
                bash "$BACKUP_SCRIPT" &>/dev/null &
            } || {
                echo -e "\033[31mFailed to schedule backup.\033[0m"
            }
        fi
    }

    # Process user choice
    case $backup_option in
        1) update_cron "* * * * * bash $BACKUP_SCRIPT" ;;
        2) update_cron "0 * * * * bash $BACKUP_SCRIPT" ;;
        3) update_cron "0 0 * * * bash $BACKUP_SCRIPT" ;;
        4) update_cron "0 0 * * 0 bash $BACKUP_SCRIPT" ;;
        5)
            if [ -n "$CURRENT_CRON" ]; then
                crontab -l 2>/dev/null | grep -v "$BACKUP_SCRIPT" | crontab - && {
                    echo -e "\033[92mAutomated backup disabled.\033[0m"
                } || {
                    echo -e "\033[31mFailed to disable backup.\033[0m"
                }
            else
                echo -e "\033[93mNo backup schedule to disable.\033[0m"
            fi
            ;;
        6) show_menu ;;
        *)
            echo -e "\033[31mInvalid option. Please try again.\033[0m"
            auto_backup
            ;;
    esac
}

# Function to renew SSL certificates
function renew_ssl() {
    echo -e "\033[33mStarting SSL renewal process...\033[0m"

    if ! command -v certbot &>/dev/null; then
        echo -e "\033[31m[ERROR]\033[0m Certbot is not installed. Please install Certbot to proceed."
        return 1
    fi

    # Stop Apache to free port 80
    echo -e "\033[33mStopping Apache...\033[0m"
    sudo systemctl stop apache2 || {
        echo -e "\033[31m[ERROR]\033[0m Failed to stop Apache. Exiting..."
        return 1
    }

    # Renew SSL certificates
    if sudo certbot renew; then
        echo -e "\033[32mSSL certificates successfully renewed.\033[0m"
    else
        echo -e "\033[31m[ERROR]\033[0m SSL renewal failed. Please check Certbot logs for more details."
        # Restart Apache even if renewal failed
        sudo systemctl start apache2
        return 1
    fi

    # Restart Apache
    echo -e "\033[33mRestarting Apache...\033[0m"
    sudo systemctl restart apache2 || {
        echo -e "\033[31m[WARNING]\033[0m Failed to restart Apache. Please check manually."
    }
}
# Function to Manage Additional Bots
function manage_additional_bots() {
    # Check if Mirza main bot is installed
    if [ ! -d "/var/www/html/mirzabotconfig" ]; then
        echo -e "\033[31m[ERROR]\033[0m The main Mirza Bot is not installed (/var/www/html/mirzabotconfig not found)."
        echo -e "\033[33mYou are not allowed to use this section without the main bot installed. Exiting...\033[0m"
        sleep 2
        exit 1
    fi

    # Check if Marzban is installed
    if check_marzban_installed; then
        echo -e "\033[31m[ERROR]\033[0m Additional bot management is not available when Marzban is installed."
        echo -e "\033[33mExiting script...\033[0m"
        sleep 2
        exit 1
    fi

    # If both checks pass, proceed with the menu
    echo -e "\033[36m1) Install Additional Bot\033[0m"
    echo -e "\033[36m2) Update Additional Bot\033[0m"
    echo -e "\033[36m3) Remove Additional Bot\033[0m"
    echo -e "\033[36m4) Export Additional Bot Database\033[0m"
    echo -e "\033[36m5) Import Additional Bot Database\033[0m"
    echo -e "\033[36m6) Configure Automated Backup for Additional Bot\033[0m"
    echo -e "\033[36m7) Back to Main Menu\033[0m"
    echo ""
    read -p "Select an option [1-7]: " sub_option
    case $sub_option in
        1) install_additional_bot ;;
        2) update_additional_bot ;;
        3) remove_additional_bot ;;
        4) export_additional_bot_database ;;
        5) import_additional_bot_database ;;
        6) configure_backup_additional_bot ;;
        7) show_menu ;;
        *)
            echo -e "\033[31mInvalid option. Please try again.\033[0m"
            manage_additional_bots
            ;;
    esac
}
function change_domain() {
    local new_domain
    while [[ ! "$new_domain" =~ ^[a-zA-Z0-9.-]+$ ]]; do
        read -p "Enter new domain: " new_domain
        [[ ! "$new_domain" =~ ^[a-zA-Z0-9.-]+$ ]] && echo -e "\033[31mInvalid domain format\033[0m"
    done

    echo -e "\033[33mStopping Apache to configure SSL...\033[0m"
    if ! sudo systemctl stop apache2; then
        echo -e "\033[31m[ERROR] Failed to stop Apache!\033[0m"
        return 1
    fi

    echo -e "\033[33mConfiguring SSL for new domain...\033[0m"
    if ! sudo certbot --apache --redirect --agree-tos --preferred-challenges http -d "$new_domain"; then
        echo -e "\033[31m[ERROR] SSL configuration failed!\033[0m"
        echo -e "\033[33mCleaning up...\033[0m"
        sudo certbot delete --cert-name "$new_domain" 2>/dev/null
        echo -e "\033[33mRestarting Apache after cleanup...\033[0m"
        sudo systemctl start apache2 || echo -e "\033[31m[ERROR] Failed to restart Apache!\033[0m"
        return 1
    fi

    echo -e "\033[33mRestarting Apache after SSL configuration...\033[0m"
    if ! sudo systemctl start apache2; then
        echo -e "\033[31m[ERROR] Failed to restart Apache!\033[0m"
        return 1
    fi

    CONFIG_FILE="/var/www/html/mirzabotconfig/config.php"
    if [ -f "$CONFIG_FILE" ]; then
        sudo cp "$CONFIG_FILE" "$CONFIG_FILE.$(date +%s).bak"

        sudo sed -i "s/\$domainhosts = '.*\/mirzabotconfig';/\$domainhosts = '${new_domain}\/mirzabotconfig';/" "$CONFIG_FILE"

        NEW_SECRET=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9')
        sudo sed -i "s/\$secrettoken = '.*';/\$secrettoken = '${NEW_SECRET%%}';/" "$CONFIG_FILE"
        
        BOT_TOKEN=$(awk -F"'" '/\$APIKEY/{print $2}' "$CONFIG_FILE")
        curl -s -o /dev/null -F "url=https://${new_domain}/mirzabotconfig/index.php" \
             -F "secret_token=${NEW_SECRET}" \
             "https://api.telegram.org/bot${BOT_TOKEN}/setWebhook" || {
            echo -e "\033[33m[WARNING] Webhook update failed\033[0m"
        }
    else
        echo -e "\033[31m[CRITICAL] Config file missing!\033[0m"
        return 1
    fi

    if curl -sI "https://${new_domain}" | grep -q "200 OK"; then
        echo -e "\033[32mDomain successfully migrated to ${new_domain}\033[0m"
        echo -e "\033[33mOld domain configuration has been automatically cleaned up\033[0m"
    else
        echo -e "\033[31m[WARNING] Final verification failed!\033[0m"
        echo -e "\033[33mPlease check:\033[0m"
        echo -e "1. DNS settings for ${new_domain}"
        echo -e "2. Apache virtual host configuration"
        echo -e "3. Firewall settings"
        return 1
    fi
}
# Added Function for Installing Additional Bot
function install_additional_bot() {
    clear
    echo -e "\033[33mStarting Additional Bot Installation...\033[0m"

    # Check for root credentials file
    ROOT_CREDENTIALS_FILE="/root/confmirza/dbrootmirza.txt"
    if [[ ! -f "$ROOT_CREDENTIALS_FILE" ]]; then
        echo -e "\033[31mError: Root credentials file not found at $ROOT_CREDENTIALS_FILE.\033[0m"
        echo -ne "\033[36mPlease enter the root MySQL password: \033[0m"
        read -s ROOT_PASS
        echo
        ROOT_USER="root"
    else
        ROOT_USER=$(grep '\$user =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
        ROOT_PASS=$(grep '\$pass =' "$ROOT_CREDENTIALS_FILE" | awk -F"'" '{print $2}')
        if [[ -z "$ROOT_USER" || -z "$ROOT_PASS" ]]; then
            echo -e "\033[31mError: Could not extract root credentials from file.\033[0m"
            return 1
        fi
    fi

    # Request Domain Name
    while true; do
        echo -ne "\033[36mEnter the domain for the additional bot: \033[0m"
        read DOMAIN_NAME
        if [[ "$DOMAIN_NAME" =~ ^[a-zA-Z0-9.-]+$ ]]; then
            break
        else
            echo -e "\033[31mInvalid domain format. Please try again.\033[0m"
        fi
    done

    # Stop Apache to free port 80
    echo -e "\033[33mStopping Apache to free port 80...\033[0m"
    sudo systemctl stop apache2

    # Obtain SSL Certificate
    echo -e "\033[33mObtaining SSL certificate...\033[0m"
    sudo certbot certonly --standalone --agree-tos --preferred-challenges http -d "$DOMAIN_NAME" || {
        echo -e "\033[31mError obtaining SSL certificate.\033[0m"
        return 1
    }

    # Restart Apache
    echo -e "\033[33mRestarting Apache...\033[0m"
    sudo systemctl start apache2

    # Configure Apache for new domain
    APACHE_CONFIG="/etc/apache2/sites-available/$DOMAIN_NAME.conf"
    if [[ -f "$APACHE_CONFIG" ]]; then
        echo -e "\033[31mApache configuration for this domain already exists.\033[0m"
        return 1
    fi

    echo -e "\033[33mConfiguring Apache for domain...\033[0m"
    sudo bash -c "cat > $APACHE_CONFIG <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    Redirect permanent / https://$DOMAIN_NAME/
</VirtualHost>

<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/html/$BOT_NAME

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
</VirtualHost>
EOF"

    sudo mkdir -p "/var/www/html/$BOT_NAME"
    sudo a2ensite "$DOMAIN_NAME.conf"
    sudo systemctl reload apache2

    # Request Bot Name
    while true; do
        echo -ne "\033[36mEnter the bot name: \033[0m"
        read BOT_NAME
        if [[ "$BOT_NAME" =~ ^[a-zA-Z0-9_-]+$ && ! -d "/var/www/html/$BOT_NAME" ]]; then
            break
        else
            echo -e "\033[31mInvalid or duplicate bot name. Please try again.\033[0m"
        fi
    done

    # Clone a Fresh Copy of the Bot's Source Code
    BOT_DIR="/var/www/html/$BOT_NAME"
    echo -e "\033[33mCloning bot's source code...\033[0m"
    git clone https://github.com/mahdiMGF2/botmirzapanel.git "$BOT_DIR" || {
        echo -e "\033[31mError: Failed to clone the repository.\033[0m"
        return 1
    }

    # Request Bot Token
    while true; do
        echo -ne "\033[36mEnter the bot token: \033[0m"
        read BOT_TOKEN
        if [[ "$BOT_TOKEN" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; then
            break
        else
            echo -e "\033[31mInvalid bot token format. Please try again.\033[0m"
        fi
    done

    # Request Chat ID
    while true; do
        echo -ne "\033[36mEnter the chat ID: \033[0m"
        read CHAT_ID
        if [[ "$CHAT_ID" =~ ^-?[0-9]+$ ]]; then
            break
        else
            echo -e "\033[31mInvalid chat ID format. Please try again.\033[0m"
        fi
    done

    # Configure Database
    DB_NAME="mirzabot_$BOT_NAME"
    DB_USERNAME="$DB_NAME"
    DEFAULT_PASSWORD=$(openssl rand -base64 10 | tr -dc 'a-zA-Z0-9' | cut -c1-8)
    echo -ne "\033[36mEnter the database password (default: $DEFAULT_PASSWORD): \033[0m"
    read DB_PASSWORD
    DB_PASSWORD=${DB_PASSWORD:-$DEFAULT_PASSWORD}

    echo -e "\033[33mCreating database and user...\033[0m"
    sudo mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE DATABASE $DB_NAME;" || {
        echo -e "\033[31mError: Failed to create database.\033[0m"
        return 1
    }
    sudo mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "CREATE USER '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD';" || {
        echo -e "\033[31mError: Failed to create database user.\033[0m"
        return 1
    }
    sudo mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USERNAME'@'localhost';" || {
        echo -e "\033[31mError: Failed to grant privileges to user.\033[0m"
        return 1
    }
    sudo mysql -u "$ROOT_USER" -p"$ROOT_PASS" -e "FLUSH PRIVILEGES;"

    # Configure the Bot
    CONFIG_FILE="$BOT_DIR/config.php"
    echo -e "\033[33mSaving bot configuration...\033[0m"
    cat <<EOF > "$CONFIG_FILE"
<?php
\$APIKEY = '$BOT_TOKEN';
\$usernamedb = '$DB_USERNAME';
\$passworddb = '$DB_PASSWORD';
\$dbname = '$DB_NAME';
\$domainhosts = '$DOMAIN_NAME/$BOT_NAME';
\$adminnumber = '$CHAT_ID';
\$usernamebot = '$BOT_NAME';
\$connect = mysqli_connect('localhost', \$usernamedb, \$passworddb, \$dbname);
if (\$connect->connect_error) {
    die('Database connection failed: ' . \$connect->connect_error);
}
mysqli_set_charset(\$connect, 'utf8mb4');
\$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
\$dsn = "mysql:host=localhost;dbname=\$dbname;charset=utf8mb4";
try {
     \$pdo = new PDO(\$dsn, \$usernamedb, \$passworddb, \$options);
} catch (\PDOException \$e) {
     throw new \PDOException(\$e->getMessage(), (int)\$e->getCode());
}
?>
EOF

    sleep 1

    sudo chown -R www-data:www-data "$BOT_DIR"
    sudo chmod -R 755 "$BOT_DIR"

    # Set Webhook
    echo -e "\033[33mSetting webhook for bot...\033[0m"
    curl -F "url=https://$DOMAIN_NAME/$BOT_NAME/index.php" "https://api.telegram.org/bot$BOT_TOKEN/setWebhook" || {
        echo -e "\033[31mError: Failed to set webhook for bot.\033[0m"
        return 1
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
    if ! git clone https://github.com/mahdiMGF2/botmirzapanel.git "$BOT_PATH"; then
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
