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
/* ============================================== */
/*   PLAYER PRINCIPAL - AUTO-INTEGRÉ 2025-08-16   */
/* ============================================== */
/**
 * YOYAKU Player V3 ULTRA-FIN
 * Height: 48px desktop, 42px mobile
 * WaveSurfer.js v7 with CDN failover
 * Pitch control: -8 to +8 (6% per step)
 */

class YoyakuPlayerUltraFin {
    constructor() {
        this.wavesurfer = null;
        this.currentProduct = null;
        this.currentTrackIndex = 0;
        this.isPlaying = false;
        this.volume = 80;
        this.pitch = 0;
        this.tracks = [];
        this.autoPlayAfterLoad = true;
        
        // Try WaveSurfer first, fallback to HTML5
        this.useHTML5Audio = false;
        
        this.initializePlayer();
    }
    
    async initializePlayer() {
        console.log('Initializing YOYAKU Player ULTRA-FIN...');
        
        // Try to load WaveSurfer
        await this.loadWaveSurfer();
        
        // Create player container
        this.createPlayerHTML();
        
        // Initialize WaveSurfer or HTML5
        this.initWaveSurfer();
        
        // Bind events
        this.bindEvents();
        
        // Check for autoplay
        this.checkAutoplay();
    }
    
    async loadWaveSurfer() {
        // BENJAMIN FIX: Always use WaveSurfer for real waveform synchronization
        console.log('Loading WaveSurfer for real audio analysis and synchronization');
        
        // Try to load WaveSurfer from CDN with fallback
        try {
            if (window.WaveSurfer) {
                console.log('WaveSurfer already loaded');
                return;
            }
            
            // Try primary CDN
            try {
                await this.loadScript('https://unpkg.com/wavesurfer.js@7.8.0/dist/wavesurfer.umd.min.js');
                console.log('WaveSurfer loaded from unpkg');
            } catch (e) {
                // Fallback to alternative CDN
                await this.loadScript('https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.min.js');
                console.log('WaveSurfer loaded from jsdelivr');
            }
            
        } catch (error) {
            console.warn('WaveSurfer loading failed, using HTML5 audio mode');
            this.useHTML5Audio = true;
        }
    }
    
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    createPlayerHTML() {
        // BENJAMIN FIX: Détection iPhone ULTRA-ROBUSTE
        const screenWidth = window.innerWidth || document.documentElement.clientWidth || screen.width;
        const userAgent = navigator.userAgent.toLowerCase();
        const platform = navigator.platform.toLowerCase();
        
        // Détection mobile multiple critères
        const isMobile = screenWidth <= 768 || 
                        /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent) ||
                        'ontouchstart' in window ||
                        (window.matchMedia && window.matchMedia('(max-device-width: 768px)').matches) ||
                        (window.matchMedia && window.matchMedia('(max-width: 768px)').matches);
        
        // Détection iPhone spécifique - TOUS MODÈLES
        const isIPhone = /iphone|ipod/.test(userAgent) || 
                         /iphone|ipod/.test(platform) ||
                         (navigator.vendor && navigator.vendor.toLowerCase().includes('apple') && /mobile/.test(userAgent)) ||
                         (screenWidth <= 430 && 'ontouchstart' in window && /safari/.test(userAgent) && !/chrome/.test(userAgent));
        
        console.log('🍎 Détection Device:', { 
            screenWidth, 
            userAgent: userAgent.substring(0, 50), 
            isMobile, 
            isIPhone, 
            platform,
            vendor: navigator.vendor 
        });
        
