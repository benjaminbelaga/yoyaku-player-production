# YOYAKU CI/CD Pipeline - Operations Runbook

## 🚨 Emergency Contacts

- **Technical Lead**: ben@yoyaku.io
- **Operations**: nizar@yoyaku.fr  
- **Emergency Discord**: #alerts channel
- **Server**: 134.122.80.6 (Cloudways)

## 🚀 Quick Reference Commands

### Emergency Response
```bash
# Site down - immediate rollback
./scripts/rollback.sh production --force

# Check all systems
./scripts/monitor.sh production

# View production logs
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'tail -100 /home/master/applications/jfnkmjmfer/logs/php_error.log'
```

### Daily Operations
```bash
# Deploy to staging for testing
./scripts/deploy.sh staging

# Monitor production health
./scripts/monitor.sh production --daemon

# Deploy to production (requires approval)
./scripts/deploy.sh production
```

## 📋 Standard Operating Procedures

### 1. Routine Deployment

**Prerequisites Check:**
- [ ] All tests passing on staging
- [ ] Code review completed
- [ ] Change request approved
- [ ] Backup verified recent
- [ ] Low traffic period selected

**Execution:**
```bash
# 1. Deploy to staging first
./scripts/deploy.sh staging

# 2. Validate staging deployment
curl -I https://woocommerce-870689-5762868.cloudwaysapps.com
./scripts/monitor.sh staging

# 3. If staging OK, deploy to production
./scripts/deploy.sh production

# 4. Post-deployment monitoring
./scripts/monitor.sh production
```

**Post-Deployment Verification:**
- [ ] HTTP 200 on main site (yoyaku.io)
- [ ] HTTP 200 on checkout (/checkout/)
- [ ] HTTP 200 on shop (/releases/)
- [ ] WordPress admin accessible
- [ ] Plugin active and functional
- [ ] No PHP errors in logs
- [ ] Response time < 5 seconds

### 2. Emergency Rollback

**Triggers for Emergency Rollback:**
- Site returning 500/404 errors
- Checkout process broken
- PHP fatal errors preventing access
- Performance degradation >10 seconds response time
- WooCommerce order processing failure

**Emergency Rollback Procedure:**
```bash
# 1. Immediate rollback (no questions asked)
./scripts/rollback.sh production --force

# 2. Verify site recovery
curl -I https://yoyaku.io/checkout/

# 3. Monitor for 30 minutes
./scripts/monitor.sh production --daemon --interval 60

# 4. Notify team
# (Automatic Discord notification sent)

# 5. Investigate root cause
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'tail -200 /home/master/applications/jfnkmjmfer/logs/php_error.log'
```

### 3. Health Monitoring

**Automated Monitoring:**
- GitHub Actions runs every 5 minutes (business hours)
- GitHub Actions runs every 15 minutes (off hours)
- Discord alerts for any failures
- Email alerts for critical issues

**Manual Health Check:**
```bash
# Full system health check
./scripts/monitor.sh production

# Specific environment monitoring
./scripts/monitor.sh staging
./scripts/monitor.sh yyd_production

# Start continuous monitoring daemon
./scripts/monitor.sh production --daemon
```

**Key Metrics to Watch:**
- HTTP Status Code: Must be 200
- Response Time: < 5 seconds warning, < 10 seconds critical
- Server Load: < 3.0 warning, < 5.0 critical
- Memory Usage: < 80% warning, < 95% critical
- Disk Usage: < 85% warning, < 95% critical
- PHP Errors: < 5/day warning, < 20/day critical

## 🔧 Troubleshooting Guide

### Issue: Site Returns 500 Error

**Immediate Action:**
```bash
# Check for PHP errors
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'tail -50 /home/master/applications/jfnkmjmfer/logs/php_error.log'

# If plugin-related, rollback immediately
./scripts/rollback.sh production --force
```

**Investigation:**
1. Check recent deployments in GitHub Actions
2. Review error logs for patterns
3. Test functionality on staging environment
4. Identify root cause before re-deployment

### Issue: Checkout Page Not Working

**Critical Priority - Immediate Action:**
```bash
# Test checkout page
curl -I https://yoyaku.io/checkout/

# If failing, immediate rollback
./scripts/rollback.sh production --force

# Verify recovery
curl -I https://yoyaku.io/checkout/
```

**Revenue Impact Assessment:**
- Checkout failure = immediate revenue loss
- Must be resolved within 15 minutes maximum
- Consider manual WooCommerce plugin deactivation if rollback fails

### Issue: Deployment Fails

