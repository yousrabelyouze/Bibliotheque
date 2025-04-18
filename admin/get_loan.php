<?php
header('Content-Type: application/json');

// Vérification de la session
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérification de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID emprunt invalide']);
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données de l'emprunt avec les informations du livre et de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT e.*, 
               l.titre as livre_titre, l.auteur as livre_auteur, l.isbn as livre_isbn,
               u.nom as utilisateur_nom, u.email as utilisateur_email, u.telephone as utilisateur_telephone
        FROM emprunts e
        JOIN livres l ON e.livre_id = l.id
        JOIN utilisateurs u ON e.utilisateur_id = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$_GET['id']]);
    $emprunt = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($emprunt) {
        echo json_encode($emprunt);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Emprunt non trouvé']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 