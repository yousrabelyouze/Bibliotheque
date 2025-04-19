<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour emprunter un livre.";
    header("Location: login.php");
    exit();
}

// Vérifier si l'ID du livre est fourni
if (!isset($_GET['livre_id'])) {
    $_SESSION['error'] = "Aucun livre spécifié.";
    header("Location: livres.php");
    exit();
}

$livre_id = $_GET['livre_id'];
$user_id = $_SESSION['user_id'];

try {
    // Vérifier le nombre d'emprunts en cours de l'utilisateur
    $stmt = $conn->prepare("SELECT COUNT(*) as nb_emprunts FROM emprunts WHERE utilisateur_id = ? AND statut = 'en_cours'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $emprunts = $result->fetch_assoc();

    if ($emprunts['nb_emprunts'] >= 3) {
        $_SESSION['error'] = "Vous avez déjà atteint la limite de 3 emprunts simultanés.";
        header("Location: livres.php");
        exit();
    }

    // Vérifier si l'utilisateur n'a pas déjà emprunté ce livre
    $stmt = $conn->prepare("SELECT id FROM emprunts WHERE livre_id = ? AND utilisateur_id = ? AND statut = 'en_cours'");
    $stmt->bind_param("ii", $livre_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Vous avez déjà emprunté ce livre.";
        header("Location: livres.php");
        exit();
    }

    // Vérifier si le livre est disponible
    $stmt = $conn->prepare("SELECT titre, statut FROM livres WHERE id = ?");
    $stmt->bind_param("i", $livre_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $livre = $result->fetch_assoc();

    if (!$livre) {
        $_SESSION['error'] = "Le livre demandé n'existe pas.";
        header("Location: livres.php");
        exit();
    }

    if ($livre['statut'] !== 'Disponible') {
        $_SESSION['error'] = "Désolé, le livre \"" . htmlspecialchars($livre['titre']) . "\" n'est pas disponible pour l'emprunt.";
        header("Location: livres.php");
        exit();
    }

    // Vérifier si l'utilisateur n'a pas de retard sur ses emprunts actuels
    $stmt = $conn->prepare("SELECT COUNT(*) as retards FROM emprunts 
                           WHERE utilisateur_id = ? 
                           AND statut = 'en_cours' 
                           AND date_retour_prevue < CURRENT_DATE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $retards = $result->fetch_assoc();

    if ($retards['retards'] > 0) {
        $_SESSION['error'] = "Vous avez des emprunts en retard. Veuillez les retourner avant d'emprunter un nouveau livre.";
        header("Location: livres.php");
        exit();
    }

    // Commencer une transaction
    $conn->begin_transaction();
    $transaction_started = true;

    try {
        // Créer l'emprunt
        $date_emprunt = date('Y-m-d');
        $date_retour = date('Y-m-d', strtotime('+15 days'));
        
        $stmt = $conn->prepare("INSERT INTO emprunts (livre_id, utilisateur_id, date_emprunt, date_retour_prevue, statut) VALUES (?, ?, ?, ?, 'en_cours')");
        $stmt->bind_param("iiss", $livre_id, $user_id, $date_emprunt, $date_retour);
        $stmt->execute();

        // Mettre à jour le statut du livre
        $stmt = $conn->prepare("UPDATE livres SET statut = 'Emprunté' WHERE id = ?");
        $stmt->bind_param("i", $livre_id);
        $stmt->execute();

        // Valider la transaction
        $conn->commit();
        $transaction_started = false;

        $_SESSION['success'] = "Vous avez emprunté \"" . htmlspecialchars($livre['titre']) . "\". Date de retour prévue : " . date('d/m/Y', strtotime($date_retour));
        header("Location: mes-emprunts.php");
        exit();

    } catch (Exception $e) {
        // En cas d'erreur pendant la transaction
        if ($transaction_started) {
            $conn->rollback();
        }
        throw $e;
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Une erreur est survenue lors de l'emprunt : " . $e->getMessage();
    header("Location: livres.php");
    exit();
}
?> 