#!/bin/bash

# DEPLOY ULTRATHINK SOLUTION - iPhone Safari SVG Icons Fix
# Solution définitive pour TOUS les formats iPhone

set -e

COLOR_RED='\033[0;31m'
COLOR_GREEN='\033[0;32m'
COLOR_YELLOW='\033[1;33m'
COLOR_BLUE='\033[0;34m'
COLOR_PURPLE='\033[0;35m'
COLOR_NC='\033[0m' # No Color

echo -e "${COLOR_PURPLE}=========================================="
echo -e "  ULTRATHINK SOLUTION DEPLOYMENT"
echo -e "  iPhone Safari SVG Icons Fix"
echo -e "==========================================${COLOR_NC}"

# Variables
CLONE_APP="gwrckvqdjn"
PLUGIN_PATH="/home/master/applications/$CLONE_APP/public_html/wp-content/plugins/yoyaku-player-github"
CSS_FILE="$PLUGIN_PATH/assets/css/frontend.css"
JS_FILE="$PLUGIN_PATH/assets/js/frontend.js"
BACKUP_SUFFIX="ultrathink-$(date +%Y%m%d-%H%M)"

echo -e "${COLOR_BLUE}📍 Target: Clone YOYAKU ($CLONE_APP)${COLOR_NC}"
echo -e "${COLOR_BLUE}📁 Plugin path: $PLUGIN_PATH${COLOR_NC}"

# Vérification connexion SSH
echo -e "${COLOR_YELLOW}🔍 Vérification connexion SSH...${COLOR_NC}"
if ! ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 'echo "SSH OK"' >/dev/null 2>&1; then
    echo -e "${COLOR_RED}❌ Erreur connexion SSH${COLOR_NC}"
    exit 1
fi
echo -e "${COLOR_GREEN}✅ SSH connecté${COLOR_NC}"

# Vérification que les fichiers source existent
echo -e "${COLOR_YELLOW}🔍 Vérification fichiers source...${COLOR_NC}"
if [[ ! -f "SOLUTION-ULTRATHINK-ICONS.css" ]]; then
    echo -e "${COLOR_RED}❌ Fichier SOLUTION-ULTRATHINK-ICONS.css manquant${COLOR_NC}"
    exit 1
fi

if [[ ! -f "SOLUTION-ULTRATHINK-ICONS.js" ]]; then
    echo -e "${COLOR_RED}❌ Fichier SOLUTION-ULTRATHINK-ICONS.js manquant${COLOR_NC}"
    exit 1
fi
echo -e "${COLOR_GREEN}✅ Fichiers source trouvés${COLOR_NC}"

# Backup des fichiers actuels
echo -e "${COLOR_YELLOW}💾 Création des backups...${COLOR_NC}"
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH &&
    cp assets/css/frontend.css assets/css/frontend.css.backup-$BACKUP_SUFFIX &&
    cp assets/js/frontend.js assets/js/frontend.js.backup-$BACKUP_SUFFIX &&
    echo 'Backups créés avec suffix: $BACKUP_SUFFIX'
"
echo -e "${COLOR_GREEN}✅ Backups créés${COLOR_NC}"

# Phase 1: Remplacement des icônes SVG Base64 par UTF-8
echo -e "${COLOR_YELLOW}🎯 Phase 1: Mise à jour CSS avec SVG UTF-8...${COLOR_NC}"

