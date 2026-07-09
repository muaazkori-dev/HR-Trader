// HR Traders POS Counter Interface Client Logic
// Manages local billing cart, barcode scanner listening, and print APIs

let billingCart = [];
let scanBuffer = "";
let lastScanTime = Date.now();

document.addEventListener('DOMContentLoaded', () => {
    // Keep search input focused for scanning
    focusSearchInput();
    
    // Auto-focus search input if clicked anywhere outside inputs
    document.addEventListener('click', (e) => {
        const activeTag = document.activeElement.tagName.toLowerCase();
        if (activeTag !== 'input' && activeTag !== 'select' && activeTag !== 'button') {
            focusSearchInput();
        }
    });

    // Event listener for manual search query
    setupPOSManualSearch();

    // Event listener for Discount & Cash Paid input change calculations
    document.getElementById('pos-discount').addEventListener('input', calculateCartTotals);
    document.getElementById('pos-cash-paid').addEventListener('input', calculateCartTotals);

    // BARCODE SCANNER EVENT LISTENER (GLOBAL KEYBOARD HOOK)
    // Hardware barcode scanners trigger sequential keystrokes separated by ~15-30ms, ending with "Enter" (ASCII 13)
    document.addEventListener('keydown', (e) => {
        const now = Date.now();
        
        // If the gap between keystrokes is too long (> 80ms), treat it as slow human manual typing
        if (now - lastScanTime > 80) {
            scanBuffer = "";
        }
        
        lastScanTime = now;

        if (e.key === 'Enter') {
            if (scanBuffer.length >= 4) {
                e.preventDefault();
                console.log("Scanner Hook Captured Barcode: " + scanBuffer);
                fetchAndAddProductByBarcode(scanBuffer);
                scanBuffer = "";
            }
        } else if (e.key !== 'Shift') {
            scanBuffer += e.key;
        }
    });
});

/**
 * Focuses barcode input
 */
function focusSearchInput() {
    const input = document.getElementById('pos-search-input');
    if (input) {
        input.focus();
    }
}

/**
 * Fetch product using exact barcode via API and append to cart
 * @param {string} barcode 
 */
function fetchAndAddProductByBarcode(barcode) {
    setStatusMessage("Searching barcode...", "info");
    
    fetch(BASE_URL + `pos/api/search.php?q=${encodeURIComponent(barcode)}`)
        .then(res => res.json())
        .then(products => {
            if (products.length > 0) {
                const p = products[0]; // Barcode queries return a single matching record
                addProductToBillingCart(p.id, p.name, p.price, p.stock_quantity, p.barcode);
                setStatusMessage(`Added '${p.name}' successfully.`, "success");
            } else {
                setStatusMessage(`Barcode '${barcode}' not found in database.`, "error");
                playAlertSound();
            }
        })
        .catch(err => {
            console.error(err);
            setStatusMessage("Lookup error. Try again.", "error");
        });
}

/**
 * Add product details into local billing cart
 * @param {number} id 
 * @param {string} name 
 * @param {number} price 
 * @param {number} stock 
 * @param {string} barcode 
 */
function addProductToBillingCart(id, name, price, stock, barcode) {
    // Check if item already exists in billing list
    const existing = billingCart.find(item => item.product_id === id);

    if (existing) {
        if (existing.quantity >= stock) {
            setStatusMessage(`Cannot exceed available stock limit (${stock}) for '${name}'`, "error");
            playAlertSound();
            return;
        }
        existing.quantity += 1;
    } else {
        if (stock <= 0) {
            setStatusMessage(`Product '${name}' is out of stock.`, "error");
            playAlertSound();
            return;
        }
        billingCart.push({
            product_id: id,
            name: name,
            price: parseFloat(price),
            stock_qty: parseInt(stock),
            barcode: barcode,
            quantity: 1
        });
    }

    renderPOSBillTable();
    focusSearchInput();
}

/**
 * Helper callback for quick selection keys
 */
function addQuickProduct(id, name, price, barcode) {
    // Quick select needs to query live stock before adding
    fetch(BASE_URL + `pos/api/search.php?q=${encodeURIComponent(barcode)}`)
        .then(res => res.json())
        .then(products => {
            if (products.length > 0) {
                const p = products[0];
                addProductToBillingCart(p.id, p.name, p.price, p.stock_quantity, p.barcode);
            }
        });
}

/**
 * Increase or decrease row item count manually
 * @param {number} id 
 * @param {number} change 
 */
function updatePOSItemQty(id, change) {
    const item = billingCart.find(item => item.product_id === id);
    if (!item) return;

    const newQty = item.quantity + change;

    if (newQty <= 0) {
        removePOSItem(id);
    } else if (newQty > item.stock_qty) {
        setStatusMessage(`Cannot exceed available stock limit (${item.stock_qty}) for '${item.name}'`, "error");
        playAlertSound();
    } else {
        item.quantity = newQty;
        renderPOSBillTable();
    }
}

