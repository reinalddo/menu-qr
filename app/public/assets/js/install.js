// Preselects the browser's time zone in the installer.
(function () {
    var select = document.getElementById('timezone');
    if (!select || select.dataset.detectTimezone !== '1' || !window.Intl) {
        return;
    }
    var zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    if (zone && select.querySelector('option[value="' + zone + '"]')) {
        select.value = zone;
    }
})();
