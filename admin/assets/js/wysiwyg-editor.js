// Lightweight WYSIWYG wrapper inspired by ACF's Visual/Text tabs.
// Enhances <textarea class="wysiwyg-textarea"> elements on the post edit screen.

(function () {
    function setupEditor(textarea) {
        if (!textarea || textarea.dataset.enhanced === '1') return;

        var mode = textarea.getAttribute('data-tabs') || 'all'; // all | visual | text
        if (mode === 'text') {
            // Plain textarea only, no enhancement.
            return;
        }

        textarea.dataset.enhanced = '1';

        var parent = textarea.parentNode;
        if (!parent) return;

        var wrapper = document.createElement('div');
        wrapper.className = 'wysiwyg-wrapper';

        parent.insertBefore(wrapper, textarea);

        // Tabs
        var tabs = document.createElement('div');
        tabs.className = 'wysiwyg-tabs';

        var visualTab = document.createElement('button');
        visualTab.type = 'button';
        visualTab.className = 'wysiwyg-tab wysiwyg-tab-visual';
        visualTab.textContent = 'Visual';

        var textTab = document.createElement('button');
        textTab.type = 'button';
        textTab.className = 'wysiwyg-tab wysiwyg-tab-text';
        textTab.textContent = 'Text';

        // Hide text tab when not available
        if (mode === 'visual') {
            textTab.style.display = 'none';
        }

        tabs.appendChild(visualTab);
        if (mode === 'all') {
            tabs.appendChild(textTab);
        }

        // Simple toolbar (bold / italic / list)
        var toolbar = document.createElement('div');
        toolbar.className = 'wysiwyg-toolbar';

        function makeToolbarButton(label, command) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'wysiwyg-toolbar-btn';
            btn.textContent = label;
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                textarea.focus();
                document.execCommand(command, false, null);
            });
            return btn;
        }

        toolbar.appendChild(makeToolbarButton('B', 'bold'));
        toolbar.appendChild(makeToolbarButton('I', 'italic'));
        toolbar.appendChild(makeToolbarButton('•', 'insertUnorderedList'));

        // Visual pane
        var visual = document.createElement('div');
        visual.className = 'wysiwyg-visual';
        visual.contentEditable = 'true';

        // Move textarea into wrapper and hide when in Visual mode
        textarea.classList.add('wysiwyg-textarea-internal');

        wrapper.appendChild(tabs);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(visual);
        wrapper.appendChild(textarea);

        function setVisualFromText() {
            visual.innerHTML = textarea.value || '';
        }

        function setTextFromVisual() {
            textarea.value = visual.innerHTML;
        }

        function activateVisual() {
            wrapper.classList.add('wysiwyg-mode-visual');
            wrapper.classList.remove('wysiwyg-mode-text');
            setVisualFromText();
            visualTab.classList.add('active');
            if (mode === 'all') {
                textTab.classList.remove('active');
            }
            visual.focus();
        }

        function activateText() {
            if (mode === 'text') return;
            wrapper.classList.add('wysiwyg-mode-text');
            wrapper.classList.remove('wysiwyg-mode-visual');
            setTextFromVisual();
            if (mode === 'all') {
                textTab.classList.add('active');
                visualTab.classList.remove('active');
            }
            textarea.focus();
        }

        visual.addEventListener('input', setTextFromVisual);

        visualTab.addEventListener('click', function () {
            activateVisual();
        });

        if (mode === 'all') {
            textTab.addEventListener('click', function () {
                activateText();
            });
        }

        // Initial state: Visual mode
        activateVisual();
    }

    function initAll() {
        var areas = document.querySelectorAll('textarea.wysiwyg-textarea');
        if (!areas.length) return;
        areas.forEach(setupEditor);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();

