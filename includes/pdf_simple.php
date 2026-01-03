<?php
/**
 * Génération simple de PDF pour les factures
 * Utilise les fonctions natives de PHP pour créer un PDF basique
 */

function generateInvoicePDF_Simple($invoice, $user, $company_settings) {
    // Générer une page HTML imprimable
    // L'utilisateur peut utiliser "Imprimer > Enregistrer en PDF" dans son navigateur
    // Pour un vrai PDF automatique, installer TCPDF: composer require tecnickcom/tcpdf
    
    // Ne pas envoyer d'en-têtes PDF, mais HTML
    // Le contenu sera affiché et l'utilisateur pourra l'imprimer en PDF
    echo generateHTMLInvoice($invoice, $user, $company_settings);
}

function generateHTMLInvoice($invoice, $user, $company_settings) {
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?php echo htmlspecialchars($invoice['invoice_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
        }
        .header { 
            margin-bottom: 40px;
            border-bottom: 3px solid #4f46e5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4f46e5;
            font-size: 32px;
            margin-bottom: 20px;
        }
        .invoice-info { 
            float: right; 
            text-align: right;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        .invoice-info p {
            margin: 5px 0;
        }
        .parties { 
            display: flex; 
            justify-content: space-between; 
            margin: 40px 0;
            clear: both;
        }
        .party { 
            width: 48%;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .party h3 {
            color: #4f46e5;
            margin-bottom: 15px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 10px;
        }
        .party p {
            margin: 8px 0;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 30px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        th { 
            background-color: #4f46e5;
            color: white;
            font-weight: bold;
        }
        .text-right { 
            text-align: right; 
        }
        .total { 
            font-weight: bold; 
            font-size: 1.2em;
            background-color: #f0f0f0;
        }
        .invoice-title {
            margin: 30px 0;
            font-size: 20px;
            color: #333;
        }
        .invoice-description {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #4f46e5;
        }
        .invoice-paid-info {
            margin-top: 30px;
            padding: 15px;
            background: #d4edda;
            border-left: 4px solid #28a745;
            border-radius: 5px;
        }
        .no-print { 
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            background: #f0f0f0;
            border-radius: 5px;
        }
        .no-print button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }
        .no-print button:hover {
            background: #4338ca;
        }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
            .container { padding: 20px; }
            .parties { page-break-inside: avoid; }
            table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
    <div class="header">
        <h1>FACTURE</h1>
        <div class="invoice-info">
            <p><strong>Numéro:</strong> <?php echo htmlspecialchars($invoice['invoice_number']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y', strtotime($invoice['issue_date'])); ?></p>
            <?php if ($invoice['due_date']): ?>
                <p><strong>Échéance:</strong> <?php echo date('d/m/Y', strtotime($invoice['due_date'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="parties">
        <div class="party">
            <h3>Facturé par</h3>
            <p><strong><?php echo htmlspecialchars($company_settings['company_name'] ?? $user['full_name'] ?? $user['username']); ?></strong></p>
            <?php if (!empty($company_settings['company_address'])): ?>
                <p><?php echo nl2br(htmlspecialchars($company_settings['company_address'])); ?></p>
            <?php endif; ?>
            <?php if (!empty($company_settings['company_city'])): ?>
                <p><?php echo htmlspecialchars($company_settings['company_city']); ?>, <?php echo htmlspecialchars($company_settings['company_postal'] ?? ''); ?></p>
            <?php endif; ?>
            <?php if (!empty($company_settings['company_email'])): ?>
                <p>Email: <?php echo htmlspecialchars($company_settings['company_email']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="party">
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
        </div>
    </div>
    
    <?php if ($invoice['title']): ?>
        <h3><?php echo htmlspecialchars($invoice['title']); ?></h3>
    <?php endif; ?>
    
    <?php if ($invoice['description']): ?>
        <p><?php echo nl2br(htmlspecialchars($invoice['description'])); ?></p>
    <?php endif; ?>
    
    <table>
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
            <tr class="total">
                <td colspan="3" class="text-right"><strong>Total TTC</strong></td>
                <td class="text-right"><strong><?php echo number_format($invoice['total_amount'], 2, ',', ' '); ?> €</strong></td>
            </tr>
        </tfoot>
    </table>
    
    <?php if ($invoice['status'] == 'paid' && $invoice['paid_date']): ?>
        <p><strong>Payée le:</strong> <?php echo date('d/m/Y', strtotime($invoice['paid_date'])); ?></p>
    <?php endif; ?>
    
    <div class="no-print">
        <button onclick="window.print()">📄 Imprimer / Enregistrer en PDF</button>
        <p style="margin-top: 10px; color: #666; font-size: 14px;">
            Utilisez le bouton "Imprimer" de votre navigateur et sélectionnez "Enregistrer en PDF" comme destination.
        </p>
    </div>
    </div>
</body>
</html>
    <?php
}

function generatePDFContent($invoice, $user, $company_settings) {
    // Placeholder pour génération PDF
    return '';
}

