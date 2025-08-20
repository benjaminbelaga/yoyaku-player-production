#!/bin/bash

# YOYAKU Monitoring Script
# Continuous monitoring for production environments
# Usage: ./scripts/monitor.sh [environment] [--daemon] [--alert-threshold]

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
CONFIG_FILE="$PROJECT_ROOT/config/environments.yml"
LOG_FILE="/tmp/yoyaku-monitor-$(date +%Y%m%d).log"
PID_FILE="/tmp/yoyaku-monitor.pid"

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
}

warning() {
    log "WARNING: $1" "$YELLOW"
}

info() {
    log "INFO: $1" "$BLUE"
}

# Alert function
send_alert() {
    local severity="$1"
    local message="$2"
    local environment="$3"
    
    log "ALERT [$severity]: $message" "$RED"
    
    # Discord notification
    if [[ -n "${DISCORD_WEBHOOK:-}" ]]; then
        local color
        case "$severity" in
            "CRITICAL") color="15158332" ;;  # Red
            "WARNING") color="16776960" ;;   # Yellow
            "INFO") color="3066993" ;;       # Green
            *) color="7506394" ;;            # Default blue
        esac
        
        curl -H "Content-Type: application/json" \
            -d "{\"embeds\": [{\"title\": \"YOYAKU Alert [$severity]\", \"description\": \"Environment: $environment\\n$message\\nTime: $(date -u)\", \"color\": $color}]}" \
            "$DISCORD_WEBHOOK" &>/dev/null || true
    fi
    
    # Email notification (if configured)
    if [[ -n "${EMAIL_RECIPIENTS:-}" ]]; then
        echo "Subject: YOYAKU Alert [$severity] - $environment
        
Environment: $environment
Severity: $severity
Message: $message
Time: $(date)
Server: $(hostname)

This is an automated alert from YOYAKU monitoring system." | \
        mail -s "YOYAKU Alert [$severity] - $environment" "$EMAIL_RECIPIENTS" &>/dev/null || true
    fi
}

# Health check function
check_http_health() {
    local url="$1"
    local timeout="${2:-10}"
    
    local response
    response=$(curl -o /dev/null -s -w "%{http_code}:%{time_total}" --max-time "$timeout" "$url" || echo "000:999")
    
    local http_code="${response%:*}"
    local response_time="${response#*:}"
    
    echo "$http_code:$response_time"
}

# Database health check
check_database_health() {
    local environment="$1"
    local ssh_opts="$2"
    local ssh_user="$3"
    local ssh_host="$4"
    local app_path="$5"
    
    local db_status
    db_status=$(ssh $ssh_opts $ssh_user@$ssh_host << EOF
        cd $app_path
        timeout 30 wp db check --path=$app_path 2>/dev/null && echo "OK" || echo "FAILED"
EOF
    )
    
    echo "$db_status"
}

# Server metrics check
check_server_metrics() {
    local ssh_opts="$1"
    local ssh_user="$2"
    local ssh_host="$3"
    local app_path="$4"
    
    ssh $ssh_opts $ssh_user@$ssh_host << EOF
        # CPU Load
        LOAD=\$(uptime | awk -F'load average:' '{ print \$2 }' | awk '{ print \$1 }' | sed 's/,//')
        
        # Memory usage
        MEMORY=\$(free | grep Mem | awk '{printf "%.1f", \$3/\$2 * 100.0}')
        
        # Disk usage
        DISK=\$(df $app_path | tail -1 | awk '{print \$5}' | sed 's/%//')
        
        # PHP-FPM processes (if available)
        PHP_PROCS=\$(pgrep -c php-fpm || echo "0")
        
        echo "LOAD:\$LOAD,MEMORY:\$MEMORY,DISK:\$DISK,PHP_PROCS:\$PHP_PROCS"
EOF
}

