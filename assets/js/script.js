// Almas Clothing Brand - Custom JavaScript

// Toast notification function
function showToast(message, type = 'success') {
    const toastContainer = document.querySelector('.toast-container');
    const toastId = 'toast-' + Date.now();
    
    const toastHTML = `
        <div id="${toastId}" class="toast ${type}" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">
                    ${type === 'success' ? '<i class="fas fa-check-circle text-success"></i> Success' : 
                      type === 'error' ? '<i class="fas fa-exclamation-circle text-danger"></i> Error' : 
                      '<i class="fas fa-exclamation-triangle text-warning"></i> Warning'}
                </strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

// Add to cart function
function addToCart(productId, quantity = 1) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product added to cart successfully!', 'success');
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Update cart count in navigation
function updateCartCount(count) {
    const cartLink = document.querySelector('a[href="cart.php"]');
    if (cartLink) {
        let cartBadge = cartLink.querySelector('.badge');
        
        // If badge doesn't exist, create it
        if (!cartBadge) {
            cartBadge = document.createElement('span');
            cartBadge.className = 'badge bg-danger ms-1';
            cartLink.appendChild(cartBadge);
        }
        
        if (count > 0) {
            cartBadge.textContent = count;
            cartBadge.style.display = 'inline-block';
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

// Update cart item quantity
function updateCartItem(itemId, quantity) {
    const formData = new FormData();
    formData.append('update_cart', 'true');
    formData.append('item_id', itemId);
    formData.append('quantity', quantity);
    
    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated cart
        } else {
            showToast(data.message || 'Failed to update cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred. Please try again.', 'error');
    });
}

// Remove from cart
function removeFromCart(itemId) {
    if (confirm('Are you sure you want to remove this item from cart?')) {
        const formData = new FormData();
        formData.append('remove_item', 'true');
        formData.append('item_id', itemId);
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Item removed from cart', 'success');
                location.reload(); // Reload to show updated cart
            } else {
                showToast(data.message || 'Failed to remove item', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
    }
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
        
        // Email validation
        if (input.type === 'email' && input.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });
    
    return isValid;
}

// Search products
function searchProducts(query) {
    if (query.length < 2) return;
    
    const formData = new FormData();
    formData.append('search', query);
    
    fetch('shop.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Update product grid with search results
        const productGrid = document.getElementById('product-grid');
        if (productGrid) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('product-grid');
            if (newGrid) {
                productGrid.innerHTML = newGrid.innerHTML;
            }
        }
    })
    .catch(error => {
        console.error('Search error:', error);
    });
}

// Filter products by category
function filterByCategory(categoryId) {
    const formData = new FormData();
    formData.append('category', categoryId);
    
    fetch('shop.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Update product grid with filtered results
        const productGrid = document.getElementById('product-grid');
        if (productGrid) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('product-grid');
            if (newGrid) {
                productGrid.innerHTML = newGrid.innerHTML;
            }
        }
    })
    .catch(error => {
        console.error('Filter error:', error);
    });
}

// Sort products
function sortProducts(sortBy) {
    const formData = new FormData();
    formData.append('sort', sortBy);
    
    fetch('shop.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Update product grid with sorted results
        const productGrid = document.getElementById('product-grid');
        if (productGrid) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newGrid = doc.getElementById('product-grid');
            if (newGrid) {
                productGrid.innerHTML = newGrid.innerHTML;
            }
        }
    })
    .catch(error => {
        console.error('Sort error:', error);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchProducts(this.value);
            }, 500);
        });
    }
    
    // Category filter
    const categorySelect = document.getElementById('category-filter');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            filterByCategory(this.value);
        });
    }
    
    // Sort functionality
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            sortProducts(this.value);
        });
    }
    
    // Form validation on submit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(form.id)) {
                e.preventDefault();
                showToast('Please fill in all required fields correctly.', 'warning');
            }
        });
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Loading spinner
function showLoading(element) {
    element.innerHTML = '<div class="spinner"></div>';
}

function hideLoading(element, originalContent) {
    element.innerHTML = originalContent;
}

// Clear cart function
function clearCart() {
    if (confirm('Are you sure you want to clear your entire cart?')) {
        const formData = new FormData();
        formData.append('clear_cart', 'true');
        
        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Cart cleared successfully!', 'success');
                location.reload();
            } else {
                showToast('Failed to clear cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred. Please try again.', 'error');
        });
    }
}
