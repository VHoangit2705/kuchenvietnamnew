/**
 * Xử lý chuyển đổi giữa các sheet
 */

function showSheet(sheetNumber) {
    // Hide all sheets
    for (let i = 1; i <= 4; i++) {
        document.getElementById('sheet' + i).classList.remove('active');
        document.querySelectorAll('.sheet-tab button')[i - 1].classList.remove('active');
    }
    
    // Show selected sheet
    document.getElementById('sheet' + sheetNumber).classList.add('active');
    document.querySelectorAll('.sheet-tab button')[sheetNumber - 1].classList.add('active');
}

