<?php
/**
 * Script pour générer un client aléatoire de test
 */

require_once 'includes/auth.php';
requireLogin();

$user = getCurrentUser();
require_once 'includes/database.php';

// Prénoms et noms aléatoires
$first_names = [
    'Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas', 'Julie', 'Nicolas', 'Camille',
    'Antoine', 'Laura', 'Alexandre', 'Emma', 'Julien', 'Léa', 'Maxime', 'Chloé',
    'David', 'Sarah', 'Vincent', 'Manon', 'Paul', 'Clara', 'Lucas', 'Inès',
    'Matthieu', 'Élise', 'Romain', 'Pauline', 'Baptiste', 'Marion', 'Florian', 'Anaïs'
];

$last_names = [
    'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand',
    'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David',
    'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Lefevre',
    'Mercier', 'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez', 'Legrand', 'Garnier'
];

// Noms d'entreprises
$companies = [
    'Tech Solutions', 'Digital Agency', 'Web Studio', 'Creative Lab', 'Innovation Corp',
    'Business Partners', 'Consulting Group', 'Design Studio', 'Marketing Pro', 'Dev Team',
    'Startup Inc', 'Media Group', 'Services Plus', 'Expert Consulting', 'Pro Solutions',
    'Global Services', 'Premium Agency', 'Elite Consulting', 'Smart Solutions', 'Next Level'
];

// Villes françaises
$cities = [
    'Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier',
    'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon', 'Grenoble',
    'Dijon', 'Angers', 'Nîmes', 'Villeurbanne', 'Saint-Denis', 'Le Mans', 'Aix-en-Provence',
    'Clermont-Ferrand', 'Brest', 'Limoges', 'Tours', 'Amiens', 'Perpignan', 'Metz'
];

// Méthodes de paiement
$payment_methods = ['stripe', 'paypal', 'bank', 'cash', 'other', null];

// Générer des données aléatoires
$first_name = $first_names[array_rand($first_names)];
$last_name = $last_names[array_rand($last_names)];
$name = $first_name . ' ' . $last_name;

// 70% de chance d'avoir une entreprise
$has_company = rand(1, 100) <= 70;
$company = $has_company ? $companies[array_rand($companies)] : null;

// Email basé sur le nom
$email_domains = ['gmail.com', 'outlook.com', 'yahoo.fr', 'hotmail.com', 'company.fr', 'business.com'];
$email = strtolower($first_name . '.' . $last_name . '@' . $email_domains[array_rand($email_domains)]);
$email = str_replace(' ', '', $email);
$email = iconv('UTF-8', 'ASCII//TRANSLIT', $email); // Enlever les accents pour l'email

// Vérifier que l'email n'existe pas déjà
$stmt = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE email = ? AND user_id = ?");
$stmt->execute([$email, $user['id']]);
if ($stmt->fetchColumn() > 0) {
    // Ajouter un numéro si l'email existe déjà
    $email = str_replace('@', rand(1, 999) . '@', $email);
}

// Téléphone français aléatoire
$phone = '0' . rand(1, 9) . ' ' . str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT) . ' ' . 
         str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT) . ' ' . 
         str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT) . ' ' . 
         str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);

// Adresse aléatoire
$streets = ['Rue', 'Avenue', 'Boulevard', 'Place', 'Impasse', 'Chemin'];
$street_names = [
    'de la République', 'Victor Hugo', 'de la Paix', 'des Champs-Élysées', 'de la Liberté',
    'Gambetta', 'Jean Jaurès', 'de la Gare', 'du Commerce', 'de l\'Église', 'Pasteur',
    'Voltaire', 'Rousseau', 'Descartes', 'Molière', 'Shakespeare', 'Dante', 'Goethe'
];
$street_number = rand(1, 200);
$street = $street_number . ' ' . $streets[array_rand($streets)] . ' ' . $street_names[array_rand($street_names)];
$city = $cities[array_rand($cities)];
$postal = str_pad(rand(1000, 99999), 5, '0', STR_PAD_LEFT);
$address = $street . "\n" . $postal . ' ' . $city;

// Méthode de paiement préférée
$payment_method = $payment_methods[array_rand($payment_methods)];

// Notes aléatoires (30% de chance)
$notes = null;
if (rand(1, 100) <= 30) {
    $notes_options = [
        'Client fidèle depuis plusieurs années',
        'Préfère être contacté par email',
        'Paiement généralement effectué dans les délais',
        'Client professionnel, très réactif',
        'Demande des devis détaillés',
        'Paiement par virement bancaire préféré',
        'Client ponctuel et sérieux',
        'Contact préféré : téléphone le matin'
    ];
    $notes = $notes_options[array_rand($notes_options)];
}

try {
    // Insérer le client
    $stmt = $pdo->prepare("INSERT INTO clients (
        user_id, 
        name, 
        email, 
        phone, 
        company, 
        address, 
        payment_method, 
        notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt->execute([
        $user['id'],
        $name,
        $email,
        $phone,
        $company,
        $address,
        $payment_method,
        $notes
    ])) {
        $client_id = $pdo->lastInsertId();
        $message = "✅ Client créé avec succès : " . htmlspecialchars($name);
        if ($company) {
            $message .= " (" . htmlspecialchars($company) . ")";
        }
        header("Location: clients.php?success=" . urlencode($message));
        exit;
    } else {
        throw new Exception("Erreur lors de l'insertion du client.");
    }
    
} catch (Exception $e) {
    header("Location: clients.php?error=" . urlencode("Erreur : " . $e->getMessage()));
    exit;
}

