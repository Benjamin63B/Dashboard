<?php
/**
 * Rapports détaillés
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';
require_once 'includes/language.php';

$page_title = __('reports');

// Paramètres de période
$start_date = $_GET['start_date'] ?? date('Y-01-01'); // Début de l'année par défaut
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Aujourd'hui par défaut

// Statistiques générales
$stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_invoices,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_invoices,
    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_invoices,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_invoices,
    COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status IN ('sent', 'overdue') THEN total_amount ELSE 0 END), 0) as pending_amount,
    COALESCE(AVG(CASE WHEN status = 'paid' THEN total_amount ELSE NULL END), 0) as avg_invoice_amount
    FROM invoices 
    WHERE user_id = ? 
    AND issue_date BETWEEN ? AND ?");
$stmt->execute([$user['id'], $start_date, $end_date]);
$stats = $stmt->fetch();

// Revenus par mois
$stmt = $pdo->prepare("SELECT 
    DATE_FORMAT(paid_date, '%Y-%m') as month,
    COUNT(*) as invoice_count,
    SUM(total_amount) as revenue
    FROM invoices 
    WHERE user_id = ? 
    AND status = 'paid' 
    AND paid_date BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(paid_date, '%Y-%m')
    ORDER BY month ASC");
$stmt->execute([$user['id'], $start_date, $end_date]);
$monthly_revenue = $stmt->fetchAll();

// Top clients
$stmt = $pdo->prepare("SELECT 
    c.name,
    c.company,
    COUNT(i.id) as invoice_count,
    SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END) as total_paid
    FROM clients c
    LEFT JOIN invoices i ON c.id = i.client_id AND i.issue_date BETWEEN ? AND ?
    WHERE c.user_id = ?
    GROUP BY c.id
    HAVING total_paid > 0
    ORDER BY total_paid DESC
    LIMIT 10");
$stmt->execute([$start_date, $end_date, $user['id']]);
$top_clients = $stmt->fetchAll();

// Factures par statut
$stmt = $pdo->prepare("SELECT 
    status,
    COUNT(*) as count,
    SUM(total_amount) as total
    FROM invoices 
    WHERE user_id = ? 
    AND issue_date BETWEEN ? AND ?
    GROUP BY status
    ORDER BY count DESC");
$stmt->execute([$user['id'], $start_date, $end_date]);
$invoices_by_status = $stmt->fetchAll();

// Paiements par méthode
$stmt = $pdo->prepare("SELECT 
    payment_method,
    COUNT(*) as count,
    SUM(amount) as total
    FROM payments 
    WHERE user_id = ? 
    AND payment_date BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY payment_method
    ORDER BY total DESC");
$stmt->execute([$user['id'], $start_date, $end_date]);
$payments_by_method = $stmt->fetchAll();

// Factures en retard
$stmt = $pdo->prepare("SELECT 
    i.*,
    c.name as client_name,
    DATEDIFF(NOW(), i.due_date) as days_overdue
    FROM invoices i
    LEFT JOIN clients c ON i.client_id = c.id
    WHERE i.user_id = ?
    AND i.status IN ('sent', 'overdue')
    AND i.due_date < CURDATE()
    ORDER BY i.due_date ASC");
$stmt->execute([$user['id']]);
$overdue_invoices = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<link rel="stylesheet" href="assets/css/style.css">
<style>
    .report-filters {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin: 0 0 10px 0;
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
    }
    .stat-card .value {
        font-size: 32px;
        font-weight: bold;
        color: #4f46e5;
    }
    .report-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .report-section h2 {
        margin-top: 0;
        border-bottom: 2px solid #4f46e5;
        padding-bottom: 10px;
    }
</style>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1><?php echo __('reports'); ?></h1>
    </div>

    <div class="report-filters">
        <form method="GET" style="display: flex; gap: 15px; align-items: end;">
            <div>
                <label for="start_date">Date de début</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control">
            </div>
            <div>
                <label for="end_date">Date de fin</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control">
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="reports.php" class="btn btn-secondary">Réinitialiser</a>
            </div>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total factures</h3>
            <div class="value"><?php echo $stats['total_invoices']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Factures payées</h3>
            <div class="value"><?php echo $stats['paid_invoices']; ?></div>
        </div>
        <div class="stat-card">
            <h3>Chiffre d'affaires</h3>
            <div class="value"><?php echo number_format($stats['total_revenue'], 0, ',', ' '); ?> €</div>
        </div>
        <div class="stat-card">
            <h3>En attente</h3>
            <div class="value"><?php echo number_format($stats['pending_amount'], 0, ',', ' '); ?> €</div>
        </div>
        <div class="stat-card">
            <h3>Montant moyen</h3>
            <div class="value"><?php echo number_format($stats['avg_invoice_amount'], 0, ',', ' '); ?> €</div>
        </div>
        <div class="stat-card">
            <h3>Factures en retard</h3>
            <div class="value" style="color: #dc2626;"><?php echo $stats['overdue_invoices']; ?></div>
        </div>
    </div>

    <div class="report-section">
        <h2>Revenus par mois</h2>
        <?php if (empty($monthly_revenue)): ?>
            <p>Aucune donnée pour cette période.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mois</th>
                        <th>Nombre de factures</th>
                        <th>Revenus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($monthly_revenue as $month): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                            <td><?php echo $month['invoice_count']; ?></td>
                            <td><strong><?php echo number_format($month['revenue'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h2>Top clients</h2>
        <?php if (empty($top_clients)): ?>
            <p>Aucun client avec des paiements pour cette période.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Entreprise</th>
                        <th>Nombre de factures</th>
                        <th>Total payé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_clients as $client): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($client['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($client['company'] ?? '-'); ?></td>
                            <td><?php echo $client['invoice_count']; ?></td>
                            <td><strong><?php echo number_format($client['total_paid'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="report-section">
        <h2>Factures par statut</h2>
        <?php if (empty($invoices_by_status)): ?>
            <p>Aucune facture pour cette période.</p>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Nombre</th>
                        <th>Montant total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices_by_status as $status): ?>
                        <tr>
                            <td><span class="badge badge-<?php echo $status['status']; ?>"><?php echo ucfirst($status['status']); ?></span></td>
                            <td><?php echo $status['count']; ?></td>
                            <td><strong><?php echo number_format($status['total'], 2, ',', ' '); ?> €</strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (!empty($payments_by_method)): ?>
    <div class="report-section">
        <h2>Paiements par méthode</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Méthode</th>
                    <th>Nombre</th>
                    <th>Montant total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments_by_method as $method): ?>
                    <tr>
                        <td><?php echo ucfirst($method['payment_method'] ?? 'Non spécifié'); ?></td>
                        <td><?php echo $method['count']; ?></td>
                        <td><strong><?php echo number_format($method['total'], 2, ',', ' '); ?> €</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($overdue_invoices)): ?>
    <div class="report-section">
        <h2>Factures en retard</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Numéro</th>
                    <th>Client</th>
                    <th>Montant</th>
                    <th>Date d'échéance</th>
                    <th>Jours de retard</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue_invoices as $invoice): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                        <td><?php echo htmlspecialchars($invoice['client_name']); ?></td>
                        <td><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</td>
                        <td><?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></td>
                        <td><span class="badge badge-danger"><?php echo $invoice['days_overdue']; ?> jours</span></td>
                        <td>
                            <a href="invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">Voir</a>
                            <a href="reminders.php" class="btn btn-sm btn-secondary">Rappel</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>

