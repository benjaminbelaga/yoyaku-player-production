// YOYAKU PLAYER V3 ULTRA-FIN - NUCLEAR INFINITE LOOP FIX
// Benjamin Emergency Version - NO BACKEND CHECKS WHATSOEVER

class YoyakuPlayerUltraFin {
    constructor() {
        console.log("Initializing YOYAKU Player ULTRA-FIN...");
        this.isPlaying = false;
        this.currentProductId = null;
        this.currentTrackIndex = 0;
        this.tracks = [];
        this.wavesurfer = null;
        this.audio = null;
        this.currentProductUrl = '';
        this.autoPlayAfterLoad = false;
        
        this.initializePlayer();
    }

    async initializePlayer() {
        console.log("Loading WaveSurfer for real audio analysis and synchronization");
        
        this.createPlayerHTML();
        await this.loadWaveSurfer();
        this.setupControlListeners();
        this.connectPlayButtons();
        this.checkAutoplay();
    }

    async loadWaveSurfer() {
        if (typeof WaveSurfer !== 'undefined') {
            console.log("WaveSurfer already loaded");
            this.initWaveSurfer();
            return;
        }

        console.log("Loading WaveSurfer from jsdelivr");
        
        try {
            await this.loadScript('https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.min.js');
            console.log("✅ Script loaded successfully: https://cdn.jsdelivr.net/npm/wavesurfer.js@7/dist/wavesurfer.min.js");
            console.log("WaveSurfer loaded from jsdelivr");
            this.initWaveSurfer();
        } catch (error) {
            console.error("❌ Failed to load WaveSurfer:", error);
            this.initHTML5Audio();
        }
    }

    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => {
                console.log("✅ Script loaded successfully:", src);
                resolve();
            };
            script.onerror = (error) => {
                console.log("❌ Script load failed:", src, error);
                reject(error);
            };
            document.head.appendChild(script);
        });
    }

    initWaveSurfer() {
        console.log("🌊 FORCING WaveSurfer initialization...");
        
        const container = document.getElementById('waveform');
        if (!container) {
            console.error("❌ Waveform container not found");
            return;
        }

        if (typeof WaveSurfer === 'undefined') {
            console.error("❌ WaveSurfer not available");
            this.initHTML5Audio();
            return;
        }

        console.log("✅ WaveSurfer available, proceeding with initialization");

        const isMobile = window.innerWidth <= 768 || 
                         /iPhone|iPod|iPad|Android/i.test(navigator.userAgent) ||
                         (window.matchMedia && window.matchMedia('(max-device-width: 768px)').matches);
        const isIPhone = /iPhone|iPod/i.test(navigator.userAgent);

        console.log("🌊 DEBUG WAVEFORM: Container exists?", !!container);
        console.log("🌊 DEBUG WAVEFORM: Container visible?", container.offsetWidth + 'x' + container.offsetHeight);
        console.log("🌊 DEBUG WAVEFORM: Screen size:", window.innerWidth, "Mobile:", isMobile, "iPhone:", isIPhone);

        try {
            this.wavesurfer = WaveSurfer.create({
                container: container,
                waveColor: 'rgba(255, 255, 255, 0.6)',
                progressColor: '#ffd700',
                cursorColor: '#ffd700',
                barWidth: 2,
                barRadius: 3,
                responsive: true,
                height: isMobile ? 40 : 24,
                normalize: true,
                backend: 'WebAudio',
                mediaControls: false
            });

            // BENJAMIN NUCLEAR FIX: Simple events only
            this.wavesurfer.on('ready', () => {
                console.log("✅ WaveSurfer ready with real waveform");
                this.showStatus('Waveform ready');
                
                // NUCLEAR SIMPLE: Just try autoplay if needed
                this.handleAutoplayAfterReady();
            });
            
            this.wavesurfer.on('error', (error) => {
                console.error('WaveSurfer error:', error);
                console.log('Falling back to HTML5 audio mode');
                this.wavesurfer = null;
                this.initHTML5Audio();
            });
            
            this.wavesurfer.on('finish', () => {
                this.nextTrack();
                this.isPlaying = false;
                this.updatePlayButton();
            });

            console.log("WaveSurfer initialized with waveform visualization");
        } catch (error) {
            console.error("❌ Error initializing WaveSurfer:", error);
            this.initHTML5Audio();
        }
    }

    initHTML5Audio() {
        console.log("🎵 Initializing HTML5 Audio fallback...");
        
        this.audio = new Audio();
        this.audio.crossOrigin = 'anonymous';
        this.audio.preload = 'metadata';
        
        this.audio.addEventListener('loadeddata', () => {
            console.log('✅ HTML5 audio loaded');
            this.showStatus('Audio ready');
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
        
        this.setupHTML5ProgressBar();
    }

    setupHTML5ProgressBar() {
        const progressBar = document.querySelector('.progress-bar');
        const progressFill = document.querySelector('.progress-fill');
        const timeDisplay = document.querySelector('.time-display');
        
        if (!progressBar || !this.audio) return;
        
        this.audio.addEventListener('timeupdate', () => {
            if (this.audio.duration) {
                const progress = (this.audio.currentTime / this.audio.duration) * 100;
                progressFill.style.width = progress + '%';
                
                const current = this.formatTime(this.audio.currentTime);
                const total = this.formatTime(this.audio.duration);
                timeDisplay.textContent = `${current} / ${total}`;
            }
        });
        
        progressBar.addEventListener('click', (e) => {
            if (this.audio.duration) {
                const rect = progressBar.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const width = rect.width;
                const newTime = (clickX / width) * this.audio.duration;
                this.audio.currentTime = newTime;
            }
        });
    }

    createPlayerHTML() {
        if (document.querySelector('.yoyaku-player-ultra-fin')) {
            return;
        }

        const playerHTML = `
            <div class="yoyaku-player-ultra-fin" id="yoyaku-player-ultra-fin">
                <div class="player-inner">
                    <div class="vinyl-cover">
                        <div class="vinyl-wrapper">
                            <img class="vinyl-image" src="${yoyaku_player_v3.plugin_url}assets/images/default-vinyl.png" alt="Album Cover" />
                        </div>
                    </div>
                    
                    <div class="playlist-container">
                        <div class="playlist-toggle">
                            <div class="playlist-info">
                                <div class="product-line-1">Select a track...</div>
                                <div class="product-line-2">No product loaded</div>
                            </div>
                            <div class="track-display">
                                <span class="current-track">No track</span>
                                <span class="dropdown-arrow">▼</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="waveform-container">
                        <div id="waveform"></div>
                    </div>
                    
                    <div class="player-controls">
                        <button class="control-btn prev" title="Previous track">
                            <span>⏮</span>
                        </button>
                        <button class="control-btn play-pause" title="Play/Pause">
                            <span class="btn-play">▶</span>
                            <span class="btn-pause" style="display: none;">❚❚</span>
                        </button>
                        <button class="control-btn next" title="Next track">
                            <span>⏭</span>
                        </button>
                    </div>
                    
                    <div class="audio-controls">
                        <button class="volume-icon" title="Volume">🔊</button>
                        <button class="pitch-icon" title="Pitch">🎚</button>
                    </div>
                    
                    <button class="cart-btn" title="Add to cart">🛒</button>
                </div>
            </div>
            <div class="status-indicator" id="status-indicator"></div>
        `;

        document.body.insertAdjacentHTML('beforeend', playerHTML);
    }

    connectPlayButtons() {
        const selectors = [
            '.play-button',
            '.fwap-play',
            '[data-product]',
            '.yoyaku-play-button',
            '[href*="play"]'
        ];

        selectors.forEach(selector => {
            const buttons = document.querySelectorAll(selector);
            buttons.forEach(button => {
                console.log(`Found play button with selector: ${selector}`, button);
                console.log(`Connecting play button to ULTRA-FIN player:`, button);
                
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    console.log("Play button clicked!", e.target);
                    
                    this.hideOldPlayers();
                    
                    const productId = button.getAttribute('data-product') || 
                                    button.getAttribute('data-product-id') ||
                                    this.extractProductIdFromUrl(button.href);
                    
                    if (productId && productId !== '0') {
                        console.log("Loading product:", productId);
                        this.loadProduct(productId, true);
                    } else {
                        console.warn("No valid product ID found");
                    }
                });
            });
        });

        // Global click handler for dynamic buttons
        document.addEventListener('click', (e) => {
            console.log("Document click detected on:", e.target);
            
            const button = e.target.closest('.play-button, .fwap-play, [data-product]');
            if (button && !button.hasAttribute('data-yoyaku-connected')) {
                button.setAttribute('data-yoyaku-connected', 'true');
                button.click();
            }
        });
    }

    hideOldPlayers() {
        const oldPlayerSelectors = [
            '.fwa-player',
            '.fwaplayer', 
            '.audio-player',
            '.jp-audio',
            '.mejs-container',
            '.wp-audio-shortcode'
        ];
        
        oldPlayerSelectors.forEach(selector => {
            const players = document.querySelectorAll(selector);
            players.forEach(player => {
                player.style.display = 'none';
            });
        });
        
        console.log("Old players hidden");
    }

    checkAutoplay() {
        setTimeout(() => {
            const productId = yoyaku_player_v3.current_product_id;
            
            console.log("Product ID from WordPress:", productId);
            
            if (!productId || productId === '0') {
                console.log("🏠 HOMEPAGE: Product ID is 0, looking for first product");
                const firstButton = document.querySelector('.play-button[data-product], .fwap-play[data-product]');
                if (firstButton) {
                    const firstProductId = firstButton.getAttribute('data-product');
                    console.log("✅ HOMEPAGE: Found first product", firstProductId);
                    
                    setTimeout(() => {
                        console.log("🚀 Auto-loading product for immediate player display:", firstProductId);
                        this.loadProduct(firstProductId, false);
                    }, 100);
                }
            } else {
                console.log("🚀 Auto-loading current product:", productId);
                this.loadProduct(productId, false);
            }
        }, 500);
    }

    setupControlListeners() {
        const player = document.querySelector('.yoyaku-player-ultra-fin');
        if (!player) return;

        // Play/Pause button
        const playPauseBtn = player.querySelector('.play-pause');
        if (playPauseBtn) {
            playPauseBtn.addEventListener('click', () => {
                if (this.isPlaying) {
                    this.pause();
                } else {
                    this.play();
                }
            });
        }

        // Previous/Next buttons
        const prevBtn = player.querySelector('.prev');
        const nextBtn = player.querySelector('.next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.previousTrack());
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextTrack());
        }

        // Cart button
        const cartBtn = player.querySelector('.cart-btn');
        if (cartBtn) {
            cartBtn.addEventListener('click', () => this.addToCart());
        }

        // Waveform click
        const waveformContainer = player.querySelector('.waveform-container');
        if (waveformContainer) {
            waveformContainer.addEventListener('click', (e) => {
                this.handleWaveformClick(e);
            });
        }
    }

    showStatus(message) {
        const indicator = document.getElementById('status-indicator');
        if (indicator) {
            indicator.textContent = `Status: ${message}`;
            indicator.style.display = 'block';
            setTimeout(() => {
                indicator.style.display = 'none';
            }, 3000);
        }
        console.log(`Status: ${message}`);
    }

    async loadProduct(productId, autoplay = false) {
        if (!productId || productId === '0') return;
        
        console.log(`[YOYAKU DEBUG] Loading product ${productId}, current: ${this.currentProductId || 'none'}`);
        
        this.currentProductId = productId;
        this.autoPlayAfterLoad = autoplay;
        
        try {
            this.showStatus('Loading...');
            
            const formData = new FormData();
            formData.append('action', 'yoyaku_player_v3_get_track');
            formData.append('product_id', productId);
            formData.append('nonce', yoyaku_player_v3.nonce);
            
            const response = await fetch(yoyaku_player_v3.ajax_url, {
                method: 'POST',
                body: formData
            });
            
            console.log("Raw response:", await response.clone().text());
            const data = await response.json();
            console.log("Parsed data:", data);
            
            if (data.success && data.data) {
                await this.loadProductData(data.data);
            } else {
                console.error("Failed to load product data:", data);
                this.showStatus('Failed to load product');
            }
        } catch (error) {
            console.error("Error loading product:", error);
            this.showStatus('Error loading product');
        }
    }

    async loadProductData(productData) {
        this.tracks = productData.tracks || [];
        this.currentTrackIndex = 0;
        
        const player = document.querySelector('.yoyaku-player-ultra-fin');
        if (!player) return;

        // Store product URL
        this.currentProductUrl = `https://woocommerce-870689-5762868.cloudwaysapps.com/?p=${productData.product_id}`;
        console.log("🔗 Product URL stored:", this.currentProductUrl);
        console.log(`Tracks loaded: ${this.tracks.length} tracks`);

        // Update UI
        const productLine1 = player.querySelector('.product-line-1');
        const productLine2 = player.querySelector('.product-line-2');
        const vinylImage = player.querySelector('.vinyl-image');

        if (productLine1) {
            productLine1.textContent = `${productData.artist} - ${productData.title}`;
        }
        if (productLine2) {
            productLine2.textContent = `${productData.label || 'Unknown Label'} - ${productData.sku || 'No SKU'}`;
        }
        if (vinylImage && productData.cover) {
            vinylImage.src = productData.cover;
            vinylImage.alt = productData.title;
        }

        // Show player
        player.classList.add('active');

        // Load first track
        if (this.tracks.length > 0) {
            await this.loadTrack(0);
        } else {
            console.log("❌ Cannot load track: 0 Total tracks:", this.tracks.length);
            this.showStatus('No tracks available');
        }
    }

    async loadTrack(trackIndex) {
        if (!this.tracks[trackIndex]) {
            console.error(`Track ${trackIndex} not found`);
            return;
        }

        this.currentTrackIndex = trackIndex;
        const track = this.tracks[trackIndex];
        
        console.log(`🎵 Loading track ${trackIndex + 1}/${this.tracks.length}: ${track.name}`);

        // NUCLEAR STOP: Force stop everything first
        console.log("🚫 EMERGENCY: Force stopping current track...");
        
        if (this.wavesurfer) {
            try {
                this.wavesurfer.stop();
                console.log("✅ WaveSurfer stopped completely");
            } catch (e) {}
        }
        
        if (this.audio) {
            this.audio.pause();
            this.audio.currentTime = 0;
        }
        
        this.isPlaying = false;
        console.log("✅ Player state reset to paused");
        this.updatePlayButton();

        // Update UI
        const player = document.querySelector('.yoyaku-player-ultra-fin');
        if (player) {
            const currentTrackElement = player.querySelector('.current-track');
            if (currentTrackElement) {
                currentTrackElement.textContent = track.name;
                console.log("✅ Titre mis à jour:", track.name);
            }
        }

        // Load track based on available player
        if (this.wavesurfer) {
            await this.loadTrackWithWaveform(track.url);
        } else if (this.audio) {
            await this.loadTrackWithHTML5(track.url);
        }
    }

    async loadTrackWithWaveform(url) {
        console.log("Loading track with waveform:", url);
        this.showStatus('Loading waveform...');
        
        try {
            console.log("🔄 EMERGENCY: Force resetting WaveSurfer backend...");
            
            // Simple timeout clear
            setTimeout(() => {
                console.log("✅ WaveSurfer EMERGENCY cleared after timeout");
            }, 2000);
            
            // NUCLEAR SIMPLE: Just load the track
            console.log("🎵 EMERGENCY loading new track:", url);
            await this.wavesurfer.load(url);
            
        } catch (error) {
            console.error("❌ Error loading waveform:", error);
            this.showStatus('Error loading waveform');
        }
    }

    async loadTrackWithHTML5(url) {
        console.log("Loading track with HTML5 audio:", url);
        this.showStatus('Loading audio...');
        
        try {
            this.audio.src = url;
            await new Promise((resolve, reject) => {
                this.audio.onloadeddata = resolve;
                this.audio.onerror = reject;
                this.audio.load();
            });
            
            this.showStatus('Audio ready');
            
            if (this.autoPlayAfterLoad) {
                this.handleAutoplayAfterReady();
            }
        } catch (error) {
            console.error("❌ Error loading HTML5 audio:", error);
            this.showStatus('Error loading audio');
        }
    }

    // BENJAMIN NUCLEAR FIX: Ultra simple autoplay - NO BACKEND CHECKS
    async handleAutoplayAfterReady() {
        if (this.autoPlayAfterLoad) {
            console.log("🎵 SIMPLE AUTOPLAY - NO BACKEND CHECKS");
            this.autoPlayAfterLoad = false;
            
            // BENJAMIN ULTRA-SIMPLE: Just play without ANY backend verification
            if (!this.isPlaying && this.wavesurfer) {
                try {
                    await this.play();
                    console.log("✅ Simple autoplay success");
                } catch (error) {
                    console.log("❌ Autoplay failed but NOT retrying:", error);
                }
            }
        }
    }

    async play() {
        if (!this.wavesurfer && !this.audio) {
            console.error("❌ No audio player available");
            return;
        }

        try {
            console.log("▶️ Play requested");
            
            if (this.wavesurfer) {
                await this.wavesurfer.play();
                console.log("✅ WaveSurfer play started");
            } else if (this.audio) {
                await this.audio.play();
                console.log("✅ HTML5 audio play started");
            }
            
            this.isPlaying = true;
            this.updatePlayButton();
            console.log("✅ UI updated: playing state");
            
        } catch (error) {
            console.error("❌ Play failed:", error);
            this.showStatus(`Play error: ${error.message}`);
        }
    }

    async pause() {
        console.log("⏸️ Pause requested");
        
        try {
            if (this.wavesurfer) {
                this.wavesurfer.pause();
                console.log("✅ WaveSurfer paused");
            } else if (this.audio) {
                this.audio.pause();
                console.log("✅ HTML5 audio paused");
            }
            
            this.isPlaying = false;
            this.updatePlayButton();
            console.log("✅ UI updated: paused state");
            
        } catch (error) {
            console.error("❌ Pause failed:", error);
        }
    }

    updatePlayButton() {
        const player = document.querySelector('.yoyaku-player-ultra-fin');
        if (!player) return;

        const playBtn = player.querySelector('.btn-play');
        const pauseBtn = player.querySelector('.btn-pause');
        const playPauseContainer = player.querySelector('.play-pause');

        if (this.isPlaying) {
            if (playBtn) playBtn.style.display = 'none';
            if (pauseBtn) pauseBtn.style.display = 'inline';
            if (playPauseContainer) playPauseContainer.classList.add('playing');
        } else {
            if (playBtn) playBtn.style.display = 'inline';
            if (pauseBtn) pauseBtn.style.display = 'none';
            if (playPauseContainer) playPauseContainer.classList.remove('playing');
        }
    }

    nextTrack() {
        if (this.currentTrackIndex < this.tracks.length - 1) {
            this.loadTrack(this.currentTrackIndex + 1);
        } else {
            console.log("Last track reached");
        }
    }

    previousTrack() {
        if (this.currentTrackIndex > 0) {
            this.loadTrack(this.currentTrackIndex - 1);
        } else {
            console.log("First track reached");
        }
    }

    addToCart() {
        if (!this.currentProductId) {
            console.warn("No product loaded");
            return;
        }

        console.log("Adding product to cart:", this.currentProductId);
        
        // Simple add to cart via AJAX
        const formData = new FormData();
        formData.append('product_id', this.currentProductId);
        formData.append('quantity', '1');

        fetch(yoyaku_player_v3.wc_ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error("Cart error:", data);
                this.showStatus('Cart error');
            } else {
                console.log("✅ Added to cart successfully");
                this.showStatus('Added to cart!');
            }
        })
        .catch(error => {
            console.error("❌ Cart request failed:", error);
            this.showStatus('Cart error');
        });
    }

    handleWaveformClick(e) {
        if (!this.wavesurfer) return;

        const container = e.currentTarget;
        const rect = container.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const width = rect.width;
        const progress = clickX / width;

        try {
            if (this.wavesurfer.getDuration() > 0) {
                const newTime = progress * this.wavesurfer.getDuration();
                this.wavesurfer.seekTo(progress);
                console.log(`⏩ Seeked to ${newTime.toFixed(1)}s`);
            }
        } catch (error) {
            console.error("❌ Seek failed:", error);
        }
    }

    extractProductIdFromUrl(url) {
        if (!url) return null;
        
        const matches = url.match(/[?&]p=(\d+)/);
        if (matches) return matches[1];
        
        const pathMatches = url.match(/\/(\d+)\/?$/);
        if (pathMatches) return pathMatches[1];
        
        return null;
    }

    formatTime(seconds) {
        if (!seconds || !isFinite(seconds)) return '0:00';
        
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    updateTimeDisplay() {
        const timeDisplay = document.querySelector('.time-display');
        if (!timeDisplay) return;

        let current = 0;
        let total = 0;

        if (this.wavesurfer) {
            current = this.wavesurfer.getCurrentTime();
            total = this.wavesurfer.getDuration();
        } else if (this.audio) {
            current = this.audio.currentTime;
            total = this.audio.duration;
        }

        timeDisplay.textContent = `${this.formatTime(current)} / ${this.formatTime(total)}`;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log("🔍 YOYAKU Player Debug:");
    console.log("- DOM ready:", document.readyState);
    console.log("- yoyaku_player_v3 config:", yoyaku_player_v3);
    console.log("- Current URL:", window.location.href);
    console.log("- Body classes:", document.body.className);
    console.log("- jQuery loaded:", typeof jQuery !== 'undefined' ? 'Yes' : 'No');
    console.log("- WaveSurfer loaded:", typeof WaveSurfer !== 'undefined' ? 'Yes' : 'No');
    
    console.log("🎵 DOM Content Loaded - Starting YOYAKU Player ULTRA-FIN");
    
    window.yoyakuPlayer = new YoyakuPlayerUltraFin();
    console.log("✅ YOYAKU Player instance created successfully");
});