<?php
require_once '../config/config.php';

/**
 * Récupère tous les produits
 */
function getProduits() {
    return json_decode(file_get_contents(PRODUITS_FILE), true);
}

/**
 * Récupère un produit par code-barres
 */
function getProduitByCode($codeBarre) {
    $produits = getProduits();
    return $produits[$codeBarre] ?? null;
}

/**
 * Ajoute ou met à jour un produit
 */
function saveProduit($codeBarre, $nom, $prixUnitaireHT, $quantiteStock) {
    $produits = getProduits();

    $produits[$codeBarre] = [
        'code_barre' => $codeBarre,
        'nom' => $nom,
        'prix_unitaire_ht' => (float) $prixUnitaireHT,
        'quantite_stock' => (int) $quantiteStock,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if (!isset($produits[$codeBarre]['created_at'])) {
        $produits[$codeBarre]['created_at'] = date('Y-m-d H:i:s');
    }

    return file_put_contents(PRODUITS_FILE, json_encode($produits, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Met à jour le stock d'un produit
 */
function updateStock($codeBarre, $nouvelleQuantite) {
    $produits = getProduits();

    if (!isset($produits[$codeBarre])) {
        return false;
    }

    $produits[$codeBarre]['quantite_stock'] = (int) $nouvelleQuantite;
    $produits[$codeBarre]['updated_at'] = date('Y-m-d H:i:s');

    return file_put_contents(PRODUITS_FILE, json_encode($produits, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Supprime un produit
 */
function deleteProduit($codeBarre) {
    $produits = getProduits();

    if (!isset($produits[$codeBarre])) {
        return false;
    }

    unset($produits[$codeBarre]);

    return file_put_contents(PRODUITS_FILE, json_encode($produits, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Recherche des produits par nom
 */
function searchProduits($query) {
    $produits = getProduits();
    $resultats = [];

    foreach ($produits as $codeBarre => $produit) {
        if (stripos($produit['nom'], $query) !== false) {
            $resultats[$codeBarre] = $produit;
        }
    }

    return $resultats;
}

/**
 * Vérifie si un produit existe
 */
function produitExists($codeBarre) {
    $produits = getProduits();
    return isset($produits[$codeBarre]);
}
?>