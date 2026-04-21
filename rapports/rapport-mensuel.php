<?php
require_once '../includes/header.php';
require_once '../includes/fonctions-factures.php';

if (!hasRole('manager')) {
    header('Location: /facturation/index.php');
    exit;
}

$mois = $_GET['mois'] ?? date('Y-m');
list($annee, $mois_num) = explode('-', $mois);

// Calculer le premier et dernier jour du mois
$dateDebut = $annee . '-' . $mois_num . '-01';
$dateFin = date('Y-m-t', strtotime($dateDebut));

$factures = getFacturesByPeriod($dateDebut, $dateFin);
$stats = getSalesStats($dateDebut, $dateFin);

// Statistiques par jour
$ventesParJour = [];
foreach ($factures as $facture) {
    $jour = $facture['date'];
    if (!isset($ventesParJour[$jour])) {
        $ventesParJour[$jour] = 0;
    }
    $ventesParJour[$jour] += $facture['total_ttc'];
}
ksort($ventesParJour);
?>

<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <i data-lucide="calendar" class="w-6 h-6 text-blue-600"></i>
            <h2 class="text-2xl">Rapport Mensuel</h2>
        </div>

        <!-- Sélecteur de mois -->
        <div class="mb-6">
            <form method="GET" class="flex gap-4 items-center">
                <label class="text-sm text-gray-700">Mois:</label>
                <input
                    type="month"
                    name="mois"
                    value="<?php echo $mois; ?>"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                />
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Afficher
                </button>
            </form>
        </div>

        <!-- Statistiques générales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600"><?php echo $stats['nombre_factures']; ?></div>
                <div class="text-sm text-gray-600">Factures</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600"><?php echo number_format($stats['total_ventes'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></div>
                <div class="text-sm text-gray-600">Ventes TTC</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600"><?php echo number_format($stats['total_ht'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></div>
                <div class="text-sm text-gray-600">Ventes HT</div>
            </div>
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600"><?php echo number_format($stats['total_tva'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></div>
                <div class="text-sm text-gray-600">TVA collectée</div>
            </div>
        </div>

        <!-- Graphique des ventes par jour -->
        <div class="mb-6">
            <h3 class="text-lg mb-4">Évolution des ventes</h3>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="space-y-2">
                    <?php foreach ($ventesParJour as $jour => $total): ?>
                    <div class="flex items-center gap-4">
                        <div class="w-20 text-sm"><?php echo date('d/m', strtotime($jour)); ?></div>
                        <div class="flex-1 bg-gray-200 rounded-full h-4">
                            <div class="bg-blue-600 h-4 rounded-full" style="width: <?php echo $stats['total_ventes'] > 0 ? ($total / $stats['total_ventes'] * 100) : 0; ?>%"></div>
                        </div>
                        <div class="w-24 text-right text-sm"><?php echo number_format($total, 0, ',', ' '); ?> <?php echo CURRENCY; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Top produits -->
        <?php if (!empty($stats['produits_vendus'])): ?>
        <div class="mb-6">
            <h3 class="text-lg mb-4">Top produits du mois</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 px-4">Produit</th>
                            <th class="text-center py-3 px-4">Quantité vendue</th>
                            <th class="text-right py-3 px-4">Total HT</th>
                            <th class="text-right py-3 px-4">% du total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Trier par total décroissant
                        arsort($stats['produits_vendus']);
                        foreach ($stats['produits_vendus'] as $produit):
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?php echo htmlspecialchars($produit['nom']); ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $produit['quantite']; ?></td>
                            <td class="py-3 px-4 text-right"><?php echo number_format($produit['total'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></td>
                            <td class="py-3 px-4 text-right"><?php echo $stats['total_ht'] > 0 ? round($produit['total'] / $stats['total_ht'] * 100, 1) : 0; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Résumé par jour -->
        <div>
            <h3 class="text-lg mb-4">Résumé par jour</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 px-4">Date</th>
                            <th class="text-center py-3 px-4">Nombre de factures</th>
                            <th class="text-right py-3 px-4">Total TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $resumeParJour = [];
                        foreach ($factures as $facture) {
                            $jour = $facture['date'];
                            if (!isset($resumeParJour[$jour])) {
                                $resumeParJour[$jour] = ['count' => 0, 'total' => 0];
                            }
                            $resumeParJour[$jour]['count']++;
                            $resumeParJour[$jour]['total'] += $facture['total_ttc'];
                        }
                        ksort($resumeParJour);

                        foreach ($resumeParJour as $jour => $data):
                        ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?php echo date('d/m/Y', strtotime($jour)); ?></td>
                            <td class="py-3 px-4 text-center"><?php echo $data['count']; ?></td>
                            <td class="py-3 px-4 text-right"><?php echo number_format($data['total'], 0, ',', ' '); ?> <?php echo CURRENCY; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>