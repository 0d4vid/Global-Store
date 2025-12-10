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
    echo '<div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-100" role="alert">Erreur de base de données: ' . $e->getMessage() . '</div>';
}

// Prepare data for Chart.js
$top_products_labels = json_encode(array_column($top_products, 'nom'));
$top_products_data = json_encode(array_column($top_products, 'total_vendu'));

$top_clients_labels = json_encode(array_column($top_clients, 'nom_client'));
$top_clients_data = json_encode(array_column($top_clients, 'nombre_achats'));

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800">Tableau de Bord Administrateur</h1>
    <p class="text-gray-600 mb-8">Bienvenue, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Chiffre d'Affaires Total</h3>
            <p class="mt-2 text-3xl font-bold text-indigo-600"><?php echo format_price($total_revenue ?? 0); ?></p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Ventes du Jour</h3>
            <p class="mt-2 text-3xl font-bold text-indigo-600">TODO</p>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900">Nouveaux Clients</h3>
            <p class="mt-2 text-3xl font-bold text-indigo-600">TODO</p>
        </div>
    </div>
    
    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Top 5 Produits Vendus</h3>
            <canvas id="topProductsChart"></canvas>
        </div>
        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Top 5 Clients Fidèles</h3>
            <canvas id="topClientsChart"></canvas>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Top Products Chart
    var ctxProducts = document.getElementById('topProductsChart').getContext('2d');
    new Chart(ctxProducts, {
        type: 'bar',
        data: {
            labels: <?php echo $top_products_labels; ?>,
            datasets: [{
                label: 'Total Vendu',
                data: <?php echo $top_products_data; ?>,
                backgroundColor: 'rgba(79, 70, 229, 0.8)',
                borderColor: 'rgba(79, 70, 229, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            legend: {
                display: false
            }
        }
    });

    // Top Clients Chart
    var ctxClients = document.getElementById('topClientsChart').getContext('2d');
    new Chart(ctxClients, {
        type: 'bar',
        data: {
            labels: <?php echo $top_clients_labels; ?>,
            datasets: [{
                label: 'Nombre d\'achats',
                data: <?php echo $top_clients_data; ?>,
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgba(34, 197, 94, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            legend: {
                display: false
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
