<?php
/**
 * Paramètres et configuration
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';
require_once 'includes/language.php';

$page_title = 'Paramètres';
$error = '';
$success = '';

// Récupérer les paramètres actuels
$stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ?");
$stmt->execute([$user['id']]);
$settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fonction helper pour récupérer une valeur de setting
function getSetting($key, $default = '') {
    global $settings_data;
    return $settings_data[$key] ?? $default;
}



// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? 'company';
    
    try {
        $pdo->beginTransaction();
        
        if ($form_type === 'company') {
            // Formulaire informations entreprise
            $project_name = trim($_POST['project_name'] ?? '');
            $project_favicon = trim($_POST['project_favicon'] ?? '📊');
            $project_theme_color = trim($_POST['project_theme_color'] ?? '#4f46e5');
            $project_language = trim($_POST['project_language'] ?? 'fr');
            $company_name = trim($_POST['company_name'] ?? '');
            
            // Valider la langue
            $available_languages = ['fr', 'en', 'es', 'de'];
            if (!in_array($project_language, $available_languages)) {
                $project_language = 'fr';
            }
            
            // Valider la couleur hexadécimale
            if (!empty($project_theme_color) && !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $project_theme_color)) {
                throw new Exception('La couleur du thème doit être une valeur hexadécimale valide (ex: #4f46e5).');
            }
            $company_address = trim($_POST['company_address'] ?? '');
            $company_city = trim($_POST['company_city'] ?? '');
            $company_postal = trim($_POST['company_postal'] ?? '');
            $company_country = trim($_POST['company_country'] ?? 'France');
            $company_phone = trim($_POST['company_phone'] ?? '');
            $company_email = trim($_POST['company_email'] ?? '');
            $company_siret = trim($_POST['company_siret'] ?? '');
            $company_website = trim($_POST['company_website'] ?? '');
            
            // Validation du format du SIRET/SIREN si fourni
            if (!empty($company_siret)) {
                // Valider le format (SIRET = 14 chiffres, SIREN = 9 chiffres)
                $company_siret = preg_replace('/[\s\-]/', '', $company_siret);
                
                if (!preg_match('/^\d{9,14}$/', $company_siret)) {
                    throw new Exception('Le SIRET/SIREN doit contenir entre 9 et 14 chiffres.');
                }
            }
            
            // Sauvegarder les informations entreprise
            $company_settings = [
                'project_name' => $project_name,
                'project_favicon' => $project_favicon,
                'project_theme_color' => $project_theme_color,
                'project_language' => $project_language,
                'company_name' => $company_name,
                'company_address' => $company_address,
                'company_city' => $company_city,
                'company_postal' => $company_postal,
                'company_country' => $company_country,
                'company_phone' => $company_phone,
                'company_email' => $company_email,
                'company_siret' => $company_siret,
                'company_website' => $company_website
            ];
            
            foreach ($company_settings as $key => $value) {
                $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute([$user['id'], $key, $value, $value]);
            }
            
            $success = 'Paramètres mis à jour avec succès !';
            
        } elseif ($form_type === 'payment') {
            // Formulaire intégrations paiement
            $stripe_public_key = trim($_POST['stripe_public_key'] ?? '');
            $stripe_secret_key = trim($_POST['stripe_secret_key'] ?? '');
            $paypal_client_id = trim($_POST['paypal_client_id'] ?? '');
            $paypal_secret = trim($_POST['paypal_secret'] ?? '');
            $paypal_mode = $_POST['paypal_mode'] ?? 'sandbox';
            
            // Sauvegarder les clés Stripe
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'stripe_public_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $stripe_public_key, $stripe_public_key]);
            
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'stripe_secret_key', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $stripe_secret_key, $stripe_secret_key]);
            
            // Sauvegarder les clés PayPal
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'paypal_client_id', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $paypal_client_id, $paypal_client_id]);
            
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'paypal_secret', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $paypal_secret, $paypal_secret]);
            
            $stmt = $pdo->prepare("INSERT INTO settings (user_id, setting_key, setting_value) VALUES (?, 'paypal_mode', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute([$user['id'], $paypal_mode, $paypal_mode]);
            
            $success = 'Paramètres mis à jour avec succès !';
        }
        
        $pdo->commit();
        
        // Recharger les paramètres
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $settings_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

require_once 'includes/header.php';
?>
<link rel="stylesheet" href="assets/css/settings.css">

<main class="dashboard-main">
    <div class="dashboard-header">
        <h1>Paramètres</h1>
        <p class="welcome-text">Configurez votre compte et vos intégrations</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="settings-container">
        <!-- Informations de l'entreprise -->
        <div class="settings-section">
            <h2>Informations de l'entreprise</h2>
            <p class="settings-description">
                Renseignez les informations de votre entreprise.
            </p>
            
            <form method="POST" class="settings-form">
                <input type="hidden" name="form_type" value="company">
                
                <div class="form-group">
                    <label for="project_name">Nom du projet *</label>
                    <input type="text" id="project_name" name="project_name" 
                           value="<?php echo htmlspecialchars(getSetting('project_name', 'Dashboard Freelance')); ?>" 
                           placeholder="Nom de votre application" required>
                    <small>Nom affiché dans le titre et la navigation</small>
                </div>
                
                <div class="form-group">
                    <label>Favicon du projet</label>
                    <div class="favicon-grid">
                        <?php
                        $favicons = [
                            '📊' => 'Graphique',
                            '💼' => 'Portefeuille',
                            '💰' => 'Argent',
                            '📈' => 'Graphique montant',
                            '📋' => 'Clipboard',
                            '🎯' => 'Cible',
                            '⚡' => 'Éclair',
                            '🚀' => 'Fusée',
                            '⭐' => 'Étoile',
                            '🔥' => 'Feu',
                            '💡' => 'Ampoule',
                            '🎨' => 'Palette',
                            '📱' => 'Smartphone',
                            '💻' => 'Ordinateur',
                            '🌐' => 'Globe',
                            '🏢' => 'Bâtiment'
                        ];
                        $current_favicon = getSetting('project_favicon', '📊');
                        foreach ($favicons as $emoji => $label):
                        ?>
                            <label class="favicon-option">
                                <input type="radio" name="project_favicon" value="<?php echo htmlspecialchars($emoji); ?>" 
                                       <?php echo $current_favicon === $emoji ? 'checked' : ''; ?>>
                                <div class="favicon-preview">
                                    <div class="favicon-emoji"><?php echo $emoji; ?></div>
                                    <div class="favicon-label"><?php echo htmlspecialchars($label); ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <small>Choisissez un favicon pour votre application</small>
                </div>
                
                <div class="form-group">
                    <label for="project_theme_color">Couleur du thème</label>
                    <div class="theme-color-picker">
                        <input type="color" id="project_theme_color" name="project_theme_color" 
                               value="<?php echo htmlspecialchars(getSetting('project_theme_color', '#4f46e5')); ?>"
                               style="width: 80px; height: 50px; border: 2px solid var(--border-color); border-radius: var(--radius); cursor: pointer;">
                        <input type="text" id="project_theme_color_text" 
                               value="<?php echo htmlspecialchars(getSetting('project_theme_color', '#4f46e5')); ?>"
                               pattern="^#[0-9A-Fa-f]{6}$"
                               placeholder="#4f46e5"
                               style="flex: 1; margin-left: 1rem;">
                    </div>
                    <small>Couleur principale du thème de votre application (utilisée pour les boutons, liens, etc.)</small>
                </div>
                
                <div class="form-group">
                    <label for="project_language">Langue de l'application</label>
                    <select id="project_language" name="project_language" required>
                        <?php
                        $languages = [
                            'fr' => 'Français',
                            'en' => 'English',
                            'es' => 'Español',
                            'de' => 'Deutsch'
                        ];
                        $current_lang = getSetting('project_language', 'fr');
                        foreach ($languages as $code => $name):
                        ?>
                            <option value="<?php echo $code; ?>" <?php echo $current_lang === $code ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small>Langue utilisée dans l'interface de l'application</small>
                </div>
                
                <div class="form-group">
                    <label for="company_name">Raison sociale *</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars(getSetting('company_name')); ?>" 
                           placeholder="Nom de votre entreprise" required>
                </div>
                
                <div class="form-group">
                    <label for="company_address">Adresse</label>
                    <input type="text" id="company_address" name="company_address" 
                           value="<?php echo htmlspecialchars(getSetting('company_address')); ?>" 
                           placeholder="Numéro et nom de rue">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_postal">Code postal</label>
                        <input type="text" id="company_postal" name="company_postal" 
                               value="<?php echo htmlspecialchars(getSetting('company_postal')); ?>" 
                               placeholder="75001" maxlength="10">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_city">Ville</label>
                        <input type="text" id="company_city" name="company_city" 
                               value="<?php echo htmlspecialchars(getSetting('company_city')); ?>" 
                               placeholder="Paris">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="company_country">Pays</label>
                    <input type="text" id="company_country" name="company_country" 
                           value="<?php echo htmlspecialchars(getSetting('company_country', 'France')); ?>" 
                           placeholder="France">
                </div>
                
                <div class="form-group">
                    <label for="company_siret">SIRET / SIREN</label>
                    <input type="text" id="company_siret" name="company_siret" 
                           value="<?php echo htmlspecialchars(getSetting('company_siret')); ?>" 
                           placeholder="12345678901234 (SIRET) ou 123456789 (SIREN)" 
                           pattern="[0-9\s\-]{9,20}">
                    <small>SIRET (14 chiffres) ou SIREN (9 chiffres)</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="company_phone">Téléphone</label>
                        <input type="tel" id="company_phone" name="company_phone" 
                               value="<?php echo htmlspecialchars(getSetting('company_phone')); ?>" 
                               placeholder="01 23 45 67 89">
                    </div>
                    
                    <div class="form-group">
                        <label for="company_email">Email</label>
                        <input type="email" id="company_email" name="company_email" 
                               value="<?php echo htmlspecialchars(getSetting('company_email')); ?>" 
                               placeholder="contact@entreprise.fr">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="company_website">Site web</label>
                    <input type="url" id="company_website" name="company_website" 
                           value="<?php echo htmlspecialchars(getSetting('company_website')); ?>" 
                           placeholder="https://www.entreprise.fr">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer les informations</button>
                </div>
            </form>
        </div>

        <!-- Intégrations de paiement -->
        <div class="settings-section">
            <h2>Intégrations de paiement</h2>
            <p class="settings-description">
                Configurez vos intégrations de paiement pour accepter les paiements en ligne.
            </p>
            
            <form method="POST" class="settings-form">
                <input type="hidden" name="form_type" value="payment">
                
                <div class="integration-subsection">
                    <h3>Stripe</h3>
                    <p class="settings-description">
                        Connectez votre compte Stripe pour accepter les paiements par carte bancaire.
                        Récupérez vos clés API sur le <a href="https://dashboard.stripe.com/apikeys" target="_blank">tableau de bord Stripe</a>.
                    </p>
                    
                    <div class="form-group">
                        <label for="stripe_public_key">Clé publique Stripe (Publishable Key)</label>
                        <input type="text" id="stripe_public_key" name="stripe_public_key" 
                               value="<?php echo htmlspecialchars(getSetting('stripe_public_key')); ?>" 
                               placeholder="pk_test_... ou pk_live_...">
                        <small>Commence par pk_test_ (test) ou pk_live_ (production)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="stripe_secret_key">Clé secrète Stripe (Secret Key)</label>
                        <input type="password" id="stripe_secret_key" name="stripe_secret_key" 
                               value="<?php echo htmlspecialchars(getSetting('stripe_secret_key')); ?>" 
                               placeholder="sk_test_... ou sk_live_...">
                        <small>Commence par sk_test_ (test) ou sk_live_ (production)</small>
                    </div>
                </div>
                
                <div class="integration-subsection" style="margin-top: 2rem;">
                    <h3>PayPal</h3>
                    <p class="settings-description">
                        Connectez votre compte PayPal pour accepter les paiements PayPal.
                        Créez une application sur le <a href="https://developer.paypal.com/dashboard/applications/sandbox" target="_blank">portail développeur PayPal</a>.
                    </p>
                    
                    <div class="form-group">
                        <label for="paypal_client_id">Client ID PayPal</label>
                        <input type="text" id="paypal_client_id" name="paypal_client_id" 
                               value="<?php echo htmlspecialchars(getSetting('paypal_client_id')); ?>" 
                               placeholder="Votre Client ID">
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_secret">Secret PayPal</label>
                        <input type="password" id="paypal_secret" name="paypal_secret" 
                               value="<?php echo htmlspecialchars(getSetting('paypal_secret')); ?>" 
                               placeholder="Votre Secret">
                    </div>
                    
                    <div class="form-group">
                        <label for="paypal_mode">Mode PayPal</label>
                        <select id="paypal_mode" name="paypal_mode">
                            <option value="sandbox" <?php echo getSetting('paypal_mode', 'sandbox') == 'sandbox' ? 'selected' : ''; ?>>Sandbox (Test)</option>
                            <option value="live" <?php echo getSetting('paypal_mode') == 'live' ? 'selected' : ''; ?>>Production (Live)</option>
                        </select>
                        <small>Utilisez Sandbox pour tester, Live pour la production</small>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer les paramètres de paiement</button>
                </div>
            </form>
        </div>

        <!-- Informations du compte -->
        <div class="settings-section">
            <h2>Informations du compte</h2>
            <div class="user-info">
                <div class="info-row">
                    <strong>Nom d'utilisateur:</strong>
                    <span><?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Email:</strong>
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-row">
                    <strong>Nom complet:</strong>
                    <span><?php echo htmlspecialchars($user['full_name'] ?: '-'); ?></span>
                </div>
                <div class="info-row">
                    <strong>Membre depuis:</strong>
                    <span><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>
</main>


<script>
// Synchroniser le color picker et le champ texte
document.addEventListener('DOMContentLoaded', function() {
    const colorPicker = document.getElementById('project_theme_color');
    const colorText = document.getElementById('project_theme_color_text');
    
    if (colorPicker && colorText) {
        // Quand le color picker change, mettre à jour le texte
        colorPicker.addEventListener('input', function() {
            colorText.value = this.value.toUpperCase();
        });
        
        // Quand le texte change, mettre à jour le color picker
        colorText.addEventListener('input', function() {
            const value = this.value.trim();
            if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                colorPicker.value = value;
            }
        });
        
        // Synchronisation initiale
        colorText.value = colorPicker.value.toUpperCase();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
