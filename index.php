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
        <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <p class="welcome-text" style="margin: 0;"><?php echo __('welcome'); ?>, <?php echo htmlspecialchars($user['username']); ?> !</p>
            <form method="GET" action="index.php" style="display: inline-block; margin: 0;" id="langForm">
                <?php
                // Préserver les autres paramètres GET
                foreach ($_GET as $key => $value) {
                    if ($key !== 'lang') {
                        echo '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
                    }
                }
                ?>
                <select name="lang" id="langSelect" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid var(--border-color); border-radius: var(--radius); background: var(--bg-color); color: var(--text-color); cursor: pointer;">
                    <?php
                    $languages = getAvailableLanguages();
                    $current_lang = getCurrentLanguage();
                    foreach ($languages as $code => $name):
                    ?>
                        <option value="<?php echo $code; ?>" <?php echo $current_lang === $code ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon revenue">€</div>
            <div class="stat-content">
                <h3><?php echo __('revenue'); ?></h3>
                <p class="stat-value"><?php echo number_format($total_revenue, 2, ',', ' '); ?> €</p>
                <p class="stat-label"><?php echo __('total'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon month">📅</div>
            <div class="stat-content">
                <h3><?php echo __('month_revenue'); ?></h3>
                <p class="stat-value"><?php echo number_format($month_revenue, 2, ',', ' '); ?> €</p>
                <p class="stat-label"><?php echo date('F Y'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon clients">👥</div>
            <div class="stat-content">
                <h3><?php echo __('active_clients'); ?></h3>
                <p class="stat-value"><?php echo $active_clients; ?></p>
                <p class="stat-label"><?php echo __('of'); ?> <?php echo $total_clients; ?> <?php echo __('clients'); ?></p>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon pending">📋</div>
            <div class="stat-content">
                <h3><?php echo __('pending_invoices'); ?></h3>
                <p class="stat-value"><?php echo $pending_invoices; ?></p>
                <p class="stat-label"><?php echo __('to_process'); ?></p>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-section">
            <div class="section-header">
                <h2><?php echo __('recent_invoices'); ?></h2>
                <a href="invoices.php" class="btn btn-secondary btn-sm"><?php echo __('see_all'); ?></a>
            </div>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th><?php echo __('invoice_number'); ?></th>
                            <th><?php echo __('client'); ?></th>
                            <th><?php echo __('amount'); ?></th>
                            <th><?php echo __('status'); ?></th>
                            <th><?php echo __('date'); ?></th>
                            <th><?php echo __('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_invoices)): ?>
                            <tr>
                                <td colspan="6" class="text-center"><?php echo __('no_invoices'); ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_invoices as $invoice): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                    <td><?php echo htmlspecialchars($invoice['client_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</td>
                                    <td><span class="badge badge-<?php echo $invoice['status']; ?>"><?php echo __('' . $invoice['status']); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($invoice['issue_date'])); ?></td>
                                    <td>
                                        <a href="invoice.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-primary"><?php echo __('view'); ?></a>
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
                <h2><?php echo __('monthly_revenue'); ?></h2>
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

