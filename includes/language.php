<?php
/**
 * Système de gestion des langues
 */

// Langue par défaut
$default_language = 'fr';

// Récupérer la langue depuis les paramètres utilisateur ou session
$current_language = $default_language;

if (isset($user) && !empty($user['id'])) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/database.php';
        }
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE user_id = ? AND setting_key = 'project_language'");
        $stmt->execute([$user['id']]);
        $result = $stmt->fetch();
        if ($result && !empty($result['setting_value'])) {
            $current_language = $result['setting_value'];
        }
    } catch (Exception $e) {
        // En cas d'erreur, on garde la langue par défaut
    }
} elseif (isset($_SESSION['language'])) {
    $current_language = $_SESSION['language'];
}

// Charger les traductions
$translations = [];
$lang_file = __DIR__ . '/../lang/' . $current_language . '.php';

if (file_exists($lang_file)) {
    $translations = require $lang_file;
} else {
    // Fallback sur la langue par défaut si le fichier n'existe pas
    $lang_file = __DIR__ . '/../lang/' . $default_language . '.php';
    if (file_exists($lang_file)) {
        $translations = require $lang_file;
    }
}

/**
 * Fonction pour traduire une clé
 * 
 * @param string $key Clé de traduction
 * @param array $params Paramètres optionnels pour remplacer dans la traduction
 * @return string Texte traduit
 */
function __($key, $params = []) {
    global $translations;
    
    $text = $translations[$key] ?? $key;
    
    // Remplacer les paramètres si fournis
    if (!empty($params)) {
        foreach ($params as $param_key => $param_value) {
            $text = str_replace(':' . $param_key, $param_value, $text);
        }
    }
    
    return $text;
}

/**
 * Fonction pour obtenir la langue actuelle
 * 
 * @return string Code de la langue (fr, en, etc.)
 */
function getCurrentLanguage() {
    global $current_language;
    return $current_language;
}

/**
 * Fonction pour obtenir toutes les langues disponibles
 * 
 * @return array Liste des langues disponibles
 */
function getAvailableLanguages() {
    return [
        'fr' => 'Français',
        'en' => 'English',
        'es' => 'Español',
        'de' => 'Deutsch',
    ];
}

