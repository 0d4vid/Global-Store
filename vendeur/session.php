<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur']);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/header.php';

$feedback_message = '';
$feedback_type = ''; // 'success' or 'error'
$id_user = $_SESSION['user_id'];
$active_session = null;

// Check for an active session for the current user
try {
    $stmt = $pdo->prepare("SELECT * FROM session_caisse WHERE id_user = ? AND date_fin IS NULL ORDER BY date_debut DESC LIMIT 1");
    $stmt->execute([$id_user]);
    $active_session = $stmt->fetch();
} catch (PDOException $e) {
    $feedback_message = "Erreur lors de la vérification de la session : " . $e->getMessage();
    $feedback_type = 'error';
}

// Handle Open Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['open_session'])) {
    if ($active_session) {
        $feedback_message = "Vous avez déjà une session active.";
        $feedback_type = 'error';
    } else {
        $fond_initial = $_POST['fond_initial'];
        if (!is_numeric($fond_initial) || $fond_initial < 0) {
            $feedback_message = "Le fond initial doit être un montant valide.";
            $feedback_type = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO session_caisse (date_debut, fond_initial, id_user) VALUES (NOW(), ?, ?)");
                $stmt->execute([$fond_initial, $id_user]);
                header("Location: session.php"); // Refresh to show the active session
                exit();
            } catch (PDOException $e) {
                $feedback_message = "Erreur lors de l'ouverture de la session : " . $e->getMessage();
                $feedback_type = 'error';
            }
        }
    }
}

// Handle Close Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_session'])) {
    if (!$active_session) {
        $feedback_message = "Aucune session active à clôturer.";
        $feedback_type = 'error';
    } else {
        try {
            // Calculate total sales for the session for the current user
            $stmt = $pdo->prepare(
                "SELECT SUM(p.montant) 
                 FROM paiement p
                 JOIN vente v ON p.id_vente = v.id_vente
                 WHERE v.id_user = ? AND v.date_vente BETWEEN ? AND NOW()"
            );
            $stmt->execute([$id_user, $active_session['date_debut']]);
            $total_ventes = $stmt->fetchColumn() ?? 0;

            $total_cloture = $active_session['fond_initial'] + $total_ventes;

            // Update the session record
            $update_stmt = $pdo->prepare("UPDATE session_caisse SET date_fin = NOW(), total_cloture = ? WHERE id_session = ?");
            $update_stmt->execute([$total_cloture, $active_session['id_session']]);

            header("Location: session.php"); // Refresh to show the closed session status
            exit();
        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de la clôture de la session : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}

?>

<div class="container mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-4">Gestion de la Session de Caisse</h1>
    <p class="text-gray-600 mb-8">Ouvrir et fermer votre session de caisse pour la journée.</p>

    <?php if ($feedback_message): ?>
        <div class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-md">
        <?php if ($active_session): ?>
            <!-- Active Session View -->
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">Session Active</h2>
            <div class="space-y-4">
                <p><strong>Date d'ouverture :</strong> <?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($active_session['date_debut']))); ?></p>
                <p><strong>Fond de caisse initial :</strong> <?php echo format_price($active_session['fond_initial']); ?></p>
                <form action="session.php" method="POST">
                    <button type="submit" name="close_session" class="mt-6 text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center" onclick="return confirm('Êtes-vous sûr de vouloir clôturer cette session ? Cette action est irréversible.');">
                        Clôturer la Session
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Open Session Form -->
            <h2 class="text-2xl font-semibold text-gray-700 mb-6">Ouvrir une Nouvelle Session</h2>
            <form action="session.php" method="POST">
                <div class="mb-6">
                    <label for="fond_initial" class="block mb-2 text-sm font-medium text-gray-900">Fond de caisse initial (FCFA)</label>
                    <input type="number" id="fond_initial" name="fond_initial" min="0" step="100" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <button type="submit" name="open_session" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">
                    Ouvrir la Caisse
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
