/**
 * Module de scan de code-barres pour le système de caisse
 */

class BarcodeScanner {
    constructor(options = {}) {
        this.video = null;
        this.canvas = null;
        this.stream = null;
        this.isScanning = false;
        this.onScan = options.onScan || function() {};
        this.onError = options.onError || function() {};
    }

    /**
     * Démarre le scan de code-barres
     */
    async start() {
        try {
            // Vérifier le support de getUserMedia
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                throw new Error('La caméra n\'est pas supportée par ce navigateur');
            }

            // Demander l'accès à la caméra
            this.stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment', // Caméra arrière sur mobile
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                }
            });

            // Créer les éléments vidéo et canvas
            this.video = document.createElement('video');
            this.canvas = document.createElement('canvas');
            const context = this.canvas.getContext('2d');

            this.video.srcObject = this.stream;
            this.video.setAttribute('playsinline', true); // Important pour iOS
            this.video.play();

            this.isScanning = true;
            this.scan(context);

        } catch (error) {
            this.onError(error.message);
        }
    }

    /**
     * Arrête le scan
     */
    stop() {
        this.isScanning = false;

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
        }

        if (this.video) {
            this.video.pause();
            this.video.srcObject = null;
        }
    }

    /**
     * Boucle de scan
     */
    scan(context) {
        if (!this.isScanning) return;

        if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
            // Dessiner l'image de la vidéo sur le canvas
            this.canvas.width = this.video.videoWidth;
            this.canvas.height = this.video.videoHeight;
            context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);

            // Obtenir les données d'image
            const imageData = context.getImageData(0, 0, this.canvas.width, this.canvas.height);

            // Tenter de détecter un code-barres
            const code = this.detectBarcode(imageData);

            if (code) {
                this.onScan(code);
                this.stop();
                return;
            }
        }

        // Continuer le scan
        requestAnimationFrame(() => this.scan(context));
    }

    /**
     * Détection simple de code-barres (simulation)
     * Dans un vrai système, utiliser une bibliothèque comme QuaggaJS ou ZXing
     */
    detectBarcode(imageData) {
        // Simulation de détection - en production, utiliser une vraie bibliothèque
        // Pour cette démo, on simule la détection de codes-barres connus

        // Cette fonction devrait analyser l'image et retourner le code-barres détecté
        // Pour l'instant, on retourne null (pas de détection automatique)

        return null;
    }

    /**
     * Obtenir l'élément vidéo pour affichage
     */
    getVideoElement() {
        return this.video;
    }
}

// Fonction utilitaire pour scanner manuellement
function manualScan() {
    const barcode = prompt('Entrez le code-barres:');
    if (barcode && barcode.trim()) {
        // Simuler un appel AJAX pour récupérer les infos du produit
        fetch(`modules/produits/lire.php?code_barre=${encodeURIComponent(barcode)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface avec les données du produit
                    updateProductInfo(data.produit);
                } else {
                    showError('Produit non trouvé');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showError('Erreur lors de la recherche du produit');
            });
    }
}

// Fonctions d'aide pour l'interface
function updateProductInfo(produit) {
    document.getElementById('product-name').textContent = produit.nom;
    document.getElementById('product-price').textContent = new Intl.NumberFormat('fr-FR').format(produit.prix_unitaire_ht) + ' CDF';
    document.getElementById('product-stock').textContent = produit.quantite_stock + ' unités';
    document.getElementById('quantity-input').max = produit.quantite_stock;
    document.getElementById('product-info').classList.remove('hidden');
    document.getElementById('product-info').dataset.product = JSON.stringify(produit);
}

function showError(message) {
    const errorDiv = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    errorText.textContent = message;
    errorDiv.classList.remove('hidden');
}

// Initialisation lors du chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Ajouter un écouteur pour le champ de code-barres
    const barcodeInput = document.getElementById('barcode-input');
    if (barcodeInput) {
        barcodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const barcode = this.value.trim();
                if (barcode) {
                    // Simuler la soumission du formulaire
                    const form = this.closest('form');
                    if (form) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'scan_barcode';
                        hiddenInput.value = barcode;
                        form.appendChild(hiddenInput);
                        form.submit();
                    }
                }
            }
        });
    }

    // Initialiser le scanner si disponible
    if ('mediaDevices' in navigator && 'getUserMedia' in navigator.mediaDevices) {
        console.log('Scanner de code-barres disponible');
    }
});