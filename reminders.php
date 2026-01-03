<?php
/**
 * Gestion des rappels de paiement
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';
require_once 'includes/language.php';

$page_title = __('payment_reminders');
$error = '';
$success = '';

// Récupérer les paramètres de l'entreprise pour l'email
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ? AND setting_key IN ('company_name', 'company_email')");
$stmt->execute([$user['id']]);
$company_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Récupérer les factures en attente de paiement
$stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.email as client_email, c.company as client_company 
    FROM invoices i 
    LEFT JOIN clients c ON i.client_id = c.id 
    WHERE i.user_id = ? 
    AND i.status IN ('sent', 'overdue') 
    AND i.due_date IS NOT NULL
    ORDER BY i.due_date ASC");
$stmt->execute([$user['id']]);
$pending_invoices = $stmt->fetchAll();

// Envoyer un rappel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {
    $invoice_id = $_POST['invoice_id'] ?? null;
    
    if ($invoice_id && is_numeric($invoice_id)) {
        $stmt = $pdo->prepare("SELECT i.*, c.name as client_name, c.email as client_email, c.company as client_company 
            FROM invoices i 
            LEFT JOIN clients c ON i.client_id = c.id 
            WHERE i.id = ? AND i.user_id = ?");
        $stmt->execute([$invoice_id, $user['id']]);
        $invoice = $stmt->fetch();
        
        if ($invoice && $invoice['client_email']) {
            // Envoyer l'email de rappel
            $subject = 'Rappel de paiement - Facture ' . $invoice['invoice_number'];
            $message = generateReminderEmail($invoice, $user, $company_settings);
            $headers = 'From: ' . ($company_settings['company_email'] ?? $user['email']) . "\r\n";
            $headers .= 'Reply-To: ' . ($company_settings['company_email'] ?? $user['email']) . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
            
            if (mail($invoice['client_email'], $subject, $message, $headers)) {
                $success = 'Rappel de paiement envoyé avec succès à ' . htmlspecialchars($invoice['client_email']);
                
                // Enregistrer la date d'envoi (optionnel - nécessiterait une colonne dans la table)
                // $stmt = $pdo->prepare("UPDATE invoices SET reminder_sent_at = NOW() WHERE id = ?");
                // $stmt->execute([$invoice_id]);
            } else {
                $error = 'Erreur lors de l\'envoi de l\'email. Vérifiez la configuration de votre serveur.';
            }
        } else {
            $error = 'Facture introuvable ou client sans email.';
        }
    }
}

// Envoyer plusieurs rappels
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_all_reminders'])) {
    $sent_count = 0;
    $errors = [];
    
    foreach ($pending_invoices as $invoice) {
        if ($invoice['client_email']) {
            $subject = 'Rappel de paiement - Facture ' . $invoice['invoice_number'];
            $message = generateReminderEmail($invoice, $user, $company_settings);
            $headers = 'From: ' . ($company_settings['company_email'] ?? $user['email']) . "\r\n";
            $headers .= 'Reply-To: ' . ($company_settings['company_email'] ?? $user['email']) . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
            
            if (mail($invoice['client_email'], $subject, $message, $headers)) {
                $sent_count++;
            } else {
                $errors[] = $invoice['invoice_number'];
            }
        }
    }
    
    if ($sent_count > 0) {
        $success = $sent_count . ' rappel(s) envoyé(s) avec succès.';
        if (!empty($errors)) {
            $error = 'Erreurs pour ' . count($errors) . ' facture(s).';
        }
    } else {
        $error = 'Aucun rappel n\'a pu être envoyé. Vérifiez la configuration de votre serveur.';
    }
}

function generateReminderEmail($invoice, $user, $company_settings) {
    $company_name = $company_settings['company_name'] ?? $user['full_name'] ?? $user['username'];
    $due_date = $invoice['due_date'] ? date('d/m/Y', strtotime($invoice['due_date'])) : 'Non spécifiée';
    $days_overdue = $invoice['due_date'] ? (time() - strtotime($invoice['due_date'])) / 86400 : 0;
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4f46e5; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .invoice-details { background-color: white; padding: 15px; margin: 20px 0; border-left: 4px solid #4f46e5; }
        .button { display: inline-block; padding: 12px 24px; background-color: #4f46e5; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rappel de paiement</h1>
        </div>
        <div class="content">
            <p>Bonjour ' . htmlspecialchars($invoice['client_name']) . ',</p>
            <p>Nous vous rappelons que la facture suivante est en attente de paiement :</p>
            
            <div class="invoice-details">
                <p><strong>Numéro de facture:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>
                <p><strong>Montant:</strong> ' . number_format($invoice['total_amount'], 2, ',', ' ') . ' €</p>
                <p><strong>Date d\'échéance:</strong> ' . $due_date . '</p>';
    
    if ($days_overdue > 0) {
        $html .= '<p><strong style="color: #dc2626;">En retard de ' . (int)$days_overdue . ' jour(s)</strong></p>';
    }
    
    $html .= '</div>
            
            <p>Merci de régler cette facture dans les plus brefs délais.</p>
            
            <p>Cordialement,<br>' . htmlspecialchars($company_name) . '</p>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>';
    
    return $html;
}

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1><?php echo __('payment_reminders'); ?></h1>
        <?php if (!empty($pending_invoices)): ?>
            <form method="POST" style="display: inline-block;">
                <button type="submit" name="send_all_reminders" class="btn btn-primary" onclick="return confirm('Êtes-vous sûr de vouloir envoyer des rappels pour toutes les factures en attente ?');">
                    Envoyer tous les rappels
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($pending_invoices)): ?>
        <div class="alert alert-info">
            <p>Aucune facture en attente de paiement.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Numéro</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Date d'échéance</th>
                        <th>Statut</th>
                        <th>Email client</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_invoices as $invoice): 
                        $days_overdue = $invoice['due_date'] ? (time() - strtotime($invoice['due_date'])) / 86400 : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                            <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                            <td><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?>
                                <?php if ($days_overdue > 0): ?>
                                    <span class="badge badge-danger" style="margin-left: 10px;">En retard de <?php echo (int)$days_overdue; ?> jour(s)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-<?php echo $invoice['status']; ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($invoice['client_email'] ?? 'Non renseigné'); ?></td>
                            <td>
                                <?php if ($invoice['client_email']): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="invoice_id" value="<?php echo $invoice['id']; ?>">
                                        <button type="submit" name="send_reminder" class="btn btn-sm btn-primary">Envoyer rappel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">Pas d'email</span>
                                <?php endif; ?>
                                <a href="invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-secondary">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>

