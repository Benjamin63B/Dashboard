<?php
/**
 * Détails d'une facture
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

$invoice_id = $_GET['id'] ?? null;

if (!$invoice_id || !is_numeric($invoice_id)) {
    header('Location: invoices.php');
    exit;
}

$stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.email as client_email, c.phone as client_phone, c.company as client_company, c.address as client_address FROM invoices i LEFT JOIN clients c ON i.client_id = c.id WHERE i.id = ? AND i.user_id = ?");
$stmt->execute([$invoice_id, $user['id']]);
$invoice = $stmt->fetch();

if (!$invoice) {
    header('Location: invoices.php');
    exit;
}

$page_title = 'Facture ' . $invoice['invoice_number'];

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="invoice-container">
        <div class="invoice-header">
            <h1>Facture <?php echo htmlspecialchars($invoice['invoice_number']); ?></h1>
            <div class="invoice-actions">
                <a href="invoices.php" class="btn btn-secondary">Retour</a>
                <a href="export_pdf.php?id=<?php echo $invoice['id']; ?>" class="btn btn-primary" target="_blank">Exporter en PDF</a>
                <a href="invoices.php?edit=<?php echo $invoice['id']; ?>" class="btn btn-primary">Modifier</a>
            </div>
        </div>

        <div class="invoice-document">
            <div class="invoice-header-section">
                <div>
                    <h2>Facture</h2>
                    <p><strong>Numéro:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
                    <p><strong>Date d'émission:</strong> <?php echo date('d/m/Y', strtotime($invoice['issue_date'])); ?></p>
                    <?php if ($invoice['due_date']): ?>
                        <p><strong>Date d'échéance:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
                    <?php endif; ?>
                </div>
                <div class="invoice-status-badge">
                    <span class="badge badge-<?php echo $invoice['status']; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                </div>
            </div>

            <div class="invoice-parties">
                <div class="invoice-party">
                    <h3>Facturé à</h3>
                    <p><strong><?php echo htmlspecialchars($invoice['client_name']); ?></strong></p>
                    <?php if ($invoice['client_company']): ?>
                        <p><?php echo htmlspecialchars($invoice['client_company']); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['client_address']): ?>
                        <p><?php echo nl2br(htmlspecialchars($invoice['client_address'])); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['client_email']): ?>
                        <p>Email: <?php echo htmlspecialchars($invoice['client_email']); ?></p>
                    <?php endif; ?>
                    <?php if ($invoice['client_phone']): ?>
                        <p>Tél: <?php echo htmlspecialchars($invoice['client_phone']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="invoice-party">
                    <h3>Facturé par</h3>
                    <p><strong><?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?></strong></p>
                    <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <?php if ($invoice['title']): ?>
                <div class="invoice-title">
                    <h3><?php echo htmlspecialchars($invoice['title']); ?></h3>
                </div>
            <?php endif; ?>

            <?php if ($invoice['description']): ?>
                <div class="invoice-description">
                    <p><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="invoice-summary">
                <table class="invoice-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Montant HT</th>
                            <th class="text-right">TVA (<?php echo $invoice['tax_rate']; ?>%)</th>
                            <th class="text-right">Montant TTC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo htmlspecialchars($invoice['title'] ?: 'Facture ' . $invoice['invoice_number']); ?></td>
                            <td class="text-right"><?php echo number_format($invoice['amount'], 2, ',', ' '); ?> €</td>
                            <td class="text-right"><?php echo number_format($invoice['tax_amount'], 2, ',', ' '); ?> €</td>
                            <td class="text-right"><strong><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right"><strong>Total TTC</strong></td>
                            <td class="text-right"><strong><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <?php if ($invoice['status'] == 'paid' && $invoice['paid_date']): ?>
                <div class="invoice-paid-info">
                    <p><strong>Payée le:</strong> <?php echo date('d/m/Y', strtotime($invoice['paid_date'])); ?></p>
                    <?php if ($invoice['payment_method']): ?>
                        <p><strong>Méthode de paiement:</strong> <?php echo ucfirst($invoice['payment_method']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

