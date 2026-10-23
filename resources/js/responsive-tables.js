function labelTables() {
    document.querySelectorAll('main table:not([data-scroll-table])').forEach((table) => {
        const labels = [...table.querySelectorAll('thead tr:last-child th')].map((cell) => cell.textContent.trim());

        table.querySelectorAll('tbody tr').forEach((row) => {
            if (row.cells.length !== labels.length) return;

            [...row.cells].forEach((cell, index) => {
                if (!cell.hasAttribute('data-label')) cell.dataset.label = labels[index];
            });
        });
    });
}

let queued = false;

function scheduleLabels() {
    if (queued) return;
    queued = true;
    requestAnimationFrame(() => {
        queued = false;
        labelTables();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    scheduleLabels();
    const main = document.querySelector('main');
    if (main) new MutationObserver(scheduleLabels).observe(main, { childList: true, subtree: true });
});
