<?php
/**
 * Export PDF des factures
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';
require_once 'includes/language.php';

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

// Récupérer les informations de l'entreprise depuis les paramètres
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ? AND setting_key IN ('company_name', 'company_address', 'company_city', 'company_postal', 'company_country', 'company_email', 'company_phone', 'company_siret')");
$stmt->execute([$user['id']]);
$company_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Pour l'instant, on génère une page HTML imprimable
// L'utilisateur peut utiliser "Imprimer > Enregistrer en PDF" dans son navigateur
// Pour un vrai PDF, installer TCPDF via Composer: composer require tecnickcom/tcpdf
require_once 'includes/pdf_simple.php';
generateInvoicePDF_Simple($invoice, $user, $company_settings);

