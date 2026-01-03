<?php
/**
 * Export CSV des clients
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

// Récupérer tous les clients
$stmt = $pdo->prepare("SELECT c.*, 
    (SELECT COUNT(*) FROM invoices WHERE client_id = c.id) as invoice_count,
    (SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE client_id = c.id AND status = 'paid') as total_paid
    FROM clients c 
    WHERE c.user_id = ? 
    ORDER BY c.name ASC");
$stmt->execute([$user['id']]);
$clients = $stmt->fetchAll();

// Définir les en-têtes pour le téléchargement CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="clients_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Créer un fichier de sortie
$output = fopen('php://output', 'w');

// Ajouter BOM pour UTF-8 (pour Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes CSV
$headers = [
    'Nom',
    'Email',
    'Téléphone',
    'Entreprise',
    'Adresse',
    'Moyen de paiement',
    'Nombre de factures',
    'Total payé (€)',
    'Notes',
    'Date de création'
];

fputcsv($output, $headers, ';');

// Données
foreach ($clients as $client) {
    $payment_methods_labels = [
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'bank' => 'Virement bancaire',
        'cash' => 'Espèces',
        'other' => 'Autre'
    ];
    
    $payment_label = $client['payment_method'] ? ($payment_methods_labels[$client['payment_method']] ?? ucfirst($client['payment_method'])) : '';
    
    $row = [
        $client['name'],
        $client['email'] ?? '',
        $client['phone'] ?? '',
        $client['company'] ?? '',
        $client['address'] ?? '',
        $payment_label,
        $client['invoice_count'],
        number_format($client['total_paid'], 2, ',', ''),
        $client['notes'] ?? '',
        date('d/m/Y', strtotime($client['created_at']))
    ];
    
    fputcsv($output, $row, ';');
}

fclose($output);
exit;

