#!/bin/bash

# YOYAKU Deployment Script
# Follows CLAUDE.md safety protocols
# Usage: ./scripts/deploy.sh [staging|production] [--force] [--skip-backup]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_ROOT/config/environments.yml"
LOG_FILE="/tmp/yoyaku-deploy-$(date +%Y%m%d-%H%M%S).log"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${2:-$GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    log "ERROR: $1" "$RED"
    exit 1
}

warning() {
    log "WARNING: $1" "$YELLOW"
}

info() {
    log "INFO: $1" "$BLUE"
}

# Parse arguments
ENVIRONMENT=""
FORCE_DEPLOY=false
SKIP_BACKUP=false

while [[ $# -gt 0 ]]; do
    case $1 in
        staging|production|yyd_production)
            ENVIRONMENT="$1"
            shift
            ;;
        --force)
            FORCE_DEPLOY=true
            shift
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --help|-h)
            echo "Usage: $0 [staging|production|yyd_production] [--force] [--skip-backup]"
            echo ""
            echo "Options:"
            echo "  staging                Deploy to staging environment (clone-dev)"
            echo "  production            Deploy to production environment"
            echo "  yyd_production        Deploy to YYD production environment"
            echo "  --force               Skip confirmation prompts"
            echo "  --skip-backup         Skip backup creation (not recommended)"
            echo "  --help, -h           Show this help message"
            exit 0
            ;;
        *)
            error "Unknown option: $1"
            ;;
    esac
done

# Validate environment
if [[ -z "$ENVIRONMENT" ]]; then
    error "Environment is required. Use: staging, production, or yyd_production"
fi

# Load environment configuration
if ! command -v yq &> /dev/null; then
    error "yq is required for configuration parsing. Install with: brew install yq"
fi

if [[ ! -f "$CONFIG_FILE" ]]; then
    error "Configuration file not found: $CONFIG_FILE"
fi

# Parse environment config
APP_ID=$(yq eval ".environments.$ENVIRONMENT.app_id" "$CONFIG_FILE")
DOMAIN=$(yq eval ".environments.$ENVIRONMENT.domain" "$CONFIG_FILE")
APP_PATH=$(yq eval ".environments.$ENVIRONMENT.path" "$CONFIG_FILE")
SSH_HOST=$(yq eval ".environments.$ENVIRONMENT.ssh_host" "$CONFIG_FILE")
SSH_USER=$(yq eval ".environments.$ENVIRONMENT.ssh_user" "$CONFIG_FILE")
HEALTH_CHECK_URL=$(yq eval ".environments.$ENVIRONMENT.health_check_url" "$CONFIG_FILE")
DEPLOYMENT_STRATEGY=$(yq eval ".environments.$ENVIRONMENT.deployment_strategy" "$CONFIG_FILE")

# Validate configuration
if [[ "$APP_ID" == "null" ]] || [[ -z "$APP_ID" ]]; then
    error "Invalid configuration for environment: $ENVIRONMENT"
fi

# SSH key configuration
SSH_KEY_PATH=$(yq eval ".security.ssh_key_path" "$CONFIG_FILE" | sed "s|~|$HOME|")
if [[ ! -f "$SSH_KEY_PATH" ]]; then
    error "SSH key not found: $SSH_KEY_PATH"
fi

SSH_OPTS="-o StrictHostKeyChecking=no -i $SSH_KEY_PATH"

log "Starting deployment to $ENVIRONMENT environment"
log "Target: $DOMAIN ($APP_ID)"
log "Strategy: $DEPLOYMENT_STRATEGY"

