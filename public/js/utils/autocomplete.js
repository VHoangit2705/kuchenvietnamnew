/**
 * Autocomplete utility functions
 * Các hàm tiện ích cho autocomplete
 */

/**
 * Setup autocomplete từ server (AJAX)
 * @param {string} inputSelector - Selector của input
 * @param {string} suggestionBoxSelector - Selector của suggestion box
 * @param {string} requestUrl - URL để lấy dữ liệu
 * @param {number} minLength - Độ dài tối thiểu để trigger (default: 1)
 */
function setupAutoComplete(inputSelector, suggestionBoxSelector, requestUrl, minLength = 1) {
    $(inputSelector).on('keyup', function() {
        let query = $(this).val();
        if (query.length === 0) {
            $(suggestionBoxSelector).hide();
            return;
        }
        if (query.length >= minLength) {
            $.ajax({
                url: requestUrl,
                type: 'GET',
                data: {
                    query: query
                },
                success: function(data) {
                    $(suggestionBoxSelector).empty();
                    if (data.length > 0) {
                        $(suggestionBoxSelector).show();
                        data.forEach(function(item) {
                            $(suggestionBoxSelector).append(
                                '<div class="suggestion-item" style="padding: 8px; cursor: pointer; border-bottom: 1px solid #eee;">' + item + '</div>'
                            );
                        });
                        // Gán lại sự kiện click cho từng item
                        $(suggestionBoxSelector + ' .suggestion-item').on('click', function() {
                            $(inputSelector).val($(this).text());
                            $(suggestionBoxSelector).hide();
                        });
                    } else {
                        $(suggestionBoxSelector).hide();
                    }
                }
            });
        }
    });
}

/**
 * Setup autocomplete từ client-side (local list)
 * @param {string} inputSelector - Selector của input
 * @param {string} suggestionBoxSelector - Selector của suggestion box
 * @param {Array} dataList - Mảng dữ liệu để tìm kiếm
 * @param {string} searchKey - Key để tìm kiếm trong object (default: 'product_name')
 * @param {number} maxResults - Số kết quả tối đa (default: 10)
 */
function setupClientAutoComplete(inputSelector, suggestionBoxSelector, dataList, searchKey = 'product_name', maxResults = 10) {
    $(inputSelector).on('input', function() {
        const keyword = $(this).val().toLowerCase().trim();
        const $suggestionsBox = $(suggestionBoxSelector);
        $suggestionsBox.empty();

        if (!keyword) {
            $suggestionsBox.addClass('d-none');
            return;
        }

        const matched = dataList.filter(item => {
            const searchValue = item[searchKey] || item;
            return typeof searchValue === 'string' && searchValue.toLowerCase().includes(keyword);
        });

        if (matched.length > 0) {
            matched.slice(0, maxResults).forEach(item => {
                const displayText = item[searchKey] || item;
                $suggestionsBox.append(
                    `<button type="button" class="list-group-item list-group-item-action">${displayText}</button>`
                );
            });
            $suggestionsBox.removeClass('d-none');
        } else {
            $suggestionsBox.addClass('d-none');
        }
    });

    $(document).on('mousedown', suggestionBoxSelector + ' button', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(inputSelector).val($(this).text());
        $(suggestionBoxSelector).addClass('d-none').empty();
    });
}

/**
 * Ẩn suggestion box khi click ra ngoài
 * @param {Array} selectors - Array of objects chứa các selector cần xử lý
 * @example [{ input: '#product', suggestion: '#suggestions-product-name' }]
 */
function setupClickOutsideToHide(selectors) {
    $(document).on('click', function(e) {
        selectors.forEach(({ input, suggestion }) => {
            if (!$(e.target).closest(input).length && !$(e.target).closest(suggestion).length) {
                $(suggestion).hide().addClass('d-none').empty();
            }
        });
    });
}

