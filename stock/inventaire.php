<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';

// Fetch list of products
try {
    $prod_stmt = $pdo->prepare("CALL lister_produits()");
    $prod_stmt->execute();
    $products = $prod_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error_message = "Erreur lors de la récupération des produits : " . $e->getMessage();
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Inventaire des Stocks</h1>
        <p class="text-gray-500">Vue globale des stocks et des alertes de seuil.</p>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Inventory Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
            <h2 class="text-2xl font-semibold text-gray-800">Liste des produits</h2>
            <div class="w-full md:w-1/3">
                <label for="productSearch" class="sr-only">Rechercher</label>
                <input type="text" id="productSearch" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Rechercher un produit...">
            </div>
        </div>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Nom du Produit</th>
                        <th scope="col" class="px-6 py-3">Catégorie</th>
                        <th scope="col" class="px-6 py-3 text-center">Stock Actuel</th>
                        <th scope="col" class="px-6 py-3 text-center">Seuil d'Alerte</th>
                        <th scope="col" class="px-6 py-3 text-center">Statut</th>
                    </tr>
                </thead>
                <tbody id="productTableBody">
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Aucun produit trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php $is_low_stock = $product['stock_actuel'] <= $product['seuil_alert']; ?>
                            <tr class="product-row bg-white border-b hover:bg-gray-50 <?php echo $is_low_stock ? 'bg-red-50' : ''; ?>">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($product['id_prod']); ?></td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap product-name">
                                    <?php echo htmlspecialchars($product['nom']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($product['categorie']); ?></td>
                                <td class="px-6 py-4 text-center font-bold <?php echo $is_low_stock ? 'text-red-600' : 'text-gray-800'; ?>">
                                    <?php echo htmlspecialchars($product['stock_actuel']); ?>
                                </td>
                                <td class="px-6 py-4 text-center"><?php echo htmlspecialchars($product['seuil_alert']); ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($is_low_stock): ?>
                                        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Réapprovisionner
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            OK
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('productSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#productTableBody .product-row');
    
    rows.forEach(row => {
        let nameCell = row.querySelector('.product-name');
        if (nameCell) {
            let name = nameCell.textContent.toLowerCase();
            if (name.includes(filter)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
