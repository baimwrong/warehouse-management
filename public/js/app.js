/**
 * Enterprise WMS Offline JavaScript Utilities
 */

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all Bootstrap tooltips if available
    if (window.bootstrap && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
    }

    // Auto-dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });
});

/**
 * Dynamic Item Row Generator for Inbound/Outbound/Adjustment Forms
 */
window.dynamicRowCounter = window.dynamicRowCounter || 100;
window.addItemRow = function (containerId, templateId) {
    const container = document.getElementById(containerId);
    const template = document.getElementById(templateId);
    if (!container || !template) return;

    window.dynamicRowCounter++;
    const index = window.dynamicRowCounter;
    let html = template.innerHTML.replace(/{index}/g, index);
    
    const div = document.createElement('div');
    div.innerHTML = html.trim();
    const rowElement = div.firstChild;

    container.appendChild(rowElement);
};

window.removeItemRow = function (button) {
    const row = button.closest('.dynamic-item-row');
    if (row) {
        row.remove();
        if (typeof window.recalculateOrderTotal === 'function') {
            window.recalculateOrderTotal();
        }
    }
};

/**
 * Total recalculation helper for Order Forms
 */
window.recalculateOrderTotal = function () {
    let total = 0;
    const rows = document.querySelectorAll('.dynamic-item-row');
    rows.forEach(row => {
        const qtyInput = row.querySelector('.item-qty');
        const priceInput = row.querySelector('.item-price');
        const subtotalSpan = row.querySelector('.item-subtotal');

        if (qtyInput && priceInput) {
            const qty = parseFloat(qtyInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const subtotal = qty * price;
            total += subtotal;

            if (subtotalSpan) {
                subtotalSpan.textContent = '$' + subtotal.toFixed(2);
            }
        }
    });

    const grandTotalElement = document.getElementById('grand-total');
    if (grandTotalElement) {
        grandTotalElement.textContent = '$' + total.toFixed(2);
    }
};