# WordPress/WooCommerce health check
check_wp_health() {
    local ssh_opts="$1"
    local ssh_user="$2"
    local ssh_host="$3"
    local app_path="$4"
    
    ssh $ssh_opts $ssh_user@$ssh_host << EOF
        cd $app_path
        
        # WordPress core health
        WP_HEALTH=\$(wp core is-installed --path=$app_path && echo "OK" || echo "FAILED")
        
        # Plugin status
        PLUGIN_HEALTH=\$(wp plugin is-active yoyaku-player-v3-production --path=$app_path && echo "ACTIVE" || echo "INACTIVE")
        
        # Database connectivity
        DB_HEALTH=\$(timeout 10 wp db check --path=$app_path 2>/dev/null && echo "OK" || echo "FAILED")
        
        # WooCommerce health (if available)
        WC_HEALTH="N/A"
        if wp plugin is-active woocommerce --path=$app_path 2>/dev/null; then
            WC_HEALTH=\$(timeout 15 wp option get woocommerce_custom_orders_table_enabled --path=$app_path >/dev/null 2>&1 && echo "OK" || echo "FAILED")
        fi
        
        # Recent error count
        ERROR_COUNT=\$(tail -100 $app_path/../logs/php_error.log 2>/dev/null | grep "\$(date +'%d-%b-%Y')" | wc -l || echo "0")
        
        echo "WP:\$WP_HEALTH,PLUGIN:\$PLUGIN_HEALTH,DB:\$DB_HEALTH,WC:\$WC_HEALTH,ERRORS:\$ERROR_COUNT"
EOF
}

# Parse arguments
ENVIRONMENT=""
DAEMON_MODE=false
ALERT_THRESHOLD="5"
MONITOR_INTERVAL="300"  # 5 minutes

while [[ $# -gt 0 ]]; do
    case $1 in
        staging|production|yyd_production)
            ENVIRONMENT="$1"
            shift
            ;;
        --daemon)
            DAEMON_MODE=true
            shift
            ;;
        --alert-threshold)
            ALERT_THRESHOLD="$2"
            shift 2
            ;;
        --interval)
            MONITOR_INTERVAL="$2"
            shift 2
            ;;
        --stop)
            if [[ -f "$PID_FILE" ]]; then
                PID=$(cat "$PID_FILE")
                if kill -0 "$PID" 2>/dev/null; then
                    kill "$PID"
                    rm -f "$PID_FILE"
                    echo "Monitoring daemon stopped"
                else
                    echo "Daemon not running"
                    rm -f "$PID_FILE"
                fi
            else
                echo "PID file not found"
            fi
            exit 0
            ;;
        --help|-h)
            echo "Usage: $0 [environment] [options]"
            echo ""
            echo "Environments:"
            echo "  staging                Monitor staging environment"
            echo "  production            Monitor production environment"
            echo "  yyd_production        Monitor YYD production environment"
            echo ""
            echo "Options:"
            echo "  --daemon              Run in daemon mode (continuous monitoring)"
            echo "  --alert-threshold N   Error threshold for alerts (default: 5)"
            echo "  --interval N          Monitoring interval in seconds (default: 300)"
            echo "  --stop                Stop running daemon"
            echo "  --help, -h           Show this help message"
            exit 0
            ;;
        *)
            error "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Validate environment
if [[ -z "$ENVIRONMENT" ]]; then
    error "Environment is required. Use: staging, production, or yyd_production"
    exit 1
fi

# Load environment configuration
if ! command -v yq &> /dev/null; then
    error "yq is required for configuration parsing. Install with: brew install yq"
    exit 1
fi

if [[ ! -f "$CONFIG_FILE" ]]; then
    error "Configuration file not found: $CONFIG_FILE"
    exit 1
fi

# Parse environment config
APP_ID=$(yq eval ".environments.$ENVIRONMENT.app_id" "$CONFIG_FILE")
DOMAIN=$(yq eval ".environments.$ENVIRONMENT.domain" "$CONFIG_FILE")
APP_PATH=$(yq eval ".environments.$ENVIRONMENT.path" "$CONFIG_FILE")
SSH_HOST=$(yq eval ".environments.$ENVIRONMENT.ssh_host" "$CONFIG_FILE")
SSH_USER=$(yq eval ".environments.$ENVIRONMENT.ssh_user" "$CONFIG_FILE")
HEALTH_CHECK_URL=$(yq eval ".environments.$ENVIRONMENT.health_check_url" "$CONFIG_FILE")

# Load monitoring thresholds
RESPONSE_TIME_THRESHOLD=$(yq eval ".monitoring.response_time_threshold" "$CONFIG_FILE")
ERROR_THRESHOLD=$(yq eval ".monitoring.error_threshold" "$CONFIG_FILE")
DISK_SPACE_THRESHOLD=$(yq eval ".monitoring.disk_space_threshold" "$CONFIG_FILE")
MEMORY_THRESHOLD=$(yq eval ".monitoring.memory_threshold" "$CONFIG_FILE")

# Setup notifications
DISCORD_WEBHOOK_VAR=$(yq eval ".notifications.discord.webhook_var" "$CONFIG_FILE")
if [[ "$DISCORD_WEBHOOK_VAR" != "null" ]]; then
    export DISCORD_WEBHOOK="${!DISCORD_WEBHOOK_VAR:-}"
fi

