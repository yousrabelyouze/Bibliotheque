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

// Vérifier que l'emprunt appartient bien à l'utilisateur et qu'il n'est pas en retard
$check_query = "SELECT e.*, l.titre FROM emprunts e 
               JOIN livres l ON e.livre_id = l.id 
               WHERE e.id = ? AND e.utilisateur_id = ? 
               AND e.date_retour IS NULL 
               AND e.date_retour_prevue >= CURDATE()";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $loan_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Emprunt non trouvé, déjà retourné ou en retard.";
    header('Location: mes-emprunts.php');
    exit;
}

$loan = $result->fetch_assoc();

// Vérifier si l'emprunt a déjà été prolongé
$check_extension = "SELECT COUNT(*) as nb_extensions 
                   FROM prolongations 
                   WHERE emprunt_id = ?";
$stmt = $conn->prepare($check_extension);
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$extension_result = $stmt->get_result();
$extension_count = $extension_result->fetch_assoc()['nb_extensions'];

if ($extension_count >= 2) {
    $_SESSION['error'] = "Vous avez déjà prolongé cet emprunt deux fois.";
    header('Location: mes-emprunts.php');
    exit;
}

// Prolonger l'emprunt de 15 jours
$update_query = "UPDATE emprunts 
                SET date_retour_prevue = DATE_ADD(date_retour_prevue, INTERVAL 15 DAY) 
                WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $loan_id);

if ($stmt->execute()) {
    // Enregistrer la prolongation
    $insert_extension = "INSERT INTO prolongations (emprunt_id, date_prolongation) 
                        VALUES (?, CURDATE())";
    $stmt = $conn->prepare($insert_extension);
    $stmt->bind_param("i", $loan_id);
    $stmt->execute();

    $_SESSION['success'] = "L'emprunt du livre \"" . htmlspecialchars($loan['titre']) . "\" a été prolongé de 15 jours.";
} else {
    $_SESSION['error'] = "Une erreur est survenue lors de la prolongation de l'emprunt.";
}

header('Location: mes-emprunts.php');
exit;
?> 