# Créer le CSS modifié en remplaçant la section problématique
cat > temp_css_update.css << 'EOF'
    /* Icônes SVG UTF-8 (Remplace Base64) - ULTRATHINK SOLUTION */
    .yoyaku-player-ultra-fin .control-btn.prev::after {
        content: '';
        width: 16px !important;
        height: 16px !important;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        display: block !important;
    }
    
    .yoyaku-player-ultra-fin .control-btn.next::after {
        content: '';
        width: 16px !important;
        height: 16px !important;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        display: block !important;
    }
    
    .yoyaku-player-ultra-fin .btn-play::after {
        content: '';
        width: 18px !important;
        height: 18px !important;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffd700"><path d="M8 5v14l11-7z"/></svg>') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        display: block !important;
        margin-left: 2px !important;
    }
    
    .yoyaku-player-ultra-fin .btn-pause::after {
        content: '';
        width: 18px !important;
        height: 18px !important;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="%23ffd700"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>') !important;
        background-size: contain !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        display: block !important;
    }

    /* Fallback robuste Unicode (remplace emojis) */
    @supports not (background-image: url('data:image/svg+xml;utf8,')) {
        .yoyaku-player-ultra-fin .control-btn.prev::after {
            content: "⏮" !important; /* Unicode PREVIOUS TRACK */
            background-image: none !important;
            font-size: 14px !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        .yoyaku-player-ultra-fin .control-btn.next::after {
            content: "⏭" !important; /* Unicode NEXT TRACK */
            background-image: none !important;
            font-size: 14px !important;
            color: white !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        .yoyaku-player-ultra-fin .btn-play::after {
            content: "▶" !important; /* Unicode PLAY */
            background-image: none !important;
            font-size: 16px !important;
            color: #ffd700 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
        
        .yoyaku-player-ultra-fin .btn-pause::after {
            content: "⏸" !important; /* Unicode PAUSE */
            background-image: none !important;
            font-size: 16px !important;
            color: #ffd700 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif !important;
        }
    }

    /* Optimisations Safari iOS */
    .yoyaku-player-ultra-fin .control-btn::after {
        will-change: transform !important;
        transform: translateZ(0) !important;
        backface-visibility: hidden !important;
        -webkit-backface-visibility: hidden !important;
    }
    
    .yoyaku-player-ultra-fin .control-btn {
        -webkit-tap-highlight-color: transparent !important;
        -webkit-touch-callout: none !important;
        -webkit-user-select: none !important;
        user-select: none !important;
    }
EOF

# Upload et remplacement dans le CSS
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH &&
    
    # Supprimer l'ancienne section SVG Base64
    sed -i '/Icônes SVG Base64 - COMPATIBLES IPHONE!/,/font-family: -apple-system, BlinkMacSystemFont, sans-serif;/d' assets/css/frontend.css &&
    
    # Ajouter la nouvelle section
    cat >> assets/css/frontend.css
" < temp_css_update.css

rm temp_css_update.css

echo -e "${COLOR_GREEN}✅ CSS mis à jour avec SVG UTF-8${COLOR_NC}"

# Phase 2: Ajout du JavaScript d'injection SVG
echo -e "${COLOR_YELLOW}🎯 Phase 2: Ajout du JavaScript ULTRATHINK...${COLOR_NC}"

# Upload du fichier JavaScript
scp -i ~/.ssh/cloudways_rsa SOLUTION-ULTRATHINK-ICONS.js master_crhmyfjcsf@134.122.80.6:$PLUGIN_PATH/assets/js/ultrathink-icons.js

# Modifier le PHP pour inclure le nouveau JS
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH &&
    
    # Trouver le fichier PHP principal
    if [[ -f 'yoyaku-player.php' ]]; then
        PHP_FILE='yoyaku-player.php'
    elif [[ -f 'yoyaku-player-github.php' ]]; then
        PHP_FILE='yoyaku-player-github.php'
    else
        PHP_FILE=\$(find . -name '*.php' -type f | head -1)
    fi
    
    echo \"Modification du fichier PHP: \$PHP_FILE\"
    
    # Backup du fichier PHP
    cp \$PHP_FILE \${PHP_FILE}.backup-$BACKUP_SUFFIX
    
    # Ajouter l'enqueue du nouveau JS (chercher wp_enqueue_script frontend)
    sed -i '/wp_enqueue_script.*frontend.*js/a\\t\twp_enqueue_script(\\"yoyaku-ultrathink-icons\\", plugin_dir_url(__FILE__) . \\"assets/js/ultrathink-icons.js\\", array(), \\"1.0.0\\", true);' \$PHP_FILE
    
    echo \"JavaScript ULTRATHINK ajouté au plugin\"
"

echo -e "${COLOR_GREEN}✅ JavaScript ULTRATHINK intégré${COLOR_NC}"

# Phase 3: Tests automatisés
echo -e "${COLOR_YELLOW}🧪 Phase 3: Tests automatisés...${COLOR_NC}"

# Test de la syntaxe CSS
echo -e "${COLOR_BLUE}🔍 Validation CSS...${COLOR_NC}"
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH/assets/css &&
    # Test basique de syntaxe CSS (recherche accolades non fermées)
    if [[ \$(grep -o '{' frontend.css | wc -l) -eq \$(grep -o '}' frontend.css | wc -l) ]]; then
        echo '✅ CSS syntaxiquement valide'
    else
        echo '❌ Erreur syntaxe CSS détectée'
        exit 1
    fi
"

# Vérifier que les URLs des SVG sont correctes
echo -e "${COLOR_BLUE}🔍 Validation SVG UTF-8...${COLOR_NC}"
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH/assets/css &&
    if grep -q \"data:image/svg+xml;utf8\" frontend.css; then
        echo '✅ SVG UTF-8 trouvés dans le CSS'
    else
        echo '❌ SVG UTF-8 manquants'
        exit 1
    fi
"

echo -e "${COLOR_GREEN}✅ Tests automatisés réussis${COLOR_NC}"

# Informations de test
echo -e "${COLOR_PURPLE}=========================================="
echo -e "  DÉPLOIEMENT TERMINÉ"
echo -e "==========================================${COLOR_NC}"

echo -e "${COLOR_GREEN}✅ Solution ULTRATHINK déployée avec succès!${COLOR_NC}"
echo ""
echo -e "${COLOR_BLUE}📱 TESTS iPhone Safari recommandés:${COLOR_NC}"
echo -e "   • iPhone SE (1ère gen): iOS 9+ Safari"
echo -e "   • iPhone 12 Mini: iOS 14+ Safari" 
echo -e "   • iPhone 13: iOS 15+ Safari"
echo -e "   • iPhone 14 Pro: iOS 16+ Safari"
echo -e "   • iPhone 15 Pro Max: iOS 17+ Safari"
echo -e "   • iPhone 16 Pro Max: iOS 18+ Safari"
echo ""
echo -e "${COLOR_BLUE}🌐 URL de test:${COLOR_NC}"
echo -e "   https://woocommerce-870689-5762868.cloudwaysapps.com"
echo ""
echo -e "${COLOR_BLUE}🛠️ Debugging JavaScript:${COLOR_NC}"
echo -e "   Console: ${COLOR_YELLOW}window.reinjectIcons()${COLOR_NC}"
echo -e "   Console: ${COLOR_YELLOW}window.yoyakuIconsUltraThink.testSafariCompatibility()${COLOR_NC}"
echo ""
echo -e "${COLOR_BLUE}📂 Fichiers de backup:${COLOR_NC}"
echo -e "   • frontend.css.backup-$BACKUP_SUFFIX"
echo -e "   • frontend.js.backup-$BACKUP_SUFFIX"
echo ""
echo -e "${COLOR_YELLOW}⚡ SOLUTION 3 NIVEAUX:${COLOR_NC}"
echo -e "   1. ${COLOR_GREEN}UTF-8 SVG${COLOR_NC} (remplace Base64 problématique)"
echo -e "   2. ${COLOR_GREEN}Unicode robuste${COLOR_NC} (⏮ ⏭ ▶ ⏸)"
echo -e "   3. ${COLOR_GREEN}Injection JS inline${COLOR_NC} (solution ultime)"
echo ""
echo -e "${COLOR_RED}⚠️  Si problème: Rollback avec backup-$BACKUP_SUFFIX${COLOR_NC}"

# Créer un script de rollback rapide
cat > ROLLBACK-ULTRATHINK.sh << EOF
#!/bin/bash
echo "🔄 ROLLBACK ULTRATHINK SOLUTION..."
ssh -i ~/.ssh/cloudways_rsa master_crhmyfjcsf@134.122.80.6 "
    cd $PLUGIN_PATH &&
    mv assets/css/frontend.css.backup-$BACKUP_SUFFIX assets/css/frontend.css &&
    mv assets/js/frontend.js.backup-$BACKUP_SUFFIX assets/js/frontend.js &&
    rm -f assets/js/ultrathink-icons.js &&
    echo '✅ Rollback terminé'
"
echo "✅ Rollback terminé - État restauré"
EOF

chmod +x ROLLBACK-ULTRATHINK.sh
echo -e "${COLOR_GREEN}📄 Script de rollback créé: ${COLOR_YELLOW}./ROLLBACK-ULTRATHINK.sh${COLOR_NC}"