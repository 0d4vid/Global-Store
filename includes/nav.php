<?php
// includes/nav.php
if (!isset($_SESSION['role'])) {
    return; // Do not show navigation if user is not logged in
}

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Utilisateur';
$project_folder = basename(dirname(__DIR__));
$base_url = "/" . $project_folder;
$current_page_url = $_SERVER['REQUEST_URI'];

$nav_links = [];

$dashboard_url = "{$base_url}/admin/dashboard.php";

if ($role === 'admin') {
    $nav_links = [
        "Dashboard" => $dashboard_url,
        "Utilisateurs" => "{$base_url}/admin/utilisateurs.php",
        "Gérer le stock" => "{$base_url}/stock/inventaire.php",
        "Point de vente" => "{$base_url}/vendeur/caisse.php",
    ];
} elseif ($role === 'vendeur') {
    $dashboard_url = "{$base_url}/vendeur/caisse.php";
    $nav_links = [
        "Caisse" => "{$base_url}/vendeur/caisse.php",
        "Clients" => "{$base_url}/vendeur/clients.php",
        "Historique" => "{$base_url}/vendeur/historique.php",
        "Session" => "{$base_url}/vendeur/session.php",
    ];
} elseif ($role === 'stock') {
    $dashboard_url = "{$base_url}/stock/inventaire.php";
    $nav_links = [
        "Inventaire" => "{$base_url}/stock/inventaire.php",
        "Produits" => "{$base_url}/stock/produits.php",
        "Catégories" => "{$base_url}/stock/categories.php",
        "Réapprovisionnement" => "{$base_url}/stock/reappro.php",
        "Fournisseurs" => "{$base_url}/stock/fournisseurs.php",
    ];
}
?>

<nav class="bg-white border-b border-gray-200 shadow-sm fixed top-0 left-0 w-full z-40">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
    <a href="<?php echo $dashboard_url; ?>" class="flex items-center space-x-3 rtl:space-x-reverse">
        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
        <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-800">GlobalStore</span>
    </a>
    <div class="flex items-center md:order-2 space-x-3 md:space-x-0 rtl:space-x-reverse">
        <button type="button" class="flex text-sm bg-gray-100 rounded-full md:me-0 focus:ring-4 focus:ring-gray-300" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
          <span class="sr-only">Open user menu</span>
          <div class="relative w-10 h-10 overflow-hidden bg-gray-200 rounded-full">
            <svg class="absolute w-12 h-12 text-gray-400 -left-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path></svg>
          </div>
        </button>
        <!-- Dropdown menu -->
        <div class="z-50 hidden my-4 text-base list-none bg-white divide-y divide-gray-100 rounded-lg shadow-lg" id="user-dropdown">
          <div class="px-4 py-3">
            <span class="block text-sm text-gray-900 font-semibold"><?php echo htmlspecialchars($name); ?></span>
            <span class="block text-sm  text-gray-500 truncate"><?php echo htmlspecialchars(ucfirst($role)); ?></span>
          </div>
          <ul class="py-2" aria-labelledby="user-menu-button">
            <li>
              <a href="<?php echo $base_url; ?>/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Déconnexion</a>
            </li>
          </ul>
        </div>
        <button data-collapse-toggle="navbar-user" type="button" class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-500 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="navbar-user" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15"/>
          </svg>
      </button>
    </div>
    <div class="items-center justify-between hidden w-full md:flex md:w-auto md:order-1" id="navbar-user">
      <ul class="flex flex-col font-medium p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:space-x-8 rtl:space-x-reverse md:flex-row md:mt-0 md:border-0 md:bg-white">
        <?php foreach ($nav_links as $title => $url): ?>
            <?php
                // Check if the current URL matches the nav link URL
                $is_active = (strpos($current_page_url, $url) !== false);
                 $active_class = $is_active ? 'text-white bg-blue-700 md:bg-transparent md:text-blue-700' : 'text-gray-900';
            ?>
            <li>
              <a href="<?php echo $url; ?>" class="block py-2 px-3 rounded md:p-0 <?php echo $active_class; ?>" <?php if($is_active) echo 'aria-current="page"'; ?>>
                <?php echo $title; ?>
              </a>
            </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="h-16"></div> <!-- Spacer for fixed navbar -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>

