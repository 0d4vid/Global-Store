<?php
// TEMPORARY SCRIPT - create_user.php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$feedback_message = '';
$feedback_type = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom_user'] ?? '';
    $prenom = $_POST['prenom_user'] ?? '';
    $mail = $_POST['mail_user'] ?? '';
    $password = $_POST['mot_de_passe'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($nom) || empty($prenom) || empty($mail) || empty($password) || empty($role)) {
        $feedback_message = "Please fill in all fields.";
    } else {
        try {
            // Hash the password using the standard, secure BCRYPT algorithm.
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("CALL ajouter_utilisateur(?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $mail, $hashed_password, $role]);
            
            $feedback_type = 'success';
            $feedback_message = "<strong>SUCCESS!</strong> User '{$mail}' was created.<br>" .
                                "<strong>Password you entered:</strong> " . htmlspecialchars($password) . "<br>" .
                                "<strong>Hashed password stored in DB:</strong> " . htmlspecialchars($hashed_password);

        } catch (PDOException $e) {
            $feedback_message = "Error creating user: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Temporary User</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto p-6 max-w-xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Temporary User Creation</h1>
    <p class="text-gray-600 mb-8">Use this page to create a new user. The password will be automatically hashed using the standard PHP `password_hash()` function before being saved.</p>

    <?php if ($feedback_message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo $feedback_message; // Using echo without htmlspecialchars to render the <strong> tags ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-md">
        <form action="create_user.php" method="POST">
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label for="nom_user" class="block mb-2 text-sm font-medium text-gray-900">Last Name</label>
                    <input type="text" id="nom_user" name="nom_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="prenom_user" class="block mb-2 text-sm font-medium text-gray-900">First Name</label>
                    <input type="text" id="prenom_user" name="prenom_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="mail_user" class="block mb-2 text-sm font-medium text-gray-900">Email</label>
                    <input type="email" id="mail_user" name="mail_user" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="mot_de_passe" class="block mb-2 text-sm font-medium text-gray-900">Password</label>
                    <input type="text" id="mot_de_passe" name="mot_de_passe" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5" required>
                </div>
                <div>
                    <label for="role" class="block mb-2 text-sm font-medium text-gray-900">Role</label>
                    <select id="role" name="role" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg w-full p-2.5">
                        <option value="admin">Admin</option>
                        <option value="vendeur" selected>Vendeur</option>
                        <option value="stock">Stock</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="add_user" class="mt-6 text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Create User</button>
        </form>
    </div>
</div>
</body>
</html>
