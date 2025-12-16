<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur', 'admin']);
require_once __DIR__ . '/../config/db.php';

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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Historique de Mes Ventes</h1>
        <p class="text-gray-500">Consulter les ventes que vous avez effectuées.</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Sales History Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">N° Facture</th>
                        <th scope="col" class="px-6 py-3">Date</th>
                        <th scope="col" class="px-6 py-3">Client</th>
                        <th scope="col" class="px-6 py-3">Montant Total</th>
                        <th scope="col" class="px-6 py-3">Statut</th>
                        <th scope="col" class="px-6 py-3 text-right">Détails</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sales)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    <p class="font-semibold">Aucune vente enregistrée</p>
                                    <p class="text-xs">Vous n'avez pas encore effectué de vente.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sales as $sale): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($sale['num_facture']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($sale['date_vente']))); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($sale['nom_client'] ?? 'Client au comptant'); ?></td>
                                <td class="px-6 py-4 font-semibold"><?php echo format_price($sale['total'] ?? 0); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full <?php echo $sale['statut'] === 'TERMINEE' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo htmlspecialchars($sale['statut']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="#" class="font-medium text-blue-600 hover:underline">Voir détails</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
