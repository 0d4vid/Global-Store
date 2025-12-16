<?php
// index.php - Login Page

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    $project_folder = basename(__DIR__);
    $base_url = "/" . $project_folder;
    $role = $_SESSION['role'] ?? '';
    switch ($role) {
        case 'admin':
            header("Location: {$base_url}/admin/dashboard.php");
            break;
        case 'vendeur':
            header("Location: {$base_url}/vendeur/caisse.php");
            break;
        case 'stock':
            header("Location: {$base_url}/stock/inventaire.php");
            break;
        default:
            // If role is unknown, just go to a safe page or log out
            header("Location: {$base_url}/logout.php");
            break;
    }
    exit();
}

require_once __DIR__ . '/config/db.php';

$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE mail_user = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['prenom_user'] . ' ' . $user['nom_user'];

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

// We don't want to show the nav bar on the login page.
// So we include a custom header or parts of it.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Store - Connexion</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-100">
    <div class="flex items-center justify-center min-h-screen">
        <div class="w-full max-w-md p-8 space-y-6 bg-white rounded-xl shadow-lg">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-gray-800">Global Store</h1>
                <p class="text-gray-500">Veuillez vous connecter pour continuer</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="p-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">
                    <span class="font-medium">Erreur:</span> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form class="space-y-6" action="index.php" method="POST">
                <div>
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-700">Adresse e-mail</label>
                    <input type="email" name="email" id="email" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="nom@exemple.com" required>
                </div>
                <div>
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-700">Mot de passe</label>
                    <input type="password" name="password" id="password" placeholder="••••••••" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                
                <button type="submit" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</body>
</html>
