/* ==============================================
   AJOUT MINIMAL: CLICK HANDLER IMAGE → URL PRODUIT
   ============================================== */

// Ajouter au bindEvents de la classe YoyakuPlayerUltraFin
if (window.YoyakuPlayerUltraFin && window.YoyakuPlayerUltraFin.prototype) {
    const originalBindEvents = window.YoyakuPlayerUltraFin.prototype.bindEvents;
    
    window.YoyakuPlayerUltraFin.prototype.bindEvents = function() {
        // Appeler la méthode originale
        if (originalBindEvents) {
            originalBindEvents.call(this);
        }
        
        // Ajouter UNIQUEMENT le click handler sur l'image vinyl
        const vinylCover = document.querySelector('.vinyl-cover');
        if (vinylCover) {
            vinylCover.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                if (!this.currentProduct || !this.currentProduct.product_id) {
                    console.log('No product loaded for image click');
                    return;
                }
                
                // Construire l'URL du produit
                const productUrl = `${window.location.origin}/?p=${this.currentProduct.product_id}`;
                
                // Redirection (Ctrl+click = nouvel onglet)
                if (e.ctrlKey || e.metaKey) {
                    window.open(productUrl, '_blank');
                } else {
                    window.location.href = productUrl;
                }
            });
            
            // Style cursor pointer
            vinylCover.style.cursor = 'pointer';
            
            console.log('✅ Image click handler added');
        }
    };
}

console.log('🔗 Image click addon loaded');