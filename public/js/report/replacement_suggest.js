function initReplacementSuggestions() {
    const replacementList = (window.replacementList || []);
    $('#replacement').on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $('#replacement-suggestions');
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }
        const matchedReplacement = replacementList.filter(p =>
            (p.product_name || '').toLowerCase().includes(keyword)
        );

        if (matchedReplacement.length > 0) {
            matchedReplacement.slice(0, 10).forEach(p => {
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${p.product_name}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', '#replacement-suggestions button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#replacement').val($(this).text());
        $('#replacement-suggestions').addClass('d-none').empty();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#replacement, #replacement-suggestions').length) {
            $('#replacement-suggestions').addClass('d-none').empty();
        }
    });
}


