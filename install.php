<?php
/**
 * Script d'installation - Environnement local
 * Dashboard Universel pour Créateurs/Freelances
 * Installation en 3 étapes
 */

session_start();
$error = '';
$success = '';

// Vérifier si déjà installé
if (file_exists('config.php') && file_get_contents('config.php')) {
    $already_installed = true;
} else {
    $already_installed = false;
}

// Initialiser les données de session pour l'installation
if (!isset($_SESSION['install_data'])) {
    $_SESSION['install_data'] = [];
}

// Gérer la navigation entre les étapes
$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($current_step < 1 || $current_step > 3) {
    $current_step = 1;
}

// Étape 1 : Configuration de la base de données
if ($current_step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && !$already_installed) {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'cookyyfr_cms';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    if (empty($db_user) || empty($db_name)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            // Test de connexion à MySQL
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer la base de données
            try {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    throw $e;
                }
            }
            
            // Test de connexion à la base de données créée
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Sauvegarder les données dans la session
            $_SESSION['install_data']['db_host'] = $db_host;
            $_SESSION['install_data']['db_name'] = $db_name;
            $_SESSION['install_data']['db_user'] = $db_user;
            $_SESSION['install_data']['db_pass'] = $db_pass;
            
            // Rediriger vers l'étape 2
            header('Location: install.php?step=2');
            exit;
            
        } catch (PDOException $e) {
            $error = 'Erreur de base de données : ' . $e->getMessage();
            if (strpos($e->getMessage(), 'Access denied') !== false) {
                $error .= '<br><small>Vérifiez que l\'utilisateur MySQL a les droits nécessaires ou utilisez "root" avec un mot de passe vide (par défaut sur WAMP).</small>';
            }
        } catch (Exception $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }
}

