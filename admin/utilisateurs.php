<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$edit_user = null;

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    // Prevent admin from deleting themselves
    if ($_GET['delete_id'] == $_SESSION['user_id']) {
        $feedback_message = "Erreur : Vous ne pouvez pas supprimer votre propre compte.";
        $feedback_type = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("CALL supprimer_utilisateur(?)");
            $stmt->execute([$_GET['delete_id']]);
            $feedback_message = "Utilisateur supprimé avec succès !";
            $feedback_type = 'success';
        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de la suppression de l'utilisateur. Il est peut-être lié à des ventes ou des sessions.";
            $feedback_type = 'error';
        }
    }
}

// Handle Add/Update Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_POST['id_user'] ?? null;
    $nom = $_POST['nom_user'];
    $prenom = $_POST['prenom_user'];
    $mail = $_POST['mail_user'];
    $password = $_POST['mot_de_passe'];
    $role = $_POST['role'];

    try {
        if ($id_user) { // Update
            $hashed_password = null;
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            }
            $stmt = $pdo->prepare("CALL modifier_utilisateur(?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_user, $nom, $prenom, $mail, $role, $hashed_password]);
            $feedback_message = "Utilisateur mis à jour avec succès !";
        } else { // Add
            if (empty($password)) {
                throw new Exception("Le mot de passe est obligatoire pour un nouvel utilisateur.");
            }
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("CALL ajouter_utilisateur(?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $mail, $hashed_password, $role]);
            $feedback_message = "Utilisateur ajouté avec succès !";
        }
        $feedback_type = 'success';
    } catch (Exception $e) {
        $feedback_message = "Erreur : " . $e->getMessage();
        $feedback_type = 'error';
    }
}

// Handle Edit Action (Fetch user to edit)
if (isset($_GET['edit_id'])) {
    try {
        $stmt = $pdo->prepare("CALL lister_utilisateur_par_id(?)");
        $stmt->execute([$_GET['edit_id']]);
        $edit_user = $stmt->fetch();
    } catch (PDOException $e) {
        $feedback_message = "Erreur lors de la récupération de l'utilisateur.";
        $feedback_type = 'error';
    }
}

// Fetch list of all users
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

    <!-- Add/Edit User Form -->
    <div class="bg-white p-8 rounded-lg shadow-md mb-8">
        <h2 class="text-2xl font-semibold text-gray-700 mb-6"><?php echo $edit_user ? "Modifier l'utilisateur" : "Ajouter l'utilisateur"; ?></h2>
        <form action="utilisateurs.php" method="POST">
            <input type="hidden" name="id_user" value="<?php echo $edit_user['id_user'] ?? ''; ?>">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label for="nom_user" class="block mb-2 text-sm font-medium text-gray-900">Nom</label>
                    <input type="text" id="nom_user" name="nom_user" value="<?php echo htmlspecialchars($edit_user['nom_user'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="prenom_user" class="block mb-2 text-sm font-medium text-gray-900">Prénom</label>
                    <input type="text" id="prenom_user" name="prenom_user" value="<?php echo htmlspecialchars($edit_user['prenom_user'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="mail_user" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input type="email" id="mail_user" name="mail_user" value="<?php echo htmlspecialchars($edit_user['mail_user'] ?? ''); ?>" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="mot_de_passe" class="block mb-2 text-sm font-medium text-gray-900">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" <?php echo !$edit_user ? 'required' : ''; ?>>
                    <?php if ($edit_user): ?>
                        <p class="mt-1 text-xs text-gray-500">Laissez vide pour ne pas changer le mot de passe.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="role" class="block mb-2 text-sm font-medium text-gray-900">Rôle</label>
                    <select id="role" name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="admin" <?php echo (isset($edit_user) && $edit_user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="vendeur" <?php echo (isset($edit_user) && $edit_user['role'] == 'vendeur') ? 'selected' : ''; ?>>Vendeur</option>
                        <option value="stock" <?php echo (isset($edit_user) && $edit_user['role'] == 'stock') ? 'selected' : ''; ?>>Stock</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center mt-6">
                <button type="submit" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    <?php echo $edit_user ? 'Mettre à jour' : 'Ajouter utilisateur'; ?>
                </button>
                <?php if ($edit_user): ?>
                    <a href="utilisateurs.php" class="ml-4 text-gray-500 bg-white hover:bg-gray-100 border border-gray-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Users List Table -->
    <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Nom</th>
                    <th scope="col" class="px-6 py-3">Email</th>
                    <th scope="col" class="px-6 py-3">Rôle</th>
                    <th scope="col" class="px-6 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="bg-white border-b hover:bg-gray-50">
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['id_user']); ?></td>
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap"><?php echo htmlspecialchars($user['prenom_user'] . ' ' . $user['nom_user']); ?></td>
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
                            <a href="utilisateurs.php?edit_id=<?php echo $user['id_user']; ?>" class="font-medium text-blue-600 hover:underline mr-4">Modifier</a>
                            <?php if ($user['id_user'] != $_SESSION['user_id']): // Prevent self-delete button from showing ?>
                                <a href="utilisateurs.php?delete_id=<?php echo $user['id_user']; ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');" class="font-medium text-red-600 hover:underline">Supprimer</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
