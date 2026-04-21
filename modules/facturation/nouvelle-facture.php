<?php
require_once '../../includes/header.php';
require_once '../../includes/fonctions-produits.php';
require_once '../../includes/fonctions-factures.php';

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$cart = &$_SESSION['cart'];
$error = '';
$success = '';
$searchResults = [];

// Vérification CSRF pour toutes les actions POST
function verifyCsrf() {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Requête invalide');
    }
}

// Recherche produit (code-barres ou nom)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scan_barcode'])) {
    verifyCsrf();
    $query = trim($_POST['scan_barcode']);
    if (empty($query)) {
        $error = 'Veuillez entrer un code-barres ou un nom';
    } else {
        $produit = getProduitByCode($query);
        if ($produit) {
            $_SESSION['current_product'] = $produit;
            $searchResults = [];
        } else {
            $_SESSION['current_product'] = null;
            $searchResults = searchProduits($query);
            if (empty($searchResults)) {
                $error = 'Aucun produit trouvé. Demandez au Manager de l\'enregistrer.';
            }
        }
    }
}

// Sélectionner un produit depuis les résultats de recherche
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_product'])) {
    verifyCsrf();
    $produit = getProduitByCode($_POST['select_product']);
    if ($produit) {
        $_SESSION['current_product'] = $produit;
        $searchResults = [];
    }
}

// Ajouter au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    verifyCsrf();
    $codeBarre = $_POST['code_barre'];
    $quantite = (int) $_POST['quantite'];
    $produit = getProduitByCode($codeBarre);

    if ($produit && $quantite > 0) {
        $qteDejaAuPanier = 0;
        foreach ($cart as $item) {
            if ($item['code_barre'] === $codeBarre) $qteDejaAuPanier += $item['quantite'];
        }
        if ($qteDejaAuPanier + $quantite > $produit['quantite_stock']) {
            $error = 'Stock insuffisant (disponible: ' . ($produit['quantite_stock'] - $qteDejaAuPanier) . ')';
        } else {
            $found = false;
            foreach ($cart as &$item) {
                if ($item['code_barre'] === $codeBarre) {
                    $item['quantite'] += $quantite;
                    $item['sous_total_ht'] = $item['prix_unitaire_ht'] * $item['quantite'];
                    $found = true;
                    break;
                }
            }
            unset($item);
            if (!$found) {
                $cart[] = [
                    'code_barre' => $codeBarre,
                    'nom' => $produit['nom'],
                    'prix_unitaire_ht' => $produit['prix_unitaire_ht'],
                    'quantite' => $quantite,
                    'sous_total_ht' => $produit['prix_unitaire_ht'] * $quantite
                ];
            }
            $_SESSION['current_product'] = null;
            $success = htmlspecialchars($produit['nom']) . ' ajouté au panier';
        }
    } else {
        $error = 'Quantité invalide';
    }
}

// Modifier la quantité d'un article du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    verifyCsrf();
    $index = (int) $_POST['cart_index'];
    $nouvelleQte = (int) $_POST['nouvelle_quantite'];
    if (isset($cart[$index])) {
        $produit = getProduitByCode($cart[$index]['code_barre']);
        if ($nouvelleQte <= 0) {
            unset($cart[$index]);
            $cart = array_values($cart);
            $success = 'Article supprimé';
        } elseif ($produit && $nouvelleQte <= $produit['quantite_stock']) {
            $cart[$index]['quantite'] = $nouvelleQte;
            $cart[$index]['sous_total_ht'] = $cart[$index]['prix_unitaire_ht'] * $nouvelleQte;
            $success = 'Quantité mise à jour';
        } else {
            $error = 'Stock insuffisant';
        }
    }
}

// Supprimer un article
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $index = (int) $_GET['remove'];
    if (isset($cart[$index])) {
        unset($cart[$index]);
        $cart = array_values($cart);
        $success = 'Article supprimé';
    }
}

// Vider le panier
if (isset($_GET['clear_cart'])) {
    $cart = [];
    $_SESSION['current_product'] = null;
    $success = 'Panier vidé';
}

