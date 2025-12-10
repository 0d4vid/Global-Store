<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$id_user = $_SESSION['user_id'];
$sales = [];

// Fetch sales history for the current user
try {
    $stmt = $pdo->prepare("CALL lister_ventes_utilisateur(?)");
    $stmt->execute([$id_user]);
    $sales = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération de l'historique des ventes : " . $e->getMessage();
}

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Historique de Mes Ventes</h1>
    <p class="text-gray-600 mb-8">Consulter les ventes que vous avez effectuées.</p>

    <?php if (isset($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Sales History Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">N° Facture</th>
                    <th scope="col" class="px-6 py-3">Date</th>
                    <th scope="col" class="px-6 py-3">Client</th>
                    <th scope="col" class="px-6 py-3">Montant Total</th>
                    <th scope="col" class="px-6 py-3">Statut</th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Détails</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Vous n'avez enregistré aucune vente pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                <?php echo htmlspecialchars($sale['num_facture']); ?>
                            </th>
                            <td class="px-6 py-4"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sale['date_vente']))); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($sale['nom_client'] ?? 'Client au comptant'); ?></td>
                            <td class="px-6 py-4"><?php echo format_price($sale['total'] ?? 0); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $sale['statut'] === 'TERMINEE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo htmlspecialchars($sale['statut']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <!-- Future link to view sale details -->
                                <a href="#" class="font-medium text-blue-600 hover:underline">Voir détails</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
