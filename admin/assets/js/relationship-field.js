// Enhanced UX for relationship and post object fields on the post edit screen.
// Inspired by ACF Pro: search input + selected chips, without changing backend data format.

(function () {
    function setupSelect(select, type) {
        if (!select || select.dataset.relationEnhanced === '1') return;
        select.dataset.relationEnhanced = '1';

        var formGroup = select.closest('.form-group');
        if (!formGroup) return;

        var wrapper = document.createElement('div');
        wrapper.className = 'relationship-field';

        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);

        // Search box
        var searchWrap = document.createElement('div');
        searchWrap.className = 'relationship-search';
        var searchInput = document.createElement('input');
        searchInput.type = 'search';
        searchInput.className = 'form-control relationship-search-input';
        searchInput.placeholder = type === 'relationship'
            ? 'Search related content…'
            : 'Search posts…';
        searchWrap.appendChild(searchInput);

        wrapper.insertBefore(searchWrap, select);

        // Selected chips
        var chips = document.createElement('div');
        chips.className = 'relationship-selected';
        wrapper.appendChild(chips);

        function rebuildChips() {
            chips.innerHTML = '';
            var opts = Array.from(select.options).filter(function (o) { return o.selected; });
            if (!opts.length) {
                chips.style.display = 'none';
                return;
            }
            chips.style.display = 'flex';
            opts.forEach(function (opt) {
                var chip = document.createElement('span');
                chip.className = 'relationship-chip';
                chip.textContent = opt.textContent;

                var remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'relationship-chip-remove';
                remove.innerHTML = '&times;';
                remove.addEventListener('click', function (e) {
                    e.preventDefault();
                    opt.selected = false;
                    rebuildChips();
                });

                chip.appendChild(remove);
                chips.appendChild(chip);
            });
        }

        select.addEventListener('change', rebuildChips);

        searchInput.addEventListener('input', function () {
            var term = searchInput.value.toLowerCase();
            Array.from(select.options).forEach(function (opt) {
                if (!term) {
                    opt.hidden = false;
                    return;
                }
                var txt = opt.textContent.toLowerCase();
                opt.hidden = txt.indexOf(term) === -1;
            });
        });

        // Initial state
        rebuildChips();
    }

    function initAll() {
        var relationshipSelects = document.querySelectorAll('.form-group[data-field-type=\"relationship\"] select');
        relationshipSelects.forEach(function (s) { setupSelect(s, 'relationship'); });

        var postObjectSelects = document.querySelectorAll('.form-group[data-field-type=\"post_object\"] select');
        postObjectSelects.forEach(function (s) { setupSelect(s, 'post_object'); });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();

