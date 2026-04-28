(function () {
    var table = document.getElementById('posts-table');
    if (!table || table.getAttribute('data-hierarchical') !== '1') return;

    var tbody = table.querySelector('tbody');
    if (!tbody) return;

    var dragRow = null;
    var draggedPostId = null;

    function rows() {
        return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-post-id]'));
    }

    function updateDropClass(target, active) {
        if (!target) return;
        target.style.outline = active ? '2px dashed var(--primary-color, #2563eb)' : '';
    }

    function syncOrderPayload() {
        var orderedRows = rows();
        var moves = [];
        for (var i = 0; i < orderedRows.length; i++) {
            var row = orderedRows[i];
            moves.push({
                id: parseInt(row.getAttribute('data-post-id'), 10),
                parent_id: row.getAttribute('data-parent-id') === '' ? null : parseInt(row.getAttribute('data-parent-id'), 10),
                order: i
            });
        }
        return moves;
    }

    function saveOrder() {
        var csrf = table.getAttribute('data-csrf') || '';
        var payload = { moves: syncOrderPayload(), _token: csrf };
        fetch('/modular/admin/posts/reorder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json || !json.success) {
                    alert((json && json.error) ? json.error : 'Opslaan van volgorde mislukt.');
                }
            })
            .catch(function () {
                alert('Opslaan van volgorde mislukt.');
            });
    }

    tbody.addEventListener('dragstart', function (e) {
        var handle = e.target.closest('.post-drag-handle');
        if (!handle) return;
        dragRow = handle.closest('tr[data-post-id]');
        if (!dragRow) return;
        draggedPostId = parseInt(dragRow.getAttribute('data-post-id'), 10);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(draggedPostId));
    });

    tbody.addEventListener('dragover', function (e) {
        var targetRow = e.target.closest('tr[data-post-id]');
        if (!dragRow || !targetRow || targetRow === dragRow) return;
        e.preventDefault();
        updateDropClass(targetRow, true);
    });

    tbody.addEventListener('dragleave', function (e) {
        var targetRow = e.target.closest('tr[data-post-id]');
        updateDropClass(targetRow, false);
    });

    tbody.addEventListener('drop', function (e) {
        var targetRow = e.target.closest('tr[data-post-id]');
        if (!dragRow || !targetRow || targetRow === dragRow) return;
        e.preventDefault();
        updateDropClass(targetRow, false);

        var rect = targetRow.getBoundingClientRect();
        var before = e.clientY < rect.top + rect.height / 2;

        var titleCell = targetRow.querySelector('.post-title-cell');
        var makeChild = false;
        if (titleCell) {
            var titleRect = titleCell.getBoundingClientRect();
            var relativeX = e.clientX - titleRect.left;
            if (relativeX > titleRect.width * 0.35) {
                makeChild = true;
            }
        }

        if (before) {
            tbody.insertBefore(dragRow, targetRow);
        } else {
            tbody.insertBefore(dragRow, targetRow.nextSibling);
        }

        if (makeChild) {
            var newParentId = parseInt(targetRow.getAttribute('data-post-id'), 10);
            dragRow.setAttribute('data-parent-id', String(newParentId));
            var parentDepth = parseInt(targetRow.getAttribute('data-depth') || '0', 10);
            dragRow.setAttribute('data-depth', String(parentDepth + 1));
        } else {
            dragRow.setAttribute('data-parent-id', targetRow.getAttribute('data-parent-id') || '');
            dragRow.setAttribute('data-depth', targetRow.getAttribute('data-depth') || '0');
        }

        saveOrder();
    });

    tbody.addEventListener('dragend', function () {
        rows().forEach(function (row) { updateDropClass(row, false); });
        dragRow = null;
        draggedPostId = null;
    });
})();
