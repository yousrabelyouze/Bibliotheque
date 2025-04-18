<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de l'emprunt est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: mes-emprunts.php');
    exit;
}

$loan_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Vérifier que l'emprunt appartient bien à l'utilisateur
$check_query = "SELECT e.*, l.titre FROM emprunts e 
               JOIN livres l ON e.livre_id = l.id 
               WHERE e.id = ? AND e.utilisateur_id = ? AND e.date_retour IS NULL";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $loan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Emprunt non trouvé ou déjà retourné.";
    header('Location: mes-emprunts.php');
    exit;
}

$loan = $result->fetch_assoc();

// Mettre à jour l'emprunt avec la date de retour
$update_query = "UPDATE emprunts SET date_retour = CURDATE() WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $loan_id);

if ($stmt->execute()) {
    // Mettre à jour le statut du livre
    $update_book = "UPDATE livres SET disponible = 1 WHERE id = ?";
    $stmt = $conn->prepare($update_book);
    $stmt->bind_param("i", $loan['livre_id']);
    $stmt->execute();

    $_SESSION['success'] = "Le livre \"" . htmlspecialchars($loan['titre']) . "\" a été retourné avec succès.";
} else {
    $_SESSION['error'] = "Une erreur est survenue lors du retour du livre.";
}

header('Location: mes-emprunts.php');
exit;
?> 