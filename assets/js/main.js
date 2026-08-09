// Personal Finance Management System — shared utilities

document.addEventListener('DOMContentLoaded', function () {

    // Auto-fade PHP session flash alerts after 5 s
    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.3s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 300);
        }, 5000);
    });

    // Animate progress bars on page load
    document.querySelectorAll('.progress-bar').forEach(function (bar) {
        const target = bar.style.width;
        bar.style.width = '0%';
        setTimeout(function () {
            bar.style.transition = 'width 0.9s ease-in-out';
            bar.style.width = target;
        }, 100);
    });

    // Auto-format decimal inputs to 2 dp on blur
    document.querySelectorAll('input[type="number"][step="0.01"]').forEach(function (el) {
        el.addEventListener('blur', function () {
            if (this.value) this.value = parseFloat(this.value).toFixed(2);
        });
    });

    // Bootstrap's collapse handles the mobile menu — no manual toggle needed

});

// Show a Bootstrap toast notification
function showNotification(message, type) {
    type = type || 'info';
    var cls = type === 'error' ? 'bg-danger' :
              type === 'success' ? 'bg-success' :
              type === 'warning' ? 'bg-warning text-dark' : 'bg-info';

    var id  = 'toast-' + Date.now();
    var html = '<div id="' + id + '" class="toast align-items-center text-white ' + cls +
               ' border-0 position-fixed top-0 end-0 m-3" style="z-index:9999" role="alert">' +
               '<div class="d-flex"><div class="toast-body">' + message + '</div>' +
               '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>' +
               '</div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
    var el = document.getElementById(id);
    var t  = new bootstrap.Toast(el, { delay: 5000 });
    t.show();
    el.addEventListener('hidden.bs.toast', function () { el.remove(); });
}

// Format a number as KSh currency string
function formatCurrency(amount) {
    return 'KSh ' + Number(amount).toLocaleString('en-KE', {
        minimumFractionDigits: 2, maximumFractionDigits: 2
    });
}
