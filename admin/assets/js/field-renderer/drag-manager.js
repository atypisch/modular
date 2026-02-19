/**
 * Centralized drag configuration for Modular CMS content/layout fields.
 * Single source for handle selectors, Sortable options, and FIXED mode.
 * Use from post-edit-fields-draggable.js; no duplicate drag logic in components.
 */
(function (global) {
    'use strict';

    var POST_TYPE_MODES = {
        MODULAR: 'modular',
        FIXED: 'fixed'
    };

    var SORTABLE_GROUP = 'modular-fields';
    var SORTABLE_ANIMATION = 150;
    var SORTABLE_GHOST_CLASS = 'sortable-ghost';
    var SORTABLE_CHOSEN_CLASS = 'sortable-chosen';

    /** Content field: toolbar left (title + edit) is handle; make it fill toolbar width with flex */
    var CONTENT_HANDLE = '.field-toolbar-handle';
    /** Layout block: toolbar left (label + rename) is the handle; collapse icon does not start drag */
    var BLOCK_HANDLE = '.layout-block-toolbar-handle';
    /** Block sortable: draggable elements */
    var BLOCK_DRAGGABLE = '.modular-layout-block, .modular-top-section';

    /**
     * @param {boolean} isFixedMode - true when Post Type mode is FIXED (no reorder, no move)
     * @returns {{ isFixed: boolean, contentHandle: string, blockHandle: string, blockDraggable: string, group: string, animation: number, ghostClass: string, chosenClass: string, canDrag: boolean }}
     */
    function createDragManager(isFixedMode) {
        return {
            isFixed: !!isFixedMode,
            contentHandle: CONTENT_HANDLE,
            blockHandle: BLOCK_HANDLE,
            blockDraggable: BLOCK_DRAGGABLE,
            group: SORTABLE_GROUP,
            animation: SORTABLE_ANIMATION,
            ghostClass: SORTABLE_GHOST_CLASS,
            chosenClass: SORTABLE_CHOSEN_CLASS,
            canDrag: !isFixedMode
        };
    }

    function readFixedModeFromDOM() {
        var area = document.getElementById('post-edit-fields-area');
        return !!(area && area.getAttribute('data-fixed') === '1');
    }

    global.ModularDragManager = {
        POST_TYPE_MODES: POST_TYPE_MODES,
        createDragManager: createDragManager,
        readFixedModeFromDOM: readFixedModeFromDOM,
        CONTENT_HANDLE: CONTENT_HANDLE,
        BLOCK_HANDLE: BLOCK_HANDLE,
        BLOCK_DRAGGABLE: BLOCK_DRAGGABLE
    };
})(typeof window !== 'undefined' ? window : this);
