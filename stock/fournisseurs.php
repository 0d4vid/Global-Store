<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['stock', 'admin']);
require_once __DIR__ . '/../config/db.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$edit_supplier = null;

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM fournisseur WHERE id_fourn = ?");
        $stmt->execute([$_GET['delete_id']]);
        $feedback_message = "Fournisseur supprimé avec succès !";
        $feedback_type = 'success';
    } catch (PDOException $e) {
        $feedback_message = "Erreur : Impossible de supprimer le fournisseur. Il est peut-être lié à un réapprovisionnement.";
        $feedback_type = 'error';
    }
}

// Handle Add/Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_fourn = $_POST['id_fourn'] ?? null;
    $nom_fourn = $_POST['nom_fourn'];
    $contact = $_POST['contact'];
    $adresse = $_POST['adresse'];

    if (empty($nom_fourn)) {
        $feedback_message = "Le nom du fournisseur ne peut pas être vide.";
        $feedback_type = 'error';
    } else {
        try {
            if ($id_fourn) { // Update
                $stmt = $pdo->prepare("UPDATE fournisseur SET nom_fourn = ?, contact = ?, adresse = ? WHERE id_fourn = ?");
                $stmt->execute([$nom_fourn, $contact, $adresse, $id_fourn]);
                $feedback_message = "Fournisseur modifié avec succès !";
            } else { // Add
                $stmt = $pdo->prepare("INSERT INTO fournisseur(nom_fourn, contact, adresse) VALUES (?, ?, ?)");
                $stmt->execute([$nom_fourn, $contact, $adresse]);
                $feedback_message = "Fournisseur ajouté avec succès !";
            }
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Handle Edit Action (Fetch supplier to edit)
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM fournisseur WHERE id_fourn = ?");
        $stmt->execute([$_GET['edit_id']]);
        $edit_supplier = $stmt->fetch();
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la récupération du fournisseur.";
        $feedback_type = 'error';
    }
}

// Fetch all suppliers for display
try {
    $stmt = $pdo->query("SELECT * FROM fournisseur ORDER BY nom_fourn");
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) {
    $suppliers = [];
    $feedback_message = "Erreur lors de la récupération des fournisseurs : " . $e->getMessage();
    $feedback_type = 'error';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Gestion des Fournisseurs</h1>
        <p class="text-gray-500">Ajouter, modifier et consulter les informations des fournisseurs.</p>
    </div>

    <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add/Edit Supplier Form -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8" id="form">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6"><?php echo $edit_supplier ? 'Modifier le fournisseur' : 'Ajouter un nouveau fournisseur'; ?></h2>
        <form action="fournisseurs.php#form" method="POST">
            <input type="hidden" name="id_fourn" value="<?php echo $edit_supplier['id_fourn'] ?? ''; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="nom_fourn" class="block mb-2 text-sm font-medium text-gray-900">Nom du fournisseur</label>
                    <input type="text" id="nom_fourn" name="nom_fourn" value="<?php echo htmlspecialchars($edit_supplier['nom_fourn'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="contact" class="block mb-2 text-sm font-medium text-gray-900">Contact (Tél/Email)</label>
                    <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($edit_supplier['contact'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
                <div>
                    <label for="adresse" class="block mb-2 text-sm font-medium text-gray-900">Adresse</label>
                    <input type="text" id="adresse" name="adresse" value="<?php echo htmlspecialchars($edit_supplier['adresse'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
            </div>
            <div class="flex items-center mt-6">
                <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <?php echo $edit_supplier ? 'Mettre à jour' : 'Ajouter le fournisseur'; ?>
                </button>
                <?php if ($edit_supplier): ?>
                    <a href="fournisseurs.php" class="ml-4 py-2.5 px-5 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-4 focus:ring-gray-200">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Suppliers List Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Liste des fournisseurs</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nom</th>
                        <th scope="col" class="px-6 py-3">Contact</th>
                        <th scope="col" class="px-6 py-3">Adresse</th>
                        <th scope="col" class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($suppliers)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Aucun fournisseur trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($suppliers as $supplier): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($supplier['nom_fourn']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($supplier['contact']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($supplier['adresse']); ?></td>
                                <td class="px-6 py-4 text-right">
                                    <a href="fournisseurs.php?edit_id=<?php echo $supplier['id_fourn']; ?>#form" class="font-medium text-blue-600 hover:underline">Modifier</a>
                                    <a href="fournisseurs.php?delete_id=<?php echo $supplier['id_fourn']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?');" class="font-medium text-red-600 hover:underline ms-3">Supprimer</a>
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
