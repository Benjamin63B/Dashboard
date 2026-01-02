<?php
if (!isset($user)) {
    $user = getCurrentUser();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    // Récupérer le nom du projet, le favicon et la couleur du thème depuis les paramètres
    $project_name = 'Dashboard Freelance';
    $project_favicon = '📊';
    $project_theme_color = '#4f46e5';
    if (isset($user) && !empty($user['id'])) {
        try {
            if (!isset($pdo)) {
                require_once __DIR__ . '/database.php';
            }
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE user_id = ? AND setting_key IN ('project_name', 'project_favicon', 'project_theme_color')");
            $stmt->execute([$user['id']]);
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (isset($settings['project_name']) && !empty($settings['project_name'])) {
                $project_name = $settings['project_name'];
            }
            if (isset($settings['project_favicon']) && !empty($settings['project_favicon'])) {
                $project_favicon = $settings['project_favicon'];
            }
            if (isset($settings['project_theme_color']) && !empty($settings['project_theme_color'])) {
                $project_theme_color = $settings['project_theme_color'];
            }
        } catch (Exception $e) {
            // En cas d'erreur, on garde les valeurs par défaut
        }
    }
    
    // Fonction pour assombrir une couleur hex
    function darkenColor($hex, $percent = 10) {
        $hex = str_replace('#', '', $hex);
        // Convertir 3 caractères en 6 si nécessaire (ex: fff -> ffffff)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $rgb = array_map('hexdec', str_split($hex, 2));
        foreach ($rgb as &$c) {
            $c = max(0, min(255, round($c * (1 - $percent / 100))));
        }
        return '#' . implode('', array_map(function($n) { return str_pad(dechex($n), 2, '0', STR_PAD_LEFT); }, $rgb));
    }
    
    // Fonction pour éclaircir une couleur hex
    function lightenColor($hex, $percent = 10) {
        $hex = str_replace('#', '', $hex);
        // Convertir 3 caractères en 6 si nécessaire (ex: fff -> ffffff)
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $rgb = array_map('hexdec', str_split($hex, 2));
        foreach ($rgb as &$c) {
            $c = max(0, min(255, round($c + (255 - $c) * ($percent / 100))));
        }
        return '#' . implode('', array_map(function($n) { return str_pad(dechex($n), 2, '0', STR_PAD_LEFT); }, $rgb));
    }
    
    $theme_color_dark = darkenColor($project_theme_color, 15);
    $theme_color_light = lightenColor($project_theme_color, 10);
    ?>
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($project_name); ?></title>
    
    <!-- Favicons dynamiques -->
    <?php
    $favicon_emoji = htmlspecialchars($project_favicon);
    $favicon_encoded = urlencode($favicon_emoji);
    ?>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E<?php echo $favicon_encoded; ?>%3C/text%3E%3C/svg%3E">
    <link rel="icon" type="image/png" sizes="32x32" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Ccircle cx='16' cy='16' r='14' fill='%234f46e5'/%3E%3Ctext x='16' y='22' font-size='18' text-anchor='middle' fill='white'%3E<?php echo $favicon_encoded; ?>%3C/text%3E%3C/svg%3E">
    <link rel="icon" type="image/png" sizes="16x16" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Ccircle cx='8' cy='8' r='7' fill='%234f46e5'/%3E%3Ctext x='8' y='12' font-size='9' text-anchor='middle' fill='white'%3E<?php echo $favicon_encoded; ?>%3C/text%3E%3C/svg%3E">
    <link rel="apple-touch-icon" sizes="180x180" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 180 180'%3E%3Crect width='180' height='180' rx='40' fill='%234f46e5'/%3E%3Ctext x='90' y='125' font-size='120' text-anchor='middle' fill='white'%3E<?php echo $favicon_encoded; ?>%3C/text%3E%3C/svg%3E">
    
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Thème personnalisé -->
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($project_theme_color); ?>;
            --primary-dark: <?php echo htmlspecialchars($theme_color_dark); ?>;
            --primary-light: <?php echo htmlspecialchars($theme_color_light); ?>;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo"><?php echo htmlspecialchars($project_name); ?></a>
            
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="clients.php" class="nav-link">Clients</a></li>
                <li><a href="invoices.php" class="nav-link">Factures</a></li>
                <li><a href="payments.php" class="nav-link">Paiements</a></li>
                <li><a href="settings.php" class="nav-link">Paramètres</a></li>
                <li>
                    <div class="nav-user">
                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                        <a href="logout.php" class="btn btn-sm btn-secondary">Déconnexion</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

