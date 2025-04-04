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
REPO_URL="git@github.com:itum/bestify_mode.git"
INSTALL_DIR="/var/www/bestify_mode"
SSH_KEY_DIR="/root/.ssh"
SSH_KEY_FILE="${SSH_KEY_DIR}/id_rsa_bestify"
KNOWN_HOSTS_FILE="${SSH_KEY_DIR}/known_hosts"
CONFIG_FILE="${SSH_KEY_DIR}/config"

# Check dependencies
function check_dependencies() {
    echo -e "${BLUE}[INFO]${NC} Checking dependencies..."
    
    DEPS=(git curl wget unzip php php-fpm php-mysql php-mbstring php-zip php-gd php-json php-curl jq)
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
    git describe --tags --abbrev=0 2>/dev/null || echo "No tagged versions found"
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
    apt install -y lamp-server^ libapache2-mod-php mysql-server apache2 php-mbstring php-zip php-gd php-json php-curl
    
    # Enable and start services
    systemctl enable mysql.service
    systemctl start mysql.service
    systemctl enable apache2
    systemctl start apache2
    
    # Allow Apache in firewall
    apt install -y ufw
    ufw allow 'Apache'
    
    echo -e "${GREEN}[SUCCESS]${NC} Required packages installed."
}

# Configure the application
function configure_application() {
    echo -e "${BLUE}[INFO]${NC} Configuring application..."
    
    # Generate random database credentials
    db_name="bestify_db"
    db_user="bestify_user_$(openssl rand -hex 4)"
    db_pass=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | cut -c1-12)
    
    echo -e "${BLUE}[INFO]${NC} Setting up database with:"
    echo -e "  Database Name: ${GREEN}$db_name${NC}"
    echo -e "  Database User: ${GREEN}$db_user${NC}"
    echo -e "  Database Pass: ${GREEN}$db_pass${NC}"
    echo ""
    
    # Create database and user
    mysql -e "CREATE DATABASE IF NOT EXISTS ${db_name};"
    mysql -e "CREATE USER IF NOT EXISTS '${db_user}'@'localhost' IDENTIFIED BY '${db_pass}';"
    mysql -e "GRANT ALL PRIVILEGES ON ${db_name}.* TO '${db_user}'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # Get Bot information
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot token:"
    read bot_token
    while [[ ! "$bot_token" =~ ^[0-9]{8,10}:[a-zA-Z0-9_-]{35}$ ]]; do
        echo -e "${RED}[ERROR]${NC} Invalid bot token format. Please try again."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot token:"
        read bot_token
    done
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter admin user ID:"
    read admin_id
    while [[ ! "$admin_id" =~ ^[0-9]+$ ]]; do
        echo -e "${RED}[ERROR]${NC} Invalid user ID format. Please try again."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter admin user ID:"
        read admin_id
    done
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter domain (e.g., example.com):"
    read domain_host
    while [[ -z "$domain_host" ]]; do
        echo -e "${RED}[ERROR]${NC} Domain cannot be empty. Please try again."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter domain (e.g., example.com):"
        read domain_host
    done
    
    echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot username (without @):"
    read bot_username
    while [[ -z "$bot_username" ]]; do
        echo -e "${RED}[ERROR]${NC} Bot username cannot be empty. Please try again."
        echo -e "${YELLOW}[INPUT NEEDED]${NC} Enter bot username (without @):"
        read bot_username
    done
    
    # Update config file
    if [ -f "$INSTALL_DIR/config.php" ]; then
        sed -i "s/\$dbname = \"databasename\"/\$dbname = \"$db_name\"/" $INSTALL_DIR/config.php
        sed -i "s/\$usernamedb = \"username\"/\$usernamedb = \"$db_user\"/" $INSTALL_DIR/config.php
        sed -i "s/\$passworddb = \"password\"/\$passworddb = \"$db_pass\"/" $INSTALL_DIR/config.php
        sed -i "s/\$APIKEY = \"\*\*TOKEN\*\*\"/\$APIKEY = \"$bot_token\"/" $INSTALL_DIR/config.php
        sed -i "s/\$adminnumber = \"5522424631\"/\$adminnumber = \"$admin_id\"/" $INSTALL_DIR/config.php
        sed -i "s/\$domainhosts = \"domain.com\/bot\"/\$domainhosts = \"$domain_host\"/" $INSTALL_DIR/config.php
        sed -i "s/\$usernamebot = \"marzbaninfobot\"/\$usernamebot = \"$bot_username\"/" $INSTALL_DIR/config.php
        
        # Set up webhook if needed
        echo -e "${BLUE}[INFO]${NC} Setting up webhook..."
        curl -F "url=https://${domain_host}/bestify_mode/index.php" \
             "https://api.telegram.org/bot${bot_token}/setWebhook"
        
        # Send welcome message
        echo -e "${BLUE}[INFO]${NC} Sending welcome message to admin..."
        MESSAGE="✅ Bestify Mode has been installed successfully! Send /start to begin."
        curl -s -X POST "https://api.telegram.org/bot${bot_token}/sendMessage" \
             -d chat_id="${admin_id}" -d text="$MESSAGE"
    else
        echo -e "${RED}[ERROR]${NC} Config file not found. Installation may be incomplete."
        exit 1
    fi
    
    # Set permissions
    chown -R www-data:www-data $INSTALL_DIR
    chmod -R 755 $INSTALL_DIR
    
    # Setup database tables
    echo -e "${BLUE}[INFO]${NC} Setting up database tables..."
    php $INSTALL_DIR/table.php
    
    echo -e "${GREEN}[SUCCESS]${NC} Application configured successfully."
    
    # Display summary
    echo -e "\n${BLUE}[INSTALLATION SUMMARY]${NC}"
    echo -e "Bot URL: ${GREEN}https://${domain_host}/bestify_mode${NC}"
    echo -e "Database name: ${GREEN}${db_name}${NC}"
    echo -e "Database username: ${GREEN}${db_user}${NC}"
    echo -e "Database password: ${GREEN}${db_pass}${NC}"
    echo -e "\n${YELLOW}[NOTE]${NC} Please save these credentials for future reference."
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
    current_version=$(git describe --tags --abbrev=0 2>/dev/null || echo "unknown")
    echo -e "${BLUE}[INFO]${NC} Current version: $current_version"
    
    # Get latest version
    latest_version=$(GIT_SSH_COMMAND="ssh -i $SSH_KEY_FILE" git tag -l | sort -V | tail -n 1)
    if [ -z "$latest_version" ]; then
        latest_version="main"
    fi
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
