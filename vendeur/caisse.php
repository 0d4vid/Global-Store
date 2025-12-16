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
    $payments = isset($_POST['payments']) && is_array($_POST['payments']) ? $_POST['payments'] : [];
    $num_facture = 'FACT-' . date('Ymd-His');

    if (empty($panier)) {
        $feedback_message = "Le panier est vide.";
        $feedback_type = 'error';
    } elseif (empty($payments)) {
        $feedback_message = "Aucun paiement n'a été fourni.";
        $feedback_type = 'error';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Create Sale and get total
            $stmt_vente = $pdo->prepare("CALL creer_vente(?, ?, ?)");
            $stmt_vente->execute([$num_facture, $id_client, $id_user]);
            $id_vente = $stmt_vente->fetchColumn();
            $stmt_vente->closeCursor();

            $total_vente = 0;
            $stmt_ligne = $pdo->prepare("CALL ajouter_ligne_vente(?, ?, ?, ?)");
            foreach ($panier as $item) {
                $stmt_ligne->execute([$id_vente, $item['id_prod'], $item['quantite'], $item['prix_unitaire']]);
                $total_vente += $item['quantite'] * $item['prix_unitaire'];
            }
            $stmt_ligne->closeCursor();

            // 2. Validate and Add Payments
            $total_paid = 0;
            foreach ($payments as $payment) {
                $total_paid += (float)$payment['amount'];
            }

            // Small tolerance for floating point issues
            if (abs($total_paid - $total_vente) > 0.01) {
                 throw new PDOException("Le montant total payé (".format_price($total_paid).") ne correspond pas au total de la vente (".format_price($total_vente).").");
            }

            $stmt_paiement = $pdo->prepare("CALL ajouter_paiement(?, ?, ?)");
            foreach ($payments as $payment) {
                if ($payment['amount'] > 0) {
                    $stmt_paiement->execute([$id_vente, (float)$payment['amount'], $payment['method']]);
                }
            }
            $stmt_paiement->closeCursor();

            $pdo->commit();
            
            // 3. Clear cart and give feedback
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

<div class="container mx-auto p-4 sm:p-6">

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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Products -->
        <div class="lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="mb-4">
                <input type="text" id="productSearch" class="w-full p-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 transition-shadow" placeholder="Rechercher un produit par son nom...">
            </div>
            <div id="product-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-h-[70vh] overflow-y-auto p-2">
                <?php foreach ($products as $product): ?>
                    <div class="product-card group border rounded-lg p-3 flex flex-col justify-between items-center bg-gray-50 hover:shadow-xl hover:scale-105 transition-all duration-300 cursor-pointer" data-name="<?php echo htmlspecialchars(strtolower($product['nom'])); ?>" onclick="addToCart(<?php echo $product['id_prod']; ?>)">
                        <div class="relative w-full h-24">
                             <?php if (!empty($product['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['nom']); ?>" class="h-full w-full object-contain rounded-md">
                            <?php else: ?>
                                <div class="h-full w-full bg-gray-200 rounded-md flex items-center justify-center text-gray-400 text-xs text-center">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="text-center mt-2">
                            <h3 class="font-semibold text-sm text-gray-800 product-name"><?php echo htmlspecialchars($product['nom']); ?></h3>
                            <p class="text-xs text-gray-500">Stock: <?php echo $product['stock_actuel']; ?></p>
                            <p class="text-md font-bold text-blue-600 mt-1"><?php echo format_price($product['prix_vente']); ?></p>
                        </div>
                        <button <?php echo !$active_session || $product['stock_actuel'] <= 0 ? 'disabled' : ''; ?> class="mt-2 w-full bg-blue-600 text-white text-sm py-2 px-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                            Ajouter
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cart and Sale -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg flex flex-col">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Vente en cours</h2>
            <div class="mb-4">
                <label for="id_client" class="block text-sm font-medium text-gray-600 mb-1">Client</label>
                <select id="id_client" name="id_client" form="sale-form" class="w-full p-2 border border-gray-300 bg-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="0">Client au comptant</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?php echo $client['id_client']; ?>"><?php echo htmlspecialchars($client['nom_client']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="cart-items" class="flex-grow mb-4 max-h-[45vh] overflow-y-auto pr-2">
                <p class="text-gray-500 text-center mt-8">Le panier est vide.</p>
            </div>
            <div class="border-t-2 border-gray-100 pt-4">
                <div class="flex justify-between items-center font-bold text-2xl text-gray-800">
                    <span>Total</span>
                    <span id="cart-total">0 FCFA</span>
                </div>
                <button <?php echo !$active_session ? 'disabled' : ''; ?> type="button" id="validate-sale-btn" data-modal-target="payment-modal" data-modal-toggle="payment-modal" class="mt-4 w-full bg-green-600 text-white py-3 rounded-lg text-lg font-bold hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                    Procéder au Paiement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="payment-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-lg max-h-full">
        <div class="relative bg-white rounded-lg shadow-xl">
            <!-- Modal header -->
            <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t">
                <h3 class="text-xl font-semibold text-gray-900">Finaliser la Vente</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="payment-modal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>
                    <span class="sr-only">Annuler</span>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-4 md:p-5">
                <div class="bg-gray-100 rounded-lg p-4 mb-4 text-center">
                    <p class="text-lg text-gray-600">Total à Payer</p>
                    <p class="font-bold text-4xl text-blue-600" id="modal-total">0 FCFA</p>
                </div>
                
                <!-- Payment Form -->
                <div id="payment-form" class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end mb-4 p-4 border rounded-lg bg-gray-50">
                    <div>
                        <label for="payment-method" class="block mb-2 text-sm font-medium text-gray-900">Mode de paiement</label>
                        <select id="payment-method" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="especes">Espèces</option>
                            <option value="carte">Carte Bancaire</option>
                            <option value="mobile">Mobile Money</option>
                            <option value="cheque">Chèque</option>
                        </select>
                    </div>
                    <div>
                        <label for="payment-amount" class="block mb-2 text-sm font-medium text-gray-900">Montant</label>
                        <input type="number" id="payment-amount" placeholder="0" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    </div>
                    <div class="sm:col-span-2">
                         <button type="button" id="add-payment-btn" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                            Ajouter le paiement
                        </button>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div id="payment-summary" class="mb-4">
                    <h4 class="font-semibold text-gray-700 mb-2">Paiements Ajoutés</h4>
                    <div id="payment-list" class="max-h-24 overflow-y-auto space-y-2">
                        <p class="text-gray-500 text-sm text-center">Aucun paiement ajouté.</p>
                    </div>
                </div>

                 <div class="bg-gray-100 rounded-lg p-4 mt-4 space-y-2">
                    <div class="flex justify-between font-semibold"><span>Montant Payé:</span> <span id="amount-paid">0 FCFA</span></div>
                    <div class="flex justify-between font-bold text-lg"><span>Reste à Payer:</span> <span id="amount-remaining" class="text-red-600">0 FCFA</span></div>
                </div>
            </div>
            <!-- Modal footer -->
            <div class="flex items-center p-4 md:p-5 border-t border-gray-200 rounded-b">
                <form id="sale-form" action="caisse.php" method="POST" class="w-full">
                    <input type="hidden" name="process_sale" value="1">
                    <div id="hidden-payments"></div> <!-- Hidden inputs for payments will be injected here -->
                    <button id="confirm-sale-btn" type="submit" disabled class="w-full text-white bg-green-600 hover:bg-green-700 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center disabled:bg-gray-400 disabled:cursor-not-allowed">
                        Confirmer la Vente
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- STATE ---
    let saleTotal = 0;
    let payments = []; // Array of { method, amount }

    const formatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XAF' });

    // --- DOM ELEMENTS ---
    const productSearchInput = document.getElementById('productSearch');
    const feedbackAlert = document.getElementById('feedback-alert');
    const cartItemsContainer = document.getElementById('cart-items');
    const cartTotalEl = document.getElementById('cart-total');
    const modalTotalEl = document.getElementById('modal-total');
    const validateSaleBtn = document.getElementById('validate-sale-btn');
    const addPaymentBtn = document.getElementById('add-payment-btn');
    const paymentMethodEl = document.getElementById('payment-method');
    const paymentAmountEl = document.getElementById('payment-amount');
    const paymentListEl = document.getElementById('payment-list');
    const amountPaidEl = document.getElementById('amount-paid');
    const amountRemainingEl = document.getElementById('amount-remaining');
    const confirmSaleBtn = document.getElementById('confirm-sale-btn');
    const hiddenPaymentsContainer = document.getElementById('hidden-payments');

    // --- INITIALIZATION ---
    updateCartDisplay(); // Initial cart load
    if (feedbackAlert) {
        setTimeout(() => { feedbackAlert.style.display = 'none'; }, 5000);
    }
    
    // --- EVENT LISTENERS ---
    productSearchInput.addEventListener('keyup', handleProductSearch);
    validateSaleBtn.addEventListener('click', preparePaymentModal);
    addPaymentBtn.addEventListener('click', handleAddPayment);
    confirmSaleBtn.addEventListener('click', prepareFormForSubmission);

    // --- FUNCTIONS ---
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
                return;
            }
            saleTotal = result.total;
            updateCartDisplay(result.cart, result.total);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur de communication est survenue.');
        });
    }

    window.addToCart = (id_prod) => handleCartAction('add', { id_prod });
    window.updateQuantity = (id_prod, quantite) => handleCartAction('update', { id_prod, quantite });
    window.removeFromCart = (id_prod) => handleCartAction('remove', { id_prod });

    function updateCartDisplay(cart, total) {
        if (cart === undefined || total === undefined) {
            handleCartAction('get');
            return;
        }

        cartItemsContainer.innerHTML = '';
        if (cart.length === 0) {
            cartItemsContainer.innerHTML = '<p class="text-gray-500 text-center mt-8">Le panier est vide.</p>';
            validateSaleBtn.disabled = true;
        } else {
            validateSaleBtn.disabled = false;
            cart.forEach(item => {
                cartItemsContainer.innerHTML += `
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <div class="flex-grow">
                            <p class="font-semibold text-sm">${item.nom}</p>
                            <p class="text-xs text-gray-500">${formatter.format(item.prix_unitaire)}</p>
                        </div>
                        <div class="flex items-center">
                            <input type="number" value="${item.quantite}" onchange="updateQuantity(${item.id_prod}, this.value)" class="w-16 text-center border-gray-200 rounded-md shadow-sm" min="1" max="${item.stock_max}">
                            <button onclick="removeFromCart(${item.id_prod})" class="ml-3 text-red-500 hover:text-red-700 font-bold text-lg">&times;</button>
                        </div>
                    </div>
                `;
            });
        }
        
        cartTotalEl.textContent = formatter.format(total);
        modalTotalEl.textContent = formatter.format(total);
    }

    function handleProductSearch() {
        let filter = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            card.style.display = card.dataset.name.includes(filter) ? '' : 'none';
        });
    }

    function preparePaymentModal() {
        // Reset payments when modal is opened
        payments = [];
        paymentAmountEl.value = '';
        updatePaymentDisplay();
    }
    
    function handleAddPayment() {
        const method = paymentMethodEl.value;
        const amount = parseFloat(paymentAmountEl.value);

        if (isNaN(amount) || amount <= 0) {
            alert("Veuillez entrer un montant de paiement valide.");
            return;
        }

        payments.push({ method, amount });
        paymentAmountEl.value = ''; // Reset input
        updatePaymentDisplay();
    }

    function removePayment(index) {
        payments.splice(index, 1);
        updatePaymentDisplay();
    }
    
    function updatePaymentDisplay() {
        paymentListEl.innerHTML = '';
        if (payments.length === 0) {
            paymentListEl.innerHTML = '<p class="text-gray-500 text-sm text-center">Aucun paiement ajouté.</p>';
        } else {
            payments.forEach((p, index) => {
                paymentListEl.innerHTML += `
                    <div class="flex justify-between items-center bg-gray-100 p-2 rounded-lg">
                        <div>
                            <span class="font-semibold">${p.method.charAt(0).toUpperCase() + p.method.slice(1)}:</span>
                            <span>${formatter.format(p.amount)}</span>
                        </div>
                        <button onclick="removePaymentFromUI(${index})" class="text-red-500 hover:text-red-700">&times;</button>
                    </div>
                `;
            });
        }
        
        const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
        const remaining = saleTotal - totalPaid;

        amountPaidEl.textContent = formatter.format(totalPaid);
        amountRemainingEl.textContent = formatter.format(remaining);

        if (remaining > 0) {
             amountRemainingEl.classList.add('text-red-600');
             amountRemainingEl.classList.remove('text-green-600');
        } else {
             amountRemainingEl.classList.remove('text-red-600');
             amountRemainingEl.classList.add('text-green-600');
        }

        // Enable confirm button only if paid amount is sufficient
        confirmSaleBtn.disabled = Math.abs(remaining) > 0.01 && remaining > 0;

        // Auto-fill remaining amount
        if(remaining > 0) {
            paymentAmountEl.value = remaining.toFixed(0);
        } else {
            paymentAmountEl.value = '';
        }
    }
    
    // Need to expose this to global scope for the inline onclick
    window.removePaymentFromUI = (index) => {
        removePayment(index);
    };

    function prepareFormForSubmission(event) {
        hiddenPaymentsContainer.innerHTML = ''; // Clear previous hidden inputs
        
        const totalPaid = payments.reduce((sum, p) => sum + p.amount, 0);
        if (Math.abs(totalPaid - saleTotal) > 0.01) {
            event.preventDefault(); // Stop form submission
            alert("Le montant payé ne correspond pas au total de la vente. Veuillez ajuster les paiements.");
            return;
        }

        payments.forEach((p, index) => {
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = `payments[${index}][method]`;
            methodInput.value = p.method;
            hiddenPaymentsContainer.appendChild(methodInput);

            const amountInput = document.createElement('input');
            amountInput.type = 'hidden';
            amountInput.name = `payments[${index}][amount]`;
            amountInput.value = p.amount;
            hiddenPaymentsContainer.appendChild(amountInput);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

