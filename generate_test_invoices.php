<?php
/**
 * Script pour générer des factures aléatoires de test
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

$count = $_GET['count'] ?? 10; // Nombre de factures à générer (par défaut 10)
$count = min($count, 100); // Limiter à 100 maximum

// Récupérer les clients existants
$stmt = $pdo->prepare("SELECT id FROM clients WHERE user_id = ?");
$stmt->execute([$user['id']]);
$clients = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($clients)) {
    die("Erreur : Vous devez d'abord créer au moins un client.");
}

// Statuts possibles
$statuses = ['draft', 'sent', 'paid', 'overdue'];
$status_weights = [
    'draft' => 10,
    'sent' => 30,
    'paid' => 50,
    'overdue' => 10
];

// Méthodes de paiement
$payment_methods = ['stripe', 'paypal', 'bank', 'cash', 'other'];

// Titres de factures possibles
$titles = [
    'Développement web',
    'Design graphique',
    'Consultation',
    'Formation',
    'Maintenance',
    'Création de contenu',
    'Marketing digital',
    'SEO',
    'Photographie',
    'Vidéographie',
    'Rédaction',
    'Traduction',
    'Conseil stratégique',
    'Audit',
    'Intégration API'
];

$generated = 0;
$errors = [];

try {
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $count; $i++) {
        // Client aléatoire
        $client_id = $clients[array_rand($clients)];
        
        // Statut aléatoire (pondéré)
        $rand = rand(1, 100);
        $cumulative = 0;
        $status = 'draft';
        foreach ($status_weights as $stat => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                $status = $stat;
                break;
            }
        }
        
        // Montant aléatoire entre 50 et 5000 €
        $amount = round(rand(5000, 500000) / 100, 2);
        
        // Taux de TVA aléatoire (0%, 5.5%, 10%, 20%)
        $tax_rates = [0, 5.5, 10, 20];
        $tax_rate = $tax_rates[array_rand($tax_rates)];
        $tax_amount = round($amount * $tax_rate / 100, 2);
        $total_amount = round($amount + $tax_amount, 2);
        
        // Dates aléatoires (sur les 12 derniers mois)
        $days_ago = rand(0, 365);
        $issue_date = date('Y-m-d', strtotime("-{$days_ago} days"));
        
        // Date d'échéance (entre 15 et 60 jours après l'émission)
        $due_days = rand(15, 60);
        $due_date = date('Y-m-d', strtotime("{$issue_date} +{$due_days} days"));
        
        // Date de paiement si la facture est payée
        $paid_date = null;
        $payment_method = null;
        if ($status === 'paid') {
            // Paiement entre la date d'émission et aujourd'hui
            $paid_days_ago = rand(0, min($days_ago, 90));
            $paid_date = date('Y-m-d', strtotime("-{$paid_days_ago} days"));
            $payment_method = $payment_methods[array_rand($payment_methods)];
        }
        
        // Numéro de facture unique
        $year = date('Y', strtotime($issue_date));
        $month = date('m', strtotime($issue_date));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_number LIKE ?");
        $stmt->execute(["FACT-{$year}{$month}-%"]);
        $invoice_count = $stmt->fetchColumn();
        $invoice_number = sprintf("FACT-%s%s-%04d", $year, $month, $invoice_count + 1);
        
        // Vérifier que le numéro n'existe pas déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE invoice_number = ?");
        $stmt->execute([$invoice_number]);
        if ($stmt->fetchColumn() > 0) {
            $invoice_number = sprintf("FACT-%s%s-%04d", $year, $month, $invoice_count + 2);
        }
        
        // Titre et description
        $title = $titles[array_rand($titles)];
        $descriptions = [
            'Prestation de services professionnels',
            'Travaux réalisés selon devis',
            'Services de conseil et développement',
            'Prestation complète incluant suivi',
            'Mission de développement et intégration'
        ];
        $description = $descriptions[array_rand($descriptions)];
        
        // Insérer la facture
        $stmt = $pdo->prepare("INSERT INTO invoices (
            user_id, 
            client_id, 
            invoice_number, 
            title, 
            description, 
            amount, 
            tax_rate, 
            tax_amount, 
            total_amount, 
            status, 
            issue_date, 
            due_date, 
            paid_date, 
            payment_method
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt->execute([
            $user['id'],
            $client_id,
            $invoice_number,
            $title,
            $description,
            $amount,
            $tax_rate,
            $tax_amount,
            $total_amount,
            $status,
            $issue_date,
            $due_date,
            $paid_date,
            $payment_method
        ])) {
            $generated++;
        } else {
            $errors[] = "Erreur lors de la création de la facture #{$i}";
        }
    }
    
    $pdo->commit();
    
    $message = "✅ {$generated} facture(s) générée(s) avec succès !";
    if (!empty($errors)) {
        $message .= " " . count($errors) . " erreur(s).";
    }
    
    header("Location: invoices.php?success=" . urlencode($message));
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    header("Location: invoices.php?error=" . urlencode("Erreur : " . $e->getMessage()));
    exit;
}

