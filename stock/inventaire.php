<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetch list of products
try {
    $prod_stmt = $pdo->prepare("CALL lister_produits()");
    $prod_stmt->execute();
    $products = $prod_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $error_message = "Erreur lors de la récupération des produits : " . $e->getMessage();
}

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Inventaire des Stocks</h1>
    <p class="text-gray-600 mb-8">Vue globale des stocks et des alertes de seuil. Les produits nécessitant un réapprovisionnement sont surlignés en rouge.</p>

    <?php if (isset($error_message)): ?>
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Search Bar -->
    <div class="mb-4">
        <label for="productSearch" class="sr-only">Rechercher</label>
        <input type="text" id="productSearch" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Rechercher un produit par nom...">
    </div>

    <!-- Inventory Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Nom du Produit</th>
                    <th scope="col" class="px-6 py-3">Catégorie</th>
                    <th scope="col" class="px-6 py-3">Stock Actuel</th>
                    <th scope="col" class="px-6 py-3">Seuil d'Alerte</th>
                    <th scope="col" class="px-6 py-3">Statut</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Aucun produit trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <?php $is_low_stock = $product['stock_actuel'] <= $product['seuil_alert']; ?>
                        <tr class="product-row border-b <?php echo $is_low_stock ? 'bg-red-50 hover:bg-red-100' : 'bg-white hover:bg-gray-50'; ?>">
                            <td class="px-6 py-4"><?php echo htmlspecialchars($product['id_prod']); ?></td>
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap product-name">
                                <?php echo htmlspecialchars($product['nom']); ?>
                            </th>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($product['categorie']); ?></td>
                            <td class="px-6 py-4 font-bold <?php echo $is_low_stock ? 'text-red-700' : 'text-gray-900'; ?>">
                                <?php echo htmlspecialchars($product['stock_actuel']); ?>
                            </td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($product['seuil_alert']); ?></td>
                            <td class="px-6 py-4">
                                <?php if ($is_low_stock): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-200 text-red-800">
                                        Réapprovisionner
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
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
