<?php
/**
 * Gestion des factures
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

$page_title = 'Factures';
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Action: Supprimer
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $invoice_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$invoice_id, $user['id']])) {
        $success = 'Facture supprimée avec succès.';
    } else {
        $error = 'Erreur lors de la suppression.';
    }
}

// Action: Créer/Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = $_POST['invoice_id'] ?? null;
    $client_id = $_POST['client_id'] ?? '';
    $invoice_number = $_POST['invoice_number'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $tax_rate = $_POST['tax_rate'] ?? 0;
    $status = $_POST['status'] ?? 'draft';
    $issue_date = $_POST['issue_date'] ?? date('Y-m-d');
    $due_date = $_POST['due_date'] ?? '';
    
    $tax_amount = ($amount * $tax_rate) / 100;
    $total_amount = $amount + $tax_amount;
    
    if (empty($client_id) || empty($invoice_number) || empty($amount)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        if ($invoice_id) {
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE invoices SET client_id = ?, invoice_number = ?, title = ?, description = ?, amount = ?, tax_rate = ?, tax_amount = ?, total_amount = ?, status = ?, issue_date = ?, due_date = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$client_id, $invoice_number, $title, $description, $amount, $tax_rate, $tax_amount, $total_amount, $status, $issue_date, $due_date ?: null, $invoice_id, $user['id']])) {
                $success = 'Facture modifiée avec succès.';
            } else {
                $error = 'Erreur lors de la modification.';
            }
        } else {
            // Création
            $stmt = $pdo->prepare("INSERT INTO invoices (user_id, client_id, invoice_number, title, description, amount, tax_rate, tax_amount, total_amount, status, issue_date, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user['id'], $client_id, $invoice_number, $title, $description, $amount, $tax_rate, $tax_amount, $total_amount, $status, $issue_date, $due_date ?: null])) {
                $success = 'Facture créée avec succès.';
            } else {
                $error = 'Erreur lors de la création.';
            }
        }
    }
}

// Récupérer la facture à modifier
$edit_invoice = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM invoices WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['edit'], $user['id']]);
    $edit_invoice = $stmt->fetch();
}

// Générer le prochain numéro de facture
if (!$edit_invoice) {
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)) as max_num FROM invoices WHERE user_id = ? AND invoice_number LIKE 'FACT-%'");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch();
    $next_num = ($result['max_num'] ?? 0) + 1;
    $default_invoice_number = 'FACT-' . str_pad($next_num, 6, '0', STR_PAD_LEFT);
} else {
    $default_invoice_number = $edit_invoice['invoice_number'];
}

// Liste des clients
$stmt = $pdo->prepare("SELECT id, name FROM clients WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user['id']]);
$clients = $stmt->fetchAll();

// Liste des factures
$stmt = $pdo->prepare("SELECT i.*, c.name as client_name FROM invoices i LEFT JOIN clients c ON i.client_id = c.id WHERE i.user_id = ? ORDER BY i.created_at DESC");
$stmt->execute([$user['id']]);
$invoices = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Factures</h1>
        <button class="btn btn-primary" onclick="openModal('invoiceModal')">+ Créer une facture</button>
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
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Titre</th>
                    <th>Montant HT</th>
                    <th>TVA</th>
                    <th>Total TTC</th>
                    <th>Statut</th>
                    <th>Date émission</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr>
                        <td colspan="9" class="text-center">Aucune facture</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($invoice['title'] ?? '-'); ?></td>
                            <td><?php echo number_format($invoice['amount'], 2, ',', ' '); ?> €</td>
                            <td><?php echo number_format($invoice['tax_amount'], 2, ',', ' '); ?> € (<?php echo $invoice['tax_rate']; ?>%)</td>
                            <td><strong><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</strong></td>
                            <td><span class="badge badge-<?php echo $invoice['status']; ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($invoice['issue_date'])); ?></td>
                            <td>
                                <a href="invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">Voir</a>
                                <a href="?edit=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-secondary">Modifier</a>
                                <a href="?delete=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette facture ?');">Supprimer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Modal Facture -->
<div id="invoiceModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2><?php echo $edit_invoice ? 'Modifier la facture' : 'Nouvelle facture'; ?></h2>
            <button class="modal-close" onclick="closeModal('invoiceModal')">&times;</button>
        </div>
        <form method="POST" class="modal-body">
            <?php if ($edit_invoice): ?>
                <input type="hidden" name="invoice_id" value="<?php echo $edit_invoice['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="invoice_number">Numéro de facture *</label>
                    <input type="text" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($default_invoice_number); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="client_id">Client *</label>
                    <select id="client_id" name="client_id" required>
                        <option value="">Sélectionner un client</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>" <?php echo ($edit_invoice && $edit_invoice['client_id'] == $client['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($client['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="title">Titre</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($edit_invoice['title'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_invoice['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="amount">Montant HT (€) *</label>
                    <input type="number" id="amount" name="amount" step="0.01" value="<?php echo $edit_invoice['amount'] ?? 0; ?>" required oninput="calculateTotal()">
                </div>
                
                <div class="form-group">
                    <label for="tax_rate">Taux de TVA (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" step="0.01" value="<?php echo $edit_invoice['tax_rate'] ?? 20; ?>" oninput="calculateTotal()">
                </div>
                
                <div class="form-group">
                    <label>Montant TTC</label>
                    <input type="text" id="total_amount_display" readonly style="background: #f5f5f5;">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="status">Statut</label>
                    <select id="status" name="status">
                        <option value="draft" <?php echo ($edit_invoice && $edit_invoice['status'] == 'draft') ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="sent" <?php echo ($edit_invoice && $edit_invoice['status'] == 'sent') ? 'selected' : ''; ?>>Envoyée</option>
                        <option value="paid" <?php echo ($edit_invoice && $edit_invoice['status'] == 'paid') ? 'selected' : ''; ?>>Payée</option>
                        <option value="overdue" <?php echo ($edit_invoice && $edit_invoice['status'] == 'overdue') ? 'selected' : ''; ?>>En retard</option>
                        <option value="cancelled" <?php echo ($edit_invoice && $edit_invoice['status'] == 'cancelled') ? 'selected' : ''; ?>>Annulée</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="issue_date">Date d'émission *</label>
                    <input type="date" id="issue_date" name="issue_date" value="<?php echo $edit_invoice['issue_date'] ?? date('Y-m-d'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Date d'échéance</label>
                    <input type="date" id="due_date" name="due_date" value="<?php echo $edit_invoice['due_date'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('invoiceModal')">Annuler</button>
                <button type="submit" class="btn btn-primary"><?php echo $edit_invoice ? 'Modifier' : 'Créer'; ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotal() {
    const amount = parseFloat(document.getElementById('amount').value) || 0;
    const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
    const taxAmount = (amount * taxRate) / 100;
    const total = amount + taxAmount;
    document.getElementById('total_amount_display').value = total.toFixed(2) + ' €';
}

<?php if ($edit_invoice): ?>
document.addEventListener('DOMContentLoaded', function() {
    openModal('invoiceModal');
    calculateTotal();
});
<?php else: ?>
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});
<?php endif; ?>
</script>

<?php require_once 'includes/footer.php'; ?>

