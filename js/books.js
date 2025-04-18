// Book Management System

// Sample book data structure (replace with actual database/API calls)
let books = [
    {
        id: 1,
        title: "Le Petit Prince",
        author: "Antoine de Saint-Exupéry",
        category: "Fiction",
        available: true,
        coverImage: "images/book1.jpg"
    }
];

// Function to display all books
function displayBooks(filters = {}) {
    const booksContainer = document.getElementById('books-container');
    if (!booksContainer) return;

    let filteredBooks = [...books];

    // Apply filters
    if (filters.category) {
        filteredBooks = filteredBooks.filter(book => book.category === filters.category);
    }
    if (filters.available !== undefined) {
        filteredBooks = filteredBooks.filter(book => book.available === filters.available);
    }
    if (filters.search) {
        const searchTerm = filters.search.toLowerCase();
        filteredBooks = filteredBooks.filter(book => 
            book.title.toLowerCase().includes(searchTerm) ||
            book.author.toLowerCase().includes(searchTerm)
        );
    }

    // Generate HTML for books
    booksContainer.innerHTML = filteredBooks.map(book => `
        <div class="book-card" data-book-id="${book.id}">
            <img src="${book.coverImage}" alt="Couverture de ${book.title}" class="book-cover">
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">Par ${book.author}</p>
                <p class="book-category">${book.category}</p>
                <div class="book-status">
                    <span class="badge ${book.available ? 'badge-success' : 'badge-danger'}">
                        ${book.available ? 'Disponible' : 'Emprunté'}
                    </span>
                </div>
                ${isAdmin() ? `
                    <div class="book-actions">
                        <button onclick="editBook(${book.id})" class="btn btn-small" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteBook(${book.id})" class="btn btn-small btn-danger" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ` : ''}
                ${isUser() && book.available ? `
                    <button onclick="borrowBook(${book.id})" class="btn btn-primary btn-small" title="Emprunter">
                        Emprunter
                    </button>
                ` : ''}
            </div>
        </div>
    `).join('');
}

// Function to add a new book
function addBook(e) {
    e.preventDefault();
    const form = document.getElementById('add-book-form');
    const formData = new FormData(form);

    const newBook = {
        id: books.length + 1,
        title: formData.get('title'),
        author: formData.get('author'),
        category: formData.get('category'),
        available: true,
        coverImage: formData.get('coverImage') || 'images/default-book.jpg'
    };

    books.push(newBook);
    hideAddBookModal();
    displayBooks();
    showAlert('Livre ajouté avec succès!', 'success');
}

// Function to edit a book
function editBook(bookId) {
    const book = books.find(b => b.id === bookId);
    if (!book) return;

    // Populate edit form
    document.getElementById('edit-book-title').value = book.title;
    document.getElementById('edit-book-author').value = book.author;
    document.getElementById('edit-book-category').value = book.category;
    
    // Show edit modal
    const editModal = document.getElementById('edit-book-modal');
    editModal.style.display = 'block';

    // Handle form submission
    document.getElementById('edit-book-form').onsubmit = (e) => {
        e.preventDefault();
        book.title = document.getElementById('edit-book-title').value;
        book.author = document.getElementById('edit-book-author').value;
        book.category = document.getElementById('edit-book-category').value;
        
        editModal.style.display = 'none';
        displayBooks();
        showAlert('Livre modifié avec succès!', 'success');
    };
}

// Function to delete a book
function deleteBook(bookId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce livre ?')) {
        books = books.filter(book => book.id !== bookId);
        displayBooks();
        showAlert('Livre supprimé avec succès!', 'success');
    }
}

// Function to borrow a book
function borrowBook(bookId) {
    const book = books.find(b => b.id === bookId);
    if (!book) return;

    book.available = false;
    displayBooks();
    showAlert('Livre emprunté avec succès!', 'success');
}

// Function to return a book
function returnBook(bookId) {
    const book = books.find(b => b.id === bookId);
    if (!book) return;

    book.available = true;
    displayBooks();
    showAlert('Livre retourné avec succès!', 'success');
}

// Function to show alert messages
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;

    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    alertContainer.appendChild(alert);
    setTimeout(() => alert.remove(), 3000);
}

// Event listeners
document.addEventListener('DOMContentLoaded', () => {
    // Initialize books display
    displayBooks();

    // Set up search functionality
    const searchInput = document.getElementById('book-search');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            displayBooks({ search: e.target.value });
        });
    }

    // Set up category filter
    const categorySelect = document.getElementById('category-filter');
    if (categorySelect) {
        categorySelect.addEventListener('change', (e) => {
            displayBooks({ category: e.target.value });
        });
    }

    // Set up add book form
    const addBookForm = document.getElementById('add-book-form');
    if (addBookForm) {
        addBookForm.addEventListener('submit', addBook);
    }
}); 