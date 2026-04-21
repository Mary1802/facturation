<?php
require_once '../../includes/header.php';
require_once '../../includes/fonctions-produits.php';

if (!hasRole('manager')) {
    header('Location: /facturation/index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$error = '';
$success = '';
$isUpdate = false;
$prefill = ['code_barre' => '', 'nom' => '', 'prix_unitaire_ht' => '', 'quantite_stock' => ''];

// Pré-remplir si édition depuis la liste
if (isset($_GET['edit'])) {
    $existing = getProduitByCode($_GET['edit']);
    if ($existing) {
        $prefill = $existing;
        $isUpdate = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Requête invalide';
    } else {
        $codeBarre = trim($_POST['code_barre'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $prixUnitaireHT = (float) ($_POST['prix_unitaire_ht'] ?? 0);
        $quantiteStock = (int) ($_POST['quantite_stock'] ?? 0);
        $forceUpdate = isset($_POST['force_update']);

        $prefill = compact('codeBarre', 'nom', 'prixUnitaireHT', 'quantiteStock');
        $prefill['code_barre'] = $codeBarre;
        $prefill['prix_unitaire_ht'] = $prixUnitaireHT;
        $prefill['quantite_stock'] = $quantiteStock;

        if (empty($codeBarre) || empty($nom) || $prixUnitaireHT <= 0 || $quantiteStock < 0) {
            $error = 'Veuillez remplir tous les champs correctement';
        } elseif (produitExists($codeBarre) && !$forceUpdate) {
            $isUpdate = true;
            $error = 'Ce code-barres existe déjà. Cochez la case ci-dessous pour mettre à jour le produit.';
        } else {
            if (saveProduit($codeBarre, $nom, $prixUnitaireHT, $quantiteStock)) {
                $success = $forceUpdate ? 'Produit mis à jour avec succès' : 'Produit enregistré avec succès';
                $prefill = ['code_barre' => '', 'nom' => '', 'prix_unitaire_ht' => '', 'quantite_stock' => ''];
                $isUpdate = false;
            } else {
                $error = 'Erreur lors de l\'enregistrement';
            }
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
            <h2 class="text-xl font-semibold">Enregistrer un Produit</h2>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4 flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-600 mt-0.5 shrink-0"></i>
            <p class="text-red-700 text-sm"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4 flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4 text-green-600 shrink-0"></i>
            <p class="text-green-700 text-sm"><?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div>
                <label class="block text-sm font-medium mb-1.5 text-gray-700">Code-barres *</label>
                <input
                    type="text"
                    name="code_barre"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="Ex: 3017620422003"
                    value="<?php echo htmlspecialchars($prefill['code_barre']); ?>"
                    <?php echo $isUpdate ? 'readonly class="w-full px-4 py-2.5 border border-gray-200 bg-gray-50 rounded-lg outline-none"' : ''; ?>
                    required
                />
            </div>

            <div>
                <label class="block text-sm font-medium mb-1.5 text-gray-700">Nom du produit *</label>
                <input
                    type="text"
                    name="nom"
                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="Ex: Huile de palme 1L"
                    value="<?php echo htmlspecialchars($prefill['nom']); ?>"
                    required
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-gray-700">Prix HT (<?php echo CURRENCY; ?>) *</label>
                    <input
                        type="number"
                        name="prix_unitaire_ht"
                        step="1"
                        min="1"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        placeholder="0"
                        value="<?php echo htmlspecialchars($prefill['prix_unitaire_ht']); ?>"
                        required
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5 text-gray-700">Quantité en stock *</label>
                    <input
                        type="number"
                        name="quantite_stock"
                        min="0"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        placeholder="0"
                        value="<?php echo htmlspecialchars($prefill['quantite_stock']); ?>"
                        required
                    />
                </div>
            </div>

            <?php if ($isUpdate): ?>
            <label class="flex items-center gap-2 text-sm text-orange-700 bg-orange-50 border border-orange-200 rounded-lg p-3 cursor-pointer">
                <input type="checkbox" name="force_update" value="1" class="rounded">
                Confirmer la mise à jour du produit existant
            </label>
            <?php endif; ?>

            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                <?php echo $isUpdate ? 'Mettre à jour le produit' : 'Enregistrer le produit'; ?>
            </button>
        </form>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="liste.php" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Voir la liste des produits
            </a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