# Production safety checks
if [[ "$ENVIRONMENT" == "production" ]] || [[ "$ENVIRONMENT" == "yyd_production" ]]; then
    if [[ "$FORCE_DEPLOY" != true ]]; then
        warning "Deploying to PRODUCTION environment!"
        warning "This will affect live e-commerce operations"
        read -p "Are you absolutely sure? Type 'YES' to continue: " -r
        if [[ ! $REPLY == "YES" ]]; then
            error "Deployment aborted by user"
        fi
    fi
    
    # Pre-deployment checks for production
    log "Running pre-deployment checks..."
    
    # Check server load
    LOAD=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST "uptime | awk -F'load average:' '{ print \$2 }' | awk '{ print \$1 }' | sed 's/,//'")
    if (( $(echo "$LOAD > 2.0" | bc -l) )); then
        error "High server load detected: $LOAD. Aborting deployment."
    fi
    
    # Check site accessibility
    HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$HEALTH_CHECK_URL")
    if [[ "$HTTP_STATUS" != "200" ]]; then
        error "Site health check failed: HTTP $HTTP_STATUS"
    fi
    
    # Check disk space
    DISK_USAGE=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST "df $APP_PATH | tail -1 | awk '{print \$5}' | sed 's/%//'")
    if [[ "$DISK_USAGE" -gt 85 ]]; then
        error "Disk usage too high: ${DISK_USAGE}%. Free up space before deployment."
    fi
    
    log "Pre-deployment checks passed"
fi

# Create backup unless skipped
if [[ "$SKIP_BACKUP" != true ]]; then
    log "Creating backup..."
    
    BACKUP_TIMESTAMP=$(date +%Y%m%d-%H%M%S)
    
    # Database backup
    ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
        cd $APP_PATH
        wp db export /tmp/${ENVIRONMENT}-db-backup-${BACKUP_TIMESTAMP}.sql --path=$APP_PATH
        if [ \$? -eq 0 ]; then
            echo "Database backup created: /tmp/${ENVIRONMENT}-db-backup-${BACKUP_TIMESTAMP}.sql"
        else
            echo "Database backup failed"
            exit 1
        fi
EOF
    
    # Plugin backup
    ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
        cd $APP_PATH/wp-content/plugins/
        if [ -d "yoyaku-player-v3-production" ]; then
            tar -czf /tmp/${ENVIRONMENT}-plugin-backup-${BACKUP_TIMESTAMP}.tar.gz yoyaku-player-v3-production/
            echo "Plugin backup created: /tmp/${ENVIRONMENT}-plugin-backup-${BACKUP_TIMESTAMP}.tar.gz"
        fi
EOF
    
    log "Backup completed"
else
    warning "Backup skipped as requested"
fi

# Create deployment package
log "Creating deployment package..."
cd "$PROJECT_ROOT"

PACKAGE_NAME="yoyaku-player-deployment-$(date +%Y%m%d-%H%M%S).tar.gz"
tar -czf "/tmp/$PACKAGE_NAME" \
    --exclude='.git' \
    --exclude='node_modules' \
    --exclude='.github' \
    --exclude='tests' \
    --exclude='coverage-html' \
    --exclude='vendor' \
    --exclude='*.log' \
    .

if [[ ! -f "/tmp/$PACKAGE_NAME" ]]; then
    error "Failed to create deployment package"
fi

log "Deployment package created: $PACKAGE_NAME"

# Upload deployment package
log "Uploading deployment package..."
scp $SSH_OPTS "/tmp/$PACKAGE_NAME" "$SSH_USER@$SSH_HOST:/tmp/"

# Deploy based on strategy
if [[ "$DEPLOYMENT_STRATEGY" == "zero_downtime" ]]; then
    log "Performing zero-downtime deployment..."
    
    ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
        set -e
        cd $APP_PATH/wp-content/plugins/
        
        # Backup current version
        if [ -d "yoyaku-player-v3-production" ]; then
            mv yoyaku-player-v3-production yoyaku-player-v3-production-backup-$(date +%Y%m%d-%H%M%S)
        fi
        
        # Extract to temporary directory
        mkdir -p yoyaku-player-v3-production-new
        cd yoyaku-player-v3-production-new
        tar -xzf /tmp/$PACKAGE_NAME
        
        # Set proper permissions
        find . -type f -exec chmod 644 {} \\;
        find . -type d -exec chmod 755 {} \\;
        
        # Atomic swap
        cd ..
        mv yoyaku-player-v3-production-new yoyaku-player-v3-production
        
        echo "Zero-downtime deployment completed"
