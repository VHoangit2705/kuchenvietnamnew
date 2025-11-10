function sanitizeSerialRangeInput() {
    const $serialRange = $("#serial_range");
    $serialRange.on("keydown", function (e) {
        if (e.key === " " || e.keyCode === 32) {
            e.preventDefault();
        }
    });
    $serialRange.on("paste", function (e) {
        e.preventDefault();
        const text = (e.originalEvent || e).clipboardData.getData("text");
        const cleanText = text.replace(/\s+/g, "");
        document.execCommand("insertText", false, cleanText);
    });
}


