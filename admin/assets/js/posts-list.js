(function () {
    var table = document.getElementById('posts-table');
    if (!table) return;

    var csrf = table.getAttribute('data-csrf') || '';
    var quickSaveUrl = table.getAttribute('data-quick-save-url') || '';
    var selectAll = document.getElementById('posts-select-all');
    var bulkForm = document.getElementById('posts-bulk-form');
    var bulkAction = document.getElementById('posts-bulk-action');

    function getCheckboxes() {
        return Array.prototype.slice.call(document.querySelectorAll('#posts-table tbody .post-select-checkbox'));
    }

    function closeAllQuickEdit() {
        var rows = table.querySelectorAll('tr.post-quick-edit-row');
        for (var i = 0; i < rows.length; i++) {
            rows[i].hidden = true;
        }
        var btns = table.querySelectorAll('.post-row-quick-edit-btn');
        for (var j = 0; j < btns.length; j++) {
            btns[j].setAttribute('aria-expanded', 'false');
        }
    }

    function openQuickEdit(mainRow) {
        var next = mainRow.nextElementSibling;
        if (!next || !next.classList.contains('post-quick-edit-row')) return;
        closeAllQuickEdit();
        next.hidden = false;
        var qeBtn = mainRow.querySelector('.post-row-quick-edit-btn');
        if (qeBtn) qeBtn.setAttribute('aria-expanded', 'true');
        var msg = next.querySelector('.qe-message');
        if (msg) msg.textContent = '';
    }

    function statusBadgeClass(status) {
        if (status === 'published') return 'badge badge-success post-status-badge';
        if (status === 'archived') return 'badge badge-warning post-status-badge';
        return 'badge badge-secondary post-status-badge';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            var on = selectAll.checked;
            getCheckboxes().forEach(function (cb) {
                cb.checked = on;
            });
        });
    }

    table.addEventListener('change', function (e) {
        if (!e.target.classList.contains('post-select-checkbox')) return;
        if (!selectAll) return;
        var all = getCheckboxes();
        var n = all.length;
        var c = all.filter(function (x) {
            return x.checked;
        }).length;
        selectAll.checked = n > 0 && c === n;
        selectAll.indeterminate = c > 0 && c < n;
    });

    table.addEventListener('click', function (e) {
        var qeBtn = e.target.closest('.post-row-quick-edit-btn');
        if (qeBtn) {
            e.preventDefault();
            var mainRow = qeBtn.closest('tr.post-main-row');
            if (!mainRow) return;
            var next = mainRow.nextElementSibling;
            if (next && next.classList.contains('post-quick-edit-row') && !next.hidden) {
                closeAllQuickEdit();
                return;
            }
            openQuickEdit(mainRow);
            return;
        }

        var cancel = e.target.closest('.qe-cancel');
        if (cancel) {
            e.preventDefault();
            closeAllQuickEdit();
            return;
        }

        var save = e.target.closest('.qe-save');
        if (save) {
            e.preventDefault();
            var qrow = save.closest('tr.post-quick-edit-row');
            var prev = qrow ? qrow.previousElementSibling : null;
            if (!qrow || !prev || !prev.classList.contains('post-main-row')) return;
            var postId = parseInt(prev.getAttribute('data-post-id'), 10);
            if (!postId) return;
            var title = (qrow.querySelector('.qe-title') && qrow.querySelector('.qe-title').value) || '';
            var slug = (qrow.querySelector('.qe-slug') && qrow.querySelector('.qe-slug').value) || '';
            var status = (qrow.querySelector('.qe-status') && qrow.querySelector('.qe-status').value) || 'draft';
            var published = (qrow.querySelector('.qe-published') && qrow.querySelector('.qe-published').value) || '';
            var excerpt = qrow.querySelector('.qe-excerpt') ? qrow.querySelector('.qe-excerpt').value : '';
            var msg = qrow.querySelector('.qe-message');
            if (!title.trim()) {
                if (msg) msg.textContent = 'Title is required.';
                return;
            }
            if (!quickSaveUrl) {
                if (msg) msg.textContent = 'Quick save URL missing.';
                return;
            }
            if (msg) msg.textContent = 'Saving…';
            fetch(quickSaveUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    _csrf_token: csrf,
                    id: postId,
                    title: title.trim(),
                    slug: slug.trim(),
                    status: status,
                    published_at: published,
                    excerpt: excerpt
                })
            })
                .then(function (res) {
                    return res.json().then(function (json) {
                        return { ok: res.ok, json: json };
                    });
                })
                .then(function (pack) {
                    if (!pack.json || !pack.json.success) {
                        if (msg) msg.textContent = (pack.json && pack.json.error) ? pack.json.error : 'Save failed.';
                        return;
                    }
                    window.location.reload();
                })
                .catch(function () {
                    if (msg) msg.textContent = 'Save failed.';
                });
        }
    });

    if (bulkForm && bulkAction) {
        bulkForm.addEventListener('submit', function (e) {
            var action = bulkAction.value;
            if (!action) {
                e.preventDefault();
                alert('Choose a bulk action.');
                return;
            }
            var selected = getCheckboxes().filter(function (cb) {
                return cb.checked;
            });
            if (selected.length === 0) {
                e.preventDefault();
                alert('Select one or more posts.');
                return;
            }
            if (action === 'trash') {
                if (!window.confirm('Move the selected posts to trash?')) {
                    e.preventDefault();
                }
            }
        });
    }
})();
