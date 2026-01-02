<?php
/**
 * Fonctions d'intégration Stripe
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Obtenir les clés Stripe de l'utilisateur
 */
function getStripeKeys($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ? AND setting_key IN ('stripe_public_key', 'stripe_secret_key')");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'public_key' => $settings['stripe_public_key'] ?? '',
        'secret_key' => $settings['stripe_secret_key'] ?? ''
    ];
}

/**
 * Vérifier si Stripe est configuré
 */
function isStripeConfigured($user_id) {
    $keys = getStripeKeys($user_id);
    return !empty($keys['public_key']) && !empty($keys['secret_key']);
}

/**
 * Créer un paiement Stripe
 * Note: Cette fonction nécessite la bibliothèque Stripe PHP
 * Pour l'installer: composer require stripe/stripe-php
 */
function createStripePayment($user_id, $amount, $currency = 'eur', $description = '') {
    $keys = getStripeKeys($user_id);
    
    if (empty($keys['secret_key'])) {
        return ['success' => false, 'error' => 'Stripe n\'est pas configuré'];
    }
    
    // Note: Nécessite la bibliothèque Stripe PHP
    // Exemple d'utilisation (à décommenter si vous installez stripe/stripe-php):
    /*
    try {
        \Stripe\Stripe::setApiKey($keys['secret_key']);
        
        $payment_intent = \Stripe\PaymentIntent::create([
            'amount' => $amount * 100, // Convertir en centimes
            'currency' => $currency,
            'description' => $description,
        ]);
        
        return [
            'success' => true,
            'client_secret' => $payment_intent->client_secret,
            'payment_intent_id' => $payment_intent->id
        ];
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
    */
    
    return ['success' => false, 'error' => 'Bibliothèque Stripe PHP non installée'];
}

