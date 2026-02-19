(function () {
    'use strict';
    if (typeof window === 'undefined') return;
    if (!window.ModularSortable || !window.ModularFieldRendererApp) return;
    var DragManager = window.ModularDragManager;
    if (!DragManager || typeof DragManager.createDragManager !== 'function') return;

    var initAttempts = 0;

    function init() {
        var area = document.getElementById('post-edit-fields-area');
        if (!area) return;
        var isFixed = area.getAttribute('data-fixed') === '1';
        var dragOpts = DragManager.createDragManager(isFixed);
        if (!dragOpts.canDrag) return;

        var root = area.querySelector('.modular-field-renderer-root');
        if (!root) {
            initAttempts += 1;
            if (initAttempts <= 30) {
                setTimeout(init, 50);
            }
            return;
        }

        var editorCard = document.getElementById('post-edit');
        var modal = document.getElementById('add-fields-modal');

        var api = window.ModularFieldRendererApp;
        function initColumnSortables() {
            var layout = api.getLayout();
            var topInner = root.querySelector('.modular-top-inner');
            if (topInner) {
                if (topInner._modularSortable) {
                    topInner._modularSortable.destroy();
                    topInner._modularSortable = null;
                }
                topInner._modularSortable = window.ModularSortable.create(topInner, {
                    animation: dragOpts.animation,
                    ghostClass: dragOpts.ghostClass,
                    chosenClass: dragOpts.chosenClass,
                    handle: dragOpts.contentHandle,
                    forceFallback: true,
                    fallbackOnBody: true,
                    group: dragOpts.group,
                    onMove: function (evt) {
                        var toInner = evt.to;
                        if (!toInner) return;
                        var over = toInner.closest('.modular-top-drop, .modular-column-drop');
                        root.querySelectorAll('.modular-top-drop, .modular-column-drop').forEach(function (el) {
                            el.classList.remove('modular-top-drag-over', 'modular-column-drag-over');
                        });
                        if (over) {
                            if (over.classList.contains('modular-top-drop')) {
                                over.classList.add('modular-top-drag-over');
                            } else {
                                over.classList.add('modular-column-drag-over');
                            }
                        }
                    },
                    onEnd: function () {
                        if (api && typeof api.syncLayoutFromDOM === 'function') api.syncLayoutFromDOM();
                        root.querySelectorAll('.modular-top-drop, .modular-column-drop').forEach(function (el) {
                            el.classList.remove('modular-top-drag-over', 'modular-column-drag-over');
                        });
                    }
                });
            }
            root.querySelectorAll('.modular-layout-block .modular-column-inner').forEach(function (innerEl) {
                var col = innerEl.closest('.modular-column-drop');
                if (!col) return;
                var blockIdx = col.getAttribute('data-block-index');
                var colIdx = col.getAttribute('data-column-index');
                var bi = blockIdx !== null && blockIdx !== '' ? parseInt(blockIdx, 10) : 0;
                var ci = colIdx !== null && colIdx !== '' ? parseInt(colIdx, 10) : 0;
                if (innerEl._modularSortable) {
                    innerEl._modularSortable.destroy();
                    innerEl._modularSortable = null;
                }
                innerEl._modularSortable = window.ModularSortable.create(innerEl, {
                    animation: dragOpts.animation,
                    ghostClass: dragOpts.ghostClass,
                    chosenClass: dragOpts.chosenClass,
                    handle: dragOpts.contentHandle,
                    forceFallback: true,
                    fallbackOnBody: true,
                    group: dragOpts.group,
                    onMove: function (evt) {
                        var toInner = evt.to;
                        if (!toInner) return;
                        var over = toInner.closest('.modular-top-drop, .modular-column-drop');
                        root.querySelectorAll('.modular-top-drop, .modular-column-drop').forEach(function (el) {
                            el.classList.remove('modular-top-drag-over', 'modular-column-drag-over');
                        });
                        if (over) {
                            if (over.classList.contains('modular-top-drop')) {
                                over.classList.add('modular-top-drag-over');
                            } else {
                                over.classList.add('modular-column-drag-over');
                            }
                        }
                    },
                    onEnd: function () {
                        if (api && typeof api.syncLayoutFromDOM === 'function') api.syncLayoutFromDOM();
                        root.querySelectorAll('.modular-top-drop, .modular-column-drop').forEach(function (el) {
                            el.classList.remove('modular-top-drag-over', 'modular-column-drag-over');
                        });
                    }
                });
            });
        }
        setTimeout(initColumnSortables, 100);

        function initBlockSortable() {
            if (!root || !window.ModularSortable) return;
            if (root._modularBlockSortable) {
                root._modularBlockSortable.destroy();
                root._modularBlockSortable = null;
            }
            root._modularBlockSortable = window.ModularSortable.create(root, {
                animation: dragOpts.animation,
                handle: dragOpts.blockHandle,
                draggable: dragOpts.blockDraggable,
                forceFallback: true,
                fallbackOnBody: true,
                onEnd: function () {
                    if (api && typeof api.syncBlocksFromDOM === 'function') api.syncBlocksFromDOM();
                }
            });
        }
        setTimeout(initBlockSortable, 100);

        // Library: click OR drag to add field to layout (modular mode only).
        // Layout is an array of instance objects { id, name }; each add creates a new instance.
        function makeNewInstance(name) {
            return { id: 'fi-' + Date.now() + '-' + Math.random().toString(36).slice(2, 8), name: name };
        }
        var libItems = document.querySelectorAll('[data-add-field-name]');
        libItems.forEach(function (el) {
            el.setAttribute('draggable', 'true');
            el.addEventListener('click', function () {
                var name = el.getAttribute('data-add-field-name');
                if (!name || !api || typeof api.addInstanceToTop !== 'function') return;
                var newId = api.addInstanceToTop(name);
                if (newId) highlightDroppedField(newId);
            });

            el.addEventListener('dragstart', function (e) {
                var name = el.getAttribute('data-add-field-name');
                if (!name) return;
                e.dataTransfer.effectAllowed = 'copy';
                e.dataTransfer.setData('text/plain', name);
                e.dataTransfer.setData('application/x-modular-field-name', name);
                try { e.dataTransfer.setData('text/html', '<span data-modular-drag="1"></span>'); } catch (err) {}
                if (modal) modal.classList.add('drag-from-library-active');
            });
        });
        function clearDragFromLibraryState() {
            if (modal) modal.classList.remove('drag-from-library-active');
        }
        document.addEventListener('dragend', clearDragFromLibraryState);
        document.addEventListener('drop', function () { setTimeout(clearDragFromLibraryState, 0); }, true);

        var insertTarget = null;
        var insertLineEl = null;

        function ensureInsertLine() {
            if (insertLineEl) return insertLineEl;
            insertLineEl = document.createElement('div');
            insertLineEl.className = 'modular-insert-line';
            insertLineEl.setAttribute('aria-hidden', 'true');
            if (area) area.appendChild(insertLineEl);
            return insertLineEl;
        }
        function hideInsertLine() {
            insertTarget = null;
            if (insertLineEl) insertLineEl.classList.remove('visible');
        }
        function showInsertLineAt(parentEl, insertIndex) {
            var line = ensureInsertLine();
            var inner = parentEl ? parentEl.querySelector('.modular-column-inner') : null;
            if (!inner) {
                line.classList.remove('visible');
                return;
            }
            var children = inner.querySelectorAll('[data-field-id]');
            var rect = inner.getBoundingClientRect();
            var areaRect = area.getBoundingClientRect();
            var y;
            if (insertIndex <= 0 || children.length === 0) {
                y = rect.top;
            } else if (insertIndex >= children.length) {
                var last = children[children.length - 1];
                y = last.getBoundingClientRect().bottom;
            } else {
                var before = children[insertIndex];
                y = before.getBoundingClientRect().top;
            }
            line.style.top = (y - areaRect.top + area.scrollTop) + 'px';
            line.style.left = (rect.left - areaRect.left) + 'px';
            line.style.width = (rect.width) + 'px';
            line.classList.add('visible');
        }

        function handleDrop(e) {
            e.preventDefault();
            e.stopPropagation();
            hideInsertLine();
            var name = e.dataTransfer.getData('text/plain') || e.dataTransfer.getData('application/x-modular-field-name');
            if (!name) return;

            var x = e.clientX;
            var y = e.clientY;
            var newId = null;

            if (insertTarget) {
                if (insertTarget.type === 'top' && api && api.addInstanceToTopAt) {
                    newId = api.addInstanceToTopAt(insertTarget.index, name);
                } else if (insertTarget.type === 'block' && api && api.addInstanceToBlockAt) {
                    newId = api.addInstanceToBlockAt(insertTarget.bi, insertTarget.ci, insertTarget.index, name);
                }
            }
            if (newId == null) {
                var blockDrops = root.querySelectorAll('.modular-column-drop[data-block-index]');
                for (var i = 0; i < blockDrops.length; i++) {
                    var r = blockDrops[i].getBoundingClientRect();
                    if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
                        var bi = parseInt(blockDrops[i].getAttribute('data-block-index'), 10);
                        var ci = parseInt(blockDrops[i].getAttribute('data-column-index'), 10);
                        if (api && typeof api.addInstanceToBlock === 'function') newId = api.addInstanceToBlock(bi, ci, name);
                        break;
                    }
                }
            }
            if (newId == null) {
                var topDrop = root.querySelector('.modular-top-drop');
                if (topDrop && api && typeof api.addInstanceToTop === 'function') {
                    var tr = topDrop.getBoundingClientRect();
                    if (x >= tr.left && x <= tr.right && y >= tr.top && y <= tr.bottom) {
                        newId = api.addInstanceToTop(name);
                    }
                }
                if (newId == null && api && typeof api.addInstanceToTop === 'function') {
                    newId = api.addInstanceToTop(name);
                }
            }

            if (newId) {
                highlightDroppedField(newId);
                setTimeout(initColumnSortables, 150);
            }
        }

        function highlightDroppedField(instanceId) {
            setTimeout(function () {
                var rootEl = document.querySelector('#post-edit-fields-area .modular-field-renderer-root');
                if (!rootEl || !instanceId) return;
                var fieldEl = rootEl.querySelector('[data-field-id="' + instanceId + '"]');
                if (!fieldEl) return;
                fieldEl.classList.add('field-drop-highlight');
                try { fieldEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (err) {}
                setTimeout(function () {
                    fieldEl.classList.remove('field-drop-highlight');
                }, 1500);
            }, 150);
        }

        function computeInsertTarget(x, y) {
            var topDrop = root.querySelector('.modular-top-drop');
            if (topDrop) {
                var tr = topDrop.getBoundingClientRect();
                if (x >= tr.left && x <= tr.right && y >= tr.top && y <= tr.bottom) {
                    var inner = topDrop.querySelector('.modular-top-inner');
                    var rows = inner ? inner.querySelectorAll('[data-field-id]') : [];
                    var insertIndex = 0;
                    for (var i = 0; i < rows.length; i++) {
                        var rowRect = rows[i].getBoundingClientRect();
                        if (y < rowRect.top + rowRect.height / 2) break;
                        insertIndex = i + 1;
                    }
                    return { type: 'top', index: insertIndex, el: topDrop };
                }
            }
            var blockDrops = root.querySelectorAll('.modular-column-drop[data-block-index]');
            for (var i = 0; i < blockDrops.length; i++) {
                var r = blockDrops[i].getBoundingClientRect();
                if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) {
                    var inner = blockDrops[i].querySelector('.modular-column-inner');
                    var rows = inner ? inner.querySelectorAll('[data-field-id]') : [];
                    var insertIndex = 0;
                    for (var j = 0; j < rows.length; j++) {
                        var rowRect = rows[j].getBoundingClientRect();
                        if (y < rowRect.top + rowRect.height / 2) break;
                        insertIndex = j + 1;
                    }
                    return {
                        type: 'block',
                        bi: parseInt(blockDrops[i].getAttribute('data-block-index'), 10),
                        ci: parseInt(blockDrops[i].getAttribute('data-column-index'), 10),
                        index: insertIndex,
                        el: blockDrops[i]
                    };
                }
            }
            return null;
        }

        root.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            var x = e.clientX;
            var y = e.clientY;
            var target = computeInsertTarget(x, y);
            if (target) {
                insertTarget = target;
                showInsertLineAt(target.el, target.index);
            } else {
                hideInsertLine();
            }
            var over = target ? target.el : null;
            root.querySelectorAll('.modular-column-drag-over, .modular-top-drag-over').forEach(function (el) {
                if (el !== over) el.classList.remove('modular-column-drag-over', 'modular-top-drag-over');
            });
            if (over) {
                over.classList.add(over.classList.contains('modular-top-drop') ? 'modular-top-drag-over' : 'modular-column-drag-over');
            }
        }, true);
        root.addEventListener('dragleave', function (e) {
            if (!root.contains(e.relatedTarget)) hideInsertLine();
        }, true);
        root.addEventListener('drop', function (e) {
            root.querySelectorAll('.modular-column-drag-over, .modular-top-drag-over').forEach(function (el) {
                el.classList.remove('modular-column-drag-over', 'modular-top-drag-over');
            });
            handleDrop(e);
        }, true);

        if (editorCard) {
            editorCard.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
            }, true);
            editorCard.addEventListener('drop', handleDrop, true);
        }

        if (modal) {
            modal.addEventListener('dragover', function (e) {
                var panel = modal.querySelector('.post-edit-modal-panel');
                if (panel && panel.contains(e.target)) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'copy';
            }, true);
            modal.addEventListener('drop', function (e) {
                var panel = modal.querySelector('.post-edit-modal-panel');
                if (panel && panel.contains(e.target)) return;
                handleDrop(e);
            }, true);
        }

        // Document-level drop when modal is open: catch drop wherever it lands (e.g. over
        // the dimmed editor area), since the actual target can vary by browser.
        function isModalOpen() {
            return modal && modal.style.display === 'flex';
        }
        function isDropFromLibrary(e) {
            if (!e.dataTransfer || !e.dataTransfer.types) return false;
            return e.dataTransfer.types.indexOf('text/plain') !== -1 || e.dataTransfer.types.indexOf('application/x-modular-field-name') !== -1;
        }
        function isOverPanel(target) {
            var panel = modal && modal.querySelector('.post-edit-modal-panel');
            return panel && panel.contains(target);
        }
        document.addEventListener('dragover', function (e) {
            if (!isModalOpen() || !isDropFromLibrary(e) || isOverPanel(e.target)) return;
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
        }, true);
        document.addEventListener('drop', function (e) {
            if (!isModalOpen() || !isDropFromLibrary(e) || isOverPanel(e.target)) return;
            handleDrop(e);
        }, true);

        document.addEventListener('modular-layout-changed', function () {
            if (root._modularBlockSortable) {
                root._modularBlockSortable.destroy();
                root._modularBlockSortable = null;
            }
            setTimeout(initColumnSortables, 50);
            setTimeout(initBlockSortable, 75);
        });

        // Add layout button + dropdown (runs after Vue app is mounted)
        var addLayoutBtn = document.getElementById('add-layout-btn');
        var addLayoutMenu = document.getElementById('add-layout-menu');
        if (addLayoutBtn && addLayoutMenu) {
            addLayoutBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                var open = addLayoutMenu.style.display === 'block';
                addLayoutMenu.style.display = open ? 'none' : 'block';
                addLayoutBtn.setAttribute('aria-expanded', open ? 'false' : 'true');
            });
            addLayoutMenu.querySelectorAll('.add-layout-opt').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var cols = parseInt(btn.getAttribute('data-cols'), 10) || 1;
                    if (api && typeof api.addLayoutBlock === 'function') {
                        api.addLayoutBlock(cols);
                    }
                    addLayoutMenu.style.display = 'none';
                    addLayoutBtn.setAttribute('aria-expanded', 'false');
                });
            });
            addLayoutMenu.addEventListener('click', function (e) { e.stopPropagation(); });
            document.addEventListener('click', function () {
                addLayoutMenu.style.display = 'none';
                addLayoutBtn.setAttribute('aria-expanded', 'false');
            });
        }

        // Remove-field confirmation modal (when field has content)
        var removeConfirmModal = document.getElementById('modular-remove-confirm-modal');
        var removeConfirmBtn = document.getElementById('modular-remove-confirm');
        var removeCancelBtn = document.getElementById('modular-remove-cancel');
        if (removeConfirmModal && removeConfirmBtn && removeCancelBtn && api) {
            var removeConfirmDone = null;
            function closeRemoveConfirm() {
                removeConfirmModal.style.display = 'none';
                removeConfirmModal.setAttribute('aria-hidden', 'true');
                removeConfirmDone = null;
            }
            removeConfirmBtn.addEventListener('click', function () {
                if (typeof removeConfirmDone === 'function') removeConfirmDone();
                closeRemoveConfirm();
            });
            removeCancelBtn.addEventListener('click', closeRemoveConfirm);
            removeConfirmModal.addEventListener('click', function (e) {
                if (e.target === removeConfirmModal) closeRemoveConfirm();
            });
            api.onRemoveConfirm = function (instanceId, done) {
                removeConfirmDone = done;
                removeConfirmModal.style.display = 'flex';
                removeConfirmModal.setAttribute('aria-hidden', 'false');
            };
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

