/**
 * Modular CMS — Sortable / drag & drop base
 *
 * Uses SortableJS (loaded globally in header). This file defines conventions
 * and a small helper so all admin drag & drop UIs use the same handle and behaviour.
 *
 * Convention:
 * - Use class "drag-handle" on the element that starts the drag (e.g. grip icon).
 * - List container: pass to Sortable.create() with handle: '.drag-handle'.
 * - Sync your data in onEnd/onUpdate from the new DOM order.
 *
 * @see https://github.com/SortableJS/Sortable
 */
(function(global) {
    'use strict';

    if (typeof global.Sortable === 'undefined') {
        console.warn('Modular: SortableJS not loaded. Drag & drop will not work.');
        return;
    }

    /**
     * Create a sortable list with CMS defaults.
     * @param {HTMLElement} el - List element (e.g. ul or div with children)
     * @param {Object} options - Sortable options (merged with defaults)
     * @returns {Sortable|null}
     */
    function createSortable(el, options) {
        if (!el || typeof global.Sortable === 'undefined') return null;
        var defaults = {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag'
        };
        var opts = Object.assign({}, defaults, options || {});
        return global.Sortable.create(el, opts);
    }

    global.ModularSortable = {
        create: createSortable,
        Sortable: global.Sortable
    };
})(typeof window !== 'undefined' ? window : this);
