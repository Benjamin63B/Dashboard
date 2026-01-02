<?php
/**
 * Gestion de la connexion à la base de données
 */

// Vérifier si config.php existe
$config_file = __DIR__ . '/../config.php';
if (!file_exists($config_file)) {
    // Récupérer le nom du script actuel (SCRIPT_NAME est plus fiable que PHP_SELF)
    $script_name = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    $current_script = basename($script_name);
    
    // Si on n'est pas déjà sur la page d'installation, rediriger
    if ($current_script !== 'install.php') {
        // Rediriger vers la page d'installation
        if (!headers_sent()) {
            header('Location: install.php');
            exit;
        } else {
            die('Le fichier config.php est introuvable. Veuillez accéder à <a href="install.php">install.php</a> pour configurer l\'application.');
        }
    }
    
    // Si on est sur install.php, on ne peut pas se connecter à la DB
    // On définit $pdo comme null pour éviter les erreurs (mais install.php ne devrait pas l'utiliser)
    $pdo = null;
    // Arrêter l'exécution de ce fichier car DB_HOST n'est pas défini
    return;
}

if (!defined('DB_HOST')) {
    require_once $config_file;
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

