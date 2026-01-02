<?php
/**
 * Fonctions d'intégration PayPal
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';

/**
 * Obtenir les paramètres PayPal de l'utilisateur
 */
function getPayPalSettings($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ? AND setting_key IN ('paypal_client_id', 'paypal_secret', 'paypal_mode')");
    $stmt->execute([$user_id]);
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    return [
        'client_id' => $settings['paypal_client_id'] ?? '',
        'secret' => $settings['paypal_secret'] ?? '',
        'mode' => $settings['paypal_mode'] ?? 'sandbox'
    ];
}

/**
 * Vérifier si PayPal est configuré
 */
function isPayPalConfigured($user_id) {
    $settings = getPayPalSettings($user_id);
    return !empty($settings['client_id']) && !empty($settings['secret']);
}

/**
 * Obtenir l'URL de l'API PayPal selon le mode
 */
function getPayPalApiUrl($mode = 'sandbox') {
    if ($mode === 'live') {
        return 'https://api.paypal.com';
    }
    return 'https://api.sandbox.paypal.com';
}

/**
 * Obtenir un token d'accès PayPal
 * Note: Cette fonction nécessite cURL
 */
function getPayPalAccessToken($user_id) {
    $settings = getPayPalSettings($user_id);
    
    if (empty($settings['client_id']) || empty($settings['secret'])) {
        return ['success' => false, 'error' => 'PayPal n\'est pas configuré'];
    }
    
    $api_url = getPayPalApiUrl($settings['mode']);
    $url = $api_url . '/v1/oauth2/token';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $settings['client_id'] . ':' . $settings['secret']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        return ['success' => true, 'access_token' => $data['access_token'] ?? ''];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'obtention du token PayPal'];
}

