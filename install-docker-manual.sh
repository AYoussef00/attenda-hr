#!/bin/bash
# Manual Docker installation commands for EC2
# Run these commands directly on EC2 server

# For Amazon Linux 2 / RHEL / CentOS
sudo yum update -y
sudo yum install docker -y
sudo systemctl start docker
sudo systemctl enable docker
sudo usermod -a -G docker ec2-user

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose

# Verify installation
docker --version
docker compose version

# Create project directory
sudo mkdir -p /var/www/hrm_system
sudo chown ec2-user:ec2-user /var/www/hrm_system

echo "Docker installation complete!"
echo "You may need to logout and login again for group changes to take effect."

