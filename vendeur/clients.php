<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur', 'admin']);
require_once __DIR__ . '/../config/db.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'

// Handle Add Client Form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_client'])) {
    $nom_client = $_POST['nom_client'];
    $telephone = $_POST['telephone'];

    if (empty($nom_client)) {
        $feedback_message = "Le nom du client est obligatoire.";
        $feedback_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("CALL ajouter_client(?, ?)");
            $stmt->execute([$nom_client, $telephone]);
            $feedback_message = "Client ajouté avec succès !";
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de l'ajout du client : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Fetch list of clients
try {
    $stmt = $pdo->prepare("CALL lister_clients()");
    $stmt->execute();
    $clients = $stmt->fetchAll();
} catch (PDOException $e) {
    $clients = [];
    $feedback_message = "Erreur lors de la récupération des clients : " . $e->getMessage();
    $feedback_type = 'error';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Gestion des Clients</h1>
        <p class="text-gray-500">Ajouter de nouveaux clients et consulter la liste.</p>
    </div>

    <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add Client Form -->
    <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Ajouter un nouveau client</h2>
        <form action="clients.php" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label for="nom_client" class="block mb-2 text-sm font-medium text-gray-900">Nom complet</label>
                    <input type="text" id="nom_client" name="nom_client" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="telephone" class="block mb-2 text-sm font-medium text-gray-900">Téléphone</label>
                    <input type="text" id="telephone" name="telephone" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                </div>
            </div>
            <button type="submit" name="add_client" class="mt-6 text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                Ajouter le client
            </button>
        </form>
    </div>

    <!-- Clients List Table -->
    <div class="bg-white p-6 rounded-xl shadow-lg">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Liste des clients enregistrés</h2>
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left rtl:text-right text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">ID</th>
                        <th scope="col" class="px-6 py-3">Nom du Client</th>
                        <th scope="col" class="px-6 py-3">Téléphone</th>
                        <th scope="col" class="px-6 py-3">Points de fidélité</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clients)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Aucun client trouvé.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clients as $client): ?>
                            <tr class="bg-white border-b hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($client['id_client']); ?></td>
                                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                    <?php echo htmlspecialchars($client['nom_client']); ?>
                                </th>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($client['telephone']); ?></td>
                                <td class="px-6 py-4 font-semibold text-blue-600"><?php echo htmlspecialchars($client['point_fidelite']); ?></td>
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
