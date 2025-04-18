<?php
session_start();
require_once 'config.php';

$message = ""; // Message par défaut vide

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];
    $telephone = $_POST['telephone'];
    $adresse = $_POST['adresse'];

    if ($password !== $confirmPassword) {
        $message = "<div class='msg error'>❌ Les mots de passe ne correspondent pas.</div>";
    } else {
        try {
            // Vérifier si l'email existe déjà
            $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $message = "<div class='msg error'>❌ Cette adresse email est déjà utilisée.</div>";
            } else {
                // Insérer l'utilisateur avec le mot de passe en clair
                $stmt = $conn->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, telephone, adresse, role) VALUES (?, ?, ?, ?, ?, 'membre')");
                $stmt->bind_param("sssss", $nom, $email, $password, $telephone, $adresse);
                
                if ($stmt->execute()) {
                    // Récupérer l'ID de l'utilisateur nouvellement créé
                    $user_id = $conn->insert_id;

                    // Créer la session utilisateur
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['role'] = 'membre';

                    // Rediriger vers profile.php
                    header("Location: profile.php");
                    exit();
                } else {
                    $message = "<div class='msg error'>❌ Erreur lors de l'inscription.</div>";
                }
            }
        } catch (Exception $e) {
            $message = "<div class='msg error'>❌ Erreur : " . $e->getMessage() . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - I.N.S.F.P BEB</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        .msg {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            max-width: 500px;
            margin: 20px auto;
        }
        .msg.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .msg.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body class="guest-mode">
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
                <a href="index.php">Accueil</a>
                <a href="livres.php">Catalogue</a>
                <a href="mes-emprunts.php" class="user-only">Mes emprunts</a>
                <a href="admin/dashboard.php" class="admin-only">Administration</a>
                <a href="login.php" class="guest-only">Connexion</a>
                <a href="register.php" class="guest-only active">Inscription</a>
            </div>
        </div>
    </nav>

    <main class="container main-content">
        <div class="auth-container">
            <div class="auth-card">
                <h1 class="text-center mb-4">Créer un compte</h1>

                <!-- Message d'erreur ou succès -->
                <?php if (!empty($message)) echo $message; ?>

                <form method="POST" action="register.php" class="auth-form">
                    <div class="form-group">
                        <label for="nom">Nom complet</label>
                        <input type="text" id="nom" name="nom" required placeholder="Votre nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" required placeholder="exemple@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required placeholder="********">
                    </div>
                    <div class="form-group">
                        <label for="confirm-password">Confirmez le mot de passe</label>
                        <input type="password" id="confirm-password" name="confirm-password" required placeholder="********">
                    </div>
                    <div class="form-group">
                        <label for="telephone">Téléphone</label>
                        <input type="tel" id="telephone" name="telephone" required placeholder="+213..." value="<?php echo isset($_POST['telephone']) ? htmlspecialchars($_POST['telephone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="adresse">Adresse</label>
                        <textarea id="adresse" name="adresse" required placeholder="Adresse complète" rows="3"><?php echo isset($_POST['adresse']) ? htmlspecialchars($_POST['adresse']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="terms" required> J'accepte les conditions d'utilisation
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">S'inscrire</button>
                </form>
                <p class="text-center mt-3">
                    Déjà inscrit ? <a href="login.php">Connexion</a>
                </p>
            </div>
        </div>
    </main>

    <?php require_once 'includes/footer.php'; ?>
</body>
</html>
