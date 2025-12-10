<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur']);
require_once __DIR__ . '/../config/db.php';

// Check for active session
$active_session = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM session_caisse WHERE id_user = ? AND date_fin IS NULL LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $active_session = $stmt->fetch();
} catch (PDOException $e) { /* ignore */ }

// Final Sale Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    $panier = $_SESSION['panier'] ?? [];
    $id_client = $_POST['id_client'] === '0' ? null : $_POST['id_client'];
    $id_user = $_SESSION['user_id'];
    $num_facture = 'FACT-' . date('Ymd-His'); // Generate a unique invoice number

    if (empty($panier)) {
        $feedback_message = "Le panier est vide.";
        $feedback_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create Sale
            $stmt = $pdo->prepare("CALL creer_vente(?, ?, ?)");
            $stmt->execute([$num_facture, $id_client, $id_user]);
            $id_vente = $stmt->fetchColumn();
            $stmt->closeCursor();

            // 2. Add Sale Lines
            $total_vente = 0;
            $stmt_ligne = $pdo->prepare("CALL ajouter_ligne_vente(?, ?, ?, ?)");
            foreach ($panier as $item) {
                $stmt_ligne->execute([$id_vente, $item['id_prod'], $item['quantite'], $item['prix_unitaire']]);
                $total_vente += $item['quantite'] * $item['prix_unitaire'];
            }
            
            // 3. Add Payment
            // Simplified: assumes full payment in one mode.
            $mode_paiement = $_POST['mode_paiement'];
            $stmt_paiement = $pdo->prepare("CALL ajouter_paiement(?, ?, ?)");
            $stmt_paiement->execute([$id_vente, $total_vente, $mode_paiement]);

            $pdo->commit();
            
            // 4. Clear cart and give feedback
            unset($_SESSION['panier']);
            $feedback_message = "Vente #$num_facture enregistrée avec succès !";
            $feedback_type = 'success';

        } catch (PDOException $e) {
            $pdo->rollBack();
            $feedback_message = "Erreur lors de la vente : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}


// Data for the page
try {
    // Call lister_produits to get products with image_url
    $stmt_products = $pdo->prepare("CALL lister_produits()");
    $stmt_products->execute();
    $products = $stmt_products->fetchAll();
    $stmt_products->closeCursor();

    $clients = $pdo->query("SELECT id_client, nom_client FROM client ORDER BY nom_client")->fetchAll();
} catch (PDOException $e) {
    $products = [];
    $clients = [];
    $feedback_message = "Erreur de chargement des données de la page.";
    $feedback_type = 'error';
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-6">

    <?php if (!$active_session): ?>
        <div class="p-4 mb-6 text-lg text-red-800 rounded-lg bg-red-100" role="alert">
            <span class="font-bold">Aucune session de caisse active.</span> Veuillez <a href="session.php" class="font-medium underline">ouvrir une session</a> pour pouvoir enregistrer des ventes.
        </div>
    <?php endif; ?>

    <?php if (isset($feedback_message)): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column: Products -->
        <div class="lg:col-span-2">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Produits Disponibles</h2>
                <input type="text" id="productSearch" class="w-full p-2 border border-gray-300 rounded-md mb-4" placeholder="Rechercher un produit...">
                <div id="product-list" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-[60vh] overflow-y-auto">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card border rounded-lg p-4 flex flex-col justify-between items-center hover:shadow-lg transition-shadow cursor-pointer" data-name="<?php echo htmlspecialchars(strtolower($product['nom'])); ?>">
                            <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="h-20 w-20 object-cover rounded-full mb-2">
                            <?php else: ?>
                                <div class="h-20 w-20 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-xs text-center mb-2">No Image</div>
                            <?php endif; ?>
                            <div>
                                <h3 class="font-bold text-center product-name"><?php echo htmlspecialchars($product['nom']); ?></h3>
                                <p class="text-sm text-gray-500 text-center">Stock: <?php echo $product['stock_actuel']; ?></p>
                                <p class="text-lg font-semibold text-indigo-600 text-center"><?php echo format_price($product['prix_vente']); ?></p>
                            </div>
                            <button <?php echo !$active_session || $product['stock_actuel'] <= 0 ? 'disabled' : ''; ?> onclick="addToCart(<?php echo $product['id_prod']; ?>)" class="mt-2 w-full bg-blue-600 text-white py-1 px-3 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                                Ajouter
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Cart -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Panier</h2>
            <div class="mb-4">
                <label for="id_client" class="block text-sm font-medium text-gray-700">Client</label>
                <select id="id_client" name="id_client" form="sale-form" class="mt-1 block w-full p-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="0">Client au comptant</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id_client']; ?>"><?php echo htmlspecialchars($client['nom_client']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="cart-items" class="mb-4 max-h-[40vh] overflow-y-auto">
                <!-- Cart items will be injected here by JavaScript -->
                <p class="text-gray-500 text-center">Le panier est vide.</p>
            </div>
            <div class="border-t pt-4">
                <div class="flex justify-between items-center font-bold text-xl">
                    <span>Total</span>
                    <span id="cart-total">0 FCFA</span>
                </div>
                <button <?php echo !$active_session ? 'disabled' : ''; ?> type="button" id="validate-sale-btn" data-modal-target="payment-modal" data-modal-toggle="payment-modal" class="mt-4 w-full bg-green-600 text-white py-2 rounded-md hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed">
                    Valider la Vente
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
    <div class="relative p-4 w-full max-w-md h-full md:h-auto">
        <div class="relative p-4 text-center bg-white rounded-lg shadow sm:p-5">
            <h3 class="mb-4 text-xl font-medium text-gray-900">Finaliser la Vente</h3>
            <form id="sale-form" action="caisse.php" method="POST">
                <input type="hidden" name="process_sale" value="1">
                <div class="mb-4">
                    <label for="mode_paiement" class="block text-sm font-medium text-gray-700">Mode de paiement</label>
                    <select id="mode_paiement" name="mode_paiement" class="mt-1 block w-full p-2 border border-gray-300 bg-white rounded-md shadow-sm">
                        <option value="especes">Espèces</option>
                        <option value="carte">Carte</option>
                        <option value="mobile">Mobile</option>
                    </select>
                </div>
                <p class="font-bold text-2xl" id="modal-total">Total: 0 FCFA</p>
                <div class="flex justify-center items-center space-x-4 mt-6">
                    <button data-modal-hide="payment-modal" type="button" class="py-2 px-3 text-sm font-medium text-gray-500 bg-white rounded-lg border border-gray-200 hover:bg-gray-100">Annuler</button>
                    <button type="submit" class="py-2 px-3 text-sm font-medium text-center text-white bg-green-600 rounded-lg hover:bg-green-700">Confirmer la Vente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initial cart load
    updateCartDisplay();

    // Product search
    document.getElementById('productSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            if (card.dataset.name.includes(filter)) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });

    // Hide feedback alert after 5s
    const feedbackAlert = document.getElementById('feedback-alert');
    if(feedbackAlert) {
        setTimeout(() => { feedbackAlert.style.display = 'none'; }, 5000);
    }
});

function handleCartAction(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }

    fetch('../ajax_cart_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.error) {
            alert('Erreur: ' + result.error);
        }
        updateCartDisplay(result.cart, result.total);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Une erreur de communication est survenue.');
    });
}

