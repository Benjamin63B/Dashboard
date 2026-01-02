<?php
/**
 * Gestion des paiements
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

$page_title = 'Paiements';
$error = '';
$success = '';

// Action: Supprimer
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $payment_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$payment_id, $user['id']])) {
        $success = 'Paiement supprimé avec succès.';
    } else {
        $error = 'Erreur lors de la suppression.';
    }
}

// Action: Créer/Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_id = $_POST['payment_id'] ?? null;
    $invoice_id = $_POST['invoice_id'] ?? null;
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? '';
    $payment_id_ref = $_POST['payment_id_ref'] ?? '';
    $transaction_id = $_POST['transaction_id'] ?? '';
    $status = $_POST['status'] ?? 'pending';
    $payment_date = $_POST['payment_date'] ?? date('Y-m-d');
    $notes = $_POST['notes'] ?? '';
    
    if (empty($amount) || empty($payment_method)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        if ($payment_id) {
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE payments SET invoice_id = ?, amount = ?, payment_method = ?, payment_id = ?, transaction_id = ?, status = ?, payment_date = ?, notes = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$invoice_id ?: null, $amount, $payment_method, $payment_id_ref ?: null, $transaction_id ?: null, $status, $payment_date, $notes, $payment_id, $user['id']])) {
                // Mettre à jour le statut de la facture si liée
                if ($invoice_id) {
                    if ($status == 'completed') {
                        $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = ?, payment_method = ? WHERE id = ? AND user_id = ?");
                        $stmt->execute([$payment_date, $payment_method, $invoice_id, $user['id']]);
                    }
                }
                $success = 'Paiement modifié avec succès.';
            } else {
                $error = 'Erreur lors de la modification.';
            }
        } else {
            // Création
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, invoice_id, amount, payment_method, payment_id, transaction_id, status, payment_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $invoice_id ?: null, $amount, $payment_method, $payment_id_ref ?: null, $transaction_id ?: null, $status, $payment_date, $notes])) {
                // Mettre à jour le statut de la facture si liée
                if ($invoice_id && $status == 'completed') {
                    $stmt = $pdo->prepare("UPDATE invoices SET status = 'paid', paid_date = ?, payment_method = ? WHERE id = ? AND user_id = ?");
                    $stmt->execute([$payment_date, $payment_method, $invoice_id, $user['id']]);
                }
                $success = 'Paiement créé avec succès.';
            } else {
                $error = 'Erreur lors de la création.';
            }
        }
    }
}

// Récupérer le paiement à modifier
$edit_payment = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $user['id']]);
    $edit_payment = $stmt->fetch();
}

// Liste des factures
$stmt = $pdo->prepare("SELECT id, invoice_number, total_amount, status FROM invoices WHERE user_id = ? ORDER BY invoice_number DESC");
$stmt->execute([$user['id']]);
$invoices = $stmt->fetchAll();

// Liste des paiements
$stmt = $pdo->prepare("SELECT p.*, i.invoice_number FROM payments p LEFT JOIN invoices i ON p.invoice_id = i.id WHERE p.user_id = ? ORDER BY p.payment_date DESC, p.created_at DESC");
$stmt->execute([$user['id']]);
$payments = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Paiements</h1>
        <button class="btn btn-primary" onclick="openModal('paymentModal')">+ Enregistrer un paiement</button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Facture</th>
                    <th>Montant</th>
                    <th>Méthode</th>
                    <th>Transaction ID</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="7" class="text-center">Aucun paiement</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo $payment['invoice_number'] ? htmlspecialchars($payment['invoice_number']) : '-'; ?></td>
                            <td><strong><?php echo number_format($payment['amount'], 2, ',', ' '); ?> €</strong></td>
                            <td><?php echo ucfirst($payment['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($payment['transaction_id'] ?? '-'); ?></td>
                            <td><span class="badge badge-<?php echo $payment['status']; ?>"><?php echo ucfirst($payment['status']); ?></span></td>
                            <td>
                                <a href="?edit=<?php echo $payment['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a>
                                <a href="?delete=<?php echo $payment['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal Paiement -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $edit_payment ? 'Modifier le paiement' : 'Nouveau paiement'; ?></h2>
            <button class="modal-close" onclick="closeModal('paymentModal')">&times;</button>
        </div>
        <form method="POST" class="modal-body">
            <?php if ($edit_payment): ?>
                <input type="hidden" name="payment_id" value="<?php echo $edit_payment['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="invoice_id">Facture (optionnel)</label>
                <select id="invoice_id" name="invoice_id">
                    <option value="">Aucune facture</option>
                    <?php foreach ($invoices as $invoice): ?>
                        <option value="<?php echo $invoice['id']; ?>" <?php echo ($edit_payment && $edit_payment['invoice_id'] == $invoice['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($invoice['invoice_number']); ?> - <?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> € (<?php echo ucfirst($invoice['status']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Montant (€) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" value="<?php echo $edit_payment['amount'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="payment_method">Méthode de paiement *</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="">Sélectionner</option>
                        <option value="stripe" <?php echo ($edit_payment && $edit_payment['payment_method'] == 'stripe') ? 'selected' : ''; ?>>Stripe</option>
                        <option value="paypal" <?php echo ($edit_payment && $edit_payment['payment_method'] == 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                        <option value="bank" <?php echo ($edit_payment && $edit_payment['payment_method'] == 'bank') ? 'selected' : ''; ?>>Virement bancaire</option>
                        <option value="cash" <?php echo ($edit_payment && $edit_payment['payment_method'] == 'cash') ? 'selected' : ''; ?>>Espèces</option>
                        <option value="other" <?php echo ($edit_payment && $edit_payment['payment_method'] == 'other') ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="payment_date">Date de paiement *</label>
                    <input type="date" id="payment_date" name="payment_date" value="<?php echo $edit_payment['payment_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="pending" <?php echo ($edit_payment && $edit_payment['status'] == 'pending') ? 'selected' : ''; ?>>En attente</option>
                        <option value="completed" <?php echo ($edit_payment && $edit_payment['status'] == 'completed') ? 'selected' : ''; ?>>Complété</option>
                        <option value="failed" <?php echo ($edit_payment && $edit_payment['status'] == 'failed') ? 'selected' : ''; ?>>Échoué</option>
                        <option value="refunded" <?php echo ($edit_payment && $edit_payment['status'] == 'refunded') ? 'selected' : ''; ?>>Remboursé</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="transaction_id">ID de transaction</label>
                <input type="text" id="transaction_id" name="transaction_id" value="<?php echo htmlspecialchars($edit_payment['transaction_id'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="payment_id_ref">ID de paiement (Stripe/PayPal)</label>
                <input type="text" id="payment_id_ref" name="payment_id_ref" value="<?php echo htmlspecialchars($edit_payment['payment_id'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($edit_payment['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('paymentModal')">Annuler</button>
                <button type="submit" class="btn btn-primary"><?php echo $edit_payment ? 'Modifier' : 'Créer'; ?></button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_payment): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    openModal('paymentModal');
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

