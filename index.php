<?php
// index.php - Login Page

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/db.php';

$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    // Trim whitespace from the password, which is a common cause for verification failure.
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs.';
    } else {
        try {
            // Find user by email
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE mail_user = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Verify password
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['prenom_user'] . ' ' . $user['nom_user'];

                // Redirect based on role
                $project_folder = basename(__DIR__);
                $base_url = "/" . $project_folder;

                switch ($user['role']) {
                    case 'admin':
                        header("Location: {$base_url}/admin/dashboard.php");
                        exit();
                    case 'vendeur':
                        header("Location: {$base_url}/vendeur/caisse.php");
                        exit();
                    case 'stock':
                        header("Location: {$base_url}/stock/inventaire.php");
                        exit();
                }
            } else {
                $error_message = 'Adresse e-mail ou mot de passe incorrect.';
            }
        } catch (PDOException $e) {
            $error_message = "Erreur de connexion : " . $e->getMessage();
        }
    }
}

// Include header
require_once __DIR__ . '/includes/header.php';
?>

<div class="flex items-center justify-center min-h-screen bg-gray-50">
    <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-lg shadow-md">
        <h2 class="text-2xl font-bold text-center text-gray-900">Global Store - Connexion</h2>
        <?php if (!empty($error_message)): ?>
            <p class="text-center text-red-500 bg-red-100 p-3 rounded-md">
                <?php echo htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>
        <form class="mt-8 space-y-6" action="index.php" method="POST">
            <input type="hidden" name="remember" value="true">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email-address" class="sr-only">Adresse e-mail</label>
                    <input id="email-address" name="email" type="email" autocomplete="email" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Adresse e-mail">
                </div>
                <div>
                    <label for="password" class="sr-only">Mot de passe</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" placeholder="Mot de passe">
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Se connecter
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
