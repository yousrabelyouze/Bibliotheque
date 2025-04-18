<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

// Redirection si non connecté ou pas admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Connexion à la base
try {
    $pdo = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Traitement de la modification d'un utilisateur
if (isset($_POST['edit_user'])) {
    try {
        $id = $_POST['edit_user_id'];
        $nom = htmlspecialchars($_POST['edit_user_name']);
        $email = htmlspecialchars($_POST['edit_user_email']);
        $role = htmlspecialchars($_POST['edit_user_role']);
        $telephone = !empty($_POST['edit_user_phone']) ? htmlspecialchars($_POST['edit_user_phone']) : null;
        $adresse = !empty($_POST['edit_user_address']) ? htmlspecialchars($_POST['edit_user_address']) : null;
        $actif = $_POST['edit_user_status'];

        if (!empty($_POST['edit_user_password'])) {
            // Si un nouveau mot de passe est fourni
            $mot_de_passe = password_hash($_POST['edit_user_password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ?, telephone = ?, adresse = ?, actif = ?, mot_de_passe = ? WHERE id = ?");
            $result = $stmt->execute([$nom, $email, $role, $telephone, $adresse, $actif, $mot_de_passe, $id]);
        } else {
            // Sans changement de mot de passe
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = ?, email = ?, role = ?, telephone = ?, adresse = ?, actif = ? WHERE id = ?");
            $result = $stmt->execute([$nom, $email, $role, $telephone, $adresse, $actif, $id]);
        }

        if ($result) {
            echo "<script>alert('Utilisateur modifié avec succès!'); window.location.href = 'dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Erreur lors de la modification de l\\'utilisateur');</script>";
        }
    } catch(PDOException $e) {
        echo "<script>alert('Erreur lors de la modification de l\\'utilisateur: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
    }
}

// Traitement de la suppression d'un utilisateur
if (isset($_GET['delete_user'])) {
    try {
        $id = $_GET['delete_user'];
        
        // Empêcher la suppression de son propre compte
        if ($id == $_SESSION['user_id']) {
            echo "<script>alert('Vous ne pouvez pas supprimer votre propre compte.'); window.location.href = 'dashboard.php';</script>";
            exit();
        }

        // Vérifier les emprunts en cours
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM emprunts WHERE utilisateur_id = ? AND statut = 'en_cours'");
        $stmt->execute([$id]);
        $emprunts_en_cours = $stmt->fetchColumn();

        if ($emprunts_en_cours > 0) {
            echo "<script>alert('Impossible de supprimer cet utilisateur car il a des emprunts en cours.'); window.location.href = 'dashboard.php';</script>";
            exit();
        }

        // Supprimer l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $result = $stmt->execute([$id]);

        if ($result) {
            echo "<script>alert('Utilisateur supprimé avec succès!'); window.location.href = 'dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Erreur lors de la suppression de l\\'utilisateur'); window.location.href = 'dashboard.php';</script>";
        }
    } catch(PDOException $e) {
        echo "<script>alert('Erreur lors de la suppression de l\\'utilisateur: " . str_replace("'", "\\'", $e->getMessage()) . "'); window.location.href = 'dashboard.php';</script>";
    }
}

// Traitement de la modification d'un livre
if (isset($_POST['edit_book'])) {
    try {
        $id = $_POST['edit_book_id'];
        $titre = htmlspecialchars($_POST['edit_title']);
        $auteur = htmlspecialchars($_POST['edit_author']);
        $date_publication = $_POST['edit_date'];
        $isbn = htmlspecialchars($_POST['edit_isbn']);
        $categorie = htmlspecialchars($_POST['edit_category']);
        $statut = htmlspecialchars($_POST['edit_status']);
        
        $stmt = $pdo->prepare("UPDATE livres SET titre = ?, auteur = ?, date_publication = ?, isbn = ?, categorie = ?, statut = ? WHERE id = ?");
        if ($stmt->execute([$titre, $auteur, $date_publication, $isbn, $categorie, $statut, $id])) {
            echo "<script>alert('Livre modifié avec succès!'); window.location.href = 'dashboard.php';</script>";
            exit();
        } else {
            echo "<script>alert('Erreur lors de la modification du livre.');</script>";
        }
    } catch(PDOException $e) {
        echo "<script>alert('Erreur lors de la modification du livre: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
    }
}

// Traitement de la suppression d'un livre
if (isset($_GET['delete_book'])) {
    try {
        $id = $_GET['delete_book'];
        
        // Vérifier si le livre n'est pas emprunté
        $stmt = $pdo->prepare("SELECT statut FROM livres WHERE id = ?");
        $stmt->execute([$id]);
        $livre = $stmt->fetch();
        
        if ($livre && $livre['statut'] !== 'Emprunté') {
            $stmt = $pdo->prepare("DELETE FROM livres WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo "<script>alert('Livre supprimé avec succès!'); window.location.href = 'dashboard.php';</script>";
                exit();
            } else {
                echo "<script>alert('Erreur lors de la suppression du livre.');</script>";
            }
        } else {
            echo "<script>alert('Impossible de supprimer ce livre car il est actuellement emprunté.');</script>";
        }
    } catch(PDOException $e) {
        echo "<script>alert('Erreur lors de la suppression du livre: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
    }
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'un livre
    if (isset($_POST['add_book'])) {
        $titre = htmlspecialchars($_POST['title']);
        $auteur = htmlspecialchars($_POST['author']);
        $date_publication = $_POST['date'];
        $isbn = htmlspecialchars($_POST['isbn']);
        $categorie = htmlspecialchars($_POST['category']);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO livres (titre, auteur, date_publication, isbn, categorie, statut) VALUES (?, ?, ?, ?, ?, 'Disponible')");
            $stmt->execute([$titre, $auteur, $date_publication, $isbn, $categorie]);
            echo "<script>alert('Livre ajouté avec succès!');</script>";
        } catch(PDOException $e) {
            echo "<script>alert('Erreur lors de l\\'ajout du livre: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
        }
    }
    
    // Ajout d'un utilisateur
    if (isset($_POST['add_user'])) {
        $nom = htmlspecialchars($_POST['userName']);
        $email = htmlspecialchars($_POST['userEmail']);
        $mot_de_passe = password_hash($_POST['userPassword'], PASSWORD_DEFAULT);
        $role = htmlspecialchars($_POST['userRole']);
        $telephone = !empty($_POST['userPhone']) ? htmlspecialchars($_POST['userPhone']) : null;
        $adresse = !empty($_POST['userAddress']) ? htmlspecialchars($_POST['userAddress']) : null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, mot_de_passe, role, telephone, adresse, actif) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$nom, $email, $mot_de_passe, $role, $telephone, $adresse]);
            echo "<script>alert('Utilisateur ajouté avec succès!'); window.location.reload();</script>";
        } catch(PDOException $e) {
            echo "<script>alert('Erreur lors de l\\'ajout de l\\'utilisateur: " . str_replace("'", "\\'", $e->getMessage()) . "');</script>";
        }
    }
    
    // Ajout d'un emprunt
    if (isset($_POST['add_loan'])) {
        $livre_id = $_POST['loanBook'];
        $user_id = $_POST['loanUser'];
        $date_emprunt = $_POST['loanStartDate'];
        $date_retour = $_POST['loanEndDate'];
        
        try {
            $pdo->beginTransaction();
            
            // Vérifier si le livre est disponible
            $stmt = $pdo->prepare("SELECT statut FROM livres WHERE id = ?");
            $stmt->execute([$livre_id]);
            $livre = $stmt->fetch();
            
            if ($livre && $livre['statut'] === 'Disponible') {
                // Créer l'emprunt
                $stmt = $pdo->prepare("INSERT INTO emprunts (livre_id, utilisateur_id, date_emprunt, date_retour_prevue, statut) VALUES (?, ?, ?, ?, 'en_cours')");
                $stmt->execute([$livre_id, $user_id, $date_emprunt, $date_retour]);
                
                // Mettre à jour le statut du livre
                $stmt = $pdo->prepare("UPDATE livres SET statut = 'Emprunté' WHERE id = ?");
                $stmt->execute([$livre_id]);
                
                $pdo->commit();
                echo "<script>alert('Emprunt enregistré avec succès!'); window.location.href = 'dashboard.php';</script>";
                exit();
            } else {
                $pdo->rollBack();
                echo "<script>alert('Ce livre n\\'est pas disponible pour l\\'emprunt.'); window.location.href = 'dashboard.php';</script>";
                exit();
            }
        } catch(Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Erreur lors de l\\'enregistrement de l\\'emprunt: " . str_replace("'", "\\'", $e->getMessage()) . "'); window.location.href = 'dashboard.php';</script>";
            exit();
        }
    }
}

