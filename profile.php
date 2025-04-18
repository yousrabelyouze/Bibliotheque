<?php
session_start();
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM utilisateurs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
$stats = $stmt->get_result()->fetch_assoc();

// Inclure le header
require_once 'includes/header.php';
?>

<main class="container main-content">
    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-title">
                <h1>Mon Profil</h1>
                <p class="subtitle">Gérez vos informations personnelles</p>
            </div>
        </div>

        <!-- Informations personnelles -->
        <div class="profile-section">
            <h2><i class="fas fa-user"></i> Informations personnelles</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nom</label>
                    <p><?php echo htmlspecialchars($user['nom']); ?></p>
                </div>
                <div class="info-item">
                    <label>Prénom</label>
                    <p><?php echo htmlspecialchars($user['prenom']); ?></p>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div class="info-item">
                    <label>Rôle</label>
                    <p><?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
                </div>
                <div class="info-item">
                    <label>Date d'inscription</label>
                    <p><?php echo date('d/m/Y', strtotime($user['date_inscription'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Statistiques des emprunts -->
        <div class="profile-section">
            <h2><i class="fas fa-chart-bar"></i> Statistiques des emprunts</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['total_emprunts']; ?></div>
                        <div class="stat-label">Total des emprunts</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['en_cours']; ?></div>
                        <div class="stat-label">Emprunts en cours</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $stats['en_retard']; ?></div>
                        <div class="stat-label">Emprunts en retard</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="profile-actions">
            <a href="mes-emprunts.php" class="btn btn-primary">
                <i class="fas fa-book-reader"></i>
                Voir mes emprunts
            </a>
            <button class="btn btn-outline" onclick="showChangePasswordModal()">
                <i class="fas fa-key"></i>
                Changer mon mot de passe
            </button>
        </div>
    </div>

    <!-- Modal pour le changement de mot de passe -->
    <div id="password-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Changer mon mot de passe</h2>
            <form id="password-form" action="change_password.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">Confirmer</button>
                    <button type="button" class="btn btn-outline" onclick="hideChangePasswordModal()">Annuler</button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.profile-container {
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-header {
    display: flex;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.profile-avatar {
    font-size: 4rem;
    color: #666;
    margin-right: 1.5rem;
}

.profile-title h1 {
    margin: 0;
    color: #333;
}

.profile-section {
    margin-bottom: 2rem;
}

.profile-section h2 {
    display: flex;
    align-items: center;
    font-size: 1.25rem;
    color: #444;
    margin-bottom: 1rem;
}

.profile-section h2 i {
    margin-right: 0.5rem;
    color: #666;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.info-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
}

.info-item label {
    display: block;
    font-size: 0.875rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.info-item p {
    margin: 0;
    color: #333;
    font-weight: 500;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.stat-icon {
    font-size: 2rem;
    color: #007bff;
    margin-right: 1rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    font-size: 0.875rem;
    color: #666;
}

.profile-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: #fff;
    width: 90%;
    max-width: 500px;
    margin: 2rem auto;
    padding: 2rem;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 1.5rem;
}
</style>

<script>
function showChangePasswordModal() {
    document.getElementById('password-modal').style.display = 'block';
}

function hideChangePasswordModal() {
    document.getElementById('password-modal').style.display = 'none';
}

// Fermer la modal si on clique en dehors
window.onclick = function(event) {
    const modal = document.getElementById('password-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}

// Validation du formulaire de changement de mot de passe
document.getElementById('password-form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas.');
    }
});
</script>

<?php
require_once 'includes/footer.php';
?> 