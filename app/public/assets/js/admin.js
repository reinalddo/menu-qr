// Admin panel enhancements. Every form also works without JavaScript.
(function () {
    'use strict';

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.content : '';

    // Keep the current section visible in the scrollable tab row.
    var currentTab = document.querySelector('.admin-nav [aria-current="page"]');
    if (currentTab) {
        var nav = currentTab.parentElement;
        nav.scrollLeft = currentTab.offsetLeft - (nav.clientWidth - currentTab.offsetWidth) / 2;
    }

    // Ask before deleting.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Availability switches: save without reloading the page.
    document.querySelectorAll('form[data-availability]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var button = form.querySelector('.switch');
            var hidden = form.querySelector('input[name="available"]');
            var label = form.querySelector('.switch-text');
            var name = form.closest('.item').querySelector('.item-name');
            if (button.getAttribute('aria-busy') === 'true') {
                return;
            }
            button.setAttribute('aria-busy', 'true');

            var body = new FormData();
            body.append('available', hidden.value);

            fetch(form.action, {
                method: 'POST',
                body: body,
                headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrfToken },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.ok) {
                            throw new Error(data.error || 'Error');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    button.setAttribute('aria-checked', data.available ? 'true' : 'false');
                    label.textContent = data.available ? button.dataset.on : button.dataset.off;
                    hidden.value = data.available ? '0' : '1';
                    name.classList.toggle('is-sold-out', !data.available);
                })
                .catch(function () {
                    // Fall back to a normal submit, which shows the error message.
                    form.submit();
                })
                .finally(function () {
                    button.removeAttribute('aria-busy');
                });
        });
    });

    // Category filter: apply as soon as it changes.
    document.querySelectorAll('form[data-auto-submit] select').forEach(function (select) {
        select.addEventListener('change', function () {
            select.form.submit();
        });
    });

    // Photo preview before uploading.
    document.querySelectorAll('input[data-photo-input]').forEach(function (input) {
        input.addEventListener('change', function () {
            var preview = input.closest('.photo-field').querySelector('[data-photo-preview]');
            var file = input.files && input.files[0];
            if (!preview || !file || !file.type.match(/^image\//)) {
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });
    });

    // Sizes: add and remove rows.
    var sizes = document.querySelector('[data-sizes]');
    var template = document.getElementById('size-row-template');
    var addSize = document.querySelector('[data-size-add]');
    if (sizes && template && addSize) {
        addSize.addEventListener('click', function () {
            var row = template.content.firstElementChild.cloneNode(true);
            sizes.appendChild(row);
            row.querySelector('input').focus();
        });
        sizes.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-size-remove]');
            if (!remove) {
                return;
            }
            var row = remove.closest('[data-size-row]');
            if (sizes.querySelectorAll('[data-size-row]').length > 1) {
                row.remove();
            } else {
                row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
            }
        });
    }
})();
