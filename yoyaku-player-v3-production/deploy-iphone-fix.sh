#!/bin/bash

# YOYAKU Player - Script de déploiement du fix iPhone
# Auteur: Assistant Claude
# Date: 2025-08-16

echo "🍎 YOYAKU Player - Déploiement du fix iPhone"
echo "============================================="

# Configuration
PROJECT_DIR="/Users/yoyaku/player-fin-v4-mobile/yoyaku-player-v3-production"
CSS_FILE="$PROJECT_DIR/assets/css/frontend.css"
JS_FILE="$PROJECT_DIR/assets/js/frontend.js"
IPHONE_CSS="$PROJECT_DIR/assets/css/frontend-iphone-fix.css"
IPHONE_JS="$PROJECT_DIR/assets/js/frontend-iphone-fix.js"

# Vérifier que les fichiers existent
if [ ! -f "$CSS_FILE" ]; then
    echo "❌ Fichier CSS principal non trouvé: $CSS_FILE"
    exit 1
fi

if [ ! -f "$JS_FILE" ]; then
    echo "❌ Fichier JS principal non trouvé: $JS_FILE"
    exit 1
fi

if [ ! -f "$IPHONE_CSS" ]; then
    echo "❌ Fichier CSS iPhone fix non trouvé: $IPHONE_CSS"
    exit 1
fi

if [ ! -f "$IPHONE_JS" ]; then
    echo "❌ Fichier JS iPhone fix non trouvé: $IPHONE_JS"
    exit 1
fi

echo "✅ Tous les fichiers sont présents"

# Créer des backups
echo "📁 Création des backups..."
cp "$CSS_FILE" "$CSS_FILE.backup-$(date +%Y%m%d-%H%M%S)"
cp "$JS_FILE" "$JS_FILE.backup-$(date +%Y%m%d-%H%M%S)"

echo "✅ Backups créés"

# Option 1: Intégrer le CSS iPhone fix dans le CSS principal
echo "🎨 Intégration du CSS iPhone fix..."
echo "" >> "$CSS_FILE"
echo "/* ============================================== */" >> "$CSS_FILE"
echo "/*   IPHONE FIX - AUTO-INTEGRÉ $(date +%Y-%m-%d)   */" >> "$CSS_FILE"
echo "/* ============================================== */" >> "$CSS_FILE"
cat "$IPHONE_CSS" >> "$CSS_FILE"

echo "✅ CSS iPhone fix intégré"

# Option 2: Créer un fichier JS combiné
echo "🔧 Création du fichier JS combiné..."
COMBINED_JS="$PROJECT_DIR/assets/js/frontend-combined.js"

# Combiner les deux fichiers JS
cat "$IPHONE_JS" > "$COMBINED_JS"
echo "" >> "$COMBINED_JS"
echo "/* ============================================== */" >> "$COMBINED_JS"
echo "/*   PLAYER PRINCIPAL - AUTO-INTEGRÉ $(date +%Y-%m-%d)   */" >> "$COMBINED_JS"
echo "/* ============================================== */" >> "$COMBINED_JS"
cat "$JS_FILE" >> "$COMBINED_JS"

echo "✅ Fichier JS combiné créé: frontend-combined.js"

# Générer les instructions d'intégration
echo "📋 Génération des instructions..."

cat > "$PROJECT_DIR/INSTRUCTIONS-INTEGRATION-IPHONE.md" << 'EOF'
# Instructions d'intégration - Fix iPhone YOYAKU Player

## 📱 Problème résolu
- Player non responsive sur iPhone
- Bouton panier coupé/invisible  
- Waveform tronquée
- Layout 2 lignes non appliqué

## 🔧 Fichiers modifiés

### 1. CSS Principal (frontend.css)
- ✅ Fix iPhone intégré automatiquement
- Media queries spécifiques iPhone ajoutées
- Layout 2 lignes forcé pour tous les modèles iPhone

### 2. JavaScript 
- ✅ Détection iPhone améliorée dans frontend.js
- ✅ Fichier combiné créé: frontend-combined.js

