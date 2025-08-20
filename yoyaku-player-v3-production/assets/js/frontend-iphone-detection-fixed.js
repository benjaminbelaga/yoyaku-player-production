/**
 * YOYAKU Player V3 - iPhone Detection ROBUSTE 2024
 * Détection améliorée pour iPhone 15/16 et compatibilité Safari
 */

class iPhoneDetection2024 {
    constructor() {
        this.deviceInfo = this.detectDevice();
        console.log('🍎 iPhone Detection 2024:', this.deviceInfo);
    }
    
    detectDevice() {
        const userAgent = navigator.userAgent.toLowerCase();
        const platform = navigator.platform.toLowerCase();
        const vendor = navigator.vendor?.toLowerCase() || '';
        
        // Détection iPhone robuste 2024
        const isIPhone = this.isIPhoneDevice(userAgent, platform, vendor);
        const isIOS = this.isIOSDevice();
        const isSafari = this.isSafariBrowser(userAgent);
        const screenInfo = this.getScreenInfo();
        
        return {
            isIPhone,
            isIOS,
            isSafari,
            isMobile: this.isMobileDevice(),
            screenInfo,
            needsMobileLayout: isIPhone || (screenInfo.width <= 768),
            safariVersion: this.getSafariVersion(userAgent),
            deviceModel: this.getIPhoneModel(screenInfo)
        };
    }
    
    isIPhoneDevice(userAgent, platform, vendor) {
        // Méthode 1: User agent classique
        if (/iphone|ipod/.test(userAgent)) return true;
        
        // Méthode 2: Platform
        if (/iphone|ipod/.test(platform)) return true;
        
        // Méthode 3: Apple vendor + mobile
        if (vendor.includes('apple') && /mobile/.test(userAgent)) return true;
        
        // Méthode 4: Détection par écran + touch (iPhone 15/16)
        const screen = this.getScreenInfo();
        if (screen.width <= 430 && 'ontouchstart' in window && 
            /safari/.test(userAgent) && !/chrome/.test(userAgent)) {
            return true;
        }
        
        // Méthode 5: Détection CSS supports spécifique iOS
        if (this.supportsIOSFeatures()) return true;
        
        return false;
    }
    
    isIOSDevice() {
        // Détection iOS robuste incluant iPad en mode desktop
        return /iPad|iPhone|iPod/.test(navigator.userAgent) ||
               (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) ||
               this.supportsIOSFeatures();
    }
    
    supportsIOSFeatures() {
        // Test des features CSS spécifiques iOS
        return CSS.supports && (
            CSS.supports('-webkit-touch-callout', 'none') ||
            CSS.supports('-webkit-overflow-scrolling', 'touch')
        );
    }
    
    isSafariBrowser(userAgent) {
        // Safari detection robuste
        return /safari/.test(userAgent) && 
               !/chrome|chromium|crios|fxios|edgios/.test(userAgent);
    }
    
    isMobileDevice() {
        const screen = this.getScreenInfo();
        return screen.width <= 768 || 
               'ontouchstart' in window ||
               /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(navigator.userAgent);
    }
    
    getScreenInfo() {
        return {
            width: window.innerWidth || document.documentElement.clientWidth || screen.width,
            height: window.innerHeight || document.documentElement.clientHeight || screen.height,
            devicePixelRatio: window.devicePixelRatio || 1,
            orientation: window.orientation || 0
        };
    }
    
    getSafariVersion(userAgent) {
        const match = userAgent.match(/version\/(\d+\.?\d*)/);
        return match ? parseFloat(match[1]) : null;
    }
    
    getIPhoneModel(screenInfo) {
        const { width, height, devicePixelRatio } = screenInfo;
        
        // iPhone models basés sur les dimensions 2024
        if (width === 430) return 'iPhone 15 Pro Max / iPhone 16 Pro Max';
        if (width === 393) return 'iPhone 15 Pro / iPhone 16 Pro';
        if (width === 390) return 'iPhone 15 / iPhone 16';
        if (width === 414) return 'iPhone 14 Pro Max';
        if (width === 393) return 'iPhone 14 Pro';
        if (width === 390) return 'iPhone 14';
        if (width === 375) return 'iPhone SE / iPhone 13 mini';
        
        return `Unknown iPhone (${width}x${height})`;
    }
    
