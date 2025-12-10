<?php
// includes/nav.php
if (!isset($_SESSION['role'])) {
    return; // Do not show navigation if user is not logged in
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Utilisateur';
$project_folder = basename(dirname(__DIR__));
$base_url = "/" . $project_folder;

$nav_links = [];

if ($role === 'admin') {
    $nav_links = [
        "Dashboard" => "{$base_url}/admin/dashboard.php",
        "Utilisateurs" => "{$base_url}/admin/utilisateurs.php",
    ];
} elseif ($role === 'vendeur') {
    $nav_links = [
        "Caisse" => "{$base_url}/vendeur/caisse.php",
        "Clients" => "{$base_url}/vendeur/clients.php",
        "Historique" => "{$base_url}/vendeur/historique.php",
        "Session" => "{$base_url}/vendeur/session.php",
    ];
} elseif ($role === 'stock') {
    $nav_links = [
        "Inventaire" => "{$base_url}/stock/inventaire.php",
        "Produits" => "{$base_url}/stock/produits.php",
        "Catégories" => "{$base_url}/stock/categories.php",
        "Réapprovisionnement" => "{$base_url}/stock/reappro.php",
        "Fournisseurs" => "{$base_url}/stock/fournisseurs.php",
    ];
}

?>

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="flex-shrink-0 flex items-center">
                    <span class="font-bold text-xl text-indigo-600">Global Store</span>
                </div>
                <div class="hidden sm:-my-px sm:ml-6 sm:flex sm:space-x-8">
                    <?php foreach ($nav_links as $title => $url): ?>
                        <a href="<?php echo $url; ?>" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">
                            <?php echo $title; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:items-center">
                <span class="text-gray-500 mr-4">Bonjour, <?php echo htmlspecialchars($name); ?></span>
                <a href="<?php echo $base_url; ?>/logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                    Déconnexion
                </a>
            </div>
        </div>
    </div>
</nav>
