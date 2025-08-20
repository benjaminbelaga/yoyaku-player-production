#!/bin/bash

# YOYAKU Emergency Rollback Script
# Follows CLAUDE.md safety protocols for production protection
# Usage: ./scripts/rollback.sh [staging|production] [--force] [--backup-timestamp]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_ROOT/config/environments.yml"
LOG_FILE="/tmp/yoyaku-rollback-$(date +%Y%m%d-%H%M%S).log"

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
FORCE_ROLLBACK=false
BACKUP_TIMESTAMP=""

while [[ $# -gt 0 ]]; do
    case $1 in
        staging|production|yyd_production)
            ENVIRONMENT="$1"
            shift
            ;;
        --force)
            FORCE_ROLLBACK=true
            shift
            ;;
        --backup-timestamp)
            BACKUP_TIMESTAMP="$2"
            shift 2
            ;;
        --help|-h)
            echo "Usage: $0 [staging|production|yyd_production] [--force] [--backup-timestamp TIMESTAMP]"
            echo ""
            echo "Options:"
            echo "  staging                  Rollback staging environment"
            echo "  production              Rollback production environment"
            echo "  yyd_production          Rollback YYD production environment"
            echo "  --force                 Skip confirmation prompts"
            echo "  --backup-timestamp      Specify backup timestamp to restore"
            echo "  --help, -h             Show this help message"
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

# SSH key configuration
SSH_KEY_PATH=$(yq eval ".security.ssh_key_path" "$CONFIG_FILE" | sed "s|~|$HOME|")
if [[ ! -f "$SSH_KEY_PATH" ]]; then
    error "SSH key not found: $SSH_KEY_PATH"
fi

SSH_OPTS="-o StrictHostKeyChecking=no -i $SSH_KEY_PATH"

log "Starting emergency rollback for $ENVIRONMENT environment"
log "Target: $DOMAIN ($APP_ID)"

# Production safety checks
if [[ "$ENVIRONMENT" == "production" ]] || [[ "$ENVIRONMENT" == "yyd_production" ]]; then
    log "🚨 EMERGENCY PRODUCTION ROLLBACK 🚨" "$RED"
    
    if [[ "$FORCE_ROLLBACK" != true ]]; then
        warning "This is an EMERGENCY PRODUCTION ROLLBACK!"
        warning "This will revert live e-commerce operations"
        read -p "Are you absolutely sure? Type 'ROLLBACK' to continue: " -r
        if [[ ! $REPLY == "ROLLBACK" ]]; then
            error "Rollback aborted by user"
        fi
    fi
fi

# Check current site status
log "Checking current site status..."
CURRENT_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$HEALTH_CHECK_URL" || echo "000")
log "Current HTTP status: $CURRENT_STATUS"

# Find available backups
log "Scanning for available backups..."

