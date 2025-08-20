# YOYAKU PLAYER V3 - CLASSIFICATION DES FICHIERS
Date: 2025-08-20
Status: AVANT REFACTORING

## 🟢 FICHIERS ACTIFS (À CONSERVER)

### Core Plugin
- **yoyaku-player-v3.php** (7.0K) - Fichier principal du plugin, classe YoyakuPlayerV3
  - Status: ACTIF
  - Rôle: Point d'entrée principal, gestion shortcodes, AJAX, enqueue scripts
  
### JavaScript
- **assets/js/frontend.js** (52K) - Logique frontend complète
  - Status: ACTIF
  - Rôle: WaveSurfer.js integration, pitch control, player controls

### CSS Principal  
- **assets/css/frontend.css** (34K) - Styles actifs actuels
  - Status: ACTIF (mais à optimiser)
  - Rôle: Styles principaux du player

## 🟡 FICHIERS À ANALYSER

### Classes Helper (structure étrange, pas de vraies classes)
- **class-yoyaku-error-handler.php** (24K) - Gestion erreurs avec WP_Error
  - Status: INCERTAIN - Pattern singleton mais peu utilisé
  - Décision: À réviser ou supprimer
  
- **class-yoyaku-theme-loader.php** (14K) - Loader de thèmes
  - Status: INCERTAIN - Fonctionnalité unclear
  - Décision: À analyser l'utilité réelle

- **class-yoyaku-compatibility.php** (30K) - Compatibilité plugins/thèmes
  - Status: INCERTAIN - Très gros fichier, utilité à vérifier
  - Décision: Probablement obsolète

- **yoyaku-fail-safe-functions.php** (12K) - Fonctions fallback
  - Status: INCERTAIN - Backup functions
  - Décision: À analyser si nécessaire

## 🔴 FICHIERS OBSOLÈTES (À SUPPRIMER)

### Fichiers vides ou inutiles
- **ajax-handler.php** (0B) - VIDE
  - Status: OBSOLÈTE
  - Action: SUPPRIMER

### CSS doublons/anciennes versions  
- **assets/css/frontend-FINAL.css** (21K) - Version antérieure
  - Status: BACKUP OBSOLÈTE
  - Action: ARCHIVER puis SUPPRIMER
  
- **assets/css/frontend-FIXED.css** (19K) - Version debug
  - Status: BACKUP OBSOLÈTE  
  - Action: ARCHIVER puis SUPPRIMER
  
- **assets/css/frontend-original.css** (14K) - Version originale
  - Status: BACKUP OBSOLÈTE
  - Action: ARCHIVER puis SUPPRIMER
  
- **assets/css/frontend-mobile-final.css** (13K) - Mobile spécifique
  - Status: POTENTIELLEMENT OBSOLÈTE
  - Action: Vérifier si intégré dans frontend.css

### Documentation
- **README.md** (1.9K) - Documentation basique
  - Status: À REMPLACER
  - Action: Créer nouvelle documentation complète

- **readme.txt** (1.4K) - WordPress readme
  - Status: À METTRE À JOUR
  - Action: Update avec infos correctes

## 📊 RÉSUMÉ

- **Fichiers ACTIFS:** 3 (core PHP, JS, CSS principal)
- **Fichiers INCERTAINS:** 4 (classes helper à analyser)
- **Fichiers OBSOLÈTES:** 5 (CSS backups, fichier vide)
- **Documentation:** 2 (à refaire)

**TOTAL:** 14 fichiers → Cible: ~6-8 fichiers maximum après refactoring

## 🎯 ARCHITECTURE CIBLE PROPOSÉE

```
yoyaku-player-v3/
├── yoyaku-player-v3.php         # Point d'entrée principal
├── includes/
│   ├── class-player.php         # Classe principale refactorée
│   └── class-ajax-handler.php   # Gestion AJAX séparée
├── assets/
│   ├── css/
│   │   └── frontend.css         # CSS unifié et optimisé
│   └── js/
│       └── frontend.js          # JS principal
├── README.md                    # Documentation technique complète
└── readme.txt                   # WordPress readme standard
```

## 📝 PROCHAINES ÉTAPES

1. Vérifier les dépendances des fichiers incertains
2. Tester le plugin sans les fichiers incertains
3. Créer la nouvelle architecture
4. Migrer le code actif
5. Supprimer/archiver les obsolètes
6. Documenter complètement