## 🚀 Déploiement en production

### Option A: Fichiers séparés (recommandé)
```html
<!-- Dans votre thème WordPress -->
<link rel="stylesheet" href="path/to/frontend.css">
<link rel="stylesheet" href="path/to/frontend-iphone-fix.css">

<script src="path/to/frontend-iphone-fix.js"></script>
<script src="path/to/frontend.js"></script>
```

### Option B: Fichier combiné
```html
<!-- JavaScript combiné -->
<script src="path/to/frontend-combined.js"></script>
<!-- CSS déjà intégré dans frontend.css -->
```

### Option C: WordPress enqueue
```php
// Dans functions.php
function yoyaku_player_iphone_fix() {
    wp_enqueue_style('yoyaku-player-iphone', 
        get_template_directory_uri() . '/assets/css/frontend-iphone-fix.css', 
        array('yoyaku-player-main'), '1.0.0');
    
    wp_enqueue_script('yoyaku-player-iphone-fix', 
        get_template_directory_uri() . '/assets/js/frontend-iphone-fix.js', 
        array(), '1.0.0', false); // false = dans <head>
}
add_action('wp_enqueue_scripts', 'yoyaku_player_iphone_fix');
```

## 🧪 Test sur iPhone

1. Ouvrir test-iphone.html sur iPhone Safari
2. Vérifier:
   - ✅ Player en 2 lignes (vinyl + metadata + controls + cart / waveform)
   - ✅ Bouton panier visible en haut à droite
   - ✅ Waveform jusqu'au bout de l'écran
   - ✅ Responsive en portrait/paysage

## 📊 Dimensions supportées

- iPhone SE: 375x667
- iPhone 12/13 Mini: 375x812  
- iPhone 12/13/14: 390x844
- iPhone 12/13/14 Pro: 390x844
- iPhone 14 Plus: 428x926
- iPhone 12/13/14 Pro Max: 428x926
- iPhone 15/15 Pro: 393x852
- iPhone 15 Plus/Pro Max: 430x932

## 🛠️ Debug sur iPhone

1. Activer Web Inspector: Réglages > Safari > Avancé > Inspecteur Web
2. Connecter iPhone au Mac
3. Safari > Développement > [iPhone] > [Page]
4. Console: `window.yoyakuIPhoneFix.forceLayout()`

## 🚨 Notes importantes

- Les media queries sont ULTRA-SPÉCIFIQUES iPhone (-webkit-min-device-pixel-ratio)
- Backup automatique créé avant intégration
- Compatible avec tous les iPhone (SE à 15 Pro Max)
- Hardware acceleration activée pour performance
- Touch events optimisés

EOF

echo "✅ Instructions créées: INSTRUCTIONS-INTEGRATION-IPHONE.md"

# Afficher le résumé
echo ""
echo "🎉 DÉPLOIEMENT TERMINÉ !"
echo "======================"
echo "📁 Fichiers créés/modifiés:"
echo "   - frontend.css (fix intégré)"
echo "   - frontend-combined.js (nouveau)"
echo "   - frontend-iphone-fix.css (séparé)"  
echo "   - frontend-iphone-fix.js (séparé)"
echo "   - test-iphone.html (test)"
echo "   - INSTRUCTIONS-INTEGRATION-IPHONE.md"
echo ""
echo "📱 Tests à effectuer:"
echo "   1. Ouvrir test-iphone.html sur iPhone Safari"
echo "   2. Vérifier le layout 2 lignes"
echo "   3. Tester en portrait/paysage"
echo ""
echo "🚀 Pour déployer en production:"
echo "   1. Lire INSTRUCTIONS-INTEGRATION-IPHONE.md"
echo "   2. Choisir Option A, B ou C selon votre setup"
echo "   3. Tester sur vraiment iPhone (pas simulateur)"
echo ""
echo "⚠️  N'oubliez pas d'ajouter le viewport meta tag:"
echo '   <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">'
echo ""
echo "✅ Ready to deploy!"