// Étape 2 : Création du compte administrateur
if ($current_step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && !$already_installed) {
    $admin_username = $_POST['admin_username'] ?? '';
    $admin_email = $_POST['admin_email'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    
    if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (strlen($admin_password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'L\'adresse email n\'est pas valide.';
    } else {
        // Vérifier que les données de l'étape 1 sont présentes
        if (empty($_SESSION['install_data']['db_host'])) {
            $error = 'Les données de la base de données sont manquantes. Veuillez recommencer depuis l\'étape 1.';
            $current_step = 1;
        } else {
            // Sauvegarder les données dans la session
            $_SESSION['install_data']['admin_username'] = $admin_username;
            $_SESSION['install_data']['admin_email'] = $admin_email;
            $_SESSION['install_data']['admin_password'] = $admin_password;
            
            // Rediriger vers l'étape 3
            header('Location: install.php?step=3');
            exit;
        }
    }
}

// Étape 3 : Vérification et finalisation
if ($current_step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST' && !$already_installed) {
    // Vérifier que toutes les données sont présentes
    if (empty($_SESSION['install_data']['db_host']) || empty($_SESSION['install_data']['admin_username'])) {
        $error = 'Des données sont manquantes. Veuillez recommencer depuis l\'étape 1.';
        $current_step = 1;
    } else {
        try {
            $db_host = $_SESSION['install_data']['db_host'];
            $db_name = $_SESSION['install_data']['db_name'];
            $db_user = $_SESSION['install_data']['db_user'];
            $db_pass = $_SESSION['install_data']['db_pass'];
            $admin_username = $_SESSION['install_data']['admin_username'];
            $admin_email = $_SESSION['install_data']['admin_email'];
            $admin_password = $_SESSION['install_data']['admin_password'];
            
            // Se connecter à la base de données
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Créer les tables
            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                phone VARCHAR(50),
                company VARCHAR(255),
                address TEXT,
                payment_method ENUM('stripe', 'paypal', 'bank', 'cash', 'other') DEFAULT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS invoices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                client_id INT NOT NULL,
                invoice_number VARCHAR(100) NOT NULL UNIQUE,
                title VARCHAR(255),
                description TEXT,
                amount DECIMAL(10, 2) NOT NULL,
                tax_rate DECIMAL(5, 2) DEFAULT 0,
                tax_amount DECIMAL(10, 2) DEFAULT 0,
                total_amount DECIMAL(10, 2) NOT NULL,
                status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
                issue_date DATE NOT NULL,
                due_date DATE,
                paid_date DATE,
                payment_method ENUM('stripe', 'paypal', 'bank', 'cash', 'other') DEFAULT NULL,
                payment_id VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_client_id (client_id),
                INDEX idx_status (status),
                INDEX idx_issue_date (issue_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                invoice_id INT,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method ENUM('stripe', 'paypal', 'bank', 'cash', 'other') NOT NULL,
                payment_id VARCHAR(255),
                transaction_id VARCHAR(255),
                status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
                payment_date DATE NOT NULL,
                notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL,
                INDEX idx_user_id (user_id),
                INDEX idx_payment_date (payment_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_user_setting (user_id, setting_key),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Vérifier que les tables ont bien été créées
            $tables = ['users', 'clients', 'invoices', 'payments', 'settings'];
            foreach ($tables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() == 0) {
                    throw new Exception("La table '$table' n'a pas été créée.");
                }
            }
            
            // Créer le fichier config.php
            $db_host_escaped = addslashes($db_host);
            $db_name_escaped = addslashes($db_name);
            $db_user_escaped = addslashes($db_user);
            $db_pass_escaped = addslashes($db_pass);
            
            $config_content = "<?php
/**
 * Configuration de l'application
 * Généré automatiquement par le script d'installation
 */

// Configuration de la base de données
define('DB_HOST', '$db_host_escaped');
define('DB_NAME', '$db_name_escaped');
define('DB_USER', '$db_user_escaped');
define('DB_PASS', '$db_pass_escaped');
define('DB_CHARSET', 'utf8mb4');

// Configuration de l'application
define('APP_NAME', 'Dashboard Freelance');
define('APP_URL', 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']));
define('TIMEZONE', 'Europe/Paris');

// Configuration de sécurité
define('SESSION_LIFETIME', 3600); // 1 heure en secondes

// Configuration du fuseau horaire
date_default_timezone_set(TIMEZONE);
";
            
            if (file_put_contents('config.php', $config_content) === false) {
                throw new Exception('Impossible d\'écrire le fichier config.php. Vérifiez les permissions d\'écriture.');
            }
            
            // Vérifier si l'utilisateur admin existe déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$admin_username, $admin_email]);
            if ($stmt->fetch()) {
                throw new Exception('Un utilisateur avec ce nom d\'utilisateur ou cet email existe déjà.');
            }
            
            // Créer le compte administrateur
            $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_username, $admin_email, $hashed_password, 'Administrateur']);
            
            // Nettoyer les données de session
            unset($_SESSION['install_data']);
            
            // Rediriger vers la page de login
            header('Location: login.php?installed=1');
            exit;
            
        } catch (PDOException $e) {
            $error = 'Erreur de base de données : ' . $e->getMessage();
        } catch (Exception $e) {
            $error = 'Erreur : ' . $e->getMessage();
        }
    }
}

// Récupérer les données de session pour pré-remplir les formulaires
$install_data = $_SESSION['install_data'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - Dashboard Freelance</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/install.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-box install-box">
            <div class="install-header">
                <h1>Installation</h1>
                <p class="subtitle">Configuration de votre Dashboard Freelance</p>
            </div>
            
            <?php if ($already_installed): ?>
                <div class="alert alert-warning">
                    <strong>⚠ Installation détectée</strong><br>
                    L'application est déjà installée. Pour réinstaller, supprimez le fichier <code>config.php</code> puis rafraîchissez cette page.
                </div>
                <div style="margin-top: 1.5rem;">
                    <a href="login.php" class="btn btn-primary">Aller à la connexion</a>
                    <a href="index.php" class="btn" style="margin-left: 0.5rem;">Retour à l'accueil</a>
                </div>
            <?php else: ?>
                <!-- Indicateur d'étapes -->
                <div class="install-steps">
                    <div class="step-indicator <?php echo $current_step >= 1 ? 'active' : ''; ?> <?php echo $current_step > 1 ? 'completed' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-label">Base de données</div>
                    </div>
                    <div class="step-connector <?php echo $current_step > 1 ? 'active' : ''; ?>"></div>
                    <div class="step-indicator <?php echo $current_step >= 2 ? 'active' : ''; ?> <?php echo $current_step > 2 ? 'completed' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-label">Compte admin</div>
                    </div>
                    <div class="step-connector <?php echo $current_step > 2 ? 'active' : ''; ?>"></div>
                    <div class="step-indicator <?php echo $current_step >= 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-label">Vérification</div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Étape 1 : Configuration base de données -->
                <?php if ($current_step === 1): ?>
                    <form method="POST" class="auth-form">
                        <h2>Configuration de la base de données</h2>
                        
                        <div class="form-group">
                            <label for="db_host">Hôte de la base de données</label>
                            <input type="text" id="db_host" name="db_host" 
                                   value="<?php echo htmlspecialchars($install_data['db_host'] ?? 'localhost'); ?>" required>
                            <small>Généralement "localhost" en local</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name">Nom de la base de données</label>
                            <input type="text" id="db_name" name="db_name" 
                                   value="<?php echo htmlspecialchars($install_data['db_name'] ?? 'cookyyfr_cms'); ?>" required>
                            <small>La base sera créée automatiquement si elle n'existe pas</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_user">Utilisateur MySQL</label>
                            <input type="text" id="db_user" name="db_user" 
                                   value="<?php echo htmlspecialchars($install_data['db_user'] ?? 'root'); ?>" required>
                            <small>Par défaut "root" sur WAMP</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Mot de passe MySQL</label>
                            <input type="password" id="db_pass" name="db_pass" 
                                   value="<?php echo htmlspecialchars($install_data['db_pass'] ?? ''); ?>">
                            <small>Laissez vide si vous utilisez les paramètres par défaut de WAMP</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">Suivant →</button>
                    </form>
                
                <!-- Étape 2 : Création compte administrateur -->
                <?php elseif ($current_step === 2): ?>
                    <form method="POST" class="auth-form">
                        <h2>Création du compte administrateur</h2>
                        <p class="form-description">Créez le compte qui vous permettra de vous connecter à l'application.</p>
                        
                        <div class="form-group">
                            <label for="admin_username">Nom d'utilisateur</label>
                            <input type="text" id="admin_username" name="admin_username" 
                                   value="<?php echo htmlspecialchars($install_data['admin_username'] ?? ''); ?>" 
                                   required autocomplete="username">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">Email</label>
                            <input type="email" id="admin_email" name="admin_email" 
                                   value="<?php echo htmlspecialchars($install_data['admin_email'] ?? ''); ?>" 
                                   required autocomplete="email">
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Mot de passe</label>
                            <input type="password" id="admin_password" name="admin_password" 
                                   required minlength="6" autocomplete="new-password">
                            <small>Minimum 6 caractères</small>
                        </div>
                        
                        <div class="form-actions">
                            <a href="install.php?step=1" class="btn">← Précédent</a>
                            <button type="submit" class="btn btn-primary">Suivant →</button>
                        </div>
                    </form>
                
                <!-- Étape 3 : Vérification -->
                <?php elseif ($current_step === 3): ?>
                    <div class="verification-step">
                        <h2>Vérification de la configuration</h2>
                        <p class="form-description">Vérifiez les informations ci-dessous avant de finaliser l'installation.</p>
                        
                        <div class="verification-summary">
                            <div class="summary-section">
                                <h3>Base de données</h3>
                                <div class="summary-item">
                                    <strong>Hôte :</strong> <?php echo htmlspecialchars($install_data['db_host'] ?? 'N/A'); ?>
                                </div>
                                <div class="summary-item">
                                    <strong>Base de données :</strong> <?php echo htmlspecialchars($install_data['db_name'] ?? 'N/A'); ?>
                                </div>
                                <div class="summary-item">
                                    <strong>Utilisateur :</strong> <?php echo htmlspecialchars($install_data['db_user'] ?? 'N/A'); ?>
                                </div>
                            </div>
                            
                            <div class="summary-section">
                                <h3>Compte administrateur</h3>
                                <div class="summary-item">
                                    <strong>Nom d'utilisateur :</strong> <?php echo htmlspecialchars($install_data['admin_username'] ?? 'N/A'); ?>
                                </div>
                                <div class="summary-item">
                                    <strong>Email :</strong> <?php echo htmlspecialchars($install_data['admin_email'] ?? 'N/A'); ?>
                                </div>
                            </div>
                        </div>
                        
                        <form method="POST" class="verification-form">
                            <div class="form-actions">
                                <a href="install.php?step=2" class="btn">← Précédent</a>
                                <button type="submit" class="btn btn-primary btn-large">Terminer l'installation</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