// Traitement du retour d'un emprunt
if (isset($_GET['return_loan'])) {
    try {
        $id = $_GET['return_loan'];
        
        // Vérifier si l'emprunt existe et n'est pas déjà retourné
        $stmt = $pdo->prepare("SELECT e.*, l.id as livre_id FROM emprunts e JOIN livres l ON e.livre_id = l.id WHERE e.id = ? AND e.statut = 'en_cours'");
        $stmt->execute([$id]);
        $emprunt = $stmt->fetch();
        
        if ($emprunt) {
            $pdo->beginTransaction();
            
            // Mettre à jour le statut de l'emprunt
            $stmt = $pdo->prepare("UPDATE emprunts SET statut = 'retourne' WHERE id = ?");
            $result1 = $stmt->execute([$id]);
            
            // Mettre à jour le statut du livre
            $stmt = $pdo->prepare("UPDATE livres SET statut = 'Disponible' WHERE id = ?");
            $result2 = $stmt->execute([$emprunt['livre_id']]);
            
            if ($result1 && $result2) {
                $pdo->commit();
                echo "<script>alert('Retour enregistré avec succès!'); window.location.href = 'dashboard.php';</script>";
                exit();
            } else {
                $pdo->rollBack();
                echo "<script>alert('Erreur lors de l\\'enregistrement du retour.'); window.location.href = 'dashboard.php';</script>";
            }
        } else {
            echo "<script>alert('Cet emprunt n\\'existe pas ou est déjà retourné.'); window.location.href = 'dashboard.php';</script>";
        }
    } catch(PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "<script>alert('Erreur lors de l\\'enregistrement du retour: " . str_replace("'", "\\'", $e->getMessage()) . "'); window.location.href = 'dashboard.php';</script>";
    }
}

