<?php
require_once __DIR__ . '/../includes/functions.php';
check_session(['vendeur']);
require_once __DIR__ . '/../config/db.php';

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
            
            $feedback_message = "Session clôturée avec succès !";
            $feedback_type = 'success';
            
            $active_session = null; // Unset active session to refresh the view

        } catch (PDOException $e) {
            $feedback_message = "Erreur lors de la clôture de la session : " . $e->getMessage();
            $feedback_type = 'error';
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mx-auto p-4 sm:p-6 lg:p-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">Gestion de la Session</h1>
        <p class="text-gray-500">Ouvrir et fermer votre session de caisse pour la journée.</p>
    </div>

    <?php if ($feedback_message): ?>
        <div id="feedback-alert" class="p-4 mb-4 text-sm rounded-lg <?php echo $feedback_type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>" role="alert">
            <?php echo htmlspecialchars($feedback_message); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg max-w-2xl mx-auto">
        <?php if ($active_session): ?>
            <!-- Active Session View -->
            <div class="text-center">
                <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 18.734M6 6h5m6 4v6m-6-10V4a2 2 0 00-2-2h-1a2 2 0 00-2 2v1m-1 4l-1.096-2.193a2 2 0 00-1.789-1.107H2.25a2 2 0 00-2 2v6a2 2 0 002 2h1.75"></path></svg>
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Session Active</h2>
                <p class="text-gray-500">Votre caisse est actuellement ouverte.</p>
            </div>
            <div class="mt-6 bg-gray-50 rounded-lg p-4 space-y-3 text-center">
                <div>
                    <p class="text-sm text-gray-500">Date d'ouverture</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($active_session['date_debut']))); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fond de caisse initial</p>
                    <p class="font-bold text-lg text-blue-600"><?php echo format_price($active_session['fond_initial']); ?></p>
                </div>
            </div>
            <form action="session.php" method="POST" class="mt-6">
                <button type="submit" name="close_session" class="w-full text-white bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-3 text-center" onclick="return confirm('Êtes-vous sûr de vouloir clôturer cette session ? Cette action est irréversible.');">
                    Clôturer la Session
                </button>
            </form>

        <?php else: ?>
            <!-- Open Session Form -->
            <div class="text-center">
                 <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Ouvrir une Nouvelle Session</h2>
                <p class="text-gray-500">Vous devez ouvrir une session pour commencer à enregistrer des ventes.</p>
            </div>
            <form action="session.php" method="POST" class="mt-6">
                <div class="mb-6">
                    <label for="fond_initial" class="block mb-2 text-sm font-medium text-gray-900">Fond de caisse initial (FCFA)</label>
                    <input type="number" id="fond_initial" name="fond_initial" min="0" step="100" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <button type="submit" name="open_session" class="w-full text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-3 text-center">
                    Ouvrir la Caisse
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
// Hide feedback alert after 5s
const feedbackAlert = document.getElementById('feedback-alert');
if(feedbackAlert) {
    setTimeout(() => { 
        feedbackAlert.style.transition = 'opacity 0.5s ease';
        feedbackAlert.style.opacity = '0';
        setTimeout(() => { feedbackAlert.style.display = 'none'; }, 500);
    }, 5000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
