<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_prod = $_POST['id_prod'];
    $id_fourn = $_POST['id_fourn'];
    $qte = $_POST['qte_recue'];
    $id_user = $_SESSION['user_id'];

    if (empty($id_prod) || empty($id_fourn) || empty($qte) || $qte <= 0) {
        $feedback_message = "Veuillez remplir tous les champs avec une quantité valide.";
        $feedback_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("CALL reapprovisionner_produit(?, ?, ?, ?)");
            $stmt->execute([$id_prod, $qte, $id_fourn, $id_user]);
            $feedback_message = "Réapprovisionnement enregistré avec succès ! Le stock a été mis à jour.";
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de l'enregistrement : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Fetch products and suppliers for dropdowns
try {
    $products = $pdo->query("SELECT id_prod, nom FROM produit ORDER BY nom")->fetchAll();
    $suppliers = $pdo->query("SELECT id_fourn, nom_fourn FROM fournisseur ORDER BY nom_fourn")->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $suppliers = [];
    $feedback_message = "Erreur critique : Impossible de charger les données du formulaire.";
    $feedback_type = 'error';
}

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Réapprovisionnement</h1>
    <p class="text-gray-600 mb-8">Enregistrer une nouvelle entrée de stock d'un fournisseur.</p>

    <?php if ($feedback_message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Restocking Form -->
    <div class="bg-white p-8 rounded-lg shadow-md">
        <h2 class="text-2xl font-semibold text-gray-700 mb-6">Formulaire de réapprovisionnement</h2>
        <form action="reappro.php" method="POST">
            <div class="grid md:grid-cols-1 gap-6">
                <div>
                    <label for="id_prod" class="block mb-2 text-sm font-medium text-gray-900">Produit</label>
                    <select id="id_prod" name="id_prod" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <option value="" disabled selected>Sélectionner un produit</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id_prod']; ?>"><?php echo htmlspecialchars($product['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="id_fourn" class="block mb-2 text-sm font-medium text-gray-900">Fournisseur</label>
                    <select id="id_fourn" name="id_fourn" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                        <option value="" disabled selected>Sélectionner un fournisseur</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id_fourn']; ?>"><?php echo htmlspecialchars($supplier['nom_fourn']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="qte_recue" class="block mb-2 text-sm font-medium text-gray-900">Quantité Reçue</label>
                    <input type="number" id="qte_recue" name="qte_recue" min="1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
            </div>
            <button type="submit" class="mt-6 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Enregistrer l'entrée</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