function addToCart(id_prod) {
    handleCartAction('add', { id_prod });
}

function updateQuantity(id_prod, quantite) {
    handleCartAction('update', { id_prod, quantite });
}

function removeFromCart(id_prod) {
    handleCartAction('remove', { id_prod });
}

function updateCartDisplay(cart, total) {
    if (cart === undefined || total === undefined) {
        // If no data is passed, fetch it
        handleCartAction('get');
        return;
    }

    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalEl = document.getElementById('cart-total');
    const modalTotalEl = document.getElementById('modal-total');
    const validateBtn = document.getElementById('validate-sale-btn');

    const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF' });

    cartItemsContainer.innerHTML = ''; // Clear current cart

    if (cart.length === 0) {
        cartItemsContainer.innerHTML = '<p class="text-gray-500 text-center">Le panier est vide.</p>';
        validateBtn.disabled = true;
    } else {
        validateBtn.disabled = false;
        cart.forEach(item => {
            cartItemsContainer.innerHTML += `
                <div class="flex justify-between items-center py-2 border-b">
                    <div>
                        <p class="font-semibold">${item.nom}</p>
                        <p class="text-sm text-gray-500">${formatter.format(item.prix_unitaire)}</p>
                    </div>
                    <div class="flex items-center">
                        <input type="number" value="${item.quantite}" onchange="updateQuantity(${item.id_prod}, this.value)" class="w-16 text-center border-gray-300 rounded-md" min="0" max="${item.stock_max}">
                        <button onclick="removeFromCart(${item.id_prod})" class="ml-2 text-red-500 hover:text-red-700">&times;</button>
                    </div>
                </div>
            `;
        });
    }
    
    cartTotalEl.textContent = formatter.format(total);
    modalTotalEl.textContent = 'Total: ' + formatter.format(total);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