        const playerHTML = `
            <div class="yoyaku-player-ultra-fin ${isMobile ? 'mobile-layout' : ''} ${isIPhone ? 'iphone-device' : ''}" id="yoyaku-player">
                <div class="player-inner">
                    <!-- Vinyl Macaron -->
                    <div class="vinyl-cover">
                        <div class="vinyl-wrapper">
                            <img class="vinyl-image" src="" alt="">
                        </div>
                    </div>
                    
                    <!-- Playlist - Will be reordered on mobile via CSS -->
                    <div class="playlist-container">
                        <div class="playlist-toggle">
                            <div class="playlist-info">
                                <div class="product-line-1">
                                    <span class="product-artist">--</span> - <span class="product-title">No product loaded</span>
                                </div>
                                <div class="product-line-2 ${isMobile ? 'mobile-hidden' : ''}">
                                    <span class="product-label">--</span> - <span class="product-sku">--</span>
                                </div>
                            </div>
                            <div class="track-display">
                                <span class="current-track">-- | No track</span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                        </div>
                        <div class="playlist-dropdown"></div>
                    </div>
                    
                    <!-- Waveform -->
                    <div class="waveform-container">
                        <div id="waveform"></div>
                    </div>
                    
                    <!-- Controls -->
                    <div class="player-controls">
                        <button class="control-btn prev" title="Previous">◄◄</button>
                        <button class="control-btn play-pause" title="Play/Pause">
                            <span class="btn-play">▶</span>
                            <span class="btn-pause" style="display:none">❚❚</span>
                        </button>
                        <button class="control-btn next" title="Next">►►</button>
                        
                        <!-- Audio Controls -->
                        <div class="audio-controls">
                            <button class="volume-icon" title="Volume">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                                </svg>
                                <div class="volume-popup">
                                    <input type="range" class="volume-slider-horizontal" min="0" max="100" value="80">
                                    <span class="volume-value">80%</span>
                                </div>
                            </button>
                            <button class="pitch-icon desktop-only" title="Pitch">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                                </svg>
                                <div class="pitch-popup">
                                    <input type="range" class="pitch-slider-horizontal" min="-8" max="8" value="0" step="1">
                                    <span class="pitch-value">0</span>
                                </div>
                            </button>
                        </div>
                        
                        <!-- Cart Button -->
                        <button class="cart-btn" title="Add to cart">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Status Indicator -->
            <div class="status-indicator" id="player-status">Ready</div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', playerHTML);
    }
    
    initWaveSurfer() {
        if (this.useHTML5Audio || !window.WaveSurfer) {
            this.initHTML5Audio();
            return;
        }
        
        try {
            this.wavesurfer = WaveSurfer.create({
                container: '#waveform',
                waveColor: 'rgba(255, 255, 255, 0.3)',
                progressColor: '#ffd700',
                cursorColor: '#ffffff',
                barWidth: 2,
                barGap: 1,
                barRadius: 0,
                responsive: true,
                height: (screenWidth <= 768 || isIPhone) ? 42 : 24, // iPhone gets bigger waveform
                normalize: true,
                interact: true,
                hideScrollbar: true,
                fillParent: true,
                // BENJAMIN: Force Web Audio API for real waveform analysis
                backend: 'WebAudio',
                // Force real peaks generation from audio analysis
                peaks: null,
                // Enable CORS for audio files
                cors: 'anonymous',
                // Real-time analysis for better synchronization
                splitChannels: false,
                // Improved rendering for mobile
                forceDecode: true
            });
            
            this.wavesurfer.on("ready", () => {
                console.log("✅ WaveSurfer ready with real waveform");
                this.showStatus("Waveform ready");
                
                // BENJAMIN FIX: Autoplay simplifié - UN SEUL GESTIONNAIRE
                this.handleAutoplayAfterReady();
            });
            
            this.wavesurfer.on('error', (error) => {
                console.error('WaveSurfer error:', error);
                console.log('Falling back to HTML5 audio mode');
                this.wavesurfer = null;
                this.initHTML5Audio();
            });
            
            this.wavesurfer.on('finish', () => {
                this.nextTrack(); // Fixed: Restore auto-advance
                this.isPlaying = false;
                this.updatePlayButton();
            });
            
            this.wavesurfer.on('audioprocess', () => {
                // Update time display during playback
                this.updateTimeDisplay();
            });
            
            console.log('WaveSurfer initialized with waveform visualization');
            
        } catch (error) {
            console.error('WaveSurfer init failed:', error);
            this.initHTML5Audio();
        }
    }
    
    initHTML5Audio() {
        console.log('Using HTML5 audio fallback');
        this.audio = new Audio();
        this.audio.addEventListener('ended', () => {
            this.nextTrack();
            this.isPlaying = false;
            this.updatePlayButton();
        });
        this.audio.addEventListener('loadedmetadata', () => {
            console.log('📝 Audio metadata loaded:', this.audio.duration + 's');
            this.showStatus('Ready');
            
            // BENJAMIN FIX: Autoplay simplifié - UN SEUL GESTIONNAIRE
            this.handleAutoplayAfterReady();
        });
        this.audio.addEventListener('error', (e) => {
            console.error('❌ Audio error:', e);
            this.showStatus('Audio error');
        });
        this.audio.addEventListener('play', () => {
            console.log('▶️ HTML5 audio started playing');
        });
        this.audio.addEventListener('pause', () => {
            console.log('⏸️ HTML5 audio paused');
        });
        
        // Simple progress bar for HTML5 fallback
        document.getElementById('waveform').innerHTML = `
            <div class="html5-progress-container">
                <div class="progress-bar"><div class="progress-fill"></div></div>
                <div class="time-display">0:00 / 0:00</div>
            </div>
        `;
        
        // Update progress
        this.audio.addEventListener('timeupdate', () => {
            if (this.audio.duration) {
                const percentage = (this.audio.currentTime / this.audio.duration) * 100;
                const progressFill = document.querySelector('.progress-fill');
                if (progressFill) {
                    progressFill.style.width = percentage + '%';
                }
                
                const timeDisplay = document.querySelector('.time-display');
                if (timeDisplay) {
                    timeDisplay.textContent = `${this.formatTime(this.audio.currentTime)} / ${this.formatTime(this.audio.duration)}`;
                }
            }
        });
        
        // Add click seeking
        const progressContainer = document.querySelector('.html5-progress-container');
        if (progressContainer) {
            progressContainer.addEventListener('click', (e) => {
                if (this.audio && this.audio.duration) {
                    const rect = progressContainer.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const percentage = Math.max(0, Math.min(1, x / rect.width));
                    this.audio.currentTime = percentage * this.audio.duration;
                }
            });
        }
    }
    
    bindEvents() {
        // Hide old players immediately
        this.hideOldPlayers();
        
        // Detect existing play buttons with multiple selectors
        const playButtonSelectors = ['.fwaplay', '.play-all', '.play-button', '[data-play]', '[href*="play"]', 'a.alltracks.fwap-play'];
        let playButtons = [];
        
        // Find ALL play buttons (including "Play All")
        for (const selector of playButtonSelectors) {
            const buttons = document.querySelectorAll(selector);
            buttons.forEach(button => {
                if (!playButtons.includes(button)) {
                    playButtons.push(button);
                    console.log(`Found play button with selector: ${selector}`, button);
                }
            });
        }
        
        // Connect all play buttons to our player
        playButtons.forEach(playButton => {
            console.log('Connecting play button to ULTRA-FIN player:', playButton);
            playButton.addEventListener('click', (e) => {
                console.log('Play button clicked!', e.target);
                e.preventDefault();
                e.stopPropagation();
                
                // Hide old players again
                this.hideOldPlayers();
                
                // Check if it's a track-specific button
                const trackIndex = playButton.dataset.trackIndex || playButton.getAttribute('data-track-index');
                
                // Get product ID from page
                // FIXED: Get product ID from clicked button FIRST
                let productId = playButton.dataset.product || playButton.getAttribute("data-product");
                if (!productId || productId === "0") {
                    productId = this.getProductIdFromPage();
                }
                if (productId) {
                    console.log('Loading product:', productId);
                    
                    // If track index specified, load that track
                    if (trackIndex !== null && trackIndex !== undefined) {
                        console.log('Loading specific track:', trackIndex);
                        this.loadProduct(productId).then(() => {
                            this.loadTrack(parseInt(trackIndex));
                            this.autoPlayAfterLoad = true;
                        });
                    } else {
                        this.loadProduct(productId).then(() => { this.autoPlayAfterLoad = true; });
                    }
                }
            });
        });
        
        if (playButtons.length === 0) {
            console.log('No existing play button found, will add one');
            setTimeout(() => this.addPlayButton(), 1000);
        }
        
        // Also support custom play buttons and track links
        document.addEventListener('click', (e) => {
            console.log('Document click detected on:', e.target);
            
            // Check for yoyaku play button
            if (e.target.matches('.yoyaku-play-button')) {
                const productId = e.target.dataset.productId;
                this.loadProduct(productId);
                return;
            }
            
            // Check for track links (a.alltracks)
            const trackLink = e.target.closest('a.alltracks');
            if (trackLink) {
                console.log('Track link clicked:', trackLink);
                e.preventDefault();
                e.stopPropagation();
                
                // Get track number from link
                const trackText = trackLink.textContent;
                const trackMatch = trackText.match(/[AB]\d/);
                const trackSide = trackMatch ? trackMatch[0][0] : null;
                const trackNum = trackMatch ? parseInt(trackMatch[0][1]) : null;
                
                console.log('Track info:', trackSide, trackNum);
                
                // Calculate track index (A1=0, A2=1, B1=2, B2=3, etc.)
                let trackIndex = 0;
                if (trackSide === 'B') {
                    // Assume 2 tracks per side for now
                    trackIndex = 2 + (trackNum - 1);
                } else {
                    trackIndex = trackNum - 1;
                }
                
                console.log('Calculated track index:', trackIndex);
                
                // Get product ID and load
                // FIXED: Get product ID from track button
                let productId = trackLink.dataset.product || trackLink.getAttribute("data-product");
                if (!productId || productId === "0") {
                    productId = this.getProductIdFromPage();
                }
                if (productId) {
                    // If player not loaded yet, load it first
                    if (!this.currentProduct || this.currentProduct.product_id != productId) {
                        this.loadProduct(productId).then(() => {
                            this.loadTrack(trackIndex);
                            this.autoPlayAfterLoad = true;
                        });
                    } else {
                        // Player already loaded, just switch track
                        this.loadTrack(trackIndex);
                        this.autoPlayAfterLoad = true;
                    }
                }
                return;
            }
            
            // Check for "Play All" link
            const playAllLink = e.target.closest('a.alltracks.fwap-play');
            if (playAllLink) {
                console.log('Play All clicked:', playAllLink);
                e.preventDefault();
                e.stopPropagation();
                
                const productId = this.getProductIdFromPage();
                if (productId) {
                    this.loadProduct(productId);
                    this.autoPlayAfterLoad = true;
                }
                return;
            }
        });
        
        // Player controls
        document.querySelector('.play-pause').addEventListener('click', () => this.togglePlayPause());
        document.querySelector('.prev').addEventListener('click', () => this.previousTrack());
        document.querySelector('.next').addEventListener('click', () => this.nextTrack());
        
        // Playlist toggle
        document.querySelector('.playlist-toggle').addEventListener('click', () => {
            document.querySelector('.playlist-container').classList.toggle('open');
        });
        
        // Playlist items (delegated)
        document.querySelector('.playlist-dropdown').addEventListener('click', (e) => {
            console.log('Playlist dropdown clicked:', e.target);
            // Find the playlist item even if clicking on child elements
            const playlistItem = e.target.closest('.playlist-item');
            if (playlistItem) {
                console.log('Playlist item found:', playlistItem);
                const index = parseInt(playlistItem.dataset.index);
                console.log('Loading track index:', index);
                
                // Always auto-play when selecting from playlist
                this.loadTrack(index);
                this.autoPlayAfterLoad = true;
                
                document.querySelector('.playlist-container').classList.remove('open');
            }
        });
        
        // Volume icon click - toggle popup
        document.querySelector('.volume-icon').addEventListener('click', (e) => {
            e.currentTarget.classList.toggle('active');
            document.querySelector('.pitch-icon').classList.remove('active');
        });
        
        // Volume control
        const volumeSlider = document.querySelector('.volume-slider-horizontal');
        volumeSlider.addEventListener('input', (e) => {
            this.volume = e.target.value;
            document.querySelector('.volume-value').textContent = this.volume + '%';
            this.updateVolume();
        });
        
        // Pitch control - only on desktop
        const pitchIcon = document.querySelector('.pitch-icon');
        if (pitchIcon && !window.yoyaku_player_v3?.is_mobile) {
            pitchIcon.addEventListener('click', (e) => {
                e.currentTarget.classList.toggle('active');
                document.querySelector('.volume-icon').classList.remove('active');
            });
            
            const pitchSlider = document.querySelector('.pitch-slider-horizontal');
            if (pitchSlider) {
                pitchSlider.addEventListener('input', (e) => {
                    this.pitch = parseInt(e.target.value);
                    document.querySelector('.pitch-value').textContent = this.pitch > 0 ? '+'+this.pitch : this.pitch;
                    this.applyPitch();
                });
            }
        }
        
        // Cart button
        document.querySelector('.cart-btn').addEventListener('click', () => this.addToCart());
        
        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.playlist-container')) {
                document.querySelector('.playlist-container').classList.remove('open');
            }
            
            // Close volume/pitch popups on outside click
            if (!e.target.closest('.volume-icon')) {
                document.querySelector('.volume-icon').classList.remove('active');
            }
            if (!e.target.closest('.pitch-icon')) {
                const pitchIcon = document.querySelector('.pitch-icon');
                if (pitchIcon) pitchIcon.classList.remove('active');
            }
        });
    }
    
    async loadProduct(productId) {
        console.log(`[YOYAKU DEBUG] Loading product ${productId}, current: ${this.currentProduct ? this.currentProduct.product_id : "none"}`);
        this.currentProductId = productId; // Store current product ID for navigation
        this.showStatus('Loading...');
        
        return new Promise((resolve, reject) => {
            const loadProductData = async () => {
        try {
            const response = await fetch(yoyaku_player_v3.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'yoyaku_player_v3_get_track',
                    product_id: productId,
                    nonce: yoyaku_player_v3.nonce || ''
                })
            });
            
            const text = await response.text();
            console.log('Raw response:', text);
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Invalid response format');
            }
            
            console.log('Parsed data:', data);
            
            if (data.success && data.data && data.data.tracks) {
                // Decode HTML entities in product data
                data.data.title = this.decodeHtmlEntities(data.data.title || '');
                data.data.artist = this.decodeHtmlEntities(data.data.artist || '');
                data.data.label = this.decodeHtmlEntities(data.data.label || '');
                
                this.currentProduct = data.data;
                this.tracks = data.data.tracks;
                this.currentTrackIndex = 0;
                
                console.log('Tracks loaded:', this.tracks.length, 'tracks');
// YOYAKU: Enhanced validation and UI state management                if (this.tracks.length === 0) {                    console.warn("[YOYAKU WARNING] No tracks found for product", productId);                    this.showStatus("No tracks available");                    reject(new Error("No tracks available"));                    return;                }                                // Ensure UI elements exist before updating                const playerElement = document.getElementById("yoyaku-player");                const vinylImage = document.querySelector(".vinyl-image");                                if (playerElement) {                    playerElement.classList.add("active");                    console.log("[YOYAKU] Player activated successfully");                } else {                    console.error("[YOYAKU ERROR] Player element not found!");                }                                if (vinylImage && data.data.cover) {                    vinylImage.src = data.data.cover;                    console.log("[YOYAKU] Cover image updated:", data.data.cover);                }
                
                // Update UI
                document.getElementById('yoyaku-player').classList.add('active');
                document.querySelector('.vinyl-image').src = data.data.cover || '';
                
                this.buildPlaylist();
                this.loadTrack(0);
                resolve(); // Resolve the promise after loading
            } else {
                console.error('Invalid data structure:', data);
                reject(new Error(data.data || 'Failed to load tracks'));
            }
        } catch (error) {
            console.error('Error loading product:', error);
            this.showStatus('Error loading tracks');
            reject(error);
        }
            };
            loadProductData();
        });
    }
    
    buildPlaylist() {
        const dropdown = document.querySelector('.playlist-dropdown');
        dropdown.innerHTML = '';
        
        this.tracks.forEach((track, index) => {
            const item = document.createElement('div');
            item.className = 'playlist-item';
            item.dataset.index = index;
            
            // Only show BPM if it exists and is not null/empty
            const bpmText = track.bpm && track.bpm !== null ? `<span class="track-bpm">${track.bpm} BPM</span>` : '';
            
            item.innerHTML = `
                <span>${track.name}</span>
                ${bpmText}
            `;
            dropdown.appendChild(item);
        });
    }
    
    loadTrack(index) {
        if (!this.tracks || !this.tracks[index]) {
            console.error('❌ Cannot load track:', index, 'Total tracks:', this.tracks?.length);
            return;
        }
        
        console.log(`🎵 Loading track ${index + 1}/${this.tracks.length}: ${this.tracks[index].name}`);
        
        // IMPORTANT: Stop current playback before loading new track
        if (this.isPlaying) {
            this.pause();
        }
        
        this.currentTrackIndex = index;
        const track = this.tracks[index];
        
        // Update playlist display
        document.querySelectorAll('.playlist-item').forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
        
        // Update product metadata (remains constant for all tracks)
        const artist = this.currentProduct?.artist || 'Unknown Artist';
        const title = this.currentProduct?.title || 'Unknown Product';
        const sku = this.currentProduct?.sku || 'SKU';
        const label = this.currentProduct?.label || 'Label';
        
        // Update the 2-line product display
        // Update the 2-line product display - FIX TITRE BENJAMIN
        const playerContainer = document.getElementById('yoyaku-player');
        if (playerContainer) {
            const artistEl = playerContainer.querySelector('.product-artist');
            const titleEl = playerContainer.querySelector('.product-title');
            const labelEl = playerContainer.querySelector('.product-label');
            const skuEl = playerContainer.querySelector('.product-sku');
            
            if (artistEl) artistEl.textContent = artist;
            if (titleEl) {
                titleEl.textContent = title;
                console.log('✅ Titre mis à jour:', title);
            } else {
                console.warn('⚠️ Element titre non trouvé');
            }
            if (labelEl) labelEl.textContent = label;
            if (skuEl) skuEl.textContent = sku;
        } else {
            console.warn('⚠️ Container player non trouvé');
        }
        
        // Update current track display
        document.querySelector('.current-track').textContent = track.name;
        
        // Load audio and regenerate waveform for this track
        if (this.wavesurfer) {
            console.log('Loading track with waveform:', track.url);
            this.showStatus('Loading waveform...');
            
            // BENJAMIN FIX: Clean stop avant nouveau track
            try {
                this.wavesurfer.stop();
                this.wavesurfer.empty();
                console.log('✅ WaveSurfer cleared for new track');
            } catch (error) {
                console.warn('⚠️ Error clearing WaveSurfer:', error);
            }
            
            // Load new track with cache busting
            this.wavesurfer.load(track.url + '?t=' + Date.now());
        } else if (this.audio) {
            console.log('🔄 Loading HTML5 audio source:', track.url);
            this.audio.src = track.url;
            this.audio.load();
        }
    }
    
    togglePlayPause() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    // BENJAMIN FIX: Méthode centralisée pour l'autoplay
    async handleAutoplayAfterReady() {
        if (this.autoPlayAfterLoad) {
            console.log("🎵 Starting autoplay after ready...");
            this.autoPlayAfterLoad = false;
            
            // Délai pour stabilité et éviter les conflits
            setTimeout(async () => {
                try {
                    // Ensure AudioContext is resumed for WaveSurfer
                    if (this.wavesurfer && this.wavesurfer.backend && this.wavesurfer.backend.ac) {
                        if (this.wavesurfer.backend.ac.state === 'suspended') {
                            console.log("🔊 Resuming AudioContext for autoplay");
                            await this.wavesurfer.backend.ac.resume();
                        }
                    }
                    
                    // Clean state check before autoplay
                    if (!this.isPlaying) {
                        await this.play();
                    }
                } catch (error) {
                    console.error('❌ Autoplay failed:', error);
                }
            }, 200); // Réduit le délai pour plus de réactivité
        }
    }
    
    async play() {
        console.log('▶️ Play requested');
        
        // BENJAMIN FIX: State check first
        if (this.isPlaying) {
            console.log('⚠️ Already playing, ignoring play request');
            return;
        }
        
        try {
            if (this.wavesurfer) {
                // Ensure AudioContext is resumed
                if (this.wavesurfer.backend && this.wavesurfer.backend.ac) {
                    if (this.wavesurfer.backend.ac.state === 'suspended') {
                        console.log("🔊 Resuming AudioContext before play");
                        await this.wavesurfer.backend.ac.resume();
                    }
                }
                
                await this.wavesurfer.play();
                console.log('✅ WaveSurfer play started');
            } else if (this.audio) {
                await this.audio.play();
                console.log('✅ HTML5 audio play started');
            }
            
            // Update state AFTER successful play
            this.isPlaying = true;
            this.updatePlayButton();
            document.getElementById('yoyaku-player').classList.add('player-playing');
            
        } catch (error) {
            console.error('❌ Play failed:', error);
            this.isPlaying = false;
            this.updatePlayButton();
        }
    }
    
    pause() {
        console.log('⏸️ Pause requested');
        
        // BENJAMIN FIX: State check first  
        if (!this.isPlaying) {
            console.log('⚠️ Already paused, ignoring pause request');
            return;
        }
        
        try {
            if (this.wavesurfer) {
                this.wavesurfer.pause();
                console.log('✅ WaveSurfer paused');
            } else if (this.audio) {
                this.audio.pause();
                console.log('✅ HTML5 audio paused');
            }
            
            // Update state AFTER successful pause
            this.isPlaying = false;
            this.updatePlayButton();
            document.getElementById('yoyaku-player').classList.remove('player-playing');
            
        } catch (error) {
            console.error('❌ Pause failed:', error);
            // Force state update even if pause failed
            this.isPlaying = false;
            this.updatePlayButton();
        }
    }
    
    updatePlayButton() {
        // BENJAMIN FIX: Sécurité éléments DOM
        try {
            const playBtn = document.querySelector('.btn-play');
            const pauseBtn = document.querySelector('.btn-pause');
            const player = document.getElementById('yoyaku-player');
            
            if (playBtn && pauseBtn && player) {
                if (this.isPlaying) {
                    playBtn.style.display = 'none';
                    pauseBtn.style.display = 'block';
                    player.classList.add('player-playing');
                    console.log('✅ UI updated: playing state');
                } else {
                    playBtn.style.display = 'block';
                    pauseBtn.style.display = 'none';
                    player.classList.remove('player-playing');
                    console.log('✅ UI updated: paused state');
                }
            } else {
                console.warn('⚠️ UI elements not found for button update');
            }
        } catch (error) {
            console.error('❌ Error updating play button:', error);
        }
    }
    
    previousTrack() {
        if (this.currentTrackIndex > 0) {
            this.loadTrack(this.currentTrackIndex - 1);
        }
    }
    
    nextTrack() {
        console.log(`🔄 NextTrack called: current=${this.currentTrackIndex}, total=${this.tracks.length}`);
        
        if (this.currentTrackIndex < this.tracks.length - 1) {
            console.log('➡️ Loading next track:', this.currentTrackIndex + 1);
            const wasPlaying = this.isPlaying;
            this.loadTrack(this.currentTrackIndex + 1);
            
            // BENJAMIN FIX: Auto-play pour next track
            this.autoPlayAfterLoad = true;
        } else {
            console.log("🎵 END OF PLAYLIST - Checking for next product");
            const nextProductId = this.detectNextProduct();
            
            if (nextProductId && nextProductId !== this.currentProductId) {
                console.log("🚀 Loading next product:", nextProductId);
                setTimeout(() => {
                    this.autoPlayAfterLoad = true;
                    this.loadProduct(nextProductId);
                }, 2000);
                return;
            } else {
                console.log("🔄 No next product, restarting current");
            const wasPlaying = this.isPlaying;
            this.loadTrack(0);
            
            // BENJAMIN FIX: Auto-play pour restart
            this.autoPlayAfterLoad = true;
            }
        }
    }
    
    detectNextProduct() {
        // Find all product play buttons
        const buttons = document.querySelectorAll(".fwap-play[data-product]");
        let foundCurrent = false;
        
        console.log("🔍 Detecting next product from", buttons.length, "buttons");
        console.log("🎯 Current product ID:", this.currentProductId);
        
        for (let btn of buttons) {
            const productId = btn.getAttribute("data-product");
            console.log("📋 Checking button productId:", productId);
            
            if (foundCurrent && productId && productId !== "0" && productId !== this.currentProductId) {
                console.log("✅ Found next product:", productId);
                return productId;
            }
            
            if (productId === this.currentProductId) {
                console.log("🎯 Found current product, next will be target");
                foundCurrent = true;
            }
        }
        
        // If no next found, return first product
        if (buttons.length > 0) {
            const firstId = buttons[0].getAttribute("data-product");
            console.log("🔄 No next product, returning first:", firstId);
            return firstId;
        }
        
        console.log("❌ No products found");
        return null;
    }

    updateVolume() {
        const volumePercent = this.volume / 100;
        
        if (this.wavesurfer) {
            this.wavesurfer.setVolume(volumePercent);
        } else if (this.audio) {
            this.audio.volume = volumePercent;
        }
    }
    
    applyPitch() {
        // Pitch: -8 to +8, 6% per step (like Technics MK2)
        const pitchPercent = this.pitch * 6;
        const rate = 1 + (pitchPercent / 100);
        
        if (this.wavesurfer) {
            this.wavesurfer.setPlaybackRate(rate);
            
            // For MediaElement backend
            if (this.wavesurfer.backend && this.wavesurfer.backend.media) {
                this.wavesurfer.backend.media.playbackRate = rate;
            }
        } else if (this.audio) {
            this.audio.playbackRate = rate;
        }
        
        const displayPercent = this.pitch > 0 ? `+${pitchPercent}%` : `${pitchPercent}%`;
        this.showStatus(`Pitch: ${displayPercent}`);
    }
    
    async addToCart() {
        if (!this.currentProduct) return;
        
        this.showStatus('Adding...');
        
        try {
            // WooCommerce AJAX add to cart
            const formData = new FormData();
            formData.append('product_id', this.currentProduct.product_id);
            formData.append('quantity', '1');
            
            const response = await fetch('?wc-ajax=add_to_cart', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            console.log('Cart response:', data);
            
            if (data.error) {
                throw new Error('Failed to add to cart');
            }
            
            this.showStatus('Added ✓');
            
            // Animate cart button
            const cartBtn = document.querySelector('.cart-btn');
            if (cartBtn) {
                cartBtn.style.transform = 'scale(1.2)';
                cartBtn.style.background = '#4CAF50';
                setTimeout(() => {
                    cartBtn.style.transform = '';
                    cartBtn.style.background = '#7db9d8'; // Return to blue
                }, 300);
            }
            
            // Safer WooCommerce cart update - avoid theme conflicts
            try {
                if (typeof jQuery !== 'undefined' && jQuery(document.body).length) {
                    // Update cart fragments safely
                    if (data.fragments) {
                        jQuery.each(data.fragments, function(key, value) {
                            jQuery(key).replaceWith(value);
                        });
                    }
                    
                    // Update cart count if element exists
                    if (data.cart_hash && jQuery('.cart-contents-count').length) {
                        jQuery('.cart-contents-count').text(data.cart_count || '');
                    }
                }
            } catch (updateError) {
                console.warn('Cart update warning:', updateError);
                // Continue anyway - product was added successfully
            }
            
        } catch (error) {
            console.error('Add to cart error:', error);
            this.showStatus('Error');
        }
    }
    
    showStatus(message) {
        console.log('Status:', message);
        const status = document.getElementById('player-status');
        status.textContent = message;
        status.classList.add('active');
        
        clearTimeout(this.statusTimeout);
        this.statusTimeout = setTimeout(() => {
            status.classList.remove('active');
        }, 2500);
    }
    
    checkAutoplay() {
        // Auto-load product on product pages to show player immediately
        const productId = this.getProductIdFromPage();
        if (productId) {
            console.log('🚀 Auto-loading product for immediate player display:', productId);
            // Add small delay to ensure DOM is ready
            setTimeout(() => {
                this.loadProduct(productId).catch(error => {
                    console.error('Failed to load product:', error);
                    // If AJAX fails, still show player structure
                    document.getElementById('yoyaku-player').classList.add('active');
                });
            }, 500); // Increased delay for better DOM readiness
            return;
        }
        
        // Fallback: Always show player on product pages even without explicit data
        if (window.yoyaku_player_v3 && window.yoyaku_player_v3.current_product_id) {
            const fallbackId = window.yoyaku_player_v3.current_product_id;
            console.log('🔄 Fallback loading with current_product_id:', fallbackId);
            setTimeout(() => {
                this.loadProduct(fallbackId);
            }, 1000);
            return;
        }
        
        // Check if there's a product to autoplay
        const autoplayButton = document.querySelector('.yoyaku-play-button[data-autoplay="true"]');
        if (autoplayButton) {
            const productId = autoplayButton.dataset.productId;
            console.log('🎵 Auto-playing product:', productId);
            this.loadProduct(productId);
        }
    }
    
    getProductIdFromPage() {
        // Try multiple methods to get product ID
        
        // Method 1: From WordPress localized script
        if (window.yoyaku_player_v3 && window.yoyaku_player_v3.current_product_id) {
            console.log('Product ID from WordPress:', window.yoyaku_player_v3.current_product_id);
            // HOMEPAGE FIX: If Product ID is 0, find first product on page
            if (window.yoyaku_player_v3.current_product_id == "0" || window.yoyaku_player_v3.current_product_id == 0) {
                console.log("🏠 HOMEPAGE: Product ID is 0, looking for first product");
                const firstProductButton = document.querySelector("[data-product]:not([data-product=\"0\"])");
                if (firstProductButton) {
                    const firstProductId = firstProductButton.getAttribute("data-product");
                    console.log("✅ HOMEPAGE: Found first product", firstProductId);
                    return firstProductId;
                }
                console.log("❌ HOMEPAGE: No product found, returning null");
                return null;
            }

            return window.yoyaku_player_v3.current_product_id;

        }
        
        // Method 2: Check body class
        const bodyClasses = document.body.className;
        const match = bodyClasses.match(/postid-(\d+)/);
        if (match) {
            console.log('Product ID from body class:', match[1]);
            return match[1];
        }
        
        // Method 3: Check product form
        const productForm = document.querySelector('form.cart');
        if (productForm) {
            const button = productForm.querySelector('button[name="add-to-cart"]');
            if (button && button.value) {
                console.log('Product ID from add-to-cart button:', button.value);
                return button.value;
            }
        }
        
        // Method 4: Check FWA player data (legacy)
        if (window.fwaPlaylist && window.fwaPlaylist.productId) {
            console.log('Product ID from FWA playlist:', window.fwaPlaylist.productId);
            return window.fwaPlaylist.productId;
        }
        
        // Method 5: Try to extract from meta tags
        const productMeta = document.querySelector('meta[property="product:id"]');
        if (productMeta && productMeta.content) {
            console.log('Product ID from meta tag:', productMeta.content);
            return productMeta.content;
        }
        
        // Method 6: Check URL and hardcode known pattern for test
        if (window.location.href.includes('telu-002-telu002')) {
            console.log('Product ID hardcoded for TELU002 test product');
            return '619830';
        }
        
        console.log('Could not determine product ID from page');
        return null;
    }
    
    formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${minutes}:${secs.toString().padStart(2, '0')}`;
    }
    
    updateTimeDisplay() {
        if (this.wavesurfer) {
            const current = this.wavesurfer.getCurrentTime();
            const total = this.wavesurfer.getDuration();
            
            const timeDisplay = document.querySelector('.time-display');
            if (timeDisplay && total) {
                timeDisplay.textContent = `${this.formatTime(current)} / ${this.formatTime(total)}`;
            }
        }
    }
    
    addPlayButton() {
        // Add a play button if none exists on product pages
        if (!document.querySelector('.product')) return;
        
        const productId = this.getProductIdFromPage();
        if (!productId) return;
        
        const button = document.createElement('button');
        button.className = 'yoyaku-play-button';
        button.dataset.productId = productId;
        button.innerHTML = '▶ Play Album';
        button.style.cssText = 'margin-top: 20px; background: #ffd700; border: none; padding: 12px 24px; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; width: 100%; display: block;';
        
        // Try to insert after add to cart button
        const addToCartButton = document.querySelector('.single_add_to_cart_button');
        if (addToCartButton && addToCartButton.parentNode) {
            addToCartButton.parentNode.insertBefore(button, addToCartButton.nextSibling);
            console.log('Added play button after add to cart button');
        } else {
            // Fallback: add to product summary
            const summary = document.querySelector('.summary');
            if (summary) {
                summary.appendChild(button);
                console.log('Added play button to product summary');
            }
        }
    }
    
    hideOldPlayers() {
        // Force hide all old players
        const oldPlayerSelectors = [
            '.fwa-player', '.fwaplayer', '.audio-player', 
            '.jp-audio', '.mejs-container', '.wp-audio-shortcode',
            '.jPlayer', '.audioplayer', '.html5-audio-player'
        ];
        
        oldPlayerSelectors.forEach(selector => {
            const players = document.querySelectorAll(selector);
            players.forEach(player => {
                player.style.display = 'none';
                player.style.visibility = 'hidden';
                player.style.zIndex = '-1';
                player.style.position = 'absolute';
                player.style.left = '-9999px';
            });
        });
        
        console.log('Old players hidden');
    }
    
    // REMOVED: generateTrackSpecificWaveform() - now using real WaveSurfer analysis
    
    // REMOVED: updateWaveformProgress() - WaveSurfer handles progress automatically
    
    hashCode(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        return Math.abs(hash);
    }
    
    seededRandom(seed) {
        const x = Math.sin(seed) * 10000;
        return x - Math.floor(x);
    }
    
    decodeHtmlEntities(text) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
    }
}

