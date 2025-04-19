<?php
session_start();

// Connexion à la base de données
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Récupération des livres
$query = "SELECT * FROM livres ORDER BY id DESC";
$stmt = $pdo->query($query);
$livres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des catégories uniques
$categories = $pdo->query("SELECT DISTINCT categorie FROM livres ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I.N.S.F.P BEB - Catalogue des Livres</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/books.css">
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
    <main class="container main-content">
        <div class="page-header">
            <h1>Catalogue des Livres</h1>
            <p class="subtitle">Découvrez notre collection de livres et empruntez vos favoris</p>
        </div>

        <div class="books-controls">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="book-search" placeholder="Rechercher un livre..." aria-label="Rechercher un livre">
            </div>
            <div class="filter-container">
                <select id="category-filter" aria-label="Filtrer par catégorie">
                    <option value="">Toutes les catégories</option>
                    <?php foreach($categories as $categorie): ?>
                <option value="<?= htmlspecialchars($categorie) ?>"><?= htmlspecialchars($categorie) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="availability-filter" aria-label="Filtrer par disponibilité">
                    <option value="">Toute disponibilité</option>
                    <option value="Disponible">Disponible</option>
                    <option value="Emprunté">Emprunté</option>
                </select>
            </div>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <button class="btn btn-primary admin-only" onclick="showAddBookModal()" aria-label="Ajouter un nouveau livre">
                    <i class="fas fa-plus"></i> Ajouter un livre
                </button>
            <?php endif; ?>
        </div>

        <div id="books-container" class="books-container">
            <?php foreach($livres as $livre): ?>
                <div class="book-card">
                    <div class="book-details">
                        <h3 class="book-title"><?= htmlspecialchars($livre['titre']) ?></h3>
                        <p class="book-author"><?= htmlspecialchars($livre['auteur']) ?></p>
                        <div class="book-info">
                            <span class="book-category">
                                <i class="fas fa-bookmark"></i>
                                <?= htmlspecialchars($livre['categorie']) ?>
                            </span>
                            <span class="book-year">
                                <i class="fas fa-calendar"></i>
                                <?= date('Y', strtotime($livre['date_publication'])) ?>
                            </span>
                        </div>
                        <div class="book-status <?= $livre['statut'] === 'Disponible' ? 'available' : '' ?>">
                            <span class="status-badge"><?= htmlspecialchars($livre['statut']) ?></span>
                        </div>
                        <div class="book-actions">
                            <?php if($livre['statut'] === 'Disponible'): ?>
                                <?php if(isset($_SESSION['user_id'])): ?>
                                    <button class="btn btn-primary" onclick="emprunterLivre(<?= $livre['id'] ?>)">
                                        <i class="fas fa-book-reader"></i>
                                        Emprunter
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-primary" onclick="window.location.href='login.php'" title="Connectez-vous pour emprunter">
                                        <i class="fas fa-sign-in-alt"></i>
                                        Se connecter
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-primary" disabled>
                                    <i class="fas fa-clock"></i>
                                    Emprunté
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline" onclick="voirDetails(<?= $livre['id'] ?>)" title="Plus d'informations">
                                <i class="fas fa-info-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Conteneur pour les alertes -->
        <div id="alert-container" role="alert" aria-live="polite"></div>
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

    <script>
    function emprunterLivre(id) {
        if(confirm('Voulez-vous emprunter ce livre ?')) {
            window.location.href = 'emprunter.php?livre_id=' + id;
        }
    }

    function voirDetails(id) {
        window.location.href = 'details-livre.php?id=' + id;
    }

    // Fonction de recherche
    document.getElementById('book-search').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const categoryFilter = document.getElementById('category-filter').value;
        const availabilityFilter = document.getElementById('availability-filter').value;
        
        const books = document.querySelectorAll('.book-card');
        
        books.forEach(book => {
            const title = book.querySelector('.book-title').textContent.toLowerCase();
            const author = book.querySelector('.book-author').textContent.toLowerCase();
            const category = book.querySelector('.book-category').textContent.toLowerCase();
            const status = book.querySelector('.status-badge').textContent;
            
            const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm);
            const matchesCategory = !categoryFilter || category.includes(categoryFilter.toLowerCase());
            const matchesAvailability = !availabilityFilter || status === availabilityFilter;
            
            book.style.display = (matchesSearch && matchesCategory && matchesAvailability) ? '' : 'none';
        });
    });

    // Filtres
    document.getElementById('category-filter').addEventListener('change', function() {
        document.getElementById('book-search').dispatchEvent(new Event('keyup'));
    });

    document.getElementById('availability-filter').addEventListener('change', function() {
        document.getElementById('book-search').dispatchEvent(new Event('keyup'));
    });
    </script>
</body>
</html> 