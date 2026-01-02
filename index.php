<?php
/**
 * Dashboard principal
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';
require_once 'includes/language.php';

// Statistiques
$current_month = date('Y-m');
$current_year = date('Y');

// CA total
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$user['id']]);
$total_revenue = $stmt->fetch()['total'];

// CA du mois
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE user_id = ? AND status = 'paid' AND DATE_FORMAT(paid_date, '%Y-%m') = ?");
$stmt->execute([$user['id'], $current_month]);
$month_revenue = $stmt->fetch()['total'];

// Clients actifs (ayant au moins une facture payée dans les 30 derniers jours)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT client_id) as count FROM invoices WHERE user_id = ? AND status = 'paid' AND paid_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stmt->execute([$user['id']]);
$active_clients = $stmt->fetch()['count'];

// Total clients
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM clients WHERE user_id = ?");
$stmt->execute([$user['id']]);
$total_clients = $stmt->fetch()['count'];

// Factures en attente
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND status IN ('draft', 'sent')");
$stmt->execute([$user['id']]);
$pending_invoices = $stmt->fetch()['count'];

// Dernières factures
$stmt = $pdo->prepare("SELECT i.*, c.name as client_name FROM invoices i LEFT JOIN clients c ON i.client_id = c.id WHERE i.user_id = ? ORDER BY i.created_at DESC LIMIT 5");
$stmt->execute([$user['id']]);
$recent_invoices = $stmt->fetchAll();

// Revenus par mois (12 derniers mois)
$stmt = $pdo->prepare("SELECT DATE_FORMAT(paid_date, '%Y-%m') as month, COALESCE(SUM(total_amount), 0) as total FROM invoices WHERE user_id = ? AND status = 'paid' AND paid_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY DATE_FORMAT(paid_date, '%Y-%m') ORDER BY month");
$stmt->execute([$user['id']]);
$monthly_revenue = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1><?php echo __('dashboard'); ?></h1>
        <p class="welcome-text"><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user['username']); ?> !</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon revenue">€</div>
            <div class="stat-content">
                <h3>Chiffre d'affaires</h3>
                <p class="stat-value"><?php echo number_format($total_revenue, 2, ',', ' '); ?> €</p>
                <p class="stat-label">Total</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon month">📅</div>
            <div class="stat-content">
                <h3>CA ce mois</h3>
                <p class="stat-value"><?php echo number_format($month_revenue, 2, ',', ' '); ?> €</p>
                <p class="stat-label"><?php echo date('F Y'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon clients">👥</div>
            <div class="stat-content">
                <h3>Clients actifs</h3>
                <p class="stat-value"><?php echo $active_clients; ?></p>
                <p class="stat-label">Sur <?php echo $total_clients; ?> clients</p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon pending">📋</div>
            <div class="stat-content">
                <h3>Factures en attente</h3>
                <p class="stat-value"><?php echo $pending_invoices; ?></p>
                <p class="stat-label">À traiter</p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2>Dernières factures</h2>
                <a href="invoices.php" class="btn btn-secondary btn-sm">Voir tout</a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Client</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_invoices)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucune facture</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</td>
                                    <td><span class="badge badge-<?php echo $invoice['status']; ?>"><?php echo ucfirst($invoice['status']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($invoice['issue_date'])); ?></td>
                                    <td>
                                        <a href="invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary">Voir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-section">
            <div class="section-header">
                <h2>Revenus mensuels</h2>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données pour le graphique
const monthlyData = <?php echo json_encode($monthly_revenue); ?>;
const labels = monthlyData.map(item => {
    const date = new Date(item.month + '-01');
    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
});
const data = monthlyData.map(item => parseFloat(item.total));

// Créer le graphique
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenus (€)',
                    data: data,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toFixed(0) + ' €';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