// Debug function
function debugYoyakuPlayer() {
    console.log('🔍 YOYAKU Player Debug:');
    console.log('- DOM ready:', document.readyState);
    console.log('- yoyaku_player_v3 config:', window.yoyaku_player_v3);
    console.log('- Current URL:', window.location.href);
    console.log('- Body classes:', document.body.className);
    console.log('- jQuery loaded:', typeof jQuery !== 'undefined' ? 'Yes' : 'No');
    console.log('- WaveSurfer loaded:', typeof window.WaveSurfer !== 'undefined' ? 'Yes' : 'No');
}

// Initialize player when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('🎵 DOM Content Loaded - Starting YOYAKU Player ULTRA-FIN');
    debugYoyakuPlayer();
    
    try {
        window.yoyakuPlayer = new YoyakuPlayerUltraFin();
        console.log('✅ YOYAKU Player instance created successfully');
    } catch (error) {
        console.error('❌ Failed to initialize YOYAKU Player:', error);
        console.error('Stack trace:', error.stack);
        
        // Show error info for debugging
        const errorDiv = document.createElement('div');
        errorDiv.style.cssText = 'position:fixed;top:10px;right:10px;background:red;color:white;padding:10px;z-index:99999;font-size:12px;max-width:300px;';
        errorDiv.innerHTML = `YOYAKU Player Error: ${error.message}`;
        document.body.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
});

// Fallback initialization after full page load
window.addEventListener('load', () => {
    if (!window.yoyakuPlayer) {
        console.log('🔄 Fallback initialization after window load');
        try {
            window.yoyakuPlayer = new YoyakuPlayerUltraFin();
        } catch (error) {
            console.error('❌ Fallback initialization failed:', error);
        }
    }
});
