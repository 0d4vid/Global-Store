<?php
// ajax_cart_handler.php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure the user is a vendor
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'vendeur') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

header('Content-Type: application/json');
$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $id_prod = $_POST['id_prod'] ?? null;
            if (!$id_prod) throw new Exception('ID de produit manquant.');

            // Fetch product details
            $stmt = $pdo->prepare("SELECT nom, prix_vente, stock_actuel FROM produit WHERE id_prod = ?");
            $stmt->execute([$id_prod]);
            $product = $stmt->fetch();

            if (!$product) throw new Exception('Produit non trouvé.');

            // Check stock
            $current_cart_qty = $_SESSION['panier'][$id_prod]['quantite'] ?? 0;
            if ($product['stock_actuel'] <= $current_cart_qty) {
                 throw new Exception('Stock insuffisant pour ajouter ce produit.');
            }

            if (isset($_SESSION['panier'][$id_prod])) {
                $_SESSION['panier'][$id_prod]['quantite']++;
            } else {
                $_SESSION['panier'][$id_prod] = [
                    'id_prod' => $id_prod,
                    'nom' => $product['nom'],
                    'prix_unitaire' => $product['prix_vente'],
                    'quantite' => 1,
                    'stock_max' => $product['stock_actuel']
                ];
            }
            break;

        case 'update':
            $id_prod = $_POST['id_prod'] ?? null;
            $quantite = $_POST['quantite'] ?? null;
            if (!$id_prod || !is_numeric($quantite) || $quantite < 0) {
                throw new Exception('Données de mise à jour invalides.');
            }

            if (isset($_SESSION['panier'][$id_prod])) {
                if ($quantite == 0) {
                    unset($_SESSION['panier'][$id_prod]);
                } elseif ($quantite > $_SESSION['panier'][$id_prod]['stock_max']) {
                    throw new Exception('La quantité demandée dépasse le stock disponible.');
                } else {
                    $_SESSION['panier'][$id_prod]['quantite'] = $quantite;
                }
            }
            break;

        case 'remove':
            $id_prod = $_POST['id_prod'] ?? null;
            if (!$id_prod) throw new Exception('ID de produit manquant.');
            unset($_SESSION['panier'][$id_prod]);
            break;

        case 'clear':
            $_SESSION['panier'] = [];
            break;
        
        case 'get':
            // This action will just return the current cart state
            break;

        default:
            throw new Exception('Action non valide.');
    }

    // Return the updated cart
    echo json_encode([
        'success' => true,
        'cart' => array_values($_SESSION['panier']), // Return as a simple array
        'total' => array_reduce($_SESSION['panier'], function ($sum, $item) {
            return $sum + ($item['quantite'] * $item['prix_unitaire']);
        }, 0)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage(), 'cart' => $_SESSION['panier']]);
}
?>