EOF
else
    log "Performing direct deployment..."
    
    ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
        set -e
        cd $APP_PATH/wp-content/plugins/
        
        # Backup current version
        if [ -d "yoyaku-player-v3-production" ]; then
            mv yoyaku-player-v3-production yoyaku-player-v3-production-backup-$(date +%Y%m%d-%H%M%S)
        fi
        
        # Extract new version
        mkdir -p yoyaku-player-v3-production
        cd yoyaku-player-v3-production
        tar -xzf /tmp/$PACKAGE_NAME
        
        # Set proper permissions
        find . -type f -exec chmod 644 {} \\;
        find . -type d -exec chmod 755 {} \\;
        
        echo "Direct deployment completed"
EOF
fi

# Clear caches
log "Clearing caches..."
ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    cd $APP_PATH
    wp cache flush --path=$APP_PATH
    echo "Caches cleared"
EOF

# Post-deployment health checks
log "Running post-deployment health checks..."

# Wait for deployment to settle
sleep 30

# Basic HTTP check
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$HEALTH_CHECK_URL")
if [[ "$HTTP_STATUS" != "200" ]]; then
    error "Post-deployment health check failed: HTTP $HTTP_STATUS"
fi

# Check for PHP errors
ERROR_COUNT=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST "tail -100 $APP_PATH/../logs/php_error.log | grep '$(date +'%d-%b-%Y')' | wc -l")
if [[ "$ERROR_COUNT" -gt 5 ]]; then
    warning "High error count detected after deployment: $ERROR_COUNT"
fi

# Environment-specific checks
if [[ "$ENVIRONMENT" == "production" ]]; then
    # Check checkout page
    CHECKOUT_URL=$(yq eval ".environments.$ENVIRONMENT.checkout_url" "$CONFIG_FILE")
    if [[ "$CHECKOUT_URL" != "null" ]]; then
        CHECKOUT_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$CHECKOUT_URL")
        if [[ "$CHECKOUT_STATUS" != "200" ]]; then
            error "Checkout page health check failed: HTTP $CHECKOUT_STATUS"
        fi
    fi
    
    # Check shop page
    SHOP_URL=$(yq eval ".environments.$ENVIRONMENT.shop_url" "$CONFIG_FILE")
    if [[ "$SHOP_URL" != "null" ]]; then
        SHOP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$SHOP_URL")
        if [[ "$SHOP_STATUS" != "200" ]]; then
            error "Shop page health check failed: HTTP $SHOP_STATUS"
        fi
    fi
fi

# Performance check
RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}" "$HEALTH_CHECK_URL")
if (( $(echo "$RESPONSE_TIME > 5.0" | bc -l) )); then
    warning "Slow response time detected: ${RESPONSE_TIME}s"
fi

log "Post-deployment health checks passed"

# Cleanup
log "Cleaning up temporary files..."
rm -f "/tmp/$PACKAGE_NAME"
ssh $SSH_OPTS $SSH_USER@$SSH_HOST "rm -f /tmp/$PACKAGE_NAME"

# Send notification if configured
DISCORD_WEBHOOK=$(yq eval ".notifications.discord.webhook_var" "$CONFIG_FILE")
if [[ "$DISCORD_WEBHOOK" != "null" ]] && [[ -n "${!DISCORD_WEBHOOK:-}" ]]; then
    curl -H "Content-Type: application/json" \
        -d "{\"embeds\": [{\"title\": \"YOYAKU Deployment Success\", \"description\": \"Environment: $ENVIRONMENT\\nDomain: $DOMAIN\\nTime: $(date -u)\", \"color\": 3066993}]}" \
        "${!DISCORD_WEBHOOK}" &>/dev/null || true
fi

log "Deployment to $ENVIRONMENT completed successfully!"
log "Deployment log saved to: $LOG_FILE"
log "Target URL: $HEALTH_CHECK_URL"

# Final reminder for production
if [[ "$ENVIRONMENT" == "production" ]] || [[ "$ENVIRONMENT" == "yyd_production" ]]; then
    log "🚨 PRODUCTION DEPLOYMENT COMPLETE 🚨"
    log "Monitor the site closely for the next 30 minutes"
    log "Check error logs: tail -f $APP_PATH/../logs/php_error.log"
fi