**GitHub Actions Deployment Failure:**
1. Check GitHub Actions logs for specific error
2. Verify SSH connectivity to server
3. Check server disk space and load
4. Retry deployment if temporary issue

**Script Deployment Failure:**
```bash
# Check connection to server
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 'uptime'

# Check disk space
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 'df -h'

# Check recent logs
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'tail -100 /home/master/applications/jfnkmjmfer/logs/php_error.log'
```

### Issue: High Server Load

**Investigation:**
```bash
# Check current load
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 'uptime'

# Check running processes
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 'top -n 1'

# Check MySQL processes
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'mysqladmin processlist'
```

**Temporary Relief:**
```bash
# Clear all caches
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/jfnkmjmfer/public_html && wp cache flush'

# Restart PHP-FPM (if needed)
# Contact Cloudways support for server restart
```

## 🔐 Security Procedures

### SSH Key Management
- SSH keys stored in GitHub Secrets
- Local development uses ~/.ssh/cloudways_rsa
- Key rotation every 90 days
- Never share private keys

### Access Control
- Production deployments require GitHub approval
- Emergency rollbacks can bypass approval
- All actions logged and audited
- Two-person rule for major changes

### Backup Verification
```bash
# List recent backups
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'ls -la /tmp/*backup* | head -10'

# Verify database backup integrity
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/jfnkmjmfer/public_html && \
   wp db import --dry-run /tmp/production-backup-latest.sql'
```

## 📊 Performance Optimization

### Database Maintenance
```bash
# Check database size
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/jfnkmjmfer/public_html && \
   wp db size --human-readable'

# Optimize tables (staging only)
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/gwrckvqdjn/public_html && \
   wp db optimize'
```

### Cache Management
```bash
# Clear all caches
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/jfnkmjmfer/public_html && \
   wp cache flush && wp transient delete --all'

# Check cache status
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 \
  'cd /home/master/applications/jfnkmjmfer/public_html && \
   wp cache status'
```

## 📋 Change Management

### Deployment Schedule
- **Preferred Times**: Tuesday-Thursday, 10:00-16:00 Paris time
- **Avoid**: Mondays, Fridays, weekends, holidays
- **Emergency**: Any time with proper escalation

### Pre-Deployment Checklist
- [ ] Staging deployment successful
- [ ] All automated tests passing
- [ ] Manual testing completed
- [ ] Performance impact assessed
- [ ] Rollback plan prepared
- [ ] Team notification sent
- [ ] Monitoring alerts configured

### Post-Deployment Checklist
- [ ] Health checks passing
- [ ] Performance metrics normal
- [ ] Error logs clean
- [ ] User acceptance testing
- [ ] Business functionality verified
- [ ] Documentation updated

## 📞 Escalation Matrix

### Level 1: Automated Response
- Monitoring detects issue
- Automatic Discord alert sent
- GitHub Actions logs available
- Self-healing attempts (cache clear, etc.)

### Level 2: Technical Team
- **Contact**: ben@yoyaku.io
- **Response Time**: 15 minutes during business hours
- **Authority**: Emergency rollback, system investigation

### Level 3: Business Team  
- **Contact**: nizar@yoyaku.fr
- **Response Time**: 30 minutes
- **Authority**: Business impact decisions, customer communication

### Level 4: External Support
- **Contact**: Cloudways support
- **Use Case**: Server-level issues, infrastructure problems
- **Access**: Through Cloudways panel

## 📚 Reference Documentation

### Internal Links
- [CLAUDE.md](../CLAUDE.md) - Development guidelines
- [Environment Config](../config/environments.yml) - Server settings
- [GitHub Workflows](../.github/workflows/) - CI/CD definitions

### External Links
- [Cloudways Dashboard](https://platform.cloudways.com/)
- [GitHub Repository](https://github.com/[repo-url])
- [Discord Server](https://discord.gg/[invite-link])

### Emergency Numbers
- Cloudways Support: Available in platform
- OVH Network Issues: Check status.ovh.net
- Domain Issues: Check registrar status

## 🔄 Regular Maintenance

### Daily Tasks
- [ ] Review monitoring dashboard
- [ ] Check error logs for patterns
- [ ] Verify backup completion
- [ ] Monitor performance metrics

### Weekly Tasks
- [ ] Review deployment statistics
- [ ] Update dependencies (staging first)
- [ ] Performance optimization review
- [ ] Security scan results review

### Monthly Tasks
- [ ] SSH key rotation planning
- [ ] Disaster recovery test
- [ ] Capacity planning review
- [ ] Documentation updates

---

**Last Updated**: 18 August 2025  
**Version**: 1.0  
**Maintained By**: YOYAKU Technical Team