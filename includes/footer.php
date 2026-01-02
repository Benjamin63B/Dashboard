    <?php
    // Récupérer le nom du projet depuis les paramètres
    $project_name = 'Dashboard Freelance';
    if (isset($user) && !empty($user['id'])) {
        try {
            if (!isset($pdo)) {
                require_once __DIR__ . '/database.php';
            }
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE user_id = ? AND setting_key = 'project_name'");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch();
            if ($result && !empty($result['setting_value'])) {
                $project_name = $result['setting_value'];
            }
        } catch (Exception $e) {
            // En cas d'erreur, on garde la valeur par défaut
        }
    }
    ?>
    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($project_name); ?> - <a href="https://github.com" target="_blank" rel="noopener noreferrer">Open Source</a></p>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>