// Récupération des données pour le tableau de bord
$totalLivres = $pdo->query("SELECT COUNT(*) FROM livres")->fetchColumn();
$totalEmprunts = $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'en_cours'")->fetchColumn();
$totalUtilisateurs = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE actif = 1")->fetchColumn();

// Derniers livres ajoutés
$derniersLivres = $pdo->query("SELECT titre, auteur, date_publication, statut FROM livres ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Liste des livres pour la gestion
$livres = $pdo->query("SELECT * FROM livres ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs pour la gestion
$utilisateurs = $pdo->query("SELECT * FROM utilisateurs WHERE actif = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Liste des emprunts en cours
$emprunts = $pdo->query("
    SELECT e.*, l.titre as livre_titre, u.nom as utilisateur_nom 
    FROM emprunts e 
    JOIN livres l ON e.livre_id = l.id 
    JOIN utilisateurs u ON e.utilisateur_id = u.id 
    WHERE e.statut = 'en_cours' 
    ORDER BY e.date_emprunt DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Liste des livres disponibles pour le formulaire d'emprunt
$livres_disponibles = $pdo->query("SELECT id, titre FROM livres WHERE statut = 'Disponible'")->fetchAll(PDO::FETCH_ASSOC);

// Liste des utilisateurs actifs pour le formulaire d'emprunt
$utilisateurs_actifs = $pdo->query("SELECT id, nom FROM utilisateurs WHERE actif = 1")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Gestion Bibliothèque</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            transition: all 0.3s;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 50;
        }
        .sidebar-collapsed {
            width: 70px;
        }
        .sidebar-expanded {
            width: 250px;
        }
        .main-content {
            transition: all 0.3s;
            padding-left: 250px;
            min-height: 100vh;
        }
        .content-expanded {
            padding-left: 250px;
        }
        .content-collapsed {
            padding-left: 70px;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
            width: 100%;
        }
        table {
            width: 100%;
            min-width: 800px;
        }
        th, td {
            white-space: nowrap;
            padding: 12px;
        }
        .section-header {
            padding: 20px;
            background: white;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .modal {
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <!-- Sidebar -->
    <div class="sidebar sidebar-expanded fixed h-full bg-blue-800 text-white shadow-lg">
        <div class="p-4 flex items-center justify-between border-b border-blue-700">
            <div class="flex items-center">
                <i class="fas fa-book-open text-2xl mr-3"></i>
<span class="text-xl font-bold">I.N.S.F.P BEB - Admin</span>
            </div>
            <button id="toggleSidebar" class="text-white focus:outline-none" title="Basculer la barre latérale">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <nav class="mt-6">
            <div id="dashboardLink" class="px-4 py-3 flex items-center hover:bg-blue-700 cursor-pointer active">
                <i class="fas fa-tachometer-alt mr-3"></i>
                <span>Tableau de bord</span>
            </div>
            <div id="booksLink" class="px-4 py-3 flex items-center hover:bg-blue-700 cursor-pointer">
                <i class="fas fa-book mr-3"></i>
                <span>Gestion des livres</span>
            </div>
            <div id="usersLink" class="px-4 py-3 flex items-center hover:bg-blue-700 cursor-pointer">
                <i class="fas fa-users mr-3"></i>
                <span>Gestion des utilisateurs</span>
            </div>
            <div id="loansLink" class="px-4 py-3 flex items-center hover:bg-blue-700 cursor-pointer">
                <i class="fas fa-calendar-alt mr-3"></i>
                <span>Gestion des emprunts</span>
            </div>
        </nav>
        <div class="absolute bottom-0 w-full p-4 border-t border-blue-700">
            <button id="logoutBtn" class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded text-white transition-colors" title="Se déconnecter">
                <i class="fas fa-sign-out-alt mr-2"></i>
                <span>Déconnexion</span>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content bg-gray-100">
        <!-- Dashboard Section -->
        <section id="dashboardSection" class="block p-6">
            <div class="section-header">
                <header class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Tableau de bord</h1>
                    <div class="relative">
                        <input type="text" placeholder="Rechercher..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </header>
            </div>
            
            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-white p-6 shadow rounded">
                    <p class="text-gray-600">Total livres</p>
                    <p class="text-2xl font-bold"><?= $totalLivres ?></p>
                </div>
                <div class="bg-white p-6 shadow rounded">
                    <p class="text-gray-600">Emprunts en cours</p>
                    <p class="text-2xl font-bold"><?= $totalEmprunts ?></p>
                </div>
                <div class="bg-white p-6 shadow rounded">
                    <p class="text-gray-600">Utilisateurs actifs</p>
                    <p class="text-2xl font-bold"><?= $totalUtilisateurs ?></p>
                </div>
            </div>

            <!-- Derniers livres -->
            <div class="bg-white p-6 shadow rounded">
                <h2 class="text-xl font-semibold mb-4">Derniers livres ajoutés</h2>
                <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Titre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Auteur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date de publication</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Disponibilité</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($derniersLivres as $livre): ?>
                <tr>
                    <td class="px-6 py-4"><?= htmlspecialchars($livre['titre']) ?></td>
                    <td class="px-6 py-4"><?= htmlspecialchars($livre['auteur']) ?></td>
                    <td class="px-6 py-4">
                        <?= isset($livre['date_publication']) ? date('Y', strtotime($livre['date_publication'])) : 'N/A' ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($livre['statut'] === 'Disponible'): ?>
                            <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">Disponible</span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-red-100 text-red-800"><?= $livre['statut'] ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

            </div>
        </section>

        <!-- Books Management Section -->
        <section id="booksSection" class="hidden">
            <header class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Gestion des livres</h1>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" onclick="showAddBookModal()">
                    <i class="fas fa-plus mr-2"></i> Ajouter un livre
                </button>
            </header>
            
            <div class="bg-white rounded-xl shadow p-6">
                <!-- Barre de recherche et filtres -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <input type="text" 
                               placeholder="Rechercher un livre..." 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les statuts</option>
                            <option value="Disponible">Disponible</option>
                            <option value="Emprunté">Emprunté</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Titre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Auteur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catégorie</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Récupération des livres
                            $stmt = $pdo->query("SELECT * FROM livres ORDER BY id DESC");
                            while($livre = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($livre['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($livre['titre']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($livre['auteur']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($livre['categorie']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $livre['statut'] === 'Disponible' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= htmlspecialchars($livre['statut']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="editBook(<?= $livre['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteBook(<?= $livre['id'] ?>)" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Message si aucun livre -->
                <?php if($stmt->rowCount() === 0): ?>
                <div class="text-center py-4 text-gray-500">
                    <p>Aucun livre dans la bibliothèque</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Users Management Section -->
        <section id="usersSection" class="hidden">
            <header class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Gestion des utilisateurs</h1>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" onclick="showAddUserModal()">
                    <i class="fas fa-user-plus mr-2"></i> Ajouter un utilisateur
                </button>
            </header>
            
            <div class="bg-white rounded-xl shadow p-6">
                <!-- Barre de recherche et filtres -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <input type="text" 
                               placeholder="Rechercher un utilisateur..." 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les rôles</option>
                            <option value="membre">Membre</option>
                            <option value="admin">Administrateur</option>
                            <option value="bibliothecaire">Bibliothécaire</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rôle</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'inscription</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Récupération des utilisateurs
                            $stmt = $pdo->query("SELECT * FROM utilisateurs ORDER BY id DESC");
                            while($user = $stmt->fetch(PDO::FETCH_ASSOC)):
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($user['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if($user['photo_profil']): ?>
                                            <img class="h-8 w-8 rounded-full mr-3" src="<?= htmlspecialchars($user['photo_profil']) ?>" alt="Photo de profil">
                                        <?php else: ?>
                                            <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                <i class="fas fa-user text-gray-400"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($user['nom']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php
                                        switch($user['role']) {
                                            case 'admin':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'bibliothecaire':
                                                echo 'bg-blue-100 text-blue-800';
                                                break;
                                            default:
                                                echo 'bg-green-100 text-green-800';
                                        }
                                        ?>">
                                        <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($user['date_inscription'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?= $user['actif'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                        <?= $user['actif'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewUser(<?= $user['id'] ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editUser(<?= $user['id'] ?>)" class="text-blue-600 hover:text-blue-900 mr-3" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if($user['id'] != $_SESSION['user_id']): // Ne pas permettre la suppression de son propre compte ?>
                                    <button onclick="deleteUser(<?= $user['id'] ?>)" class="text-red-600 hover:text-red-900" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Message si aucun utilisateur -->
                <?php if($stmt->rowCount() === 0): ?>
                <div class="text-center py-4 text-gray-500">
                    <p>Aucun utilisateur trouvé</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Loans Management Section -->
        <section id="loansSection" class="hidden">
            <header class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Gestion des emprunts</h1>
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition" onclick="showAddLoanModal()">
                    <i class="fas fa-plus mr-2"></i> Nouvel emprunt
                </button>
            </header>
            
            <div class="bg-white rounded-xl shadow p-6">
                <!-- Barre de recherche et filtres -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <input type="text" 
                               placeholder="Rechercher un emprunt..." 
                               class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <select class="px-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Tous les statuts</option>
                            <option value="en_cours">En cours</option>
                            <option value="retourne">Retourné</option>
                            <option value="retard">En retard</option>
                        </select>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Livre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emprunteur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date d'emprunt</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date de retour prévue</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php
                            // Récupération des emprunts avec les informations des livres et utilisateurs
                            $stmt = $pdo->query("
                                SELECT e.*, 
                                       l.titre as livre_titre, 
                                       u.nom as utilisateur_nom,
                                       DATEDIFF(e.date_retour_prevue, CURRENT_DATE) as jours_restants
                                FROM emprunts e
                                JOIN livres l ON e.livre_id = l.id
                                JOIN utilisateurs u ON e.utilisateur_id = u.id
                                ORDER BY e.date_emprunt DESC
                            ");
                            
                            while($emprunt = $stmt->fetch(PDO::FETCH_ASSOC)):
                                $statut_class = '';
                                $statut_text = '';
                                
                                if($emprunt['statut'] === 'retourne') {
                                    $statut_class = 'bg-green-100 text-green-800';
                                    $statut_text = 'Retourné';
                                } elseif($emprunt['jours_restants'] < 0) {
                                    $statut_class = 'bg-red-100 text-red-800';
                                    $statut_text = 'En retard';
                                } else {
                                    $statut_class = 'bg-yellow-100 text-yellow-800';
                                    $statut_text = 'En cours';
                                }
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($emprunt['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($emprunt['livre_titre']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?= htmlspecialchars($emprunt['utilisateur_nom']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($emprunt['date_emprunt'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('d/m/Y', strtotime($emprunt['date_retour_prevue'])) ?>
                                    <?php if($emprunt['statut'] === 'en_cours'): ?>
                                        <br>
                                        <span class="text-xs <?= $emprunt['jours_restants'] < 0 ? 'text-red-600' : 'text-gray-500' ?>">
                                            <?php
                                            if($emprunt['jours_restants'] < 0) {
                                                echo 'Retard de ' . abs($emprunt['jours_restants']) . ' jour(s)';
                                            } else {
                                                echo $emprunt['jours_restants'] . ' jour(s) restant(s)';
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statut_class ?>">
                                        <?= $statut_text ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if($emprunt['statut'] === 'en_cours'): ?>
                                        <button onclick="confirmReturn(<?= $emprunt['id'] ?>)" class="text-green-600 hover:text-green-900 mr-3" title="Marquer comme retourné">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="viewLoan(<?= $emprunt['id'] ?>)" class="text-blue-600 hover:text-blue-900" title="Voir détails">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Add Book Modal -->
        <div id="addBookModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Ajouter un nouveau livre</h3>
                    <button id="closeModal" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="title">Titre</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="title" name="title" type="text" placeholder="Titre du livre" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="author">Auteur</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="author" name="author" type="text" placeholder="Nom de l'auteur" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="date">Date de publication</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="date" name="date" type="date" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="isbn">ISBN</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="isbn" name="isbn" type="text" placeholder="Numéro ISBN" required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="category">Catégorie</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="category" name="category" required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Roman">Roman</option>
                                <option value="Science-fiction">Science-fiction</option>
                                <option value="Biographie">Biographie</option>
                                <option value="Histoire">Histoire</option>
                                <option value="Poésie">Poésie</option>
                            </select>
                        </div>
                        <div class="flex justify-end border-t pt-4">
                            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400" onclick="hideModal('addBookModal')">Annuler</button>
                            <button type="submit" name="add_book" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div id="addUserModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Ajouter un nouvel utilisateur</h3>
                    <button id="closeUserModal" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" action="" id="addUserForm">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userName">Nom complet *</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="userName" 
                                   name="userName" 
                                   type="text" 
                                   placeholder="Nom de l'utilisateur" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userEmail">Email *</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="userEmail" 
                                   name="userEmail" 
                                   type="email" 
                                   placeholder="email@exemple.com" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userPassword">Mot de passe *</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="userPassword" 
                                   name="userPassword" 
                                   type="password" 
                                   placeholder="Mot de passe" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userRole">Rôle *</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="userRole" 
                                    name="userRole" 
                                    required>
                                <option value="">Sélectionnez un rôle</option>
                                <option value="membre">Membre</option>
                                <option value="admin">Administrateur</option>
                                <option value="bibliothecaire">Bibliothécaire</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userPhone">Téléphone</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="userPhone" 
                                   name="userPhone" 
                                   type="tel" 
                                   placeholder="Numéro de téléphone">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="userAddress">Adresse</label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                      id="userAddress" 
                                      name="userAddress" 
                                      placeholder="Adresse complète"
                                      rows="3"></textarea>
                        </div>
                        <div class="flex justify-end border-t pt-4">
                            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400" onclick="hideModal('addUserModal')">Annuler</button>
                            <button type="submit" name="add_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                <i class="fas fa-user-plus mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Add Loan Modal -->
        <div id="addLoanModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Ajouter un nouvel emprunt</h3>
                    <button id="closeLoanModal" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="loanBook">Livre</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="loanBook" name="loanBook" required>
                                <option value="">Sélectionnez un livre</option>
                                <?php foreach ($livres_disponibles as $livre): ?>
                                    <option value="<?= $livre['id'] ?>"><?= htmlspecialchars($livre['titre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="loanUser">Utilisateur</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="loanUser" name="loanUser" required>
                                <option value="">Sélectionnez un utilisateur</option>
                                <?php foreach ($utilisateurs_actifs as $utilisateur): ?>
                                    <option value="<?= $utilisateur['id'] ?>"><?= htmlspecialchars($utilisateur['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="loanStartDate">Date d'emprunt</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="loanStartDate" name="loanStartDate" type="date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="loanEndDate">Date de retour prévue</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="loanEndDate" name="loanEndDate" type="date" required value="<?= date('Y-m-d', strtotime('+14 days')) ?>">
                        </div>
                        <div class="flex justify-end border-t pt-4">
                            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400" onclick="hideModal('addLoanModal')">Annuler</button>
                            <button type="submit" name="add_loan" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Book Modal -->
        <div id="editBookModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Modifier le livre</h3>
                    <button onclick="hideModal('editBookModal')" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" action="" id="editBookForm">
                        <input type="hidden" id="editBookId" name="edit_book_id">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editTitle">Titre</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editTitle" 
                                   name="edit_title" 
                                   type="text" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editAuthor">Auteur</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editAuthor" 
                                   name="edit_author" 
                                   type="text" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editDate">Date de publication</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editDate" 
                                   name="edit_date" 
                                   type="date" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editIsbn">ISBN</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editIsbn" 
                                   name="edit_isbn" 
                                   type="text" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editCategory">Catégorie</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="editCategory" 
                                    name="edit_category" 
                                    required>
                                <option value="">Sélectionnez une catégorie</option>
                                <option value="Roman">Roman</option>
                                <option value="Science-fiction">Science-fiction</option>
                                <option value="Biographie">Biographie</option>
                                <option value="Histoire">Histoire</option>
                                <option value="Poésie">Poésie</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editStatus">Statut</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="editStatus" 
                                    name="edit_status" 
                                    required>
                                <option value="Disponible">Disponible</option>
                                <option value="Emprunté">Emprunté</option>
                            </select>
                        </div>
                        <div class="flex justify-end border-t pt-4">
                            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400" onclick="hideModal('editBookModal')">Annuler</button>
                            <button type="submit" name="edit_book" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div id="editUserModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Modifier l'utilisateur</h3>
                    <button onclick="hideModal('editUserModal')" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <form method="POST" action="" id="editUserForm">
                        <input type="hidden" id="editUserId" name="edit_user_id">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserName">Nom complet *</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editUserName" 
                                   name="edit_user_name" 
                                   type="text" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserEmail">Email *</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editUserEmail" 
                                   name="edit_user_email" 
                                   type="email" 
                                   required>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserRole">Rôle *</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="editUserRole" 
                                    name="edit_user_role" 
                                    required>
                                <option value="membre">Membre</option>
                                <option value="admin">Administrateur</option>
                                <option value="bibliothecaire">Bibliothécaire</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserPhone">Téléphone</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editUserPhone" 
                                   name="edit_user_phone" 
                                   type="tel">
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserAddress">Adresse</label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                      id="editUserAddress" 
                                      name="edit_user_address" 
                                      rows="3"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserStatus">Statut</label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                    id="editUserStatus" 
                                    name="edit_user_status" 
                                    required>
                                <option value="1">Actif</option>
                                <option value="0">Inactif</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2" for="editUserPassword">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" 
                                   id="editUserPassword" 
                                   name="edit_user_password" 
                                   type="password">
                        </div>
                        <div class="flex justify-end border-t pt-4">
                            <button type="button" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400" onclick="hideModal('editUserModal')">Annuler</button>
                            <button type="submit" name="edit_user" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- View Loan Modal -->
        <div id="viewLoanModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-lg">
                <div class="flex justify-between items-center border-b px-6 py-4">
                    <h3 class="text-lg font-semibold">Détails de l'emprunt</h3>
                    <button onclick="hideModal('viewLoanModal')" class="text-gray-500 hover:text-gray-700" title="Fermer">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Informations du livre</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Titre</p>
                                <p id="loanBookTitle" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Auteur</p>
                                <p id="loanBookAuthor" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">ISBN</p>
                                <p id="loanBookIsbn" class="font-medium"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Informations de l'emprunteur</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Nom</p>
                                <p id="loanUserName" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p id="loanUserEmail" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Téléphone</p>
                                <p id="loanUserPhone" class="font-medium"></p>
                            </div>
                        </div>
                    </div>
                    <div class="mb-6">
                        <h4 class="text-lg font-semibold mb-4">Détails de l'emprunt</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Date d'emprunt</p>
                                <p id="loanStartDate" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Date de retour prévue</p>
                                <p id="loanEndDate" class="font-medium"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Statut</p>
                                <p id="loanStatus" class="font-medium"></p>
                            </div>
                            <div id="loanReturnDateContainer" class="hidden">
                                <p class="text-sm text-gray-600">Date de retour effective</p>
                                <p id="loanReturnDate" class="font-medium"></p>
                            </div>
                        </div>
                    </div>
                    <div id="loanActions" class="flex justify-end border-t pt-4">
                        <button onclick="hideModal('viewLoanModal')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded mr-2 hover:bg-gray-400">Fermer</button>
                        <button id="returnLoanButton" onclick="confirmReturn()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 hidden">
                            <i class="fas fa-check mr-2"></i>Confirmer le retour
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fonction pour masquer toutes les sections
        function hideAllSections() {
            document.getElementById('dashboardSection').classList.add('hidden');
            document.getElementById('booksSection').classList.add('hidden');
            document.getElementById('usersSection').classList.add('hidden');
            document.getElementById('loansSection').classList.add('hidden');
        }

        // Fonction pour afficher une section spécifique
        function showSection(sectionId) {
            hideAllSections();
            document.getElementById(sectionId).classList.remove('hidden');
        }

        // Gestionnaires d'événements pour les liens du menu
        document.getElementById('dashboardLink').addEventListener('click', () => showSection('dashboardSection'));
        document.getElementById('booksLink').addEventListener('click', () => showSection('booksSection'));
        document.getElementById('usersLink').addEventListener('click', () => showSection('usersSection'));
        document.getElementById('loansLink').addEventListener('click', () => showSection('loansSection'));

        // Gestion du toggle sidebar
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleButton = document.getElementById('toggleSidebar');

        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-collapsed');
            sidebar.classList.toggle('sidebar-expanded');
            mainContent.classList.toggle('content-collapsed');
            mainContent.classList.toggle('content-expanded');
        });

        // Gestion des modales
        function showModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function hideModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // Gestionnaires pour les modales
        window.showAddBookModal = () => showModal('addBookModal');
        window.showAddUserModal = () => showModal('addUserModal');
        window.showAddLoanModal = () => showModal('addLoanModal');

        // Fermeture des modales
        document.getElementById('closeModal')?.addEventListener('click', () => hideModal('addBookModal'));
        document.getElementById('closeUserModal')?.addEventListener('click', () => hideModal('addUserModal'));
        document.getElementById('closeLoanModal')?.addEventListener('click', () => hideModal('addLoanModal'));

        // Gestion de la déconnexion
        document.getElementById('logoutBtn').addEventListener('click', function() {
            if(confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
                window.location.href = '../logout.php';
            }
        });

        // Fonctions pour la gestion des livres
        function editBook(id) {
            fetch(`get_book.php?id=${id}`)
                .then(response => response.json())
                .then(book => {
                    if (book.error) {
                        alert(book.error);
                        return;
                    }
                    
                    document.getElementById('editBookId').value = book.id;
                    document.getElementById('editTitle').value = book.titre;
                    document.getElementById('editAuthor').value = book.auteur;
                    document.getElementById('editDate').value = book.date_publication;
                    document.getElementById('editIsbn').value = book.isbn;
                    document.getElementById('editCategory').value = book.categorie;
                    document.getElementById('editStatus').value = book.statut;
                    
                    showModal('editBookModal');
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la récupération des données du livre');
                });
        }

        function deleteBook(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) {
                window.location.href = 'dashboard.php?delete_book=' + id;
            }
        }

        // Fonctions pour la gestion des utilisateurs
        function viewUser(id) {
            // À implémenter : afficher les détails de l'utilisateur
            alert('Voir les détails de l\'utilisateur ' + id);
        }

        function editUser(id) {
            fetch('get_user.php?id=' + id)
                .then(response => response.json())
                .then(user => {
                    if (user.error) {
                        alert(user.error);
                        return;
                    }
                    
                    document.getElementById('editUserId').value = user.id;
                    document.getElementById('editUserName').value = user.nom;
                    document.getElementById('editUserEmail').value = user.email;
                    document.getElementById('editUserRole').value = user.role;
                    document.getElementById('editUserPhone').value = user.telephone || '';
                    document.getElementById('editUserAddress').value = user.adresse || '';
                    document.getElementById('editUserStatus').value = user.actif;
                    document.getElementById('editUserPassword').value = ''; // Toujours vide pour le mot de passe
                    
                    showModal('editUserModal');
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la récupération des données de l\'utilisateur');
                });
        }

        function deleteUser(id) {
            if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
                window.location.href = 'dashboard.php?delete_user=' + id;
            }
        }

        // Fonctions pour la gestion des emprunts
        function confirmReturn(id) {
            if(confirm('Êtes-vous sûr de vouloir confirmer le retour de ce livre ?')) {
                window.location.href = 'dashboard.php?return_loan=' + id;
            }
        }

        let currentLoanId = null;

        // Fonction pour voir les détails d'un emprunt
        function viewLoan(id) {
            currentLoanId = id;
            fetch('get_loan.php?id=' + id)
                .then(response => response.json())
                .then(loan => {
                    if (loan.error) {
                        alert(loan.error);
                        return;
                    }
                    
                    // Remplir les informations du livre
                    document.getElementById('loanBookTitle').textContent = loan.livre_titre;
                    document.getElementById('loanBookAuthor').textContent = loan.livre_auteur;
                    document.getElementById('loanBookIsbn').textContent = loan.livre_isbn;
                    
                    // Remplir les informations de l'utilisateur
                    document.getElementById('loanUserName').textContent = loan.utilisateur_nom;
                    document.getElementById('loanUserEmail').textContent = loan.utilisateur_email;
                    document.getElementById('loanUserPhone').textContent = loan.utilisateur_telephone || 'Non renseigné';
                    
                    // Remplir les détails de l'emprunt
                    document.getElementById('loanStartDate').textContent = formatDate(loan.date_emprunt);
                    document.getElementById('loanEndDate').textContent = formatDate(loan.date_retour_prevue);
                    
                    // Gérer l'affichage du statut et du bouton de retour
                    const statusElement = document.getElementById('loanStatus');
                    const returnButton = document.getElementById('returnLoanButton');
                    const returnDateContainer = document.getElementById('loanReturnDateContainer');
                    
                    if (loan.statut === 'retourne') {
                        statusElement.textContent = 'Retourné';
                        statusElement.className = 'font-medium text-green-600';
                        returnButton.classList.add('hidden');
                        returnDateContainer.classList.add('hidden'); // On cache la date de retour car elle n'existe pas
                    } else {
                        const isLate = new Date(loan.date_retour_prevue) < new Date();
                        statusElement.textContent = isLate ? 'En retard' : 'En cours';
                        statusElement.className = 'font-medium ' + (isLate ? 'text-red-600' : 'text-yellow-600');
                        returnButton.classList.remove('hidden');
                        returnDateContainer.classList.add('hidden');
                    }
                    
                    showModal('viewLoanModal');
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la récupération des données de l\'emprunt');
                });
        }

        // Fonction pour confirmer le retour d'un emprunt
        function confirmReturn(id) {
            if(confirm('Êtes-vous sûr de vouloir confirmer le retour de ce livre ?')) {
                window.location.href = 'dashboard.php?return_loan=' + id;
            }
        }

        // Fonction pour formater les dates
        function formatDate(dateStr) {
            if (!dateStr) return 'Non définie';
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR');
        }

        // Fonction de recherche pour les livres
        document.querySelector('#booksSection input[type="text"]').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const statusFilter = document.querySelector('#booksSection select').value;
            const rows = document.querySelectorAll('#booksSection tbody tr');

            rows.forEach(row => {
                const title = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const author = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const category = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const status = row.querySelector('td:nth-child(5)').textContent.toLowerCase();

                const matchesSearch = title.includes(searchTerm) || 
                                    author.includes(searchTerm) || 
                                    category.includes(searchTerm);
                const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase());

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        });

        // Filtre par statut pour les livres
        document.querySelector('#booksSection select').addEventListener('change', function() {
            document.querySelector('#booksSection input[type="text"]').dispatchEvent(new Event('keyup'));
        });

        // Fonction de recherche pour les utilisateurs
        document.querySelector('#usersSection input[type="text"]').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const roleFilter = document.querySelector('#usersSection select').value;
            const rows = document.querySelectorAll('#usersSection tbody tr');

            rows.forEach(row => {
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const role = row.querySelector('td:nth-child(4)').textContent.toLowerCase();

                const matchesSearch = name.includes(searchTerm) || 
                                    email.includes(searchTerm);
                const matchesRole = !roleFilter || role.includes(roleFilter.toLowerCase());

                row.style.display = matchesSearch && matchesRole ? '' : 'none';
            });
        });

        // Filtre par rôle pour les utilisateurs
        document.querySelector('#usersSection select').addEventListener('change', function() {
            document.querySelector('#usersSection input[type="text"]').dispatchEvent(new Event('keyup'));
        });

        // Fonction de recherche pour les emprunts
        document.querySelector('#loansSection input[type="text"]').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const statusFilter = document.querySelector('#loansSection select').value;
            const rows = document.querySelectorAll('#loansSection tbody tr');

            rows.forEach(row => {
                const bookTitle = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const userName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const status = row.querySelector('td:nth-child(6)').textContent.toLowerCase();

                const matchesSearch = bookTitle.includes(searchTerm) || 
                                    userName.includes(searchTerm);
                const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase());

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        });

        // Filtre par statut pour les emprunts
        document.querySelector('#loansSection select').addEventListener('change', function() {
            document.querySelector('#loansSection input[type="text"]').dispatchEvent(new Event('keyup'));
        });

        // Recherche globale dans le tableau de bord
        document.querySelector('#dashboardSection input[type="text"]').addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#dashboardSection table tbody tr');

            rows.forEach(row => {
                const title = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const author = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const matches = title.includes(searchTerm) || author.includes(searchTerm);
                row.style.display = matches ? '' : 'none';
            });
        });

        // Fonction de recherche par statut pour les utilisateurs
        document.querySelector('#usersSection select').addEventListener('change', function() {
            const roleFilter = this.value.toLowerCase();
            const searchTerm = document.querySelector('#usersSection input[type="text"]').value.toLowerCase();
            const rows = document.querySelectorAll('#usersSection tbody tr');

            rows.forEach(row => {
                const role = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const status = row.querySelector('td:nth-child(6)').textContent.toLowerCase();

                const matchesRole = !roleFilter || role.includes(roleFilter);
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);

                row.style.display = (matchesRole && matchesSearch) ? '' : 'none';
            });
        });

        // Fonction de recherche par statut pour les emprunts
        document.querySelector('#loansSection select').addEventListener('change', function() {
            const statusFilter = this.value.toLowerCase();
            const searchTerm = document.querySelector('#loansSection input[type="text"]').value.toLowerCase();
            const rows = document.querySelectorAll('#loansSection tbody tr');

            rows.forEach(row => {
                const status = row.querySelector('td:nth-child(6)').textContent.toLowerCase();
                const bookTitle = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const userName = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

                let matchesStatus = true;
                if (statusFilter === 'en_cours') {
                    matchesStatus = status.includes('en cours');
                } else if (statusFilter === 'retourne') {
                    matchesStatus = status.includes('retourné');
                } else if (statusFilter === 'retard') {
                    matchesStatus = status.includes('retard');
                }

                const matchesSearch = bookTitle.includes(searchTerm) || userName.includes(searchTerm);

                row.style.display = (matchesStatus && matchesSearch) ? '' : 'none';
            });
        });

        // Mise à jour de la recherche utilisateur pour inclure le statut
        document.querySelector('#usersSection input[type="text"]').addEventListener('keyup', function() {
            document.querySelector('#usersSection select').dispatchEvent(new Event('change'));
        });

        // Mise à jour de la recherche emprunt pour inclure le statut
        document.querySelector('#loansSection input[type="text"]').addEventListener('keyup', function() {
            document.querySelector('#loansSection select').dispatchEvent(new Event('change'));
        });
    </script>
</body>
</html> 
