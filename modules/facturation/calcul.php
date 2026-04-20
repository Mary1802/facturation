<?php
require_once '../../includes/fonctions-factures.php';

/**
 * Calcule les totaux pour un panier
 * Utilisé via AJAX pour mettre à jour les totaux en temps réel
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (is_array($items)) {
        $totals = calculateTotals($items);
        echo json_encode([
            'success' => true,
            'totals' => $totals
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Données invalides'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Méthode non autorisée'
    ]);
}
?>