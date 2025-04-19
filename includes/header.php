<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I.N.S.F.P BEB - <?php echo ucfirst(str_replace(['.php', '-'], ['', ' '], $current_page)); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/books.css">
    <link rel="stylesheet" href="css/loans.css">
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
    <script src="js/main.js"></script>
</body>
</html> 