/**
 * Remove an item from current bill
 * @param {number} id 
 */
function removePOSItem(id) {
    billingCart = billingCart.filter(item => item.product_id !== id);
    renderPOSBillTable();
    focusSearchInput();
}

/**
 * Render items in bill body and compute calculations
 */
function renderPOSBillTable() {
    const tbody = document.getElementById('pos-bill-body');
    if (!tbody) return;

    if (billingCart.length === 0) {
        tbody.innerHTML = `
            <tr id="empty-cart-row">
                <td colspan="5" class="py-24 text-center text-slate-400">
                    <div class="flex flex-col items-center gap-3">
                        <i class="fas fa-barcode text-5xl opacity-20"></i>
                        <p class="text-sm">Scan barcode or type items to begin billing.</p>
                    </div>
                </td>
            </tr>
        `;
        calculateCartTotals();
        return;
    }

    let html = "";
    billingCart.forEach(item => {
        const total = item.price * item.quantity;
        html += `
            <tr class="hover:bg-slate-50 transition-colors border-b border-slate-200">
                <td class="p-3.5 pl-5">
                    <span class="font-bold text-slate-805 block">${item.name}</span>
                    <span class="text-xs text-slate-400 font-mono">Barcode: ${item.barcode}</span>
                </td>
                <td class="p-3.5 text-slate-700 font-semibold font-mono">Rs. ${item.price.toFixed(2)}</td>
                <td class="p-3.5 text-center">
                    <div class="inline-flex items-center bg-white border border-slate-200 rounded-xl">
                        <button onclick="updatePOSItemQty(${item.product_id}, -1)" class="px-3 py-1 text-slate-500 hover:text-slate-805 font-bold text-sm">-</button>
                        <span class="px-2 font-bold font-mono text-slate-800 text-sm w-8 text-center">${item.quantity}</span>
                        <button onclick="updatePOSItemQty(${item.product_id}, 1)" class="px-3 py-1 text-slate-500 hover:text-slate-805 font-bold text-sm">+</button>
                    </div>
                </td>
                <td class="p-3.5 text-right font-black font-mono text-emerald-600 pr-5">Rs. ${total.toFixed(2)}</td>
                <td class="p-3.5 text-center">
                    <button onclick="removePOSItem(${item.product_id})" class="p-2 text-slate-400 hover:text-rose-600 transition-colors">
                        <i class="fas fa-trash-can"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    calculateCartTotals();
}

/**
 * Handle Subtotal, Discount %, Net Amount, Cash Paid and Change calculations
 */
function calculateCartTotals() {
    let subtotal = 0;
    billingCart.forEach(item => {
        subtotal += item.price * item.quantity;
    });

    const discountInput = document.getElementById('pos-discount');
    const cashInput = document.getElementById('pos-cash-paid');
    
    let discount = discountInput ? parseFloat(discountInput.value) : 0;
    if (isNaN(discount) || discount < 0) discount = 0;
    if (discount > 100) discount = 100;

    let cashPaid = cashInput ? parseFloat(cashInput.value) : 0;
    if (isNaN(cashPaid) || cashPaid < 0) cashPaid = 0;

    const netAmount = subtotal * (1 - discount / 100);
    const changeDue = Math.max(0, cashPaid - netAmount);

    document.getElementById('pos-subtotal').innerText = `Rs. ${subtotal.toFixed(2)}`;
    document.getElementById('pos-net-payable').innerText = `Rs. ${netAmount.toFixed(2)}`;
    document.getElementById('pos-change-due').innerText = `Rs. ${changeDue.toFixed(2)}`;
}

/**
 * AJAX Submit POS checkout transaction
 */
function checkoutPOS() {
    if (billingCart.length === 0) {
        setStatusMessage("Cannot checkout an empty invoice list.", "error");
        playAlertSound();
        return;
    }

    const discountInput = document.getElementById('pos-discount');
    const discount = discountInput ? parseFloat(discountInput.value) : 0;
    const paymentMethod = document.getElementById('pos-payment-method').value;
    const cashPaid = parseFloat(document.getElementById('pos-cash-paid').value) || 0;

    let subtotal = 0;
    billingCart.forEach(item => subtotal += item.price * item.quantity);
    const netAmount = subtotal * (1 - discount / 100);

    if (cashPaid < netAmount && paymentMethod === 'Cash') {
        if (!confirm("Received Cash is less than Net Amount Due. Proceed anyway?")) {
            return;
        }
    }

    const payload = {
        items: billingCart.map(item => ({
            product_id: item.product_id,
            quantity: item.quantity
        })),
        discount: discount,
        payment_method: paymentMethod
    };

    setStatusMessage("Submitting POS order...", "info");

    fetch(BASE_URL + 'pos/api/checkout.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            setStatusMessage("Transaction completed. Spawning printable receipt window...", "success");
            
            // Clear cart
            billingCart = [];
            renderPOSBillTable();
            
            // Reset discount & cash paid inputs
            if (discountInput) discountInput.value = 0;
            if (document.getElementById('pos-cash-paid')) document.getElementById('pos-cash-paid').value = 0;
            
            // Open print window popup optimized for thermal paper sizes (58mm / 80mm roll width)
            const printUrl = BASE_URL + `pos/print.php?sale_id=${data.sale_id}`;
            const printWindow = window.open(printUrl, 'Thermal Receipt', 'width=350,height=600,top=100,left=100');
            if (printWindow) {
                printWindow.focus();
            } else {
                // If popup blocker, redirect instead or advise
                alert("Popup blocker prevented receipt preview. Please allow popups or visit: " + printUrl);
            }
        } else {
            setStatusMessage(data.message || "Failed to submit transaction.", "error");
            playAlertSound();
        }
    })
    .catch(err => {
        console.error(err);
        setStatusMessage("Network error checkout failed.", "error");
    });
}

/**
 * Configure auto suggestions dropdown for manual query typing
 */
function setupPOSManualSearch() {
    const input = document.getElementById('pos-search-input');
    const resultsBox = document.getElementById('pos-search-results');
    const clearBtn = document.getElementById('clear-search-btn');

    if (!input || !resultsBox) return;

    let timer;

    input.addEventListener('input', (e) => {
        clearTimeout(timer);
        const query = e.target.value.trim();

        if (query.length > 0) {
            clearBtn.classList.remove('hidden');
        } else {
            clearBtn.classList.add('hidden');
        }

        if (query.length < 2) {
            resultsBox.innerHTML = '';
            resultsBox.classList.add('hidden');
            return;
        }

        timer = setTimeout(() => {
            fetch(BASE_URL + `pos/api/search.php?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(products => {
                    if (products.length === 0) {
                        resultsBox.innerHTML = `<div class="p-3 text-xs text-slate-500 text-center">No catalog matches.</div>`;
                        resultsBox.classList.remove('hidden');
                        return;
                    }

                    let html = '';
                    products.forEach(p => {
                        html += `
                            <div onclick="selectPOSSearchItem(${p.id}, '${p.name.replace(/'/g, "\\'")}', ${p.price}, ${p.stock_quantity}, '${p.barcode}')"
                                 class="p-2.5 hover:bg-slate-50 border-b border-slate-200 last:border-0 cursor-pointer flex items-center justify-between transition-colors">
                                <div>
                                    <span class="font-bold text-slate-800 block text-xs">${p.name}</span>
                                    <span class="text-[10px] text-slate-500 font-mono">Barcode: ${p.barcode}${p.weight ? ` | Weight: ${p.weight}` : ''}</span>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-bold text-emerald-600 block">Rs. ${p.price.toFixed(2)}</span>
                                    <span class="text-[9px] text-slate-500 block">Stock: ${p.stock_quantity}</span>
                                </div>
                            </div>
                        `;
                    });
                    resultsBox.innerHTML = html;
                    resultsBox.classList.remove('hidden');
                })
                .catch(err => console.error(err));
        }, 150);
    });

    clearBtn.addEventListener('click', () => {
        input.value = "";
        clearBtn.classList.add('hidden');
        resultsBox.innerHTML = '';
        resultsBox.classList.add('hidden');
        focusSearchInput();
    });

    // Close suggestions box if click occurs outside
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.classList.add('hidden');
        }
    });
}

