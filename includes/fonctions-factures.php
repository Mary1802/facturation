<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/fonctions-produits.php';

/**
 * Génère un numéro de facture unique
 */
function generateInvoiceNumber() {
    $date = date('Ymd');
    $factures = getFactures();

    // Compter les factures du jour
    $count = 0;
    foreach ($factures as $facture) {
        if (strpos($facture['numero'], $date) === 0) {
            $count++;
        }
    }

    return 'FAC-' . $date . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
}

/**
 * Récupère toutes les factures
 */
function getFactures() {
    $content = file_get_contents(FACTURES_FILE);
    return $content ? json_decode($content, true) : [];
}

/**
 * Récupère une facture par numéro
 */
function getFactureByNumero($numero) {
    $factures = getFactures();
    foreach ($factures as $facture) {
        if ($facture['numero'] === $numero) {
            return $facture;
        }
    }
    return null;
}

/**
 * Calcule les totaux d'une facture
 */
function calculateTotals($items) {
    $total_ht = 0;

    foreach ($items as $item) {
        $total_ht += $item['sous_total_ht'];
    }

    $tva = $total_ht * TVA_RATE;
    $total_ttc = $total_ht + $tva;

    return [
        'total_ht' => $total_ht,
        'tva' => $tva,
        'total_ttc' => $total_ttc
    ];
}

/**
 * Crée une nouvelle facture
 */
function createFacture($items, $caissier) {
    $numero = generateInvoiceNumber();
    $date = date('Y-m-d');
    $heure = date('H:i:s');
    $totals = calculateTotals($items);

    $facture = [
        'numero' => $numero,
        'date' => $date,
        'heure' => $heure,
        'caissier' => $caissier,
        'items' => $items,
        'total_ht' => $totals['total_ht'],
        'tva' => $totals['tva'],
        'total_ttc' => $totals['total_ttc'],
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Mettre à jour le stock
    foreach ($items as $item) {
        $produit = getProduitByCode($item['code_barre']);
        if ($produit) {
            $nouveauStock = $produit['quantite_stock'] - $item['quantite'];
            updateStock($item['code_barre'], $nouveauStock);
        }
    }

    // Sauvegarder la facture
    $factures = getFactures();
    $factures[] = $facture;

    return file_put_contents(FACTURES_FILE, json_encode($factures, JSON_PRETTY_PRINT), LOCK_EX) !== false ? $facture : false;
}

/**
 * Récupère les factures d'une période
 */
function getFacturesByPeriod($dateDebut, $dateFin) {
    $factures = getFactures();
    $resultats = [];

    foreach ($factures as $facture) {
        if ($facture['date'] >= $dateDebut && $facture['date'] <= $dateFin) {
            $resultats[] = $facture;
        }
    }

    return $resultats;
}

/**
 * Récupère les factures du jour
 */
function getFacturesToday() {
    $today = date('Y-m-d');
    return getFacturesByPeriod($today, $today);
}

/**
 * Calcule le total des ventes pour une période
 */
function getSalesTotal($dateDebut, $dateFin) {
    $factures = getFacturesByPeriod($dateDebut, $dateFin);
    $total = 0;

    foreach ($factures as $facture) {
        $total += $facture['total_ttc'];
    }

    return $total;
}

/**
 * Récupère les statistiques de vente
 */
function getSalesStats($dateDebut, $dateFin) {
    $factures = getFacturesByPeriod($dateDebut, $dateFin);

    $stats = [
        'nombre_factures' => count($factures),
        'total_ventes' => 0,
        'total_ht' => 0,
        'total_tva' => 0,
        'produits_vendus' => []
    ];

    foreach ($factures as $facture) {
        $stats['total_ventes'] += $facture['total_ttc'];
        $stats['total_ht'] += $facture['total_ht'];
        $stats['total_tva'] += $facture['tva'];

        foreach ($facture['items'] as $item) {
            $code = $item['code_barre'];
            if (!isset($stats['produits_vendus'][$code])) {
                $stats['produits_vendus'][$code] = [
                    'nom' => $item['nom'],
                    'quantite' => 0,
                    'total' => 0
                ];
            }
            $stats['produits_vendus'][$code]['quantite'] += $item['quantite'];
            $stats['produits_vendus'][$code]['total'] += $item['sous_total_ht'];
        }
    }

    return $stats;
}
?>