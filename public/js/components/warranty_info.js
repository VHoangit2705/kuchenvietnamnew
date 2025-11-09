/**
 * JavaScript for warranty_info component
 */

$(document).ready(function() {
    $('.timeline-header, .timeline-details').click(function() {
        toggleTimelineDetails($(this).closest('.timeline-item'));
    });
});

/**
 * Hiển thị/ẩn chi tiết lịch sử bảo hành
 * @param {jQuery} timelineItem - jQuery object của timeline item
 */
function toggleTimelineDetails(timelineItem) {
    const details = timelineItem.find('.timeline-details');

    // Đóng tất cả các chi tiết khác
    $('.timeline-details').not(details).animate({
        height: 0
    }, 10);
    $('.toggle-details').not(timelineItem.find('.toggle-details')).text('▼');

    // Toggle chi tiết được click
    if (details.height() > 0) {
        details.animate({
            height: 0
        }, 10);
        timelineItem.find('.toggle-details').text('▼');
    } else {
        const fullHeight = details.prop('scrollHeight');
        details.animate({
            height: fullHeight
        }, 10);
        timelineItem.find('.toggle-details').text('▲');
    }
}