function selectPOSSearchItem(id, name, price, stock, barcode) {
    addProductToBillingCart(id, name, price, stock, barcode);
    const input = document.getElementById('pos-search-input');
    const resultsBox = document.getElementById('pos-search-results');
    const clearBtn = document.getElementById('clear-search-btn');
    
    input.value = "";
    clearBtn.classList.add('hidden');
    resultsBox.innerHTML = '';
    resultsBox.classList.add('hidden');
    focusSearchInput();
}

/**
 * Output helper to edit alert messaging in POS view
 */
function setStatusMessage(msg, type = "info") {
    const el = document.getElementById('pos-status-msg');
    if (!el) return;

    let icon = '<i class="fas fa-circle-info text-emerald-650 text-base"></i>';
    if (type === "success") {
        icon = '<i class="fas fa-check-circle text-emerald-650 text-base"></i>';
    } else if (type === "error") {
        icon = '<i class="fas fa-times-circle text-rose-600 text-base animate-pulse"></i>';
    }

    el.innerHTML = `${icon} <span>${msg}</span>`;
}

/**
 * Browser sound alerts helper for warning validations
 */
function playAlertSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(300, audioCtx.currentTime); // Low pitch error frequency
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        
        oscillator.start();
        setTimeout(() => oscillator.stop(), 200);
    } catch(e) {
        // Fallback if browser security blocks audio contexts
        console.warn("Audio block context: " + e.message);
    }
}
