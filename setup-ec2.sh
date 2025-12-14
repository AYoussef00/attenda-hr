#!/bin/bash

# Setup script to run on EC2 server
# This script installs Docker and prepares the environment

set -e

echo "Setting up EC2 server for Docker deployment..."

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    echo "Cannot detect OS. Exiting."
    exit 1
fi

# Install Docker based on OS
if [ "$OS" == "amzn" ] || [ "$OS" == "rhel" ] || [ "$OS" == "centos" ]; then
    echo "Detected Amazon Linux / RHEL / CentOS"
    
    # Update system
    sudo yum update -y
    
    # Install Docker
    if ! command -v docker &> /dev/null; then
        echo "Installing Docker..."
        sudo yum install -y docker
        sudo systemctl start docker
        sudo systemctl enable docker
        sudo usermod -a -G docker $USER
    else
        echo "Docker is already installed"
    fi
    
    # Install Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        echo "Installing Docker Compose..."
        sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
        sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose
    else
        echo "Docker Compose is already installed"
    fi
    
elif [ "$OS" == "ubuntu" ] || [ "$OS" == "debian" ]; then
    echo "Detected Ubuntu / Debian"
    
    # Update system
    sudo apt-get update -y
    
    # Install Docker
    if ! command -v docker &> /dev/null; then
        echo "Installing Docker..."
        sudo apt-get install -y docker.io
        sudo systemctl start docker
        sudo systemctl enable docker
        sudo usermod -aG docker $USER
    else
        echo "Docker is already installed"
    fi
    
    # Install Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        echo "Installing Docker Compose..."
        sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        sudo chmod +x /usr/local/bin/docker-compose
    else
        echo "Docker Compose is already installed"
    fi
else
    echo "Unsupported OS: $OS"
    exit 1
fi

# Create project directory
echo "Creating project directory..."
sudo mkdir -p /var/www/hrm_system
sudo chown $USER:$USER /var/www/hrm_system

# Configure firewall (if firewalld is installed)
if command -v firewall-cmd &> /dev/null; then
    echo "Configuring firewall..."
    sudo firewall-cmd --permanent --add-port=80/tcp
    sudo firewall-cmd --permanent --add-port=443/tcp
    sudo firewall-cmd --reload
fi

echo ""
echo "Setup complete!"
echo ""
echo "Docker version: $(docker --version)"
echo "Docker Compose version: $(docker compose version 2>/dev/null || docker-compose --version)"
echo ""
echo "Next steps:"
echo "1. Transfer files using deploy-to-ec2.sh from your local machine"
echo "2. Or manually copy files to /var/www/hrm_system"
echo "3. Configure .env file"
echo "4. Run: docker compose up -d --build"

