// HR Traders E-commerce Front-end Vanilla Javascript
// Handles AJAX cart updates, drawer transitions, and storefront search recommendations

document.addEventListener('DOMContentLoaded', () => {
    // Initial cart load
    refreshCartDrawer();

    // Initialize stars selection
    initStarsSelector();

    // Setup live search listeners
    setupStorefrontSearch('storefront-search', 'search-results-dropdown');
    setupStorefrontSearch('storefront-search-mobile', 'search-results-dropdown-mobile');

    // Timing Popover Toggle logic
    const timingToggleBtn = document.getElementById('timing-toggle-btn');
    const timingPopover = document.getElementById('timing-popover');
    const timingCloseBtn = document.getElementById('timing-close-btn');

    if (timingToggleBtn && timingPopover) {
        const showPopover = () => {
            timingPopover.classList.remove('hidden');
        };

        const hidePopover = () => {
            timingPopover.classList.add('hidden');
        };

        timingToggleBtn.addEventListener('click', (e) => {
            if (!timingPopover.contains(e.target)) {
                if (timingPopover.classList.contains('hidden')) {
                    showPopover();
                } else {
                    hidePopover();
                }
            }
        });

        document.addEventListener('click', (e) => {
            if (!timingPopover.classList.contains('hidden')) {
                // If user clicks the close button (or its icon), or clicks outside both the popover and the toggle button
                if ((timingCloseBtn && timingCloseBtn.contains(e.target)) || 
                    (!timingPopover.contains(e.target) && !timingToggleBtn.contains(e.target))) {
                    hidePopover();
                }
            }
        });
    }
});

/**
 * Slide the Cart Drawer open or closed
 * @param {boolean} open 
 */
function toggleCartDrawer(open) {
    const drawer = document.getElementById('cart-drawer');
    const backdrop = document.getElementById('cart-drawer-backdrop');
    
    if (open) {
        // Refresh items before showing
        refreshCartDrawer();
        drawer.classList.remove('translate-x-full');
        backdrop.classList.remove('opacity-0', 'pointer-events-none');
        backdrop.classList.add('opacity-100');
    } else {
        drawer.classList.add('translate-x-full');
        backdrop.classList.remove('opacity-100');
        backdrop.classList.add('opacity-0', 'pointer-events-none');
    }
}

/**
 * Perform AJAX add to cart
 * @param {number} productId 
 */
function addToCart(productId) {
    fetch(BASE_URL + `api/cart.php?action=add&product_id=${productId}`, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                updateCartBadge(data.cart_count);
                refreshCartDrawer();
            } else {
                showToast(data.message || 'Failed to add item.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error, please try again.', 'error');
        });
}

/**
 * Perform AJAX add to cart and redirect immediately to checkout
 * @param {number} productId
 */
function buyNow(productId) {
    fetch(BASE_URL + `api/cart.php?action=add&product_id=${productId}`, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = BASE_URL + 'checkout.php';
            } else {
                showToast(data.message || 'Failed to add item.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Network error, please try again.', 'error');
        });
}

/**
 * Update the cart quantities via AJAX
 * @param {number} productId 
 * @param {number} newQty 
 */
function updateCartQty(productId, newQty) {
    fetch(BASE_URL + `api/cart.php?action=update&product_id=${productId}&quantity=${newQty}`, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.cart_count);
                refreshCartDrawer();
                // If we are on checkout page, refresh checkout table
                if (typeof loadCheckoutSummary === 'function') {
                    loadCheckoutSummary();
                }
            } else {
                showToast(data.message || 'Failed to update quantity.', 'error');
            }
        })
        .catch(err => console.error(err));
}

/**
 * Remove product from cart via AJAX
 * @param {number} productId 
 */
function removeFromCart(productId) {
    fetch(BASE_URL + `api/cart.php?action=remove&product_id=${productId}`, { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'info');
                updateCartBadge(data.cart_count);
                refreshCartDrawer();
                if (typeof loadCheckoutSummary === 'function') {
                    loadCheckoutSummary();
                }
            }
        })
        .catch(err => console.error(err));
}

/**
 * Clear whole cart
 */
