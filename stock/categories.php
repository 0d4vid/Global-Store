<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$edit_category = null;

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("CALL supprimer_categorie(?)");
        $stmt->execute([$_GET['delete_id']]);
        $feedback_message = "Catégorie supprimée avec succès !";
        $feedback_type = 'success';
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la suppression de la catégorie. Elle est peut-être utilisée par un produit.";
        $feedback_type = 'error';
    }
}

// Handle Add/Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = $_POST['libelle'];
    $id_cat = $_POST['id_cat'] ?? null;

    if (empty($libelle)) {
        $feedback_message = "Le libellé ne peut pas être vide.";
        $feedback_type = 'error';
    } else {
        try {
            if ($id_cat) { // Update
                $stmt = $pdo->prepare("CALL modifier_categorie(?, ?)");
                $stmt->execute([$id_cat, $libelle]);
                $feedback_message = "Catégorie modifiée avec succès !";
            } else { // Add
                $stmt = $pdo->prepare("CALL ajouter_categorie(?)");
                $stmt->execute([$libelle]);
                $feedback_message = "Catégorie ajoutée avec succès !";
            }
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Handle Edit Action (Fetch category to edit)
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM categorie WHERE id_cat = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_category = $stmt->fetch();
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la récupération de la catégorie.";
        $feedback_type = 'error';
    }
}


// Fetch all categories for display
try {
    $stmt = $pdo->prepare("CALL lister_categories()");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
    $feedback_message = "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $feedback_type = 'error';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Gestion des Catégories</h1>
        <p class="text-gray-500">Ajouter, modifier et organiser les catégories de produits.</p>
    </div>

    <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Category Form -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8" id="form">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $edit_category ? 'Modifier la catégorie' : 'Ajouter une nouvelle catégorie'; ?></h2>
        <form action="categories.php#form" method="POST">
            <input type="hidden" name="id_cat" value="<?php echo $edit_category['id_cat'] ?? ''; ?>">
            <div class="flex flex-col sm:flex-row items-end gap-4">
                <div class="w-full sm:flex-grow">
                    <label for="libelle" class="block mb-2 text-sm font-medium text-gray-900">Libellé de la catégorie</label>
                    <input type="text" id="libelle" name="libelle" value="<?php echo htmlspecialchars($edit_category['libelle'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div class="flex items-center space-x-2 w-full sm:w-auto">
                    <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                        <?php echo $edit_category ? 'Mettre à jour' : 'Ajouter'; ?>
                    </button>
                    <?php if ($edit_category): ?>
                        <a href="categories.php" class="w-full py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200 text-center">Annuler</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Categories List Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Liste des catégories</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Libellé</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Aucune catégorie trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($category['id_cat']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($category['libelle']); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="categories.php?edit_id=<?php echo $category['id_cat']; ?>#form" class="font-medium text-blue-600 hover:underline">Modifier</a>
                                    <a href="categories.php?delete_id=<?php echo $category['id_cat']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');" class="font-medium text-red-600 hover:underline ms-3">Supprimer</a>
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
