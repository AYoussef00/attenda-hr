#!/bin/bash

# Deployment script to transfer Docker setup to EC2
# Usage: ./deploy-to-ec2.sh

set -e

# Configuration
EC2_HOST="16.16.63.104"
EC2_USER="ec2-user"
EC2_KEY="system-pair.pem"
REMOTE_PATH="/var/www/hrm_system"
LOCAL_PATH="."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Starting deployment to EC2...${NC}"

# Check if key file exists
if [ ! -f "$EC2_KEY" ]; then
    echo -e "${RED}Error: Key file '$EC2_KEY' not found!${NC}"
    exit 1
fi

# Set correct permissions for key
chmod 400 "$EC2_KEY"

# Test SSH connection
echo -e "${YELLOW}Testing SSH connection...${NC}"
ssh -i "$EC2_KEY" -o StrictHostKeyChecking=no "$EC2_USER@$EC2_HOST" "echo 'Connection successful!'" || {
    echo -e "${RED}Error: Cannot connect to EC2 instance!${NC}"
    exit 1
}

# Create remote directory
echo -e "${YELLOW}Creating remote directory...${NC}"
ssh -i "$EC2_KEY" "$EC2_USER@$EC2_HOST" "sudo mkdir -p $REMOTE_PATH && sudo chown $EC2_USER:$EC2_USER $REMOTE_PATH"

# Transfer Docker files
echo -e "${YELLOW}Transferring Docker configuration files...${NC}"
scp -i "$EC2_KEY" docker-compose.prod.yml "$EC2_USER@$EC2_HOST:$REMOTE_PATH/docker-compose.yml"
scp -i "$EC2_KEY" Dockerfile "$EC2_USER@$EC2_HOST:$REMOTE_PATH/"
scp -i "$EC2_KEY" -r docker/ "$EC2_USER@$EC2_HOST:$REMOTE_PATH/"

# Transfer application files (excluding unnecessary files)
echo -e "${YELLOW}Transferring application files...${NC}"
if command -v rsync &> /dev/null; then
    rsync -avz -e "ssh -i $EC2_KEY" \
      --exclude 'node_modules' \
      --exclude 'vendor' \
      --exclude '.git' \
      --exclude '.env' \
      --exclude 'storage/logs/*' \
      --exclude 'storage/framework/cache/*' \
      --exclude 'storage/framework/sessions/*' \
      --exclude 'storage/framework/views/*' \
      --exclude '.DS_Store' \
      --exclude '*.log' \
      "$LOCAL_PATH/" "$EC2_USER@$EC2_HOST:$REMOTE_PATH/"
else
    echo -e "${YELLOW}rsync not found, using scp for key files...${NC}"
    # Transfer essential files only
    scp -i "$EC2_KEY" -r app/ bootstrap/ config/ database/ public/ resources/ routes/ "$EC2_USER@$EC2_HOST:$REMOTE_PATH/"
    scp -i "$EC2_KEY" artisan composer.json composer.lock package.json package-lock.json "$EC2_USER@$EC2_HOST:$REMOTE_PATH/"
    echo -e "${YELLOW}Note: For full deployment, install rsync or use Git clone on server${NC}"
fi

echo -e "${GREEN}Files transferred successfully!${NC}"
echo ""
echo -e "${YELLOW}Next steps on EC2:${NC}"
echo "1. SSH to server: ssh -i $EC2_KEY $EC2_USER@$EC2_HOST"
echo "2. Navigate to: cd $REMOTE_PATH"
echo "3. Create .env file with production settings"
echo "4. Build and start: docker compose up -d --build"
echo "5. Generate app key: docker compose exec app php artisan key:generate"
echo "6. Run migrations: docker compose exec app php artisan migrate --force"
echo "7. Set permissions: docker compose exec app chown -R www-data:www-data /var/www/storage"
echo ""
echo -e "${GREEN}For detailed instructions, see: DEPLOYMENT.md or QUICK-START.md${NC}"