    addViewportMetaTag() {
        // Ajoute le viewport meta tag si manquant
        let viewport = document.querySelector('meta[name="viewport"]');
        
        if (!viewport) {
            console.warn('🚨 VIEWPORT META TAG MANQUANT - Ajout automatique');
            viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, shrink-to-fit=no, viewport-fit=cover';
            document.head.insertBefore(viewport, document.head.firstChild);
            
            // Force refresh du layout
            window.dispatchEvent(new Event('resize'));
        } else {
            console.log('✅ Viewport meta tag présent:', viewport.content);
        }
    }
    
    applyIOSFixes() {
        if (!this.deviceInfo.isIPhone && !this.deviceInfo.isIOS) return;
        
        console.log('🔧 Application des fixes iOS Safari...');
        
        // Fix 1: Viewport meta tag
        this.addViewportMetaTag();
        
        // Fix 2: CSS classes pour ciblage
        document.documentElement.classList.add('ios-device');
        if (this.deviceInfo.isIPhone) {
            document.documentElement.classList.add('iphone-device');
        }
        
        // Fix 3: Safe area insets
        this.applySafeAreaInsets();
        
        // Fix 4: iOS Safari viewport height fix
        this.fixIOSViewportHeight();
        
        // Fix 5: Touch event optimizations
        this.optimizeTouchEvents();
    }
    
    applySafeAreaInsets() {
        // Ajoute les CSS custom properties pour safe areas
        const style = document.createElement('style');
        style.textContent = `
            :root {
                --safe-area-inset-top: env(safe-area-inset-top, 0px);
                --safe-area-inset-right: env(safe-area-inset-right, 0px);
                --safe-area-inset-bottom: env(safe-area-inset-bottom, 0px);
                --safe-area-inset-left: env(safe-area-inset-left, 0px);
            }
        `;
        document.head.appendChild(style);
    }
    
    fixIOSViewportHeight() {
        // Fix pour le problème de hauteur viewport iOS Safari
        const setVH = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };
        
        setVH();
        window.addEventListener('resize', setVH);
        window.addEventListener('orientationchange', () => {
            setTimeout(setVH, 100); // Délai pour iOS
        });
    }
    
    optimizeTouchEvents() {
        // Optimisations touch events pour iOS
        document.body.style.touchAction = 'manipulation';
        document.body.style.webkitTouchCallout = 'none';
        document.body.style.webkitUserSelect = 'none';
    }
    
    addDebugInfo() {
        // Debug info pour développement
        if (window.location.search.includes('debug=ios')) {
            const debug = document.createElement('div');
            debug.className = 'debug-iphone-viewport';
            debug.innerHTML = `
                <div>iPhone: ${this.deviceInfo.isIPhone}</div>
                <div>iOS: ${this.deviceInfo.isIOS}</div>
                <div>Safari: ${this.deviceInfo.isSafari}</div>
                <div>Model: ${this.deviceInfo.deviceModel}</div>
                <div>Screen: ${this.deviceInfo.screenInfo.width}x${this.deviceInfo.screenInfo.height}</div>
                <div>DPR: ${this.deviceInfo.screenInfo.devicePixelRatio}</div>
                <div>UA: ${navigator.userAgent.substring(0, 50)}...</div>
            `;
            debug.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                background: rgba(0,0,0,0.9);
                color: white;
                padding: 10px;
                font-size: 10px;
                z-index: 999999;
                font-family: monospace;
                max-width: 300px;
                line-height: 1.2;
            `;
            document.body.appendChild(debug);
        }
    }
}

// Auto-initialize sur DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.iPhoneDetection = new iPhoneDetection2024();
    window.iPhoneDetection.applyIOSFixes();
    window.iPhoneDetection.addDebugInfo();
});

// Export pour utilisation dans autres scripts
window.iPhoneDetection2024 = iPhoneDetection2024;