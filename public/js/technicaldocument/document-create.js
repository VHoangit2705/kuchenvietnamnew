/**
 * Document Create Page JavaScript
 */

(function () {
    'use strict';

    // Wait for DOM to be ready
    jQuery(document).ready(function () {
        // Initialize filter with custom selectors
        window.TechnicalDocumentFilter.init(window.docCreateRoutes, {
            selectors: {
                category: '#docCategory',
                product: '#docProduct',
                origin: '#docOrigin',
                model: '#docModelId'
            }
        });
    });
})();
