document.addEventListener('keydown', handlePrintShortcut);
function handlePrintShortcut(e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'p') {
        e.preventDefault();
        const iframe = document.getElementById('pdfViewer');
        try {
            if (iframe && iframe.contentWindow && typeof iframe.contentWindow.print === 'function') {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        } catch (error) {
            console.error('Lỗi in:', error);
        }
    }
}

document.getElementById('pdfViewer').addEventListener('load', () => {
    try {
        const iframeWindow = document.getElementById('pdfViewer').contentWindow;
        iframeWindow.document.addEventListener('keydown', handlePrintShortcut);
    } catch (e) {
        console.warn('Không thể gắn listener bên trong iframe (có thể do cross-origin)');
    }
});

$(document).ready(function() {
    const pdfSupported = isPdfSupported();
    if (!pdfSupported) {
        $('#downloadPdf').show();
    } else {
        $('#downloadPdf').hide();
    }
});

function isPdfSupported() {
    let ua = navigator.userAgent.toLowerCase();
    if (ua.indexOf('iphone') !== -1 || ua.indexOf('ipad') !== -1) {
        return false;
    }
    return !!navigator.mimeTypes['application/pdf'];
}

