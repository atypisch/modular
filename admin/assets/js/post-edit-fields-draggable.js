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
            function onFieldSortMove(evt) {
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
            }
            function onFieldSortEnd() {
                if (api && typeof api.syncLayoutFromDOM === 'function') api.syncLayoutFromDOM();
                root.querySelectorAll('.modular-top-drop, .modular-column-drop').forEach(function (el) {
                    el.classList.remove('modular-top-drag-over', 'modular-column-drag-over');
                });
            }
            function onFieldSortAdd() {
                if (api && typeof api.syncLayoutFromDOM === 'function') api.syncLayoutFromDOM();
            }
            function cleanupFieldDropContainer(innerEl) {
                if (!innerEl) return;
                if (innerEl._modularSortable) {
                    innerEl._modularSortable.destroy();
                    innerEl._modularSortable = null;
                }
                if (innerEl._modularOuterSortable) {
                    innerEl._modularOuterSortable.destroy();
                    innerEl._modularOuterSortable = null;
                }
                innerEl.querySelectorAll('.modular-field-group-inner').forEach(function (ge) {
                    if (ge._modularSortable) {
                        ge._modularSortable.destroy();
                        ge._modularSortable = null;
                    }
                });
            }
            function fieldDropContainerHasTabGroups(innerEl) {
                if (!innerEl) return false;
                var chi = innerEl.children;
                for (var c = 0; c < chi.length; c++) {
                    if (chi[c].classList && chi[c].classList.contains('modular-field-group')) return true;
                }
                return false;
            }
            function attachFieldDropContainerSortables(innerEl) {
                cleanupFieldDropContainer(innerEl);
                if (!innerEl) return;
                var lockedAncestor = innerEl.closest && innerEl.closest('[data-field-group-locked="1"]');
                if (lockedAncestor) {
                    return;
                }
                var commonOpts = {
                    animation: dragOpts.animation,
                    ghostClass: dragOpts.ghostClass,
                    chosenClass: dragOpts.chosenClass,
                    forceFallback: true,
                    fallbackOnBody: true,
                    emptyInsertThreshold: 40,
                    swapThreshold: 0.65
                };

                var fieldGroup = { name: dragOpts.group, pull: true, put: true };
                if (fieldDropContainerHasTabGroups(innerEl)) {
                    innerEl._modularOuterSortable = window.ModularSortable.create(innerEl, Object.assign({}, commonOpts, {
                        draggable: '> .modular-field-group',
                        handle: '.modular-field-group-handle',
                        onEnd: onFieldSortEnd
                    }));
                    innerEl.querySelectorAll('.modular-field-group-inner').forEach(function (groupInnerEl) {
                        groupInnerEl._modularSortable = window.ModularSortable.create(groupInnerEl, Object.assign({}, commonOpts, {
                            draggable: '> [data-field-id]',
                            handle: dragOpts.contentHandle,
                            group: fieldGroup,
                            onMove: onFieldSortMove,
                            onEnd: onFieldSortEnd,
                            onAdd: onFieldSortAdd
                        }));
                    });
                } else {
                    innerEl._modularSortable = window.ModularSortable.create(innerEl, Object.assign({}, commonOpts, {
                        draggable: '> [data-field-id]',
                        handle: dragOpts.contentHandle,
                        group: fieldGroup,
                        onMove: onFieldSortMove,
                        onEnd: onFieldSortEnd,
                        onAdd: onFieldSortAdd
                    }));
                }
            }

            attachFieldDropContainerSortables(root.querySelector('.modular-top-inner'));
            root.querySelectorAll('.modular-layout-block .modular-column-inner').forEach(function (innerEl) {
                attachFieldDropContainerSortables(innerEl);
            });
            // Explicitly attach to field group pane drop zones (may be nested deeper)
            root.querySelectorAll('.modular-field-group-pane-drop .modular-column-inner').forEach(function (innerEl) {
                if (!innerEl._modularSortable) {
                    attachFieldDropContainerSortables(innerEl);
                }
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

        // Add field group button + modal
        var addFieldGroupBtn = document.getElementById('add-field-group-btn');
        var addFieldGroupModal = document.getElementById('add-field-group-modal');
        if (addFieldGroupBtn && addFieldGroupModal) {
            var fgModalClose = document.getElementById('add-field-group-modal-close');
            var fgTabs = addFieldGroupModal.querySelectorAll('.field-group-modal-tab');
            var fgPanes = addFieldGroupModal.querySelectorAll('.field-group-modal-pane');
            var fgCreateLabel = document.getElementById('fg-create-label');
            var fgCreateSubmit = document.getElementById('fg-create-submit');
            var fgAttachList = document.getElementById('fg-attach-list');

            function openFgModal() {
                addFieldGroupModal.style.display = 'flex';
                addFieldGroupModal.setAttribute('aria-hidden', 'false');
                if (fgCreateLabel) {
                    fgCreateLabel.value = '';
                    setTimeout(function () { try { fgCreateLabel.focus(); } catch (_) {} }, 50);
                }
                renderAttachList();
            }
            function closeFgModal() {
                addFieldGroupModal.style.display = 'none';
                addFieldGroupModal.setAttribute('aria-hidden', 'true');
            }
            function switchTab(name) {
                fgTabs.forEach(function (b) {
                    var isOn = b.getAttribute('data-fg-tab') === name;
                    b.classList.toggle('is-active', isOn);
                    b.setAttribute('aria-selected', isOn ? 'true' : 'false');
                });
                fgPanes.forEach(function (p) {
                    var isOn = p.getAttribute('data-fg-pane') === name;
                    p.classList.toggle('is-active', isOn);
                    p.style.display = isOn ? '' : 'none';
                });
            }
            function renderAttachList() {
                if (!fgAttachList) return;
                var fixed = (window.MODULAR_FIXED_GROUPS || []).slice();
                var shared = (window.MODULAR_SHARED_GROUPS_INDEX || []).slice();
                if (!fixed.length && !shared.length) {
                    fgAttachList.innerHTML = '<p class="help-text" style="opacity:0.7;">No field groups defined yet. Create one in the &ldquo;Create new&rdquo; tab.</p>';
                    return;
                }
                var html = '';
                if (fixed.length) {
                    html += '<div class="fg-attach-section"><h4>Fixed for this post type</h4>';
                    fixed.forEach(function (g) {
                        html += '<button type="button" class="fg-attach-item" data-fg-kind="fixed_pt" data-fg-ref="' + encodeURIComponent(String(g.id)) + '">'
                            + '<strong>' + escapeHtml(g.label || g.id) + '</strong>'
                            + '<span class="fg-attach-meta">' + (g.items ? g.items.length : 0) + ' fields</span>'
                            + '</button>';
                    });
                    html += '</div>';
                }
                if (shared.length) {
                    html += '<div class="fg-attach-section"><h4>Shared across post types</h4>';
                    shared.forEach(function (g) {
                        html += '<button type="button" class="fg-attach-item" data-fg-kind="shared" data-fg-ref="' + encodeURIComponent(String(g.id)) + '">'
                            + '<strong>' + escapeHtml(g.label || g.name || g.id) + '</strong>'
                            + '<span class="fg-attach-meta">' + (g.items ? g.items.length : 0) + ' fields</span>'
                            + '</button>';
                    });
                    html += '</div>';
                }
                fgAttachList.innerHTML = html;
                fgAttachList.querySelectorAll('.fg-attach-item').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        var kind = btn.getAttribute('data-fg-kind');
                        var ref = decodeURIComponent(btn.getAttribute('data-fg-ref') || '');
                        if (api && typeof api.attachFieldGroup === 'function') {
                            api.attachFieldGroup(kind, ref);
                        }
                        closeFgModal();
                    });
                });
            }
            function escapeHtml(s) {
                return String(s).replace(/[&<>"']/g, function (c) {
                    return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                });
            }

            addFieldGroupBtn.addEventListener('click', openFgModal);
            if (fgModalClose) fgModalClose.addEventListener('click', closeFgModal);
            addFieldGroupModal.addEventListener('click', function (e) {
                if (e.target === addFieldGroupModal) closeFgModal();
            });
            fgTabs.forEach(function (b) {
                b.addEventListener('click', function () { switchTab(b.getAttribute('data-fg-tab')); });
            });
            if (fgCreateSubmit) {
                fgCreateSubmit.addEventListener('click', function () {
                    var label = (fgCreateLabel && fgCreateLabel.value || '').trim();
                    if (!label) label = 'Field Group';
                    if (api && typeof api.addFieldGroup === 'function') {
                        api.addFieldGroup({ label: label, source: { kind: 'local' } });
                    }
                    closeFgModal();
                });
            }

            // Promote a local field group to fixed/shared via API
            window.ModularPromoteFieldGroup = function (blockId, kind) {
                if (!api || typeof api.getLayout !== 'function') return;
                var layout = api.getLayout();
                var block = null;
                (layout.layoutBlocks || []).some(function (b) {
                    if (b && b.id === blockId) { block = b; return true; }
                    return false;
                });
                if (!block || block.type !== 'field_group') return;
                var items = (block.columns && block.columns[0]) || [];
                var base = (window.MODULAR_BASE_URL || '/modular').replace(/\/$/, '');
                var body = new URLSearchParams();
                body.append('action', kind === 'shared' ? 'create_shared' : 'create_local_fixed');
                body.append('post_type_id', String(window.MODULAR_POST_TYPE_ID || 0));
                body.append('label', block.label || 'Field Group');
                body.append('items', JSON.stringify(items));
                if (window.MODULAR_CSRF_TOKEN) body.append('_csrf_token', window.MODULAR_CSRF_TOKEN);
                fetch(base + '/admin/post-types/field-group-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json' },
                    body: body.toString(),
                    credentials: 'same-origin'
                }).then(function (r) { return r.json(); }).then(function (resp) {
                    if (!resp || resp.error) {
                        window.alert((resp && resp.error) || 'Could not promote field group.');
                        return;
                    }
                    var inst = window.ModularFieldRendererApp && window.ModularFieldRendererApp._instance;
                    if (!inst || !inst.layoutBlocks) return;
                    var def = {
                        id: resp.id,
                        label: resp.label || block.label || 'Field Group',
                        items: items.slice()
                    };
                    if (kind === 'shared') {
                        window.MODULAR_SHARED_GROUPS_INDEX = (window.MODULAR_SHARED_GROUPS_INDEX || []).concat([Object.assign({}, def, { name: resp.name || def.label })]);
                    } else {
                        window.MODULAR_FIXED_GROUPS = (window.MODULAR_FIXED_GROUPS || []).concat([def]);
                    }
                    inst.layoutBlocks.value = (inst.layoutBlocks.value || []).map(function (b) {
                        if (b.id !== blockId) return b;
                        return Object.assign({}, b, {
                            source: { kind: kind === 'shared' ? 'shared' : 'fixed_pt', ref: String(resp.id) }
                        });
                    });
                    try { document.dispatchEvent(new CustomEvent('modular-layout-changed')); } catch (e) {}
                }).catch(function () {
                    window.alert('Network error promoting field group.');
                });
            };
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

