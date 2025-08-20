# YOYAKU PLAYER V3 - ARCHITECTURE CIBLE
Version: 6.0.0 (Refactored)
Date: 2025-08-20

## 📐 STRUCTURE MODERNE PROPOSÉE

```
yoyaku-player-v3/
│
├── yoyaku-player-v3.php              # Point d'entrée principal WordPress
│
├── includes/                          # Classes PHP organisées
│   ├── class-yoyaku-player.php       # Classe principale du player
│   ├── class-ajax-handler.php        # Gestion AJAX centralisée
│   └── class-assets-loader.php       # Enqueue scripts/styles
│
├── assets/                            # Ressources frontend
│   ├── css/
│   │   ├── player.css               # Styles principaux unifiés
│   │   └── player.min.css           # Version minifiée production
│   │
│   └── js/
│       ├── player.js                # JavaScript principal
│       └── player.min.js            # Version minifiée production
│
├── templates/                         # Templates HTML réutilisables
│   └── player-template.php          # Template principal du player
│
├── docs/                             # Documentation complète
│   ├── README.md                    # Documentation technique
│   ├── CHANGELOG.md                 # Historique versions
│   └── ARCHITECTURE.md             # Ce document
│
├── .gitignore                        # Fichiers à ignorer Git
├── composer.json                     # Autoloader PSR-4 (optionnel)
└── readme.txt                        # WordPress.org readme
```

## 🎯 PRINCIPES D'ARCHITECTURE

### 1. SÉPARATION DES RESPONSABILITÉS
- **yoyaku-player-v3.php**: Bootstrap uniquement, pas de logique
- **includes/**: Toute la logique métier en classes séparées
- **assets/**: Ressources statiques organisées
- **templates/**: HTML réutilisable

### 2. CONVENTION DE NOMMAGE
- Classes: `Yoyaku_Player_*` (WordPress style)
- Fichiers: `class-*.php` pour les classes
- Hooks: Préfixe `yoyaku_player_v3_`
- Functions: Préfixe `ypv3_`

### 3. ORGANISATION DU CODE

#### Fichier Principal (yoyaku-player-v3.php)
```php
// Bootstrap minimal
define('YPV3_VERSION', '6.0.0');
define('YPV3_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YPV3_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload classes
require_once YPV3_PLUGIN_DIR . 'includes/class-yoyaku-player.php';

// Initialize plugin
function ypv3_init() {
    $player = new Yoyaku_Player();
    $player->init();
}
add_action('plugins_loaded', 'ypv3_init');
```

#### Classe Principale (class-yoyaku-player.php)
```php
class Yoyaku_Player {
    private $ajax_handler;
    private $assets_loader;
    
    public function init() {
        $this->load_dependencies();
        $this->define_hooks();
    }
    
    private function load_dependencies() {
        require_once YPV3_PLUGIN_DIR . 'includes/class-ajax-handler.php';
        require_once YPV3_PLUGIN_DIR . 'includes/class-assets-loader.php';
        
        $this->ajax_handler = new Yoyaku_Player_Ajax();
        $this->assets_loader = new Yoyaku_Player_Assets();
    }
    
    private function define_hooks() {
        add_shortcode('yoyaku_player_v3', [$this, 'render_shortcode']);
        // Autres hooks...
    }
}
```

## 📦 MODULES FONCTIONNELS

### 1. MODULE PLAYER (Core)
- Gestion du shortcode
- Rendu du player HTML
- Configuration des options

### 2. MODULE AJAX
- Endpoint pour récupérer les tracks
- Gestion sécurisée avec nonces
- Réponses JSON structurées

### 3. MODULE ASSETS
- Enqueue intelligent (seulement si shortcode présent)
- Versioning automatique
- Minification production

### 4. MODULE TEMPLATES
- Templates override depuis thème
- Fallback vers plugin
- Filtres pour customisation

## 🔧 OPTIMISATIONS TECHNIQUES

### Performance
- Lazy loading des assets
- CSS/JS minifiés en production
- Cache des requêtes AJAX

### Sécurité
- Nonces sur toutes les requêtes AJAX
- Sanitization des inputs
- Escaping des outputs

### Maintenabilité
- Code commenté PHPDoc
- Fonctions unitaires testables
- Pas de code dupliqué

## 📊 MÉTRIQUES CIBLES

- **Fichiers**: 8-10 maximum (vs 14 actuels)
- **Taille totale**: < 100KB (vs ~200KB actuels)
- **Temps chargement**: < 50ms
- **Score Lighthouse**: > 95/100

## 🚀 MIGRATION DEPUIS V5.4.3

### Fichiers à migrer
1. `yoyaku-player-v3.php` → Refactorer en bootstrap minimal
2. `assets/js/frontend.js` → Renommer en `player.js`
3. `assets/css/frontend.css` → Optimiser et renommer `player.css`

### Fichiers à supprimer
- Tous les fichiers class-* non utilisés
- Toutes les versions CSS obsolètes
- Le fichier ajax-handler.php vide

### Nouvelle fonctionnalité
- Ajout d'un système de templates overridable
- Configuration via filtres WordPress
- Support des hooks pour extensions

## ✅ CHECKLIST PRÉ-REFACTORING

- [ ] Backup complet effectué
- [ ] Tests fonctionnels documentés
- [ ] Dépendances identifiées
- [ ] Plan de rollback défini
- [ ] Documentation mise à jour

## 📝 STANDARDS DE CODE

- **PHP**: PSR-12 + WordPress Coding Standards
- **JS**: ESLint avec config WordPress
- **CSS**: BEM methodology
- **Git**: Conventional commits

## 🎨 EXEMPLE STRUCTURE CSS

```css
/* BEM Naming Convention */
.ypv3-player {}
.ypv3-player__controls {}
.ypv3-player__button {}
.ypv3-player__button--play {}
.ypv3-player__waveform {}
.ypv3-player--loading {}
```

## 🔄 WORKFLOW DÉVELOPPEMENT

1. **Feature branch**: `feature/player-refactoring`
2. **Commits atomiques**: Un commit par changement logique
3. **Tests**: Validation après chaque étape
4. **Review**: Code review avant merge
5. **Deploy**: Tag de version et changelog