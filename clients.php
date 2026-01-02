<?php
/**
 * Gestion des clients
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

$page_title = 'Clients';
$error = '';
$success = '';

// Action: Supprimer
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $client_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$client_id, $user['id']])) {
        $success = 'Client supprimé avec succès.';
    } else {
        $error = 'Erreur lors de la suppression.';
    }
}

// Action: Créer/Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $company = $_POST['company'] ?? '';
    $address = $_POST['address'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = $_POST['notes'] ?? '';
    
    if (empty($name)) {
        $error = 'Le nom du client est obligatoire.';
    } else {
        // Validation du moyen de paiement
        $valid_payment_methods = ['stripe', 'paypal', 'bank', 'cash', 'other', ''];
        if (!in_array($payment_method, $valid_payment_methods)) {
            $payment_method = '';
        }
        
        if ($client_id) {
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, email = ?, phone = ?, company = ?, address = ?, payment_method = ?, notes = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$name, $email, $phone, $company, $address, $payment_method ?: null, $notes, $client_id, $user['id']])) {
                $success = 'Client modifié avec succès.';
            } else {
                $error = 'Erreur lors de la modification.';
            }
        } else {
            // Création
            $stmt = $pdo->prepare("INSERT INTO clients (user_id, name, email, phone, company, address, payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $name, $email, $phone, $company, $address, $payment_method ?: null, $notes])) {
                $success = 'Client créé avec succès.';
            } else {
                $error = 'Erreur lors de la création.';
            }
        }
    }
}

// Récupérer le client à modifier
$edit_client = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $user['id']]);
    $edit_client = $stmt->fetch();
}

// Liste des clients
$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM invoices WHERE client_id = c.id) as invoice_count,
    (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE client_id = c.id AND status = 'paid') as total_paid
    FROM clients c 
    WHERE c.user_id = ? 
    ORDER BY c.name ASC");
$stmt->execute([$user['id']]);
$clients = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Clients</h1>
        <button class="btn btn-primary" onclick="openModal('clientModal')">+ Ajouter un client</button>
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
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Entreprise</th>
                    <th>Moyen de paiement</th>
                    <th>Factures</th>
                    <th>Total payé</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="8" class="text-center">Aucun client</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $payment_methods_labels = [
                        'stripe' => 'Stripe',
                        'paypal' => 'PayPal',
                        'bank' => 'Virement bancaire',
                        'cash' => 'Espèces',
                        'other' => 'Autre'
                    ];
                    foreach ($clients as $client): 
                        $payment_label = $client['payment_method'] ? ($payment_methods_labels[$client['payment_method']] ?? ucfirst($client['payment_method'])) : '-';
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($client['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($client['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($client['company'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($payment_label); ?></td>
                            <td><?php echo $client['invoice_count']; ?></td>
                            <td><?php echo number_format($client['total_paid'], 2, ',', ' '); ?> €</td>
                            <td>
                                <a href="?edit=<?php echo $client['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a>
                                <a href="?delete=<?php echo $client['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce client ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal Client -->
<div id="clientModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><?php echo $edit_client ? 'Modifier le client' : 'Nouveau client'; ?></h2>
            <button class="modal-close" onclick="closeModal('clientModal')">&times;</button>
        </div>
        <form method="POST" class="modal-body">
            <?php if ($edit_client): ?>
                <input type="hidden" name="client_id" value="<?php echo $edit_client['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name">Nom *</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($edit_client['name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_client['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($edit_client['phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="company">Entreprise</label>
                <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($edit_client['company'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Adresse</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($edit_client['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="payment_method">Moyen de paiement préféré</label>
                <select id="payment_method" name="payment_method">
                    <option value="">Aucun</option>
                    <option value="stripe" <?php echo (isset($edit_client['payment_method']) && $edit_client['payment_method'] === 'stripe') ? 'selected' : ''; ?>>Stripe</option>
                    <option value="paypal" <?php echo (isset($edit_client['payment_method']) && $edit_client['payment_method'] === 'paypal') ? 'selected' : ''; ?>>PayPal</option>
                    <option value="bank" <?php echo (isset($edit_client['payment_method']) && $edit_client['payment_method'] === 'bank') ? 'selected' : ''; ?>>Virement bancaire</option>
                    <option value="cash" <?php echo (isset($edit_client['payment_method']) && $edit_client['payment_method'] === 'cash') ? 'selected' : ''; ?>>Espèces</option>
                    <option value="other" <?php echo (isset($edit_client['payment_method']) && $edit_client['payment_method'] === 'other') ? 'selected' : ''; ?>>Autre</option>
                </select>
                <small>Moyen de paiement préféré de ce client</small>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($edit_client['notes'] ?? ''); ?></textarea>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('clientModal')">Annuler</button>
                <button type="submit" class="btn btn-primary"><?php echo $edit_client ? 'Modifier' : 'Créer'; ?></button>
            </div>
        </form>
    </div>
</div>

<?php if ($edit_client): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    openModal('clientModal');
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>

