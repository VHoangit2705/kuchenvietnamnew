
$(document).ready(function () {
    initSupportIcon();
});

function initSupportIcon(iconPath = '/icons/customersupport.png') {
    const supportIcon = document.createElement('img');
    supportIcon.src = iconPath;
    supportIcon.alt = 'Support Icon';
    supportIcon.title = 'Hỗ trợ lỗi';
    supportIcon.style.position = 'fixed';
    supportIcon.style.bottom = '3%';
    supportIcon.style.left = '1%';
    supportIcon.style.width = '3rem';
    supportIcon.style.height = '3rem';
    supportIcon.style.cursor = 'pointer';
    supportIcon.style.zIndex = '1000';
    supportIcon.style.display = 'none';
    supportIcon.style.filter = 'brightness(0) saturate(100%) invert(17%) sepia(98%) saturate(7491%) hue-rotate(1deg) brightness(99%) contrast(121%)';
    supportIcon.style.transition = 'transform 0.3s ease';

    $(supportIcon)
        .on('mouseenter', function () {
            $(this).css('transform', 'scale(1.2)');
        })
        .on('mouseleave', function () {
            $(this).css('transform', 'scale(1)');
        })
        .on('click', function () {
            window.location.href = '/formerror';
        });
    // Kiểm tra xem ảnh có tồn tại không
    fetch(iconPath, { method: 'HEAD' })
        .then(response => {
            if (response.ok) {
                document.body.appendChild(supportIcon);
                supportIcon.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Không tìm thấy ảnh hỗ trợ:', error);
        });
}