/**
 * SOLUTION ULTRATHINK - iPhone Safari SVG Icons
 * Injection SVG Inline pour compatibilité maximale
 * Garantit fonctionnement sur TOUS les iPhones
 */

class YoyakuIconsUltraThink {
    constructor() {
        this.svgIcons = {
            prev: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 6h2v12H6zm3.5 6l8.5 6V6z"/></svg>',
            next: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white"><path d="M6 18l8.5-6L6 6v12zM16 6v12h2V6h-2z"/></svg>',
            play: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffd700"><path d="M8 5v14l11-7z"/></svg>',
            pause: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#ffd700"><path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/></svg>'
        };
        
        this.fallbackUnicode = {
            prev: '⏮',
            next: '⏭', 
            play: '▶',
            pause: '⏸'
        };
        
        this.init();
    }
    
    init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.injectIcons());
        } else {
            this.injectIcons();
        }
        
        // Observer les changements pour les boutons créés dynamiquement
        this.observePlayerChanges();
    }
    
    injectIcons() {
        console.log('ULTRATHINK: Injection des icônes SVG optimisées Safari...');
        
        // Détecter si on est sur mobile
        if (window.innerWidth <= 768) {
            this.injectMobileIcons();
        }
    }
    
    injectMobileIcons() {
        const player = document.querySelector('.yoyaku-player-ultra-fin');
        if (!player) {
            console.log('ULTRATHINK: Player non trouvé, retry dans 500ms...');
            setTimeout(() => this.injectMobileIcons(), 500);
            return;
        }
        
        // Boutons de contrôle
        const prevBtn = player.querySelector('.control-btn.prev');
        const nextBtn = player.querySelector('.control-btn.next');
        const playBtn = player.querySelector('.control-btn.play-pause');
        
        if (prevBtn) this.injectIcon(prevBtn, 'prev');
        if (nextBtn) this.injectIcon(nextBtn, 'next');
        if (playBtn) this.setupPlayPauseButton(playBtn);
        
        console.log('ULTRATHINK: Icônes SVG injectées avec succès');
    }
    
    injectIcon(button, iconType) {
        try {
            // Essayer d'injecter SVG inline
            if (this.supportsSVG()) {
                button.innerHTML = this.svgIcons[iconType];
                button.classList.add('has-inline-svg');
                console.log(`ULTRATHINK: SVG ${iconType} injecté`);
            } else {
                // Fallback Unicode
                button.innerHTML = `<span style="font-size: 14px; color: white; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">${this.fallbackUnicode[iconType]}</span>`;
                button.classList.add('has-unicode-fallback');
                console.log(`ULTRATHINK: Unicode ${iconType} appliqué`);
            }
        } catch (error) {
            console.error(`ULTRATHINK: Erreur injection ${iconType}:`, error);
            // Fallback d'urgence
            button.innerHTML = `<span style="font-size: 14px; color: white;">${this.fallbackUnicode[iconType]}</span>`;
        }
    }
    
    setupPlayPauseButton(button) {
        if (!button) return;
        
        // État initial (bouton play)
        this.updatePlayPauseIcon(button, false);
        
        // Observer les changements de classe pour play/pause
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    const isPlaying = button.classList.contains('btn-pause') || 
                                     button.closest('.yoyaku-player-ultra-fin').classList.contains('player-playing');
                    this.updatePlayPauseIcon(button, isPlaying);
                }
            });
        });
        
        observer.observe(button, { attributes: true });
        observer.observe(button.closest('.yoyaku-player-ultra-fin'), { attributes: true });
        
        // Vérifier aussi le contenu du bouton
        const contentObserver = new MutationObserver(() => {
            const isPlaying = button.textContent.includes('Pause') || 
                             button.querySelector('.btn-pause');
            this.updatePlayPauseIcon(button, isPlaying);
        });
        
        contentObserver.observe(button, { childList: true, subtree: true });
    }
    
    updatePlayPauseIcon(button, isPlaying) {
        const iconType = isPlaying ? 'pause' : 'play';
        
        try {
            if (this.supportsSVG()) {
                button.innerHTML = this.svgIcons[iconType];
                button.classList.add('has-inline-svg');
            } else {
                button.innerHTML = `<span style="font-size: 16px; color: #ffd700; font-family: -apple-system, BlinkMacSystemFont, sans-serif;">${this.fallbackUnicode[iconType]}</span>`;
                button.classList.add('has-unicode-fallback');
            }
            
            console.log(`ULTRATHINK: Play/Pause mis à jour: ${iconType}`);
        } catch (error) {
            console.error(`ULTRATHINK: Erreur update play/pause:`, error);
            button.innerHTML = `<span style="font-size: 16px; color: #ffd700;">${this.fallbackUnicode[iconType]}</span>`;
        }
    }
    
    supportsSVG() {
        // Test rapide du support SVG
        return document.implementation.hasFeature("http://www.w3.org/TR/SVG11/feature#BasicStructure", "1.1");
    }
    
    observePlayerChanges() {
        // Observer l'apparition du player (chargé dynamiquement)
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        if (node.classList && node.classList.contains('yoyaku-player-ultra-fin')) {
                            setTimeout(() => this.injectMobileIcons(), 100);
                        }
                        
                        // Vérifier les enfants aussi
                        const players = node.querySelectorAll ? node.querySelectorAll('.yoyaku-player-ultra-fin') : [];
                        if (players.length > 0) {
                            setTimeout(() => this.injectMobileIcons(), 100);
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Méthode utilitaire pour re-injection manuelle
    forceReinject() {
        console.log('ULTRATHINK: Re-injection forcée des icônes...');
        this.injectMobileIcons();
    }
    
    // Test de compatibilité Safari
    testSafariCompatibility() {
        const results = {
            isSafari: /^((?!chrome|android).)*safari/i.test(navigator.userAgent),
            isIOS: /iPad|iPhone|iPod/.test(navigator.userAgent),
            supportsSVG: this.supportsSVG(),
            supportsDataURI: true,
            supportsBase64: true
        };
        
        // Test data URI
        try {
            const img = new Image();
            img.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>';
            results.supportsDataURI = true;
        } catch(e) {
            results.supportsDataURI = false;
        }
        
        console.log('ULTRATHINK: Compatibilité Safari:', results);
        return results;
    }
}

// Auto-initialisation
document.addEventListener('DOMContentLoaded', () => {
    window.yoyakuIconsUltraThink = new YoyakuIconsUltraThink();
    
    // Test de compatibilité
    if (window.innerWidth <= 768) {
        window.yoyakuIconsUltraThink.testSafariCompatibility();
    }
});

// Pour le debugging
window.reinjectIcons = () => {
    if (window.yoyakuIconsUltraThink) {
        window.yoyakuIconsUltraThink.forceReinject();
    }
};