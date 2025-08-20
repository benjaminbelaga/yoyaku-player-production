/**
 * YOYAKU Player - iPhone Detection & Fix Robuste
 * Détection iPhone plus précise et application forcée du layout mobile
 */

(function() {
    'use strict';
    
    console.log('🍎 iPhone Fix Script chargé');
    
    // Détection iPhone ultra-robuste
    function isIPhone() {
        const userAgent = navigator.userAgent.toLowerCase();
        const platform = navigator.platform.toLowerCase();
        const vendor = navigator.vendor.toLowerCase();
        
        // Vérifications multiples
        const checks = [
            /iphone/.test(userAgent),
            /ipod/.test(userAgent),
            /iphone/.test(platform),
            /ipod/.test(platform),
            vendor.includes('apple') && /mobile/.test(userAgent),
            'ontouchstart' in window && /safari/.test(userAgent) && !/chrome/.test(userAgent)
        ];
        
        const isIPhoneDevice = checks.some(check => check);
        
        console.log('🍎 iPhone Detection:', {
            userAgent: userAgent,
            platform: platform,
            vendor: vendor,
            checks: checks,
            result: isIPhoneDevice
        });
        
        return isIPhoneDevice;
    }
    
    // Détection mobile générale
    function isMobile() {
        const width = window.innerWidth || document.documentElement.clientWidth;
        const userAgent = navigator.userAgent.toLowerCase();
        
        const isMobileWidth = width <= 768;
        const isMobileUA = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent);
        const isTouchDevice = 'ontouchstart' in window;
        
        const result = isMobileWidth || isMobileUA || isTouchDevice;
        
        console.log('📱 Mobile Detection:', {
            width: width,
            isMobileWidth: isMobileWidth,
            isMobileUA: isMobileUA,
            isTouchDevice: isTouchDevice,
            result: result
        });
        
        return result;
    }
    
    // Force l'application des classes mobile
    function forceIPhoneLayout() {
        const player = document.getElementById('yoyaku-player') || document.querySelector('.yoyaku-player-ultra-fin');
        
        if (!player) {
            console.log('⚠️ Player non trouvé, retry dans 500ms');
            setTimeout(forceIPhoneLayout, 500);
            return;
        }
        
        console.log('🍎 Application forcée du layout iPhone');
        
        // Supprimer toutes les classes existantes
        player.classList.remove('mobile-layout', 'iphone-device', 'desktop-layout');
        
        // Ajouter les classes appropriées
        if (isIPhone()) {
            player.classList.add('iphone-device', 'mobile-layout');
            console.log('✅ Classes iPhone appliquées');
        } else if (isMobile()) {
            player.classList.add('mobile-layout');
            console.log('✅ Classe mobile appliquée');
        }
        
        // Force le recalcul du layout
        player.style.display = 'none';
        player.offsetHeight; // Force reflow
        player.style.display = '';
        
        // Debug: Afficher les classes appliquées
        console.log('📋 Classes finales:', player.classList.toString());
        
        // Ajouter un indicateur de debug temporaire
        if (isIPhone()) {
            addDebugIndicator();
        }
    }
    
    // Ajouter un indicateur de debug
    function addDebugIndicator() {
        // Supprimer ancien indicateur
        const existingDebug = document.querySelector('.debug-iphone-player');
        if (existingDebug) {
            existingDebug.remove();
        }
        
        const debug = document.createElement('div');
        debug.className = 'debug-iphone-player';
        debug.innerHTML = `
            iPhone: ${isIPhone() ? '✅' : '❌'}<br>
            Mobile: ${isMobile() ? '✅' : '❌'}<br>
            Screen: ${window.innerWidth}x${window.innerHeight}<br>
            UA: ${navigator.userAgent.substring(0, 20)}...
        `;
        
        document.body.appendChild(debug);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            debug.remove();
        }, 5000);
    }
    
    // Force le viewport meta tag si absent
    function ensureViewportMeta() {
        let viewport = document.querySelector('meta[name="viewport"]');
        
        if (!viewport) {
            console.log('📱 Ajout du viewport meta tag');
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover';
            document.head.appendChild(viewport);
        } else {
            // Mettre à jour le viewport existant
            console.log('📱 Mise à jour du viewport meta tag');
            viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover';
        }
    }
    
    // Fonction d'initialisation
    function initIPhoneFix() {
        console.log('🚀 Initialisation iPhone Fix');
        
        // Assurer le viewport
        ensureViewportMeta();
        
        // Forcer le layout immédiatement
        forceIPhoneLayout();
        
        // Re-forcer après changement d'orientation
        window.addEventListener('orientationchange', function() {
            console.log('🔄 Changement orientation détecté');
            setTimeout(() => {
                forceIPhoneLayout();
            }, 300);
        });
        
        // Re-forcer après resize
        window.addEventListener('resize', function() {
            console.log('🔄 Resize détecté');
            setTimeout(() => {
                forceIPhoneLayout();
            }, 300);
        });
        
        // Observer le DOM pour les changements du player
        if (window.MutationObserver) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        const addedNodes = Array.from(mutation.addedNodes);
                        const hasPlayer = addedNodes.some(node => 
                            node.nodeType === 1 && 
                            (node.id === 'yoyaku-player' || node.classList?.contains('yoyaku-player-ultra-fin'))
                        );
                        
                        if (hasPlayer) {
                            console.log('🎵 Player ajouté au DOM, application du fix');
                            setTimeout(forceIPhoneLayout, 100);
                        }
                    }
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }
    
    // Patcher la classe YoyakuPlayerUltraFin si elle existe
    function patchPlayerClass() {
        if (window.YoyakuPlayerUltraFin) {
            console.log('🔧 Patching YoyakuPlayerUltraFin.createPlayerHTML');
            
            const originalCreatePlayerHTML = window.YoyakuPlayerUltraFin.prototype.createPlayerHTML;
            
            window.YoyakuPlayerUltraFin.prototype.createPlayerHTML = function() {
                // Appeler la méthode originale
                originalCreatePlayerHTML.call(this);
                
                // Forcer le layout après création
                setTimeout(() => {
                    forceIPhoneLayout();
                }, 100);
            };
        }
    }
    
    // Démarrage selon l'état du DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initIPhoneFix);
    } else {
        initIPhoneFix();
    }
    
    // Patcher après le chargement complet
    window.addEventListener('load', function() {
        patchPlayerClass();
        
        // Force finale après 2 secondes
        setTimeout(() => {
            console.log('🏁 Force finale du layout iPhone');
            forceIPhoneLayout();
        }, 2000);
    });
    
    // Exposer les fonctions pour debug
    window.yoyakuIPhoneFix = {
        isIPhone: isIPhone,
        isMobile: isMobile,
        forceLayout: forceIPhoneLayout,
        addDebug: addDebugIndicator
    };
    
    console.log('✅ iPhone Fix Script initialisé');
    
})();