EMAIL_RECIPIENTS=$(yq eval ".notifications.email.recipients[]" "$CONFIG_FILE" | tr '\n' ',' | sed 's/,$//')

# SSH configuration
SSH_KEY_PATH=$(yq eval ".security.ssh_key_path" "$CONFIG_FILE" | sed "s|~|$HOME|")
if [[ ! -f "$SSH_KEY_PATH" ]]; then
    error "SSH key not found: $SSH_KEY_PATH"
    exit 1
fi

SSH_OPTS="-o StrictHostKeyChecking=no -i $SSH_KEY_PATH"

# Daemon mode
if [[ "$DAEMON_MODE" == true ]]; then
    if [[ -f "$PID_FILE" ]] && kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
        error "Monitoring daemon already running (PID: $(cat "$PID_FILE"))"
        exit 1
    fi
    
    # Start daemon
    echo $$ > "$PID_FILE"
    log "Starting monitoring daemon for $ENVIRONMENT (PID: $$)"
    log "Monitoring interval: ${MONITOR_INTERVAL}s"
    log "Log file: $LOG_FILE"
    
    # Trap signals for clean shutdown
    trap 'log "Monitoring daemon stopped"; rm -f "$PID_FILE"; exit 0' SIGTERM SIGINT
    
    while true; do
        # Run monitoring check
        "$0" "$ENVIRONMENT" --alert-threshold "$ALERT_THRESHOLD"
        sleep "$MONITOR_INTERVAL"
    done
fi

# Single monitoring run
log "Running monitoring check for $ENVIRONMENT"

# HTTP Health Check
info "Checking HTTP health..."
HTTP_RESULT=$(check_http_health "$HEALTH_CHECK_URL")
HTTP_CODE="${HTTP_RESULT%:*}"
RESPONSE_TIME="${HTTP_RESULT#*:}"

if [[ "$HTTP_CODE" != "200" ]]; then
    send_alert "CRITICAL" "HTTP health check failed: $HTTP_CODE for $HEALTH_CHECK_URL" "$ENVIRONMENT"
else
    log "HTTP health check passed: $HTTP_CODE (${RESPONSE_TIME}s)"
    
    # Check response time
    if (( $(echo "$RESPONSE_TIME > $RESPONSE_TIME_THRESHOLD" | bc -l) )); then
        send_alert "WARNING" "Slow response time: ${RESPONSE_TIME}s (threshold: ${RESPONSE_TIME_THRESHOLD}s)" "$ENVIRONMENT"
    fi
fi

# Environment-specific HTTP checks
if [[ "$ENVIRONMENT" == "production" ]]; then
    CHECKOUT_URL=$(yq eval ".environments.$ENVIRONMENT.checkout_url" "$CONFIG_FILE")
    if [[ "$CHECKOUT_URL" != "null" ]]; then
        CHECKOUT_RESULT=$(check_http_health "$CHECKOUT_URL")
        CHECKOUT_CODE="${CHECKOUT_RESULT%:*}"
        if [[ "$CHECKOUT_CODE" != "200" ]]; then
            send_alert "CRITICAL" "Checkout page failed: $CHECKOUT_CODE for $CHECKOUT_URL" "$ENVIRONMENT"
        else
            log "Checkout health check passed: $CHECKOUT_CODE"
        fi
    fi
    
    SHOP_URL=$(yq eval ".environments.$ENVIRONMENT.shop_url" "$CONFIG_FILE")
    if [[ "$SHOP_URL" != "null" ]]; then
        SHOP_RESULT=$(check_http_health "$SHOP_URL")
        SHOP_CODE="${SHOP_RESULT%:*}"
        if [[ "$SHOP_CODE" != "200" ]]; then
            send_alert "WARNING" "Shop page failed: $SHOP_CODE for $SHOP_URL" "$ENVIRONMENT"
        else
            log "Shop health check passed: $SHOP_CODE"
        fi
    fi
fi

# Server Metrics Check
info "Checking server metrics..."
METRICS=$(check_server_metrics "$SSH_OPTS" "$SSH_USER" "$SSH_HOST" "$APP_PATH")

# Parse metrics
LOAD=$(echo "$METRICS" | grep -o 'LOAD:[^,]*' | cut -d: -f2)
MEMORY=$(echo "$METRICS" | grep -o 'MEMORY:[^,]*' | cut -d: -f2)
DISK=$(echo "$METRICS" | grep -o 'DISK:[^,]*' | cut -d: -f2)
PHP_PROCS=$(echo "$METRICS" | grep -o 'PHP_PROCS:[^,]*' | cut -d: -f2)

