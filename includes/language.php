<?php
/**
 * Système de gestion des langues
 */

// Langue par défaut
$default_language = 'fr';

// Récupérer la langue depuis les paramètres utilisateur ou session
$current_language = $default_language;

// Vérifier si $user est défini, sinon essayer de le récupérer
if (!isset($user)) {
    if (function_exists('getCurrentUser')) {
        $user = getCurrentUser();
    }
}

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PRIORITÉ 1: Si la langue est passée en paramètre GET, elle a la priorité absolue
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en', 'es', 'de'])) {
    $current_language = $_GET['lang'];
    $_SESSION['language'] = $current_language;
    
    // Si un utilisateur est connecté, mettre à jour aussi la base de données
    if (isset($user) && !empty($user['id'])) {
        try {
            if (!isset($pdo)) {
                require_once __DIR__ . '/database.php';
            }
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'project_language', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $current_language, $current_language]);
        } catch (Exception $e) {
            // En cas d'erreur, on continue avec la langue de la session
        }
    }
} 
// PRIORITÉ 2: Si un utilisateur est connecté, récupérer sa langue depuis la base de données
elseif (isset($user) && !empty($user['id'])) {
    try {
        if (!isset($pdo)) {
            require_once __DIR__ . '/database.php';
        }
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE user_id = ? AND setting_key = 'project_language'");
        $stmt->execute([$user['id']]);
        $result = $stmt->fetch();
        if ($result && !empty($result['setting_value'])) {
            $current_language = $result['setting_value'];
            $_SESSION['language'] = $current_language;
        }
    } catch (Exception $e) {
        // En cas d'erreur, on garde la langue par défaut
    }
} 
// PRIORITÉ 3: Utiliser la langue de la session
elseif (isset($_SESSION['language'])) {
    $current_language = $_SESSION['language'];
}

// Mettre à jour la variable globale pour que getCurrentLanguage() fonctionne
$GLOBALS['current_language'] = $current_language;

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
    
    // Si les traductions ne sont pas chargées, les charger
    if (empty($translations)) {
        $current_lang = getCurrentLanguage();
        $lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
        if (file_exists($lang_file)) {
            $translations = require $lang_file;
        } else {
            // Fallback sur français
            $lang_file = __DIR__ . '/../lang/fr.php';
            if (file_exists($lang_file)) {
                $translations = require $lang_file;
            }
        }
    }
    
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
    // Vérifier aussi dans les GLOBALS au cas où
    if (isset($GLOBALS['current_language'])) {
        return $GLOBALS['current_language'];
    }
    return $current_language ?? 'fr';
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

