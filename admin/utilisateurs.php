<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'

// Handle Add User Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $nom = $_POST['nom_user'];
    $prenom = $_POST['prenom_user'];
    $mail = $_POST['mail_user'];
    $password = $_POST['mot_de_passe'];
    $role = $_POST['role'];

    if (empty($nom) || empty($prenom) || empty($mail) || empty($password) || empty($role)) {
        $feedback_message = "Veuillez remplir tous les champs.";
        $feedback_type = 'error';
    } else {
        try {
            // Hash the password using the standard BCRYPT algorithm
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("CALL ajouter_utilisateur(?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $mail, $hashed_password, $role]);
            
            $feedback_message = "Utilisateur ajouté avec succès !";
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

// Fetch list of users
try {
    $stmt = $pdo->prepare("CALL lister_utilisateurs()");
    $stmt->execute();
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $feedback_message = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
    $feedback_type = 'error';
}

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Gestion des Utilisateurs</h1>
    <p class="text-gray-600 mb-8">Créer, modifier et gérer les comptes des employés.</p>

    <?php if ($feedback_message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="bg-white p-8 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-semibold text-gray-700 mb-6">Ajouter un utilisateur</h2>
        <form action="utilisateurs.php" method="POST">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="nom_user" class="block mb-2 text-sm font-medium text-gray-900">Nom</label>
                    <input type="text" id="nom_user" name="nom_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="prenom_user" class="block mb-2 text-sm font-medium text-gray-900">Prénom</label>
                    <input type="text" id="prenom_user" name="prenom_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="mail_user" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input type="email" id="mail_user" name="mail_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="mot_de_passe" class="block mb-2 text-sm font-medium text-gray-900">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div>
                    <label for="role" class="block mb-2 text-sm font-medium text-gray-900">Rôle</label>
                    <select id="role" name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="admin">Admin</option>
                        <option value="vendeur" selected>Vendeur</option>
                        <option value="stock">Stock</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user" class="mt-6 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Ajouter l'utilisateur</button>
        </form>
    </div>

    <!-- Users List Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <h2 class="text-2xl font-semibold text-gray-700 p-6 bg-white">Liste des Utilisateurs</h2>
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Nom</th>
                    <th scope="col" class="px-6 py-3">Prénom</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Rôle</th>
                    <th scope="col" class="px-6 py-3">
                        <span class="sr-only">Actions</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Aucun utilisateur trouvé.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                                <?php echo htmlspecialchars($user['id_user']); ?>
                            </th>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['nom_user']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['prenom_user']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($user['mail_user']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                    switch($user['role']) {
                                        case 'admin': echo 'bg-red-100 text-red-800'; break;
                                        case 'vendeur': echo 'bg-green-100 text-green-800'; break;
                                        case 'stock': echo 'bg-yellow-100 text-yellow-800'; break;
                                    }
                                ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <!-- TODO: Add edit/delete actions -->
                                <a href="#" class="font-medium text-blue-600 hover:underline">Modifier</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
