// Gestion des onglets
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons d'onglets
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    // Ajouter les écouteurs d'événements pour chaque bouton
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Retirer la classe active de tous les boutons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Ajouter la classe active au bouton cliqué
            button.classList.add('active');
            
            // Récupérer l'ID de la section à afficher
            const sectionId = button.getAttribute('data-tab');
            
            // Masquer toutes les sections
            document.querySelectorAll('.tab-content').forEach(section => {
                section.style.display = 'none';
                section.classList.remove('active');
            });
            
            // Afficher la section correspondante avec animation
            const targetSection = document.getElementById(sectionId + 'Tab');
            targetSection.style.display = 'block';
            setTimeout(() => {
                targetSection.classList.add('active');
            }, 10);
        });
    });

    // Afficher la section livres par défaut
    document.querySelector('.tab-btn[data-tab="books"]').click();
});

// Gestion des modales
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Fermer les modales en cliquant en dehors
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Gestion des formulaires
document.getElementById('bookForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Traitement du formulaire livre
    closeModal('bookModal');
});

document.getElementById('userForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    // Traitement du formulaire utilisateur
    closeModal('userModal');
});

// Fonctions pour les actions sur les éléments
function editBook(id) {
    showModal('bookModal');
}

function editUser(id) {
    showModal('userModal');
}

function deleteBook(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) {
        // Logique de suppression
    }
}

function deleteUser(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        // Logique de suppression
    }
}

// Animations pour les transitions
function animateSection(section) {
    section.style.opacity = '0';
    section.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        section.style.opacity = '1';
        section.style.transform = 'translateY(0)';
    }, 10);
}

// Gestionnaire de recherche et filtres
function handleSearch(input, tableId) {
    const searchText = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const cells = row.getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
            const cellText = cells[j].textContent.toLowerCase();
            if (cellText.includes(searchText)) {
                found = true;
                break;
            }
        }

        row.style.display = found ? '' : 'none';
    }
} 
   // Toggle sidebar
   document.getElementById('toggleSidebar').addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar.classList.contains('sidebar-expanded')) {
        sidebar.classList.remove('sidebar-expanded');
        sidebar.classList.add('sidebar-collapsed');
        mainContent.classList.remove('content-expanded');
        mainContent.classList.add('content-collapsed');
    } else {
        sidebar.classList.remove('sidebar-collapsed');
        sidebar.classList.add('sidebar-expanded');
        mainContent.classList.remove('content-collapsed');
        mainContent.classList.add('content-expanded');
    }
});

// Generic modal handling
function showModal(modalId) {
    document.getElementById(modalId).classList.remove('hidden');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.add('hidden');
}

