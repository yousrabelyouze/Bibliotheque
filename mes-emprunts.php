<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'config.php';

// Vérifier la connexion à la base de données
if (!isset($conn) || $conn->connect_error) {
    die("Erreur de connexion à la base de données : " . ($conn ? $conn->connect_error : "Variable conn non définie"));
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Récupérer les statistiques des emprunts
$stats_query = "SELECT 
    COUNT(*) as total_emprunts,
    SUM(CASE WHEN date_retour_prevue >= CURDATE() THEN 1 ELSE 0 END) as en_cours,
    SUM(CASE WHEN date_retour_prevue < CURDATE() THEN 1 ELSE 0 END) as en_retard
FROM emprunts 
WHERE utilisateur_id = ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Récupérer tous les emprunts de l'utilisateur
$loans_query = "SELECT e.*, l.titre, l.auteur,
    DATEDIFF(e.date_retour_prevue, CURDATE()) as jours_restants,
    CASE 
        WHEN date_retour_prevue < CURDATE() THEN 'en-retard'
        ELSE 'en-cours'
    END as statut
FROM emprunts e
JOIN livres l ON e.livre_id = l.id
WHERE e.utilisateur_id = ?
ORDER BY e.date_emprunt DESC";

$stmt = $conn->prepare($loans_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$loans_result = $stmt->get_result();

// Inclure l'en-tête
require_once 'includes/header.php';
?>

<!-- Contenu principal -->
    <main class="container main-content">
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

        <div class="page-header">
            <h1>Mes Emprunts</h1>
            <p class="subtitle">Gérez vos emprunts en cours et votre historique</p>
        </div>

        <!-- Stats des emprunts -->
        <div class="loan-stats">
            <div class="stat-card">
                <div class="stat-icon books">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['en_cours']; ?></div>
                    <div class="stat-label">Emprunts en cours</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon late">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['en_retard']; ?></div>
                    <div class="stat-label">Retard</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon returned">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total_emprunts']; ?></div>
                    <div class="stat-label">Total des emprunts</div>
                </div>
            </div>
        </div>

        <!-- Filtres -->
        <div class="loans-controls">
            <div class="search-container">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Rechercher un emprunt..." class="search-input">
            </div>
            <div class="filter-container">
                <select class="filter-select" id="statusFilter" title="Filtrer par statut">
                    <option value="">Tous les statuts</option>
                    <option value="en-cours">En cours</option>
                    <option value="en-retard">En retard</option>
                </select>
            </div>
        </div>

        <!-- Liste des emprunts -->
        <div class="loans-list">
            <?php if ($loans_result->num_rows > 0): ?>
                <?php while ($loan = $loans_result->fetch_assoc()): ?>
                    <div class="loan-card <?php echo $loan['statut']; ?>" data-status="<?php echo $loan['statut']; ?>">
                        <div class="loan-book">
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($loan['titre']); ?></h3>
                                <p class="book-author"><?php echo htmlspecialchars($loan['auteur']); ?></p>
                            </div>
                        </div>
                        <div class="loan-details">
                            <div class="loan-dates">
                                <span class="loan-date">
                                    <i class="fas fa-calendar-plus"></i>
                                    Emprunté le: <?php echo date('d/m/Y', strtotime($loan['date_emprunt'])); ?>
                                </span>
                                <span class="return-date">
                                    <i class="fas fa-calendar-<?php echo $loan['statut'] == 'en-retard' ? 'times' : 'check'; ?>"></i>
                                    À retourner le: <?php echo date('d/m/Y', strtotime($loan['date_retour_prevue'])); ?>
                                </span>
                            </div>
                            <div class="loan-status">
                                <span class="status-badge <?php echo $loan['statut']; ?>">
                                    <?php
                                    if ($loan['statut'] == 'en-retard') {
                                        echo 'Retard (' . abs($loan['jours_restants']) . ' jours)';
                                    } else {
                                        echo 'En cours';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="loan-actions">
                            <?php if ($loan['statut'] == 'en-cours'): ?>
                                <button class="btn btn-outline extend-loan" data-loan-id="<?php echo $loan['id']; ?>" 
                                        title="Prolonger l'emprunt">
                                    <i class="fas fa-clock"></i>
                                    Prolonger
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-loans">
                    <i class="fas fa-book-open"></i>
                    <p>Vous n'avez pas encore emprunté de livres.</p>
                    <a href="livres.php" class="btn btn-primary">Parcourir le catalogue</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const loanCards = document.querySelectorAll('.loan-card');

        function filterLoans() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedStatus = statusFilter.value;

            loanCards.forEach(card => {
                const title = card.querySelector('.book-title').textContent.toLowerCase();
                const author = card.querySelector('.book-author').textContent.toLowerCase();
                const status = card.dataset.status;
                
                const matchesSearch = title.includes(searchTerm) || author.includes(searchTerm);
                const matchesStatus = selectedStatus === '' || status === selectedStatus;

                card.style.display = matchesSearch && matchesStatus ? 'flex' : 'none';
            });
        }

        searchInput.addEventListener('input', filterLoans);
        statusFilter.addEventListener('change', filterLoans);

        // Gestion des actions sur les emprunts
        document.querySelectorAll('.extend-loan').forEach(button => {
            button.addEventListener('click', function() {
                const loanId = this.dataset.loanId;
                if (confirm('Voulez-vous prolonger cet emprunt de 15 jours ?')) {
                    window.location.href = `extend_loan.php?id=${loanId}`;
                }
            });
        });
    });
    </script>

<?php
// Inclure le pied de page
require_once 'includes/footer.php';
?>

<!-- Scripts -->
<script src="js/main.js"></script>
</body>
</html> 