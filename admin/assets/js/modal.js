/**
 * Modular admin modals – replaces native alert() and confirm().
 * API: ModularModal.alert(message, title?), ModularModal.confirm(message, title?, options?) -> Promise<boolean>
 * Links/buttons with data-confirm="message" are handled automatically (confirm before following link or submitting form).
 */
(function (global) {
    'use strict';

    var TITLE_ALERT = 'Modular';
    var TITLE_CONFIRM = 'Bevestigen';
    var LABEL_OK = 'OK';
    var LABEL_CANCEL = 'Annuleren';
    var LABEL_CONFIRM = 'OK';

    function createOverlay() {
        var overlay = document.createElement('div');
        overlay.className = 'modular-modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'modular-modal-title');
        return overlay;
    }

    function createDialog(headerText, bodyHtml, buttons) {
        var dialog = document.createElement('div');
        dialog.className = 'modular-modal-dialog';
        dialog.innerHTML =
            '<div class="modular-modal-header" id="modular-modal-title">' + escapeHtml(headerText) + '</div>' +
            '<div class="modular-modal-body">' + bodyHtml + '</div>' +
            '<div class="modular-modal-footer"></div>';
        var footer = dialog.querySelector('.modular-modal-footer');
        buttons.forEach(function (b) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = b.className || 'btn btn-secondary';
            btn.textContent = b.label;
            btn.addEventListener('click', b.onClick);
            footer.appendChild(btn);
        });
        return dialog;
    }

    function escapeHtml(s) {
        if (s == null) return '';
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function show(overlay, focusSelector) {
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
        var firstBtn = overlay.querySelector('.modular-modal-footer .btn');
        if (firstBtn) firstBtn.focus();
    }

    function hide(overlay, resolve, value) {
        if (!overlay || !overlay.parentNode) return;
        overlay.parentNode.removeChild(overlay);
        document.body.style.overflow = '';
        if (typeof resolve === 'function') resolve(value);
    }

    function alert(message, title) {
        return new Promise(function (resolve) {
            var overlay = createOverlay();
            var dialog = createDialog(title || TITLE_ALERT, '<p style="margin:0;">' + escapeHtml(message) + '</p>', [
                {
                    label: LABEL_OK,
                    className: 'btn btn-primary',
                    onClick: function () {
                        hide(overlay, resolve, undefined);
                    }
                }
            ]);
            overlay.appendChild(dialog);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) hide(overlay, resolve, undefined);
            });
            show(overlay);
        });
    }

    function confirm(message, title, options) {
        options = options || {};
        var confirmLabel = options.confirmLabel || LABEL_CONFIRM;
        var cancelLabel = options.cancelLabel || LABEL_CANCEL;
        var confirmClass = options.confirmClass || 'btn btn-primary';
        return new Promise(function (resolve) {
            var overlay = createOverlay();
            var dialog = createDialog(title || TITLE_CONFIRM, '<p style="margin:0;">' + escapeHtml(message) + '</p>', [
                {
                    label: cancelLabel,
                    className: 'btn btn-secondary',
                    onClick: function () {
                        hide(overlay, resolve, false);
                    }
                },
                {
                    label: confirmLabel,
                    className: confirmClass,
                    onClick: function () {
                        hide(overlay, resolve, true);
                    }
                }
            ]);
            overlay.appendChild(dialog);
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) hide(overlay, resolve, false);
            });
            show(overlay);
        });
    }

    // Global click: elements with data-confirm show confirm modal; on confirm, follow link or submit form
    function initDataConfirm() {
        document.body.addEventListener('click', function (e) {
            var el = e.target.closest('[data-confirm]');
            if (!el) return;
            var message = el.getAttribute('data-confirm');
            if (!message) return;
            e.preventDefault();
            e.stopPropagation();
            confirm(message).then(function (yes) {
                if (!yes) return;
                if (el.tagName === 'A' && el.getAttribute('href')) {
                    window.location.href = el.getAttribute('href');
                    return;
                }
                var form = el.closest('form');
                if (form && (el.type === 'submit' || el.getAttribute('type') === 'submit' || el.tagName === 'BUTTON')) {
                    form.submit();
                    return;
                }
                // Button with data-href
                var href = el.getAttribute('data-href');
                if (href) window.location.href = href;
            });
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDataConfirm);
    } else {
        initDataConfirm();
    }

    global.ModularModal = {
        alert: alert,
        confirm: confirm
    };
})(typeof window !== 'undefined' ? window : this);