// Generic delete confirmation
function showDeleteConfirmation(type, id, name) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer ${type} "${name}" ?`)) {
        alert(`${type} "${name}" a été supprimé avec succès.`);
        // Ici vous ajouteriez la logique de suppression réelle
    }
}

// Books handling
function showAddBookModal() {
    // Réinitialiser le formulaire
    document.getElementById('title').value = '';
    document.getElementById('author').value = '';
    document.getElementById('date').value = '';
    document.getElementById('isbn').value = '';
    document.getElementById('category').value = 'Roman';
    
    // Mettre à jour le titre du modal
    document.querySelector('#addBookModal h3').textContent = 'Ajouter un nouveau livre';
    showModal('addBookModal');
}

function showEditBookModal(title, author, date, isbn, category) {
    // Pré-remplir le formulaire avec les données existantes
    document.getElementById('title').value = title;
    document.getElementById('author').value = author;
    document.getElementById('date').value = date;
    document.getElementById('isbn').value = isbn || '';
    document.getElementById('category').value = category || 'Roman';
    
    // Mettre à jour le titre du modal
    document.querySelector('#addBookModal h3').textContent = 'Modifier un livre';
    showModal('addBookModal');
}

function deleteBook(title) {
    showDeleteConfirmation('le livre', 'book-1', title);
}

// Initialize book buttons
function initializeBookButtons() {
    // Boutons d'ajout de livre
    document.querySelectorAll('button[onclick="showAddBookModal()"]').forEach(button => {
        button.addEventListener('click', showAddBookModal);
    });

    // Boutons de modification de livre
    document.querySelectorAll('button[title="Modifier"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const title = row.cells[0].textContent;
            const author = row.cells[1].textContent;
            const date = row.cells[2].textContent;
            const isbn = row.cells[2].textContent;
            const category = 'Roman';
            showEditBookModal(title, author, date, isbn, category);
        });
    });

    // Boutons de suppression de livre
    document.querySelectorAll('button[title="Supprimer"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const title = row.cells[0].textContent;
            deleteBook(title);
        });
    });
}

// Close buttons for all modals
document.querySelectorAll('.modal button[id^="close"]').forEach(button => {
    button.addEventListener('click', function() {
        this.closest('.modal').classList.add('hidden');
    });
});

// Cancel buttons for all modals
document.querySelectorAll('.modal button.bg-gray-300').forEach(button => {
    button.addEventListener('click', function() {
        this.closest('.modal').classList.add('hidden');
    });
});

// Save buttons for all modals
document.querySelectorAll('.modal button.bg-blue-600').forEach(button => {
    button.addEventListener('click', function() {
        const modal = this.closest('.modal');
        const modalTitle = modal.querySelector('h3').textContent;
        let message = 'Les modifications ont été enregistrées avec succès.';
        
        if (modalTitle.includes('Ajouter')) {
            message = 'L\'élément a été ajouté avec succès.';
        }
        
        alert(message);
        modal.classList.add('hidden');
        
        // Réinitialiser le formulaire si c'était un ajout
        if (modalTitle.includes('Ajouter')) {
            const form = modal.querySelector('form');
            if (form) form.reset();
        }
    });
});

// Section navigation
const sections = ['dashboard', 'books', 'users', 'loans'];
const navLinks = document.querySelectorAll('nav div');

function showSection(sectionId) {
    sections.forEach(section => {
        document.getElementById(section + 'Section').classList.add('hidden');
    });
    document.getElementById(sectionId + 'Section').classList.remove('hidden');

    // Update active navigation link
    navLinks.forEach(link => {
        link.classList.remove('bg-blue-700');
    });
    document.getElementById(sectionId + 'Link').classList.add('bg-blue-700');
}

// Add click event listeners to navigation links
document.getElementById('dashboardLink').addEventListener('click', () => showSection('dashboard'));
document.getElementById('booksLink').addEventListener('click', () => showSection('books'));
document.getElementById('usersLink').addEventListener('click', () => showSection('users'));
document.getElementById('loansLink').addEventListener('click', () => showSection('loans'));

// Logout functionality
document.getElementById('logoutBtn').addEventListener('click', function() {
    window.location.href = 'login.html';
});

// Users handling
function showAddUserModal() {
    document.getElementById('userName').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'Utilisateur';
    
    document.querySelector('#addUserModal h3').textContent = 'Ajouter un nouvel utilisateur';
    showModal('addUserModal');
}

function showEditUserModal(name, email, role) {
    document.getElementById('userName').value = name;
    document.getElementById('userEmail').value = email;
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = role;
    
    document.querySelector('#addUserModal h3').textContent = 'Modifier un utilisateur';
    showModal('addUserModal');
}

function deleteUser(name) {
    showDeleteConfirmation('l\'utilisateur', 'user-1', name);
}

// Loans handling
function showAddLoanModal() {
    document.getElementById('loanBook').value = '';
    document.getElementById('loanUser').value = '';
    document.getElementById('loanStartDate').value = '';
    document.getElementById('loanEndDate').value = '';
    
    document.querySelector('#addLoanModal h3').textContent = 'Ajouter un nouvel emprunt';
    showModal('addLoanModal');
}

function showEditLoanModal(book, user, startDate, endDate) {
    document.getElementById('loanBook').value = book;
    document.getElementById('loanUser').value = user;
    document.getElementById('loanStartDate').value = startDate;
    document.getElementById('loanEndDate').value = endDate;
    
    document.querySelector('#addLoanModal h3').textContent = 'Modifier un emprunt';
    showModal('addLoanModal');
}

function deleteLoan(book, user) {
    showDeleteConfirmation('l\'emprunt de', 'loan-1', `${book} par ${user}`);
}

// Initialize all buttons when the page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeBookButtons();
    
    // Initialize user buttons
    document.querySelectorAll('#usersSection button[title="Modifier"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            const email = row.cells[1].textContent;
            showEditUserModal(name, email, 'Utilisateur');
        });
    });

    document.querySelectorAll('#usersSection button[title="Supprimer"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            deleteUser(name);
        });
    });

    // Initialize loan buttons
    document.querySelectorAll('#loansSection button[title="Marquer comme retourné"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const book = row.cells[0].textContent;
            const user = row.cells[1].textContent;
            alert(`Le livre "${book}" a été marqué comme retourné par ${user}`);
        });
    });

    document.querySelectorAll('#loansSection button[title="Détails"]').forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const book = row.cells[0].textContent;
            const user = row.cells[1].textContent;
            const startDate = row.cells[2].textContent;
            const endDate = row.cells[3].textContent;
            showEditLoanModal(book, user, startDate, endDate);
        });
    });
});