AVAILABLE_BACKUPS=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST << 'EOF'
    cd /home/master/applications/*/public_html/wp-content/plugins/ 2>/dev/null
    find . -maxdepth 1 -type d -name "yoyaku-player-v3-production-backup-*" | sort -r | head -10
EOF
)

if [[ -z "$AVAILABLE_BACKUPS" ]]; then
    error "No plugin backups found for rollback"
fi

log "Available plugin backups:"
echo "$AVAILABLE_BACKUPS" | while read -r backup; do
    if [[ -n "$backup" ]]; then
        TIMESTAMP=$(echo "$backup" | grep -o '[0-9]\{8\}-[0-9]\{6\}')
        HUMAN_DATE=$(date -j -f "%Y%m%d-%H%M%S" "$TIMESTAMP" "+%Y-%m-%d %H:%M:%S" 2>/dev/null || echo "$TIMESTAMP")
        log "  $backup ($HUMAN_DATE)" "$BLUE"
    fi
done

# Select backup to restore
if [[ -n "$BACKUP_TIMESTAMP" ]]; then
    SELECTED_BACKUP="./yoyaku-player-v3-production-backup-$BACKUP_TIMESTAMP"
    log "Using specified backup: $SELECTED_BACKUP"
else
    # Use most recent backup
    SELECTED_BACKUP=$(echo "$AVAILABLE_BACKUPS" | head -1)
    log "Using most recent backup: $SELECTED_BACKUP"
fi

if [[ -z "$SELECTED_BACKUP" ]]; then
    error "No suitable backup found for rollback"
fi

# Verify backup exists
BACKUP_EXISTS=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    cd $APP_PATH/wp-content/plugins/
    if [ -d "$SELECTED_BACKUP" ]; then
        echo "exists"
    else
        echo "missing"
    fi
EOF
)

if [[ "$BACKUP_EXISTS" != "exists" ]]; then
    error "Selected backup does not exist: $SELECTED_BACKUP"
fi

# Create emergency backup of current state
log "Creating emergency backup of current state..."
EMERGENCY_TIMESTAMP=$(date +%Y%m%d-%H%M%S)

ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    cd $APP_PATH/wp-content/plugins/
    if [ -d "yoyaku-player-v3-production" ]; then
        mv yoyaku-player-v3-production yoyaku-player-v3-production-emergency-$EMERGENCY_TIMESTAMP
        echo "Emergency backup created: yoyaku-player-v3-production-emergency-$EMERGENCY_TIMESTAMP"
    fi
EOF

# Perform rollback
log "Performing rollback..."

ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    set -e
    cd $APP_PATH/wp-content/plugins/
    
    # Restore from backup
    if [ -d "$SELECTED_BACKUP" ]; then
        cp -r "$SELECTED_BACKUP" yoyaku-player-v3-production
        echo "Plugin restored from backup: $SELECTED_BACKUP"
    else
        echo "Backup directory not found: $SELECTED_BACKUP"
        exit 1
    fi
    
    # Set proper permissions
    find yoyaku-player-v3-production -type f -exec chmod 644 {} \\;
    find yoyaku-player-v3-production -type d -exec chmod 755 {} \\;
    
    echo "Permissions set correctly"
EOF

# Clear caches
log "Clearing caches..."
ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    cd $APP_PATH
    wp cache flush --path=$APP_PATH
    echo "Caches cleared"
EOF

# Post-rollback health checks
log "Running post-rollback health checks..."

# Wait for rollback to settle
sleep 30

# Basic HTTP check
HTTP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$HEALTH_CHECK_URL" || echo "000")
if [[ "$HTTP_STATUS" != "200" ]]; then
    error "Post-rollback health check failed: HTTP $HTTP_STATUS"
fi

log "Basic health check passed: HTTP $HTTP_STATUS"

# Environment-specific checks
if [[ "$ENVIRONMENT" == "production" ]]; then
    # Check checkout page
    CHECKOUT_URL=$(yq eval ".environments.$ENVIRONMENT.checkout_url" "$CONFIG_FILE")
    if [[ "$CHECKOUT_URL" != "null" ]]; then
        CHECKOUT_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$CHECKOUT_URL" || echo "000")
        if [[ "$CHECKOUT_STATUS" != "200" ]]; then
            warning "Checkout page check failed: HTTP $CHECKOUT_STATUS"
        else
            log "Checkout page check passed: HTTP $CHECKOUT_STATUS"
        fi
    fi
    
    # Check shop page
    SHOP_URL=$(yq eval ".environments.$ENVIRONMENT.shop_url" "$CONFIG_FILE")
    if [[ "$SHOP_URL" != "null" ]]; then
        SHOP_STATUS=$(curl -o /dev/null -s -w "%{http_code}" "$SHOP_URL" || echo "000")
        if [[ "$SHOP_STATUS" != "200" ]]; then
            warning "Shop page check failed: HTTP $SHOP_STATUS"
        else
            log "Shop page check passed: HTTP $SHOP_STATUS"
        fi
    fi
fi

# Check for PHP errors
log "Checking for recent PHP errors..."
ERROR_COUNT=$(ssh $SSH_OPTS $SSH_USER@$SSH_HOST "tail -50 $APP_PATH/../logs/php_error.log | grep '$(date +'%d-%b-%Y')' | wc -l" || echo "0")
if [[ "$ERROR_COUNT" -gt 0 ]]; then
    warning "PHP errors detected after rollback: $ERROR_COUNT"
    log "Check error log: tail -f $APP_PATH/../logs/php_error.log"
else
    log "No recent PHP errors detected"
fi

# Performance check
RESPONSE_TIME=$(curl -o /dev/null -s -w "%{time_total}" "$HEALTH_CHECK_URL" || echo "999")
if (( $(echo "$RESPONSE_TIME > 5.0" | bc -l) )); then
    warning "Slow response time detected: ${RESPONSE_TIME}s"
else
    log "Response time acceptable: ${RESPONSE_TIME}s"
fi

# WordPress/WooCommerce checks
log "Running WordPress/WooCommerce checks..."
ssh $SSH_OPTS $SSH_USER@$SSH_HOST << EOF
    cd $APP_PATH
    
    # Check WordPress health
    WP_STATUS=\$(wp core is-installed --path=$APP_PATH && echo "OK" || echo "FAILED")
    echo "WordPress status: \$WP_STATUS"
    
    # Check plugin activation
    PLUGIN_STATUS=\$(wp plugin is-active yoyaku-player-v3-production --path=$APP_PATH && echo "ACTIVE" || echo "INACTIVE")
    echo "Plugin status: \$PLUGIN_STATUS"
    
    # Check WooCommerce if available
    if wp plugin is-active woocommerce --path=$APP_PATH; then
        WC_STATUS=\$(wp wc status --path=$APP_PATH --format=json | grep -o '"status":"[^"]*' | cut -d'"' -f4 | head -1 || echo "UNKNOWN")
        echo "WooCommerce status: \$WC_STATUS"
    fi
EOF

# Send notification if configured
DISCORD_WEBHOOK=$(yq eval ".notifications.discord.webhook_var" "$CONFIG_FILE")
if [[ "$DISCORD_WEBHOOK" != "null" ]] && [[ -n "${!DISCORD_WEBHOOK:-}" ]]; then
    if [[ "$HTTP_STATUS" == "200" ]]; then
        COLOR="3066993"  # Green
        STATUS="Success"
    else
        COLOR="15158332"  # Red
        STATUS="Partial"
    fi
    
    curl -H "Content-Type: application/json" \
        -d "{\"embeds\": [{\"title\": \"YOYAKU Emergency Rollback $STATUS\", \"description\": \"Environment: $ENVIRONMENT\\nBackup: $SELECTED_BACKUP\\nStatus: HTTP $HTTP_STATUS\\nTime: $(date -u)\", \"color\": $COLOR}]}" \
        "${!DISCORD_WEBHOOK}" &>/dev/null || true
fi

log "Emergency rollback completed!"
log "Rollback log saved to: $LOG_FILE"
log "Restored from backup: $SELECTED_BACKUP"
log "Emergency backup created: yoyaku-player-v3-production-emergency-$EMERGENCY_TIMESTAMP"
log "Site status: HTTP $HTTP_STATUS"

# Final instructions
if [[ "$ENVIRONMENT" == "production" ]] || [[ "$ENVIRONMENT" == "yyd_production" ]]; then
    log "🚨 PRODUCTION ROLLBACK COMPLETE 🚨" "$GREEN"
    log "NEXT STEPS:"
    log "1. Monitor the site closely for the next 30 minutes"
    log "2. Check error logs: tail -f $APP_PATH/../logs/php_error.log"
    log "3. Test critical functionality (checkout, orders, payments)"
    log "4. Investigate root cause of the issue that required rollback"
    log "5. Plan proper fix and redeployment"
    
    if [[ "$HTTP_STATUS" != "200" ]]; then
        warning "Site still showing issues after rollback!"
        warning "Consider database restoration or further investigation"
    fi
fi