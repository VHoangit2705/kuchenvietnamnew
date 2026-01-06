function resizeTableContainer() {
    const windowHeight = $(window).height();
    const containerHeight = $('.container').outerHeight(true);
    const newHeight = windowHeight - containerHeight;
    $('.table-container').height(newHeight - 10);
}

function initTooltipsAndResize() {
    $(document).ready(function() {
        resizeTableContainer();
        $(window).on('resize', resizeTableContainer);
    });

    document.addEventListener("DOMContentLoaded", function() {
        const cells = document.querySelectorAll('.shorten-text');
        cells.forEach(cell => {
            const originalText = cell.textContent.trim();
            if (originalText.length > 50) {
                const words = originalText.split(' ');
                let shortText = '';
                for (let word of words) {
                    if ((shortText + word).length > 50) break;
                    shortText += word + ' ';
                }
                shortText = shortText.trim() + '...';
                cell.textContent = shortText;
                cell.setAttribute('title', originalText);
                cell.setAttribute('data-bs-toggle', 'tooltip');
            }
        });
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl)
        })
    });
}