function clearCart() {
    if (confirm("Are you sure you want to clear your cart?")) {
        fetch(BASE_URL + 'api/cart.php?action=clear', { method: 'GET' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast("Cart cleared.", 'info');
                    updateCartBadge(0);
                    refreshCartDrawer();
                    if (typeof loadCheckoutSummary === 'function') {
                        loadCheckoutSummary();
                    }
                }
            })
            .catch(err => console.error(err));
    }
}

/**
 * Update count bubble in header
 * @param {number} count 
 */
function updateCartBadge(count) {
    const badge = document.getElementById('cart-badge');
    if (badge) {
        badge.innerText = count;
        if (count > 0) {
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

/**
 * Reload Cart items inside drawer using AJAX
 */
function refreshCartDrawer() {
    const container = document.getElementById('cart-items-container');
    const totalEl = document.getElementById('cart-drawer-total');
    
    if (!container) return;

    fetch(BASE_URL + 'api/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (!data.success || data.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-slate-500">
                        <i class="fas fa-shopping-basket text-4xl mb-3 opacity-30"></i>
                        <p class="text-sm">Your cart is empty.</p>
                        <a href="${BASE_URL}shop.php" onclick="toggleCartDrawer(false)" class="mt-4 text-xs font-semibold text-emerald-400 hover:underline">Browse Products</a>
                    </div>
                `;
                totalEl.innerText = "Rs. 0.00";
                return;
            }

            totalEl.innerText = `Rs. ${data.subtotal.toFixed(2)}`;
            
            let html = '';
            data.items.forEach(item => {
                const isFrozen = item.category === 'ice_cream';
                html += `
                    <div class="flex items-center gap-3 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                        <!-- Product Icon -->
                        <div class="w-12 h-12 bg-slate-50 rounded-lg flex items-center justify-center flex-shrink-0 text-slate-500">
                            <i class="fas ${getProductIcon(item.category)} text-xl"></i>
                        </div>
                        
                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-sm text-slate-800 truncate" title="${item.name}">${item.name}</h4>
                            <p class="text-xs text-slate-500">${item.weight ? `${item.weight} | ` : ''}Rs. ${item.price.toFixed(2)}</p>
                            ${isFrozen ? '<p class="text-[10px] text-rose-600 font-semibold mt-0.5"><i class="fas fa-circle-exclamation mr-1"></i>Nearby only</p>' : ''}
                        </div>

                        <!-- Quantity Actions -->
                        <div class="flex flex-col items-center gap-1">
                            <div class="flex items-center bg-slate-100 border border-slate-200 rounded-lg">
                                <button onclick="updateCartQty(${item.id}, ${item.qty - 1})" class="px-2 py-0.5 text-slate-500 hover:text-slate-800 text-xs">-</button>
                                <span class="px-2 text-xs font-bold text-slate-800">${item.qty}</span>
                                <button onclick="updateCartQty(${item.id}, ${item.qty + 1})" class="px-2 py-0.5 text-slate-500 hover:text-slate-800 text-xs">+</button>
                            </div>
                            <button onclick="removeFromCart(${item.id})" class="text-[10px] text-slate-400 hover:text-red-605 transition-colors">Remove</button>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = `<p class="text-xs text-red-400 text-center py-4">Error loading cart.</p>`;
        });
}

/**
 * Helper to match category to FontAwesome icon
 * @param {string} category 
 * @returns {string} FontAwesome icon class
 */
function getProductIcon(category) {
    switch (category) {
        case 'anaj': return 'fa-seedling';
        case 'beverages': return 'fa-glass-water';
        case 'ice_cream': return 'fa-ice-cream';
        case 'milk': return 'fa-cow';
        case 'cosmetics': return 'fa-sparkles';
        case 'shampoo': return 'fa-pump-soap';
        case 'soap': return 'fa-soap';
        case 'toothpaste': return 'fa-tooth';
        default: return 'fa-box';
    }
}

/**
 * Toast notifications generator
 * @param {string} message 
 * @param {string} type 'success', 'error', 'info'
 */
function showToast(message, type = 'success') {
    // Create toast container if not exists
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-50 flex flex-col gap-2 max-w-sm w-full px-4 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-msg pointer-events-auto p-4 rounded-xl shadow-2xl flex items-center gap-3 border text-sm ';
    
    // Theme colors
    if (type === 'success') {
        toast.className += 'bg-white border-emerald-250 text-emerald-700 shadow-xl';
        toast.innerHTML = `<i class="fas fa-check-circle text-emerald-600"></i> <span>${message}</span>`;
    } else if (type === 'error') {
        toast.className += 'bg-white border-rose-250 text-rose-700 shadow-xl';
        toast.innerHTML = `<i class="fas fa-times-circle text-rose-600"></i> <span>${message}</span>`;
    } else {
        toast.className += 'bg-white border-slate-250 text-slate-700 shadow-xl';
        toast.innerHTML = `<i class="fas fa-info-circle text-slate-550"></i> <span>${message}</span>`;
    }

    container.appendChild(toast);

    // Auto remove toast after 3 seconds
    setTimeout(() => {
        toast.style.transition = 'all 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, -20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function setupStorefrontSearch(inputId, suggestionsId) {
    const searchInput = document.getElementById(inputId);
    const suggestionsBox = document.getElementById(suggestionsId);
    
    if (!searchInput || !suggestionsBox) return;

    let debounceTimer;
    let abortController = null;

    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length < 1) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            if (abortController) {
                abortController.abort();
            }
            return;
        }

        debounceTimer = setTimeout(() => {
            // Abort previous request if still in flight
            if (abortController) {
                abortController.abort();
            }
            abortController = new AbortController();
            const signal = abortController.signal;

            fetch(BASE_URL + `api/live_search.php?q=${encodeURIComponent(query)}`, { signal })
                .then(res => res.json())
                .then(products => {
                    if (products.length === 0) {
                        suggestionsBox.innerHTML = `<div class="p-4 text-xs text-slate-505 text-center">No grocery matches found for "${query}"</div>`;
                        suggestionsBox.classList.remove('hidden');
                        return;
                    }

                    let html = '';
                    products.forEach(p => {
                        html += `
                            <div onclick="window.location.href = '${BASE_URL}shop.php?query=' + encodeURIComponent('${p.name.replace(/'/g, "\\'")}')" 
                                 class="p-3 hover:bg-slate-50 flex items-center justify-between gap-3 border-b border-slate-100 last:border-0 cursor-pointer transition-colors">
                                <div class="flex items-center gap-3">
                                    <img src="${BASE_URL + p.image}" class="w-8 h-8 object-cover rounded-lg border border-slate-200 bg-slate-50 flex-shrink-0" loading="lazy">
                                    <div>
                                        <h5 class="text-sm font-semibold text-slate-805 leading-tight">${p.name}</h5>
                                        <p class="text-xs text-slate-500 font-mono">Rs. ${p.price.toFixed(2)}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[8px] uppercase font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 flex-shrink-0">
                                    ${p.category.replace('_', ' ')}
                                </span>
                            </div>
                        `;
                    });
                    suggestionsBox.innerHTML = html;
                    suggestionsBox.classList.remove('hidden');
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error(err);
                    }
                });
        }, 300);
    });

    // Close suggestions box when click occurs outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.classList.add('hidden');
        }
    });
}