// Valider la facture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['validate_invoice'])) {
    verifyCsrf();
    if (empty($cart)) {
        $error = 'Le panier est vide';
    } else {
        $facture = createFacture($cart, $currentUser['name']);
        if ($facture) {
            $cart = [];
            $_SESSION['current_product'] = null;
            header('Location: afficher-facture.php?numero=' . urlencode($facture['numero']));
            exit;
        } else {
            $error = 'Erreur lors de la création de la facture';
        }
    }
}

$totals = calculateTotals($cart);
$currentProduct = $_SESSION['current_product'] ?? null;
?>

<div class="max-w-6xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Colonne gauche : scan + produit -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center gap-3 mb-5">
                    <i data-lucide="scan-line" class="w-6 h-6 text-blue-600"></i>
                    <h2 class="text-xl font-semibold">Nouvelle Facture</h2>
                </div>

                <form method="POST" id="scan-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <div class="flex gap-2">
                        <input
                            type="text"
                            name="scan_barcode"
                            id="barcode-field"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-lg"
                            placeholder="Code-barres ou nom du produit..."
                            value="<?php echo htmlspecialchars($_POST['scan_barcode'] ?? ''); ?>"
                            autofocus
                            autocomplete="off"
                        />
                        <button type="submit" class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i data-lucide="search" class="w-5 h-5"></i>
                        </button>
                    </div>
                </form>

                <?php if ($error): ?>
                <div class="mt-3 bg-red-50 border border-red-200 rounded-lg p-3 flex items-center gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 shrink-0"></i>
                    <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mt-3 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-4 h-4 text-green-600 shrink-0"></i>
                    <p class="text-green-700 text-sm"><?php echo $success; ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Résultats de recherche par nom -->
            <?php if (!empty($searchResults)): ?>
            <div class="bg-white rounded-xl shadow-sm p-4">
                <p class="text-sm text-gray-500 mb-3"><?php echo count($searchResults); ?> résultat(s) trouvé(s)</p>
                <div class="space-y-2 max-h-60 overflow-y-auto">
                    <?php foreach ($searchResults as $p): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="select_product" value="<?php echo htmlspecialchars($p['code_barre']); ?>">
                        <button type="submit" class="w-full text-left flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition-colors">
                            <div>
                                <p class="font-medium text-sm"><?php echo htmlspecialchars($p['nom']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($p['code_barre']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-blue-600"><?php echo number_format($p['prix_unitaire_ht'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></p>
                                <p class="text-xs <?php echo $p['quantite_stock'] <= 5 ? 'text-red-500' : 'text-gray-400'; ?>">
                                    Stock: <?php echo $p['quantite_stock']; ?>
                                </p>
                            </div>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Fiche produit sélectionné -->
            <?php if ($currentProduct): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-5">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-blue-900"><?php echo htmlspecialchars($currentProduct['nom']); ?></h3>
                        <p class="text-xs text-blue-600 mt-0.5"><?php echo htmlspecialchars($currentProduct['code_barre']); ?></p>
                    </div>
                    <span class="text-lg font-bold text-blue-700">
                        <?php echo number_format($currentProduct['prix_unitaire_ht'], 0, ',', ' '); ?> <?php echo CURRENCY; ?>
                    </span>
                </div>

                <div class="flex items-center gap-2 mb-4 text-sm">
                    <i data-lucide="package" class="w-4 h-4 text-blue-500"></i>
                    <span class="<?php echo $currentProduct['quantite_stock'] <= 5 ? 'text-red-600 font-semibold' : 'text-blue-700'; ?>">
                        <?php echo $currentProduct['quantite_stock']; ?> unité(s) en stock
                    </span>
                    <?php if ($currentProduct['quantite_stock'] <= 5 && $currentProduct['quantite_stock'] > 0): ?>
                    <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Stock faible</span>
                    <?php elseif ($currentProduct['quantite_stock'] == 0): ?>
                    <span class="bg-red-100 text-red-700 text-xs px-2 py-0.5 rounded-full">Rupture</span>
                    <?php endif; ?>
                </div>

                <?php if ($currentProduct['quantite_stock'] > 0): ?>
                <form method="POST" class="flex gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="code_barre" value="<?php echo htmlspecialchars($currentProduct['code_barre']); ?>">
                    <input
                        type="number"
                        name="quantite"
                        value="1"
                        min="1"
                        max="<?php echo $currentProduct['quantite_stock']; ?>"
                        class="w-24 px-3 py-2 border border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-center"
                        required
                    />
                    <button type="submit" name="add_to_cart" class="flex-1 flex items-center justify-center gap-2 bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Ajouter au panier
                    </button>
                </form>
                <?php else: ?>
                <p class="text-center text-red-600 text-sm py-2">Produit en rupture de stock</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne droite : panier -->
        <div class="bg-white rounded-xl shadow-sm p-6 flex flex-col">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl font-semibold">
                    Panier
                    <?php if (!empty($cart)): ?>
                    <span class="ml-2 bg-blue-100 text-blue-700 text-sm px-2 py-0.5 rounded-full"><?php echo count($cart); ?></span>
                    <?php endif; ?>
                </h2>
                <?php if (!empty($cart)): ?>
                <a href="?clear_cart=1" class="text-xs text-red-500 hover:text-red-700 flex items-center gap-1"
                   onclick="return confirm('Vider le panier ?')">
                    <i data-lucide="trash" class="w-3 h-3"></i> Vider
                </a>
                <?php endif; ?>
            </div>

            <?php if (empty($cart)): ?>
            <div class="flex-1 flex flex-col items-center justify-center py-16 text-gray-400">
                <i data-lucide="shopping-cart" class="w-14 h-14 mb-3 opacity-30"></i>
                <p class="text-sm">Aucun article dans le panier</p>
                <p class="text-xs mt-1">Scannez un produit pour commencer</p>
            </div>
            <?php else: ?>
            <div class="flex-1 space-y-2 mb-4 max-h-80 overflow-y-auto pr-1">
                <?php foreach ($cart as $index => $item): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg group">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($item['nom']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo number_format($item['prix_unitaire_ht'], 0, ',', ' '); ?> <?php echo CURRENCY; ?> / unité</p>
                    </div>
                    <!-- Modifier quantité inline -->
                    <form method="POST" class="flex items-center gap-1">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="cart_index" value="<?php echo $index; ?>">
                        <input
                            type="number"
                            name="nouvelle_quantite"
                            value="<?php echo $item['quantite']; ?>"
                            min="0"
                            class="w-14 px-2 py-1 border border-gray-300 rounded text-center text-sm focus:ring-1 focus:ring-blue-500 outline-none"
                            onchange="this.form.submit()"
                        />
                        <button type="submit" name="update_cart" class="hidden"></button>
                    </form>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold"><?php echo number_format($item['sous_total_ht'], 0, ',', ' '); ?></p>
                        <p class="text-xs text-gray-400"><?php echo CURRENCY; ?></p>
                    </div>
                    <a href="?remove=<?php echo $index; ?>" class="text-gray-300 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Totaux -->
            <div class="border-t border-gray-100 pt-4 space-y-2 text-sm">
                <div class="flex justify-between text-gray-600">
                    <span>Sous-total HT</span>
                    <span><?php echo number_format($totals['total_ht'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></span>
                </div>
                <div class="flex justify-between text-gray-600">
                    <span>TVA (<?php echo (int)(TVA_RATE * 100); ?>%)</span>
                    <span><?php echo number_format($totals['tva'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></span>
                </div>
                <div class="flex justify-between items-center pt-3 border-t-2 border-gray-200">
                    <span class="text-lg font-bold">Net à payer</span>
                    <span class="text-2xl font-bold text-green-600"><?php echo number_format($totals['total_ttc'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></span>
                </div>
            </div>

            <form method="POST" class="mt-4">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" name="validate_invoice"
                    class="w-full flex items-center justify-center gap-2 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium text-lg">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    Valider la facture
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Re-focus sur le champ après soumission du formulaire de scan
document.addEventListener('DOMContentLoaded', function() {
    const field = document.getElementById('barcode-field');
    if (field) {
        field.focus();
        field.select();
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?>
