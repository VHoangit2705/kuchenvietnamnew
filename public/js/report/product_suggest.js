function initProductSuggestions() {
    let debounceTimer;
    const $productInput = $('#product');
    const $suggestionsBox = $('#product-suggestions');
    const suggestionRoute = window.productSuggestRoute || '/sanpham';

    $productInput.on('input', function() {
        const keyword = $(this).val().trim();
        $suggestionsBox.empty();

        // Clear previous timer
        clearTimeout(debounceTimer);

        if (!keyword || keyword.length < 2) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        // Debounce: wait 300ms after user stops typing
        debounceTimer = setTimeout(function() {
            $.ajax({
                url: suggestionRoute,
                method: 'GET',
                data: { query: keyword },
                success: function(response) {
                    $suggestionsBox.empty();

                    if (response && response.length > 0) {
                        // Limit to 10 suggestions
                        response.slice(0, 10).forEach(function(item) {
                            const displayText = item || '';
                            $suggestionsBox.append(
                                `<button type="button" class="list-group-item list-group-item-action">${displayText}</button>`
                            );
                        });
                        $suggestionsBox.removeClass('d-none');
                    } else {
                        $suggestionsBox.addClass('d-none');
                    }
                },
                error: function() {
                    $suggestionsBox.addClass('d-none');
                }
            });
        }, 300);
    });

    // Handle click on suggestion
    $(document).on('mousedown', '#product-suggestions button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $productInput.val($(this).text());
        $suggestionsBox.addClass('d-none').empty();
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#product, #product-suggestions').length) {
            $suggestionsBox.addClass('d-none').empty();
        }
    });

    // Handle keyboard navigation
    $productInput.on('keydown', function(e) {
        const $visibleSuggestions = $suggestionsBox.find('button:visible');
        
        if ($suggestionsBox.hasClass('d-none') || $visibleSuggestions.length === 0) {
            return;
        }

        const $active = $suggestionsBox.find('button.active');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if ($active.length === 0) {
                $visibleSuggestions.first().addClass('active');
            } else {
                $active.removeClass('active');
                const $next = $active.next('button');
                if ($next.length) {
                    $next.addClass('active');
                } else {
                    $visibleSuggestions.first().addClass('active');
                }
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if ($active.length === 0) {
                $visibleSuggestions.last().addClass('active');
            } else {
                $active.removeClass('active');
                const $prev = $active.prev('button');
                if ($prev.length) {
                    $prev.addClass('active');
                } else {
                    $visibleSuggestions.last().addClass('active');
                }
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if ($active.length) {
                $productInput.val($active.text());
                $suggestionsBox.addClass('d-none').empty();
            }
        } else if (e.key === 'Escape') {
            $suggestionsBox.addClass('d-none').empty();
        }
    });
}

// Initialize when document is ready
$(document).ready(function() {
    initProductSuggestions();
});