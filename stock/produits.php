<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$edit_product = null;

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("CALL supprimer_produit(?)");
        $stmt->execute([$_GET['delete_id']]);
        $feedback_message = "Produit supprimé avec succès !";
        $feedback_type = 'success';
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la suppression du produit.";
        $feedback_type = 'error';
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prod = $_POST['id_prod'] ?? null;
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $prix_vente = $_POST['prix_vente'];
    $prix_achat = $_POST['prix_achat'];
    $stock = $_POST['stock_actuel'];
    $seuil = $_POST['seuil_alert'];
    $id_cat = $_POST['id_cat'];
    $image_url = $_POST['image_url'] ?? null; // Get image_url from form

    if (empty($nom) || empty($prix_vente) || empty($prix_achat) || !isset($stock) || !isset($seuil) || empty($id_cat)) {
        $feedback_message = "Veuillez remplir tous les champs obligatoires.";
        $feedback_type = 'error';
    } else {
        try {
            if ($id_prod) { // Update
                $stmt = $pdo->prepare("CALL modifier_produit(?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_prod, $nom, $description, $prix_vente, $prix_achat, $stock, $seuil, $id_cat, $image_url]);
                $feedback_message = "Produit modifié avec succès !";
            } else { // Add
                $stmt = $pdo->prepare("CALL ajouter_produit(?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $description, $prix_vente, $prix_achat, $stock, $seuil, $id_cat, $image_url]);
                $feedback_message = "Produit ajouté avec succès !";
            }
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Handle Edit Action (Fetch product to edit)
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM produit WHERE id_prod = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_product = $stmt->fetch();
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la récupération du produit.";
        $feedback_type = 'error';
    }
}

// Fetch categories for the form dropdown
try {
    $cat_stmt = $pdo->prepare("CALL lister_categories()");
    $cat_stmt->execute();
    $categories = $cat_stmt->fetchAll();
    $cat_stmt->closeCursor();
} catch (PDOException $e) {
    $categories = [];
    $feedback_message = "Erreur critique : Impossible de charger les catégories.";
    $feedback_type = 'error';
}

// Fetch list of products
try {
    $prod_stmt = $pdo->prepare("CALL lister_produits()");
    $prod_stmt->execute();
    $products = $prod_stmt->fetchAll();
} catch (PDOException $e) {
    $products = [];
    if(empty($feedback_message)) {
        $feedback_message = "Erreur lors de la récupération des produits : " . $e->getMessage();
        $feedback_type = 'error';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Gestion des Produits</h1>
        <p class="text-gray-500">Ajouter, modifier et visualiser les produits du magasin.</p>
    </div>

    <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Product Form -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8" id="form">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $edit_product ? 'Modifier le produit' : 'Ajouter un nouveau produit'; ?></h2>
        <form action="produits.php#form" method="POST">
            <input type="hidden" name="id_prod" value="<?php echo $edit_product['id_prod'] ?? ''; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="lg:col-span-2">
                    <label for="nom" class="block mb-2 text-sm font-medium text-gray-900">Nom du produit</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($edit_product['nom'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div class="lg:col-span-2">
                    <label for="id_cat" class="block mb-2 text-sm font-medium text-gray-900">Catégorie</label>
                    <select id="id_cat" name="id_cat" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <option value="" disabled selected>Choisir une catégorie</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id_cat']; ?>" <?php echo (isset($edit_product) && $edit_product['id_cat'] == $cat['id_cat']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['libelle']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-4">
                    <label for="description" class="block mb-2 text-sm font-medium text-gray-900">Description</label>
                    <textarea id="description" name="description" rows="3" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                </div>
                <div>
                    <label for="prix_achat" class="block mb-2 text-sm font-medium text-gray-900">Prix d'achat (FCFA)</label>
                    <input type="number" id="prix_achat" name="prix_achat" step="1" value="<?php echo htmlspecialchars($edit_product['prix_achat'] ?? '0'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="prix_vente" class="block mb-2 text-sm font-medium text-gray-900">Prix de vente (FCFA)</label>
                    <input type="number" id="prix_vente" name="prix_vente" step="1" value="<?php echo htmlspecialchars($edit_product['prix_vente'] ?? '0'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="stock_actuel" class="block mb-2 text-sm font-medium text-gray-900">Stock Initial</label>
                    <input type="number" id="stock_actuel" name="stock_actuel" value="<?php echo htmlspecialchars($edit_product['stock_actuel'] ?? '0'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="seuil_alert" class="block mb-2 text-sm font-medium text-gray-900">Seuil d'alerte</label>
                    <input type="number" id="seuil_alert" name="seuil_alert" value="<?php echo htmlspecialchars($edit_product['seuil_alert'] ?? '5'); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div class="lg:col-span-4">
                    <label for="image_url" class="block mb-2 text-sm font-medium text-gray-900">URL de l'image (Optionnel)</label>
                    <input type="url" id="image_url" name="image_url" value="<?php echo htmlspecialchars($edit_product['image_url'] ?? ''); ?>" placeholder="https://exemple.com/image.png" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
            </div>
            <div class="flex items-center mt-6">
                <button type="submit" name="submit_product" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <?php echo $edit_product ? 'Mettre à jour le produit' : 'Ajouter le produit'; ?>
                </button>
                <?php if ($edit_product): ?>
                    <a href="produits.php" class="ml-4 py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Products List Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Liste des produits</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="p-4">Image</th>
                        <th scope="col" class="px-6 py-3">Nom</th>
                        <th scope="col" class="px-6 py-3">Catégorie</th>
                        <th scope="col" class="px-6 py-3">Prix Vente</th>
                        <th scope="col" class="px-6 py-3">Prix Achat</th>
                        <th scope="col" class="px-6 py-3">Stock</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Aucun produit trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="p-4">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="h-10 w-10 object-cover rounded-md">
                                    <?php else: ?>
                                        <div class="h-10 w-10 bg-gray-100 rounded-md flex items-center justify-center text-gray-400 text-xs">No img</div>
                                    <?php endif; ?>
                                </td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($product['nom']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($product['categorie']); ?></td>
                                <td class="px-6 py-4 font-semibold"><?php echo format_price($product['prix_vente']); ?></td>
                                <td class="px-6 py-4 text-gray-600"><?php echo format_price($product['prix_achat']); ?></td>
                                <td class="px-6 py-4 <?php echo ($product['stock_actuel'] <= $product['seuil_alert']) ? 'text-red-600 font-bold' : ''; ?>">
                                    <?php echo $product['stock_actuel']; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="produits.php?edit_id=<?php echo $product['id_prod']; ?>#form" class="font-medium text-blue-600 hover:underline">Modifier</a>
                                    <a href="produits.php?delete_id=<?php echo $product['id_prod']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');" class="font-medium text-red-600 hover:underline ms-3">Supprimer</a>
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
// Hide feedback alert after 5s
const feedbackAlert = document.getElementById('feedback-alert');
if(feedbackAlert) {
    setTimeout(() => { 
        feedbackAlert.style.transition = 'opacity 0.5s ease';
        feedbackAlert.style.opacity = '0';
        setTimeout(() => { feedbackAlert.style.display = 'none'; }, 500);
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
