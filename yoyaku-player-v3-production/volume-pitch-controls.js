/* =============================================
   VOLUME & PITCH CONTROLS INJECTION
   ============================================= */

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        // Find controls section
        const controlsSection = document.querySelector('.yoyaku-player-ultra-fin .controls-section');
        
        if (controlsSection) {
            // Create Volume Control
            const volumeControl = document.createElement('div');
            volumeControl.className = 'volume-control';
            volumeControl.innerHTML = `
                <div class="volume-icon"></div>
                <div class="volume-popup">
                    <input type="range" class="volume-slider" min="0" max="100" value="80">
                </div>
            `;
            
            // Create Pitch Control
            const pitchControl = document.createElement('div');
            pitchControl.className = 'pitch-control';
            pitchControl.innerHTML = `
                <div class="pitch-icon"></div>
                <div class="pitch-popup">
                    <input type="range" class="pitch-slider" min="50" max="150" value="100">
                </div>
            `;
            
            // Insert after controls
            controlsSection.appendChild(volumeControl);
            controlsSection.appendChild(pitchControl);
            
            // Volume control functionality
            const volumeSlider = volumeControl.querySelector('.volume-slider');
            volumeSlider.addEventListener('input', function(e) {
                const volume = e.target.value / 100;
                if (window.yoyakuPlayer && window.yoyakuPlayer.wavesurfer) {
                    window.yoyakuPlayer.wavesurfer.setVolume(volume);
                }
                console.log('Volume set to:', volume);
            });
            
            // Pitch control functionality
            const pitchSlider = pitchControl.querySelector('.pitch-slider');
            pitchSlider.addEventListener('input', function(e) {
                const playbackRate = e.target.value / 100;
                if (window.yoyakuPlayer && window.yoyakuPlayer.wavesurfer) {
                    window.yoyakuPlayer.wavesurfer.setPlaybackRate(playbackRate);
                }
                console.log('Pitch/Speed set to:', playbackRate);
            });
            
            console.log('✅ Volume and Pitch controls injected');
        } else {
            console.log('❌ Controls section not found');
        }
    }, 2000); // Wait for player to load
});