/**
 * Initialize star selection hover & click handlers for writing a review
 */
function initStarsSelector() {
    const starsContainer = document.getElementById('review-stars-selector');
    if (!starsContainer) return;

    const stars = starsContainer.querySelectorAll('i');
    const ratingInput = document.getElementById('review-rating');

    stars.forEach(star => {
        // Highlight on hover
        star.addEventListener('mouseenter', () => {
            const val = parseInt(star.getAttribute('data-value'));
            highlightStars(stars, val);
        });

        // Click to set rating
        star.addEventListener('click', () => {
            const val = parseInt(star.getAttribute('data-value'));
            ratingInput.value = val;
            highlightStars(stars, val);
        });
    });

    // Reset highlighting to selected value on mouseleave
    starsContainer.addEventListener('mouseleave', () => {
        const selectedVal = parseInt(ratingInput.value) || 0;
        highlightStars(stars, selectedVal);
    });
}

function highlightStars(stars, value) {
    stars.forEach(star => {
        const starVal = parseInt(star.getAttribute('data-value'));
        if (starVal <= value) {
            star.classList.remove('text-slate-300');
            star.classList.add('text-amber-400');
        } else {
            star.classList.remove('text-amber-400');
            star.classList.add('text-slate-300');
        }
    });
}

/**
 * Open Product Details modal overlay
 * @param {number} productId
 */
