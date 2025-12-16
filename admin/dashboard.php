<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['admin']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

// Fetching dashboard data
try {
    // Total Revenue
    $stmt_ca = $pdo->prepare("CALL chiffre_affaires_total()");
    $stmt_ca->execute();
    $total_revenue = $stmt_ca->fetchColumn();
    $stmt_ca->closeCursor();

    // Top 5 Products
    $stmt_prod = $pdo->prepare("CALL produits_plus_vendus()");
    $stmt_prod->execute();
    $top_products = $stmt_prod->fetchAll();
    $stmt_prod->closeCursor();

    // Top 5 Clients
    $stmt_clients = $pdo->prepare("CALL clients_fideles()");
    $stmt_clients->execute();
    $top_clients = $stmt_clients->fetchAll();
    $stmt_clients->closeCursor();

} catch (PDOException $e) {
    // Handle potential errors
    $total_revenue = 0;
    $top_products = [];
    $top_clients = [];
    echo '<div class="p-4 m-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">Erreur de base de données: ' . $e->getMessage() . '</div>';
}

// Prepare data for Chart.js
$top_products_labels = json_encode(array_column($top_products, 'nom'));
$top_products_data = json_encode(array_column($top_products, 'total_vendu'));

$top_clients_labels = json_encode(array_column($top_clients, 'nom_client'));
$top_clients_data = json_encode(array_column($top_clients, 'nombre_achats'));

?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Tableau de Bord</h1>
        <p class="text-gray-500">Aperçu des performances du magasin, bienvenue <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Revenue Card -->
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-6">
            <div class="bg-blue-100 p-4 rounded-full">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v.01"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-500">Chiffre d'Affaires Total</h3>
                <p class="mt-1 text-3xl font-bold text-gray-800"><?php echo format_price($total_revenue ?? 0); ?></p>
            </div>
        </div>
        <!-- Daily Sales Card -->
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-6">
            <div class="bg-green-100 p-4 rounded-full">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-500">Ventes du Jour</h3>
                <p class="mt-1 text-3xl font-bold text-gray-800">TODO</p>
            </div>
        </div>
        <!-- New Clients Card -->
        <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-6">
            <div class="bg-yellow-100 p-4 rounded-full">
                <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
            </div>
            <div>
                <h3 class="text-lg font-medium text-gray-500">Nouveaux Clients</h3>
                <p class="mt-1 text-3xl font-bold text-gray-800">TODO</p>
            </div>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <div class="lg:col-span-3 bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Top 5 Produits Vendus</h3>
            <canvas id="topProductsChart"></canvas>
        </div>
        <div class="lg:col-span-2 bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Top 5 Clients Fidèles</h3>
            <canvas id="topClientsChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Chart.js Global Settings
    Chart.defaults.global.defaultFontFamily = "Inter, -apple-system, system-ui, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'";
    Chart.defaults.global.defaultFontColor = '#6B7280';

    // Top Products Chart
    if (document.getElementById('topProductsChart')) {
        var ctxProducts = document.getElementById('topProductsChart').getContext('2d');
        new Chart(ctxProducts, {
            type: 'bar',
            data: {
                labels: <?php echo $top_products_labels; ?>,
                datasets: [{
                    label: 'Total Vendu (FCFA)',
                    data: <?php echo $top_products_data; ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    yAxes: [{ ticks: { beginAtZero: true, callback: function(value) { return value + ' FCFA'; } } }],
                    xAxes: [{ gridLines: { display: false } }]
                }
            }
        });
    }

    // Top Clients Chart
    if (document.getElementById('topClientsChart')) {
        var ctxClients = document.getElementById('topClientsChart').getContext('2d');
        new Chart(ctxClients, {
            type: 'horizontalBar',
            data: {
                labels: <?php echo $top_clients_labels; ?>,
                datasets: [{
                    label: 'Nombre d\'achats',
                    data: <?php echo $top_clients_data; ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    borderRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                legend: { display: false },
                scales: {
                    xAxes: [{ ticks: { beginAtZero: true, stepSize: 1 }, gridLines: { display: false } }]
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
