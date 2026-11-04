// Public menu: category tabs that follow the scroll, search and the dish detail sheet.
(function () {
    'use strict';

    var tabs = document.querySelector('[data-tabs]');
    var sections = Array.prototype.slice.call(document.querySelectorAll('[data-category]'));

    // ---- Highlight the tab of the category on screen
    var activeId = null;

    function setActive(id) {
        if (!tabs || id === activeId) {
            return;
        }
        activeId = id;
        tabs.querySelectorAll('a').forEach(function (link) {
            var active = link.dataset.tab === id;
            link.setAttribute('aria-current', active ? 'true' : 'false');
            if (active) {
                tabs.scrollTo({ left: link.offsetLeft - (tabs.clientWidth - link.offsetWidth) / 2, behavior: 'smooth' });
            }
        });
    }

    // The active category is the last one whose title has passed under the tab bar.
    // At the very bottom of the page the last category wins: on a short menu (desktop,
    // few dishes) the last sections can never scroll up to the tab bar.
    function currentSection() {
        var shown = sections.filter(function (section) { return !section.hidden; });
        if (shown.length === 0) {
            return null;
        }
        var page = document.documentElement;
        if (window.scrollY > 0 && window.innerHeight + window.scrollY >= page.scrollHeight - 4) {
            return shown[shown.length - 1];
        }
        var line = tabs.getBoundingClientRect().bottom + 16;
        var current = shown[0];
        shown.forEach(function (section) {
            if (section.getBoundingClientRect().top <= line) {
                current = section;
            }
        });
        return current;
    }

    // After tapping a tab, keep it highlighted even if the page cannot scroll that far,
    // until the visitor scrolls by hand again.
    var lockedByTap = false;
    var pending = false;

    function update() {
        pending = false;
        if (lockedByTap) {
            return;
        }
        var section = currentSection();
        if (section) {
            setActive(section.id);
        }
    }

    if (tabs) {
        window.addEventListener('scroll', function () {
            if (!pending) {
                pending = true;
                window.requestAnimationFrame(update);
            }
        }, { passive: true });
        ['wheel', 'touchstart', 'keydown'].forEach(function (type) {
            window.addEventListener(type, function () { lockedByTap = false; }, { passive: true });
        });
        tabs.addEventListener('click', function (event) {
            var link = event.target.closest('a[data-tab]');
            if (link) {
                lockedByTap = true;
                setActive(link.dataset.tab);
            }
        });
        update();
    }

    // ---- Search: hides dishes and categories that do not match
    var search = document.querySelector('[data-search]');
    var noResults = document.querySelector('[data-no-results]');
    var featured = document.querySelector('[data-featured]');

    function normalize(text) {
        // "Café" matches "cafe": drop accents after splitting them from their letters.
        return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    if (search) {
        search.addEventListener('input', function () {
            var query = normalize(search.value.trim());
            var anyMatch = false;
            sections.forEach(function (section) {
                var matches = 0;
                section.querySelectorAll('[data-dish]').forEach(function (dish) {
                    var match = query === '' || normalize(dish.dataset.searchText).indexOf(query) !== -1;
                    dish.hidden = !match;
                    matches += match ? 1 : 0;
                });
                section.hidden = matches === 0;
                anyMatch = anyMatch || matches > 0;
            });
            if (featured) {
                featured.hidden = query !== '';
            }
            if (tabs) {
                tabs.querySelectorAll('a').forEach(function (link) {
                    link.hidden = document.getElementById(link.dataset.tab).hidden;
                });
            }
            noResults.hidden = anyMatch;
            if (tabs) {
                update();
            }
        });
    }

    // ---- Dish detail sheet
    var sheet = document.querySelector('[data-sheet]');
    var content = document.querySelector('[data-sheet-content]');

    function closeSheet() {
        if (sheet && sheet.open) {
            sheet.close();
        }
    }

    document.addEventListener('click', function (event) {
        var opener = event.target.closest('[data-open]');
        if (opener && sheet && typeof sheet.showModal === 'function') {
            var template = document.getElementById(opener.dataset.open);
            if (!template) {
                return;
            }
            content.replaceChildren(template.content.cloneNode(true));
            sheet.showModal();
            sheet.scrollTop = 0;
            document.dispatchEvent(new CustomEvent('menu:dish-opened', { detail: { id: opener.dataset.dishId } }));
            return;
        }
        if (event.target.closest('[data-close]')) {
            closeSheet();
        }
    });

    if (sheet) {
        // A tap on the dark backdrop (outside the sheet) closes it.
        sheet.addEventListener('click', function (event) {
            var box = sheet.getBoundingClientRect();
            var inside = event.clientX >= box.left && event.clientX <= box.right && event.clientY >= box.top && event.clientY <= box.bottom;
            if (!inside) {
                closeSheet();
            }
        });
    }

    // ---- Visit counter: one visit per browser session, one view per dish opened.
    // No cookies: the browser's sessionStorage remembers what was already counted.
    function countOnce(key) {
        try {
            if (window.sessionStorage.getItem(key)) {
                return false;
            }
            window.sessionStorage.setItem(key, '1');
        } catch (error) {
            // Private mode without storage: count anyway.
        }
        return true;
    }

    function beacon(url, data) {
        var body = new FormData();
        Object.keys(data).forEach(function (key) { body.append(key, data[key]); });
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, body);
        } else {
            fetch(url, { method: 'POST', body: body, keepalive: true });
        }
    }

    var params = new URLSearchParams(window.location.search);
    var source = params.get('src') === 'qr' ? 'qr' : 'link';
    if (params.has('src') && window.history.replaceState) {
        // Drop ?src=qr so a link shared from this page is not counted as a scan.
        params.delete('src');
        var query = params.toString();
        window.history.replaceState(null, '', window.location.pathname + (query ? '?' + query : '') + window.location.hash);
    }
    if (countOnce('menuqr:visit')) {
        beacon('/api/visit', { source: source });
    }
    document.addEventListener('menu:dish-opened', function (event) {
        var id = event.detail && event.detail.id;
        if (id && countOnce('menuqr:dish:' + id)) {
            beacon('/api/view', { dish: id });
        }
    });
})();