log "Server metrics - Load: $LOAD, Memory: ${MEMORY}%, Disk: ${DISK}%, PHP processes: $PHP_PROCS"

# Check thresholds
if (( $(echo "$LOAD > 3.0" | bc -l) )); then
    send_alert "WARNING" "High server load: $LOAD" "$ENVIRONMENT"
fi

if [[ -n "$MEMORY" ]] && (( $(echo "$MEMORY > $MEMORY_THRESHOLD" | bc -l) )); then
    send_alert "WARNING" "High memory usage: ${MEMORY}% (threshold: ${MEMORY_THRESHOLD}%)" "$ENVIRONMENT"
fi

if [[ -n "$DISK" ]] && [[ "$DISK" -gt "$DISK_SPACE_THRESHOLD" ]]; then
    send_alert "WARNING" "High disk usage: ${DISK}% (threshold: ${DISK_SPACE_THRESHOLD}%)" "$ENVIRONMENT"
fi

# WordPress/WooCommerce Health Check
info "Checking WordPress/WooCommerce health..."
WP_HEALTH=$(check_wp_health "$SSH_OPTS" "$SSH_USER" "$SSH_HOST" "$APP_PATH")

# Parse WordPress health
WP_STATUS=$(echo "$WP_HEALTH" | grep -o 'WP:[^,]*' | cut -d: -f2)
PLUGIN_STATUS=$(echo "$WP_HEALTH" | grep -o 'PLUGIN:[^,]*' | cut -d: -f2)
DB_STATUS=$(echo "$WP_HEALTH" | grep -o 'DB:[^,]*' | cut -d: -f2)
WC_STATUS=$(echo "$WP_HEALTH" | grep -o 'WC:[^,]*' | cut -d: -f2)
ERROR_COUNT=$(echo "$WP_HEALTH" | grep -o 'ERRORS:[^,]*' | cut -d: -f2)

log "WordPress health - Core: $WP_STATUS, Plugin: $PLUGIN_STATUS, DB: $DB_STATUS, WC: $WC_STATUS, Errors: $ERROR_COUNT"

# Check WordPress health
if [[ "$WP_STATUS" != "OK" ]]; then
    send_alert "CRITICAL" "WordPress core health check failed: $WP_STATUS" "$ENVIRONMENT"
fi

if [[ "$PLUGIN_STATUS" != "ACTIVE" ]]; then
    send_alert "CRITICAL" "YOYAKU plugin not active: $PLUGIN_STATUS" "$ENVIRONMENT"
fi

if [[ "$DB_STATUS" != "OK" ]]; then
    send_alert "CRITICAL" "Database health check failed: $DB_STATUS" "$ENVIRONMENT"
fi

if [[ "$WC_STATUS" == "FAILED" ]]; then
    send_alert "WARNING" "WooCommerce health check failed: $WC_STATUS" "$ENVIRONMENT"
fi

if [[ -n "$ERROR_COUNT" ]] && [[ "$ERROR_COUNT" -gt "$ERROR_THRESHOLD" ]]; then
    send_alert "WARNING" "High error count: $ERROR_COUNT errors today (threshold: $ERROR_THRESHOLD)" "$ENVIRONMENT"
fi

# Database Health Check
info "Checking database connectivity..."
DB_HEALTH=$(check_database_health "$ENVIRONMENT" "$SSH_OPTS" "$SSH_USER" "$SSH_HOST" "$APP_PATH")

if [[ "$DB_HEALTH" != "OK" ]]; then
    send_alert "CRITICAL" "Database connectivity failed: $DB_HEALTH" "$ENVIRONMENT"
else
    log "Database connectivity check passed"
fi

# Performance metrics log
cat >> "$LOG_FILE" << EOF
METRICS_$(date +%s): HTTP=$HTTP_CODE,RESPONSE_TIME=$RESPONSE_TIME,LOAD=$LOAD,MEMORY=$MEMORY,DISK=$DISK,PHP_PROCS=$PHP_PROCS,WP=$WP_STATUS,PLUGIN=$PLUGIN_STATUS,DB=$DB_STATUS,WC=$WC_STATUS,ERRORS=$ERROR_COUNT
EOF

log "Monitoring check completed for $ENVIRONMENT"

# Summary report
if [[ "$HTTP_CODE" == "200" ]] && [[ "$WP_STATUS" == "OK" ]] && [[ "$PLUGIN_STATUS" == "ACTIVE" ]] && [[ "$DB_STATUS" == "OK" ]]; then
    info "All systems operational ✓"
else
    warning "Some systems showing issues - check alerts above"
fi