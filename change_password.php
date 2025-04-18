<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier si les nouveaux mots de passe correspondent
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Les nouveaux mots de passe ne correspondent pas.";
        header('Location: profile.php');
        exit();
    }

    // Récupérer le mot de passe actuel de l'utilisateur
    $query = "SELECT password FROM utilisateurs WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Vérifier si le mot de passe actuel est correct
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Le mot de passe actuel est incorrect.";
        header('Location: profile.php');
        exit();
    }

    // Hasher le nouveau mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Mettre à jour le mot de passe
    $update_query = "UPDATE utilisateurs SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Votre mot de passe a été modifié avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue lors du changement de mot de passe.";
    }
}

header('Location: profile.php');
exit(); 