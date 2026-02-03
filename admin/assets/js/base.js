// Base JavaScript functionality

// DOM Ready function
function ready(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

// Event Delegation
function delegate(element, selector, event, handler) {
    element.addEventListener(event, function(e) {
        if (e.target.matches(selector)) {
            handler.call(this, e);
        }
    });
}

// Form Validation
function validateForm(form) {
    const inputs = form.querySelectorAll('.form-control');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('has-error');
            isValid = false;
        } else {
            input.classList.remove('has-error');
        }
    });

    return isValid;
}

// Alert System
function showAlert(type, message, timeout = 3000) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;

    document.body.appendChild(alert);

    setTimeout(() => {
        alert.remove();
    }, timeout);
}

// Loading State
function setLoading(element, isLoading) {
    if (isLoading) {
        element.classList.add('loading');
    } else {
        element.classList.remove('loading');
    }
}

// AJAX Request Helper
async function ajaxRequest(method, url, data = null) {
    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: data ? JSON.stringify(data) : null
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('AJAX Error:', error);
        throw error;
    }
}

// Sidebar collapse toggle: persist state, main content stretches with flex
(function initSidebarToggle() {
    function run() {
        var btn = document.getElementById('sidebar-toggle');
        if (!btn) return;
        var collapsed = localStorage.getItem('modular-sidebar-collapsed') === '1';
        if (collapsed) document.body.classList.add('sidebar-collapsed');
        btn.addEventListener('click', function() {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('modular-sidebar-collapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
