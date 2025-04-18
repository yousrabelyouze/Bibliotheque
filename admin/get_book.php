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
    echo json_encode(['error' => 'ID de livre invalide']);
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération des données du livre
    $stmt = $pdo->prepare("SELECT * FROM livres WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $livre = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($livre) {
        echo json_encode($livre);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Livre non trouvé']);
    }
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 