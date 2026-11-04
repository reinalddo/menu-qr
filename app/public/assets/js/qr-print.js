// Print button for the QR cards page (inline handlers are blocked by the CSP).
document.querySelector('[data-print]').addEventListener('click', function () {
    window.print();
});
