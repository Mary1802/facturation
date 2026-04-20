<?php
require_once '../../includes/fonctions-produits.php';

/**
 * API pour récupérer les informations d'un produit par code-barres
 * Utilisé via AJAX pour le scan de code-barres
 */

header('Content-Type: application/json');

$codeBarre = $_GET['code_barre'] ?? '';

if (empty($codeBarre)) {
    echo json_encode([
        'success' => false,
        'error' => 'Code-barres manquant'
    ]);
    exit;
}

$produit = getProduitByCode($codeBarre);

if ($produit) {
    echo json_encode([
        'success' => true,
        'produit' => $produit
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Produit non trouvé'
    ]);
}
?>