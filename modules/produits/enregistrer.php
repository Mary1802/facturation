<?php
require_once '../../includes/header.php';
require_once '../../includes/fonctions-produits.php';

if (!hasRole('manager')) {
    header('Location: ../../index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codeBarre = trim($_POST['code_barre'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prixUnitaireHT = (float) ($_POST['prix_unitaire_ht'] ?? 0);
    $quantiteStock = (int) ($_POST['quantite_stock'] ?? 0);

    // Validation
    if (empty($codeBarre) || empty($nom) || $prixUnitaireHT <= 0 || $quantiteStock < 0) {
        $error = 'Veuillez remplir tous les champs correctement';
    } elseif (produitExists($codeBarre)) {
        $error = 'Un produit avec ce code-barres existe déjà';
    } else {
        // Enregistrer le produit
        if (saveProduit($codeBarre, $nom, $prixUnitaireHT, $quantiteStock)) {
            $success = 'Produit enregistré avec succès';
            // Réinitialiser le formulaire
            $_POST = [];
        } else {
            $error = 'Erreur lors de l\'enregistrement du produit';
        }
    }
}
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <i data-lucide="package" class="w-6 h-6 text-blue-600"></i>
            <h2 class="text-2xl">Enregistrer un Produit</h2>
        </div>

        <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
            <p class="text-green-700"><?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm mb-2 text-gray-700">Code-barres *</label>
                <input
                    type="text"
                    name="code_barre"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="Entrez le code-barres"
                    value="<?php echo htmlspecialchars($_POST['code_barre'] ?? ''); ?>"
                    required
                />
            </div>

            <div>
                <label class="block text-sm mb-2 text-gray-700">Nom du produit *</label>
                <input
                    type="text"
                    name="nom"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="Entrez le nom du produit"
                    value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                    required
                />
            </div>

            <div>
                <label class="block text-sm mb-2 text-gray-700">Prix unitaire HT (<?php echo CURRENCY; ?>) *</label>
                <input
                    type="number"
                    name="prix_unitaire_ht"
                    step="0.01"
                    min="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="0.00"
                    value="<?php echo htmlspecialchars($_POST['prix_unitaire_ht'] ?? ''); ?>"
                    required
                />
            </div>

            <div>
                <label class="block text-sm mb-2 text-gray-700">Quantité en stock *</label>
                <input
                    type="number"
                    name="quantite_stock"
                    min="0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    placeholder="0"
                    value="<?php echo htmlspecialchars($_POST['quantite_stock'] ?? ''); ?>"
                    required
                />
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors"
            >
                Enregistrer le produit
            </button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>