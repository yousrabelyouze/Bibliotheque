<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>I.N.S.F.P BEB - Accueil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/home.css">
<link rel="stylesheet" href="css/auth.css">
<link rel="stylesheet" href="css/components.css">
<link rel="stylesheet" href="css/books.css">
<link rel="stylesheet" href="css/admin.css">
</head>
<body class="<?php echo isset($_SESSION['user_id']) ? 'user-mode' : 'guest-mode'; ?>">
    <!-- Navigation -->
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
            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Liens pour les visiteurs -->
<a href="index.php" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>Accueil</a>
                <a href="livres.php" <?php echo $current_page === 'livres.php' ? 'class="active"' : ''; ?>>Catalogue</a>
                <a href="login.php" <?php echo $current_page === 'login.php' ? 'class="active"' : ''; ?>>Connexion</a>
                <a href="register.php" <?php echo $current_page === 'register.php' ? 'class="active"' : ''; ?>>Inscription</a>

            <?php else: ?>
                <!-- Liens pour les utilisateurs connectés -->
        <a href="index.php" <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>Accueil</a>
<a href="livres.php" <?php echo $current_page === 'livres.php' ? 'class="active"' : ''; ?>>Catalogue</a>
<a href="profile.php" class="user-only">Mon profil</a>

                <a href="mes-emprunts.php" <?php echo $current_page === 'mes-emprunts.php' ? 'class="active"' : ''; ?>>Mes emprunts</a>
                <?php if ($is_admin): ?>
                    <a href="admin/dashboard.php" <?php echo $current_page === 'dashboard.php' ? 'class="active"' : ''; ?>>Administration</a>
                <?php endif; ?>
                <a href="logout.php" title="Se déconnecter">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            <?php endif; ?>
        </div>
    </div>
    </nav>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?> 

<!-- Contenu principal -->
<main>
    <!-- Section Hero -->
        <section class="hero-section">
            <div class="container">
                <h1 class="hero-title">Bienvenue à l'I.N.S.F.P BEB</h1>
                <p class="hero-subtitle">
                    "Une bibliothèque est un hôpital pour l'esprit." <br>
                    - Inscription au fronton de la bibliothèque de Thèbes
                </p>
                <div class="hero-buttons">
                    <a href="register.php" class="btn btn-primary guest-only">S'inscrire</a>
<a href="livres.php" class="btn btn-secondary">Découvrir nos livres</a>
                </div>
            </div>
        </section>

        <!-- Section Services -->
        <section class="container">
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon collection">
                        <i class="fas fa-book"></i>
                    </div>
                    <h3 class="service-title">Large Collection</h3>
                    <p class="service-description">Plus de 10,000 livres couvrant tous les genres et sujets.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon student">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <h3 class="service-title">Espace Étudiant</h3>
                    <p class="service-description">Zone dédiée avec des ressources académiques et des espaces de travail.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon events">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="service-title">Événements Culturels</h3>
                    <p class="service-description">Rencontres d'auteurs, clubs de lecture et ateliers d'écriture.</p>
                </div>
            </div>
        </section>

        <!-- Section Comment ça marche -->
        <section class="container how-it-works">
            <h2 class="text-center mb-4">Comment ça marche ?</h2>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Inscrivez-vous</h3>
                    <p class="step-description">Créez votre compte gratuitement en quelques clics.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Parcourez</h3>
                    <p class="step-description">Explorez notre vaste collection de livres.</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Empruntez</h3>
                    <p class="step-description">Réservez vos livres préférés en ligne.</p>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
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
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 I.N.S.F.P BEB. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="js/router.js"></script>
    <script src="js/books.js"></script>
    <script src="js/main.js"></script>
</body>
</html>