function openProductDetails(productId) {
    const modal = document.getElementById('product-details-modal');
    if (!modal) return;

    // Reset review form inputs
    const form = document.getElementById('product-review-form');
    if (form) form.reset();
    const ratingInput = document.getElementById('review-rating');
    if (ratingInput) ratingInput.value = 0;
    const starsContainer = document.getElementById('review-stars-selector');
    if (starsContainer) {
        const stars = starsContainer.querySelectorAll('i');
        highlightStars(stars, 0);
    }
    
    // Set hidden product ID in form
    const pidInput = document.getElementById('review-product-id');
    if (pidInput) pidInput.value = productId;

    // Show loading spinner or blank state first
    document.getElementById('modal-product-name').innerText = "Loading product...";
    document.getElementById('modal-product-desc').innerText = "";
    document.getElementById('modal-product-price').innerText = "";
    document.getElementById('modal-product-weight').innerText = "";
    document.getElementById('modal-product-barcode').innerText = "";
    document.getElementById('modal-product-category').innerText = "";
    document.getElementById('modal-product-img').src = BASE_URL + 'assets/images/placeholder.svg';
    document.getElementById('modal-reviews-list').innerHTML = `<p class="text-xs text-slate-400 text-center py-4"><i class="fas fa-spinner animate-spin mr-1"></i> Loading reviews...</p>`;
    document.getElementById('modal-average-stars').innerHTML = '';
    document.getElementById('modal-average-score').innerText = '0.0';
    document.getElementById('modal-total-reviews-count').innerText = '(0 reviews)';

    // Open overlay animation
    modal.classList.remove('opacity-0', 'pointer-events-none');
    modal.querySelector('.relative').classList.remove('scale-95');
    modal.querySelector('.relative').classList.add('scale-100');

    // Fetch details & reviews
    fetch(BASE_URL + `api/reviews.php?product_id=${productId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message || 'Product loading failed.', 'error');
                closeProductDetails();
                return;
            }

            const p = data.product;
            
            // Populate Details
            document.getElementById('modal-product-name').innerText = p.name;
            document.getElementById('modal-product-desc').innerText = p.description || 'No description available for this product.';
            document.getElementById('modal-product-price').innerText = `Rs. ${p.price.toFixed(2)}`;
            document.getElementById('modal-product-weight').innerText = p.weight || 'N/A';
            document.getElementById('modal-product-barcode').innerText = p.barcode || 'N/A';
            document.getElementById('modal-product-category').innerText = p.category.replace('_', ' ');
            document.getElementById('modal-product-img').src = BASE_URL + p.image;

            // Stock Badge styling
            const stockBadge = document.getElementById('modal-product-stock-badge');
            if (p.stock_quantity <= 0) {
                stockBadge.className = "px-2.5 py-0.5 rounded-lg text-[9px] font-extrabold uppercase bg-rose-600 text-white";
                stockBadge.innerText = "Sold Out";
            } else {
                stockBadge.className = "px-2.5 py-0.5 rounded-lg text-[9px] font-extrabold uppercase bg-emerald-50 text-emerald-700 border border-emerald-250";
                stockBadge.innerText = `In Stock (${p.stock_quantity})`;
            }

            // Populate Action Buttons
            const actionsContainer = document.getElementById('modal-actions');
            if (p.stock_quantity <= 0) {
                actionsContainer.innerHTML = `
                    <button disabled class="w-full px-5 py-3 bg-slate-100 text-slate-400 rounded-xl font-bold text-xs cursor-not-allowed">
                        Out of Stock
                    </button>
                `;
            } else {
                actionsContainer.innerHTML = `
                    <button onclick="addToCart(${p.id})" class="flex-1 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-300 active:scale-95 text-slate-700 rounded-xl font-bold text-xs transition-all flex items-center justify-center gap-1.5">
                        <i class="fas fa-plus"></i> Add
                    </button>
                    <button onclick="buyNow(${p.id})" class="flex-1 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 active:scale-95 text-white rounded-xl font-black text-xs transition-all shadow-md shadow-emerald-600/10 flex items-center justify-center gap-1.5">
                        Buy Now
                    </button>
                `;
            }

            // Populate Average Score & Total reviews
            document.getElementById('modal-average-score').innerText = data.average_rating.toFixed(1);
            document.getElementById('modal-total-reviews-count').innerText = `(${data.total_reviews} review${data.total_reviews !== 1 ? 's' : ''})`;
            
            // Average Stars Icons
            let starsHtml = '';
            const floorRating = Math.floor(data.average_rating);
            const hasHalf = (data.average_rating - floorRating) >= 0.4;
            for (let i = 1; i <= 5; i++) {
                if (i <= floorRating) {
                    starsHtml += '<i class="fas fa-star text-amber-400"></i>';
                } else if (i === floorRating + 1 && hasHalf) {
                    starsHtml += '<i class="fas fa-star-half-alt text-amber-400"></i>';
                } else {
                    starsHtml += '<i class="far fa-star text-slate-350"></i>';
                }
            }
            document.getElementById('modal-average-stars').innerHTML = starsHtml;

            // Render Reviews List
            const reviewsContainer = document.getElementById('modal-reviews-list');
            if (data.reviews.length === 0) {
                reviewsContainer.innerHTML = `
                    <div class="text-center py-6 text-slate-400">
                        <p class="text-xs">No reviews yet. Be the first to review this product!</p>
                    </div>
                `;
            } else {
                let revHtml = '';
                data.reviews.forEach(r => {
                    let rStars = '';
                    for (let k = 1; k <= 5; k++) {
                        if (k <= r.rating) {
                            rStars += '<i class="fas fa-star text-amber-400 text-[10px]"></i>';
                        } else {
                            rStars += '<i class="far fa-star text-slate-300 text-[10px]"></i>';
                        }
                    }
                    
                    // Simple relative or formatted date
                    const reviewDate = new Date(r.created_at).toLocaleDateString(undefined, {
                        year: 'numeric', month: 'short', day: 'numeric'
                    });

                    revHtml += `
                        <div class="bg-white p-3.5 border border-slate-200/60 rounded-2xl shadow-sm text-xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-800">${r.reviewer_name}</span>
                                    <div class="flex gap-0.5 text-amber-450">${rStars}</div>
                                </div>
                                <span class="text-[10px] text-slate-400">${reviewDate}</span>
                            </div>
                            <p class="text-slate-605 font-medium leading-relaxed">${r.comment}</p>
                        </div>
                    `;
                });
                reviewsContainer.innerHTML = revHtml;
            }
        })
        .catch(err => {
            console.error(err);
            showToast('Failed to load reviews.', 'error');
        });
}

/**
 * Close Product Details modal overlay
 */
function closeProductDetails() {
    const modal = document.getElementById('product-details-modal');
    if (!modal) return;

    modal.classList.add('opacity-0', 'pointer-events-none');
    modal.querySelector('.relative').classList.remove('scale-100');
    modal.querySelector('.relative').classList.add('scale-95');
}

/**
 * Submit Product Review via AJAX
 */
function submitProductReview() {
    const pid = document.getElementById('review-product-id').value;
    const name = document.getElementById('review-name').value.trim();
    const rating = parseInt(document.getElementById('review-rating').value);
    const comment = document.getElementById('review-comment').value.trim();

    if (!pid || !name || !comment) {
        showToast('Please fill in all fields.', 'error');
        return;
    }

    if (rating < 1 || rating > 5) {
        showToast('Please select a star rating (1 to 5).', 'error');
        return;
    }

    const submitBtn = document.getElementById('review-submit-btn');
    const originalBtnHtml = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<i class="fas fa-spinner animate-spin mr-1"></i> Submitting...`;

    // Construct form data payload
    const formData = new FormData();
    formData.append('product_id', pid);
    formData.append('reviewer_name', name);
    formData.append('rating', rating);
    formData.append('comment', comment);

    fetch(BASE_URL + 'api/reviews.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;

        if (data.success) {
            showToast(data.message, 'success');
            // Refresh modal reviews listing
            openProductDetails(pid);
        } else {
            showToast(data.message || 'Submission failed.', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnHtml;
        showToast('Network error, please try again.', 'error');
    });
}
