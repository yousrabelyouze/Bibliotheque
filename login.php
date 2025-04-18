<?php
session_start();

// Connexion à la base de données
$host = 'localhost';
$dbname = 'bibliotheque';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérification du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $mot_de_passe = $_POST['password'];

    // Requête SQL
    $sql = "SELECT * FROM utilisateurs WHERE email = :email AND mot_de_passe = :mot_de_passe";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':mot_de_passe', $mot_de_passe);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Connexion réussie
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Redirection
        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
header("Location: index.php");
        }
        exit();
    } else {
        // Mauvais identifiants
        echo "<script>alert('Email ou mot de passe incorrect.'); window.location.href='login.php';</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I.N.S.F.P BEB - Connexion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="guest-mode">
    <nav class="navbar">
        <div class="container nav-container">
<a href="index.php" class="logo">
                <i class="fas fa-book-open"></i>
                <span>I.N.S.F.P BEB</span>
            </a>
            <button class="mobile-menu-btn" aria-label="Menu de navigation">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links">
                <a href="index.php">Accueil</a>
                <a href="livres.php">Catalogue</a>
                <a href="mes-emprunts.php" class="user-only">Mes emprunts</a>
                <a href="profile.php" class="user-only">Mon profil</a>
                <a href="admin/dashboard.php" class="admin-only">Administration</a>
                <a href="login.php" class="guest-only active">Connexion</a>
<a href="register.php" class="guest-only">Inscription</a>
                <button class="btn-logout user-only" title="Se déconnecter">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </div>
        </div>
    </nav>

    <main class="container main-content">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="text-center mb-4">Connexion</h1>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div style="color: red; text-align: center; margin-bottom: 15px;">
                        <?= $_SESSION['login_error']; ?>
                        <?php unset($_SESSION['login_error']); ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required 
                               class="form-control" placeholder="Entrez votre email">
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required 
                               class="form-control" placeholder="Entrez votre mot de passe">
                    </div>
                    <div class="form-group">
                        <label class="checkbox-container">
                            <input type="checkbox" name="remember">
                            <span class="checkmark"></span>
                            Se souvenir de moi
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
                </form>

                <div class="auth-footer">
                    <p><a href="#" class="forgot-password">Mot de passe oublié ?</a></p>
<p>Pas encore membre ? <a href="register.php">Inscrivez-vous</a></p>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>I.N.S.F.P BEB</h3>
                    <p>Notre mission est de rendre la connaissance accessible à tous à travers notre vaste collection de livres.</p>
                </div>
                <div class="footer-section">
                    <h3>Horaires d'ouverture</h3>
                    <ul class="hours-list">
                        <li>Dimanche - Jeudi : 8h - 16h30</li>
                        <li>Vendredi - Samedi : Fermé</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact</h3>
                    <ul class="contact-list">
                        <li><i class="fas fa-map-marker-alt"></i> Bordj El Bahri, Alger, Algérie</li>
                        <li><i class="fas fa-phone"></i> +213 XX XX XX XX</li>
                        <li><i class="fas fa-envelope"></i> contact@insfp-beb.dz</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Suivez-nous</h3>
                    <div class="social-links">
                        <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-link" title="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 I.N.S.F.P BEB. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
