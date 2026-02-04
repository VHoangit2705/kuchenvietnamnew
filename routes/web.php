<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TechSupportController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CollaboratorInstallController;
use App\Http\Controllers\CollaboratorInstallCountsController;
use App\Http\Controllers\ImportExcelSyncController;
use App\Http\Controllers\CollaboratorController;
use App\Http\Controllers\PrintWarrantyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ExportReportController;
use App\Http\Controllers\Api\ReportCommandController;
use App\Http\Controllers\RequestAgencyController;
use App\Http\Controllers\UserAgencyController;
use App\Http\Middleware\CheckBrandSession;
use App\Http\Middleware\CheckCookieLogin;
use App\Http\Controllers\CollaboratorInstallBulkController;
use App\Http\Controllers\TechnicalDocumentController;
use App\Http\Controllers\DocumentShareController;
use App\Http\Controllers\CommonErrorController;

Route::get('/login', [loginController::class, 'Index'])->name("login.form");
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/login', [loginController::class, 'Login'])->name("login");
// Password change routes
Route::middleware('auth')->group(function () {
    Route::post('/password/change', [loginController::class, 'changePassword'])->name('password.change');
    Route::get('/password/check-expiry', [loginController::class, 'checkPasswordExpiry'])->name('password.check-expiry');
});


// Password change routes
Route::middleware('auth')->group(function () {
    Route::post('/password/change', [loginController::class, 'changePassword'])->name('password.change');
    Route::get('/password/check-expiry', [loginController::class, 'checkPasswordExpiry'])->name('password.check-expiry');
});

Route::middleware('auth')->get('/keep-alive', function () {
    return response()->json(['status' => 'alive']);
});

// Home
Route::middleware(['auth', CheckCookieLogin::class])->group(function () {
    Route::get('/', [HomeController::class, 'Index'])->name("home");
    Route::get('/baohanh/capnhat', [HomeController::class, 'UpdateWarrantyEnd'])->name('capnhat');
    Route::get('/baohanh/capnhatactiv', [HomeController::class, 'UpdateWarrantyActive'])->name('capnhatkichhoatbaohanh');
    Route::get('/baohanh/hurom', [WarrantyController::class, 'IndexHurom'])->name("warranty.hurom"); //Hurom
    Route::get('/baohanh/kuchen', [WarrantyController::class, 'IndexKuchen'])->name("warranty.kuchen"); //Kuchen
});

// Warranty
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    Route::get('/thongbao', [WarrantyController::class, 'ThongBaoBaoHanh'])->name("warranty.thongbao");
    Route::get('/baohanh/kuchen/timkiem', [WarrantyController::class, 'Search'])->name("warranty.search");
    Route::post('/baohanh/kuchen', [WarrantyController::class, 'UpdateStatus'])->name("warranty.updatestatus");
    Route::get('/baohanh/chitiet/{id}', [WarrantyController::class, 'Details'])->name("warranty.detail");
    Route::post('/baohanh/chitiet/capnhat', [WarrantyController::class, 'UpdateDetail'])->name("warranty.updatedetail");
    Route::post('/baohanh/chitiet/xoa', [WarrantyController::class, 'DeleteDetail'])->name("warranty.delete");
    Route::post('/baohanh/congsuachua', [WarrantyController::class, 'saveRepairJob'])->name('warranty.repairjobs.save');
    Route::get('/baohanh/congsuachua/{repairJob}', [WarrantyController::class, 'showRepairJob'])->name('warranty.repairjobs.show');
    Route::delete('/baohanh/congsuachua/{repairJob}', [WarrantyController::class, 'deleteRepairJob'])->name('warranty.repairjobs.delete');
    Route::post('/baohanh/chitiet/capnhatseri', [WarrantyController::class, 'UpdateSerial'])->name("warranty.updateserial");
    Route::post('/baohanh/chitiet/uploadphoto', [WarrantyController::class, 'UploadPhoto'])->name('photo.upload');  // tải ảnh lên
    Route::post('/baohanh/chitiet/uploadvideo', [WarrantyController::class, 'UploadVideo'])->name('video.upload');  // tải video lên
    Route::get('/baohanh/phieuin/{id}', [WarrantyController::class, 'GeneratePdf'])->name('warranty.pdf');
    Route::get('/baohanh/dowloadpdf/{id}', [WarrantyController::class, 'DowloadPdf'])->name('warranty.dowloadpdf');
    Route::get('/baohanh/qr/{id}', [WarrantyController::class, 'GetPaymentQr'])->name('warranty.qr');
    Route::get('/baohanh/request/{id}', [WarrantyController::class, 'Request'])->name('warranty.request');
    Route::get('/baohanh/kiemtrabaohanh', [WarrantyController::class, 'CheckWarranty'])->name("warranty.check");
    Route::post('/baohanh/kiemtrabaohanh', [WarrantyController::class, 'FindWarranty'])->name("warranty.find"); // tra cứu
    Route::post('/baohanh/kiemtrabaohanh/order', [WarrantyController::class, 'FindWarrantyByOrderCode'])->name("warranty.findbyorder");
    Route::post('/baohanh/kiemtrabaohanh/phone', [WarrantyController::class, 'FindWarrantyByPhone'])->name("warranty.findbyphone"); // tra cứu theo SĐT
    Route::post('/baohanh/kiemtranhanh', [WarrantyController::class, 'FindWarrantyQR'])->name("warranty.findqr"); // tra cứu qr
    Route::post('/baohanh/kiemtrabaohanhold', [WarrantyController::class, 'findWarantyOld'])->name("warranty.findold");
    Route::match(['GET', 'POST'], '/baohanh/phieubaohanh', [WarrantyController::class, 'FormWarrantyCard'])->name('warranty.formcard');
    Route::post('/baohanh/phieubaohanh/taophieu', [WarrantyController::class, 'CreateWarrany'])->name('warranty.createwarranty');
    Route::post('/getcollaborator', [WarrantyController::class, 'getCollaboratorByPhoneNumber'])->name('getcollaborator');
    Route::get('/baohanh/themanhsanpham', [WarrantyController::class, 'TakePhotoWarranty'])->name("warranty.takephoto");
    Route::post('/baohanh/savemedia', [WarrantyController::class, 'StoreMedia'])->name('warranty.storemedia');
    Route::get('/baohanh/linhkiensua/{sophieu}', [WarrantyController::class, 'GetComponents'])->name('warranty.getcomponent');
    Route::get('/baohanh/getproductcategory', [WarrantyController::class, 'getProductCategory'])->name('warranty.getProductCategory');
    Route::get('/baohanh/getproductsbycategory', [WarrantyController::class, 'getProductsByCategory'])->name('warranty.getProductsByCategory');
    Route::get('/baohanh/getproductsuggestions', [WarrantyController::class, 'getProductSuggestions'])->name('warranty.getProductSuggestions');
    //Cảnh báo khóa nhập hộ ca bảo hành
    Route::get('/baohanh/anomaly-alerts', [WarrantyController::class, 'AnomalyAlertsPage'])->name('warranty.anomaly.page');
    Route::get('/baohanh/anomaly-alerts/api', [WarrantyController::class, 'getAnomalyAlerts'])->name('warranty.anomaly.alerts');
    Route::post('/baohanh/anomaly-alerts/{id}/resolve', [WarrantyController::class, 'resolveAnomalyAlert'])->name('warranty.anomaly.resolve');
    Route::post('/baohanh/anomaly-alerts/{id}/unblock', [WarrantyController::class, 'unblockStaff'])->name('warranty.anomaly.unblock');
    Route::delete('/baohanh/anomaly-alerts/{id}', [WarrantyController::class, 'deleteAnomalyAlert'])->name('warranty.anomaly.delete');
});

// Collaborator
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    Route::get('/congtacvien', [CollaboratorController::class, 'Index'])->name('ctv.getlist');
    Route::post('/getbyid', [CollaboratorController::class, 'getByID'])->name('ctv.getbyid');
    Route::get('/getdistrict/{province_id}', [CollaboratorController::class, 'GetDistrictByProvinveId'])->name('ctv.getdistrict');
    Route::get('/getward/{district_id}', [CollaboratorController::class, 'GetWardByDistrictId'])->name('ctv.getward');
    Route::post('/createcollaborator', [CollaboratorController::class, 'CreateCollaborator'])->name('ctv.create');
    Route::get('/collaborator/delete/{id}', [CollaboratorController::class, 'DeleteCollaborator'])->name('ctv.delete');
    Route::post('/congtacvien/capnhat', [CollaboratorController::class, 'UpdateCollaborator'])->name('ctv.update');
    Route::post('/congtacvien/clear', [CollaboratorController::class, 'ClearCollaborator'])->name('ctv.clear'); //Clear CTV data
    Route::post('/congtacvien/switch', [CollaboratorController::class, 'SwitchToCtv'])->name('ctv.switch'); //Switch to CTV
    Route::get('/congtacvien/{id}', [CollaboratorController::class, 'getCollaboratorByID'])->name('collaborator.show');
    Route::post('/daily/capnhat', [CollaboratorController::class, 'UpdateAgency'])->name('agency.update'); //Cập nhật đại lý
    Route::get('/congtacvien/lichsu/{id}', [CollaboratorController::class, 'getCollaboratorHistory'])->name('ctv.history'); //Lịch sử thay đổi CTV
    Route::get('/congtacvien/lichsu-order/{order_code}', [CollaboratorController::class, 'getOrderHistory'])->name('ctv.order.history'); //Lịch sử thay đổi theo order
    //Điều phối công tác viên
    Route::get('/dieuphoicongtacvien', [CollaboratorInstallController::class, 'Index'])->name('dieuphoi.index');
    Route::get('/dieuphoi/tab-data', [CollaboratorInstallController::class, 'getTabData'])->name('dieuphoi.tabdata');
    Route::get('/dieuphoi/chitiet/{id}', [CollaboratorInstallController::class, 'Details'])->name("dieuphoi.detail");
    Route::get('/dieuphoicongtacvien/counts', [CollaboratorInstallCountsController::class, 'Counts'])->name('dieuphoi.counts');
    Route::post('/dieuphoi/update', [CollaboratorInstallController::class, 'Update'])->name("dieuphoi.update");
    Route::post('/dieuphoi/bulk-update', [CollaboratorInstallBulkController::class, 'BulkUpdate'])->name("dieuphoi.bulk.update");
    Route::post('/dieuphoi/bulk-update-excel', [CollaboratorInstallBulkController::class, 'BulkUpdateByExcel'])->name("dieuphoi.bulk.update.excel");
    Route::post('/dieuphoi/chitiet/update-address', [CollaboratorInstallController::class, 'UpdateDetailCustomerAddress'])->name('dieuphoi.update.address');
    Route::post('/dieuphoi/chitiet/filter', [CollaboratorInstallController::class, 'Filter'])->name('collaborators.filter');
    // Route::post('/upload-excel', [CollaboratorInstallController::class, 'ImportExcel'])->name('upload-excel'); // Import old data
    Route::post('/upload-excel-sync', [ImportExcelSyncController::class, 'ImportExcelSync'])->name('upload-excel-sync'); // Sync data with upsert
    Route::get('/dieuphoi/baocaothongke', [ExportReportController::class, 'ReportCollaboratorInstall'])->name('collaborator.export');
    Route::get('/dieuphoi/baocaothongke/preview', [ExportReportController::class, 'ReportCollaboratorInstallPreview'])->name('collaborator.export.preview');
});

// Report
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    Route::get('/baohanh/baocao', [ReportController::class, "Index"])->name('baocao');
    Route::get('/sanpham', [ReportController::class, 'RecommentProduct'])->name('baocao.sanpham');
    Route::get('/linhkien', [ReportController::class, 'RecommentProductPart'])->name('baocao.linhkien');
    Route::get('/nhanvien', [ReportController::class, 'RecommentStaff'])->name('baocao.nhanvien');
    Route::get('/xuatbaocao', [ReportController::class, 'GetExportExcel'])->name('xuatbaocao');
    Route::get('/baohanh/baocao/preview-product-warranty', [ReportController::class, 'previewProductWarrantyReport'])->name('baocao.preview.product.warranty');
    Route::get('/baohanh/baocao/preview-excel', [ReportController::class, 'previewReportExcel'])->name('baocao.preview.excel');
});

// Public route to view report PDF (for email links)
Route::get('/reports/view/{filename}', [ReportController::class, 'viewReportPdf']);

//Code Warranty
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    Route::get('/baohanh/inphieubaohanh', [PrintWarrantyController::class, "Index"])->name('warrantycard');
    Route::get('/baohanh/inphieubaohanh/loc', [PrintWarrantyController::class, "Search"])->name('warrantycard.search');
    Route::post('/baohanh/inphieubaohanh/taomoi', [PrintWarrantyController::class, "Create"])->name('warrantycard.create');
    Route::delete('/baohanh/inphieubaohanh/delete/{id}', [PrintWarrantyController::class, "Delete"])->name('warrantycard.delete');
    Route::get('/baohanh/inphieubaohanh/body', [PrintWarrantyController::class, 'partialTable'])->name('warrantycard.partial');
    Route::get('/baohanh/inphieubaohanh/chitiet/{id}', [PrintWarrantyController::class, 'Details'])->name("warrantycard.detail");
    Route::get('/baohanh/inphieubaohanh/{maphieu}', [PrintWarrantyController::class, 'SerialDetails'])->name("warrantycard.serial_detail");
    Route::get('/baohanh/inphieubaohanh/tem/{id}', [PrintWarrantyController::class, 'TemView'])->name('warrantycard.tem');
    Route::get('/baohanh/inphieubaohanh/dowloadtem/{id}', [PrintWarrantyController::class, 'TemDowload'])->name('warrantycard.temdowload');
    Route::get('/baocaokichhoatbaohanh', [PrintWarrantyController::class, 'ExportActiveWarranty'])->name('baocaokichhoatbaohanh');
    Route::get('/baohanh/tailieukithuat', [TechnicalDocumentController::class, 'Index'])->name('warranty.document')->middleware('role:admin,kythuatvien');
    Route::get('/baohanh/tailieukithuat/create', [TechnicalDocumentController::class, 'Create'])->name('warranty.document.create')->middleware('role:admin,kythuatvien');
    Route::prefix('baohanh/tailieukithuat')->group(function () {

        Route::get(
            '/get-products-by-category',
            [TechnicalDocumentController::class, 'getProductsByCategory']
        )->name('warranty.document.getProductsByCategory');

        Route::get(
            '/get-origins-by-product',
            [TechnicalDocumentController::class, 'getOriginsByProduct']
        )->name('warranty.document.getOriginsByProduct');

        Route::get(
            '/get-models-by-origin',
            [TechnicalDocumentController::class, 'getModelsByOrigin']
        )->name('warranty.document.getModelsByOrigin');

        Route::get(
            '/get-errors-by-model',
            [TechnicalDocumentController::class, 'getErrorsByModel']
        )->name('warranty.document.getErrorsByModel');

        Route::get(
            '/get-error-detail',
            [TechnicalDocumentController::class, 'getErrorDetail']
        )->name('warranty.document.getErrorDetail');

        Route::get(
            '/download-all-documents',
            [TechnicalDocumentController::class, 'downloadAllDocuments']
        )->name('warranty.document.downloadAllDocuments');

        Route::post(
            '/store-origin',
            [TechnicalDocumentController::class, 'storeOrigin']
        )->name('warranty.document.storeOrigin')->middleware('role:admin,kythuatvien');

        Route::post(
            '/store-error',
            [TechnicalDocumentController::class, 'storeError']
        )->name('warranty.document.storeError')->middleware('role:admin,kythuatvien');

        Route::post(
            '/store-repair-guide',
            [TechnicalDocumentController::class, 'storeRepairGuide']
        )->name('warranty.document.storeRepairGuide')->middleware('role:admin,kythuatvien');

        // Common errors CRUD (update, destroy; create/store đã có)
        Route::get('/common-errors/{id}', [TechnicalDocumentController::class, 'getErrorById'])->name('warranty.document.commonError.show');
        Route::put('/common-errors/{id}', [TechnicalDocumentController::class, 'updateError'])->name('warranty.document.commonError.update')->middleware('role:admin,kythuatvien');
        Route::delete('/common-errors/{id}', [TechnicalDocumentController::class, 'destroyError'])->name('warranty.document.commonError.destroy')->middleware('role:admin,kythuatvien');

        // Repair guides CRUD (edit, update, destroy; create/store đã có)
        Route::get('/repair-guides-by-error', [TechnicalDocumentController::class, 'getRepairGuidesByError'])->name('warranty.document.repairGuides.byError');
        Route::get('/repair-guides/edit/{id}', [TechnicalDocumentController::class, 'editRepairGuide'])->name('warranty.document.repairGuide.edit')->middleware('role:admin,kythuatvien');
        Route::put('/repair-guides/{id}', [TechnicalDocumentController::class, 'updateRepairGuide'])->name('warranty.document.repairGuide.update')->middleware('role:admin,kythuatvien');
        Route::delete('/repair-guides/{id}', [TechnicalDocumentController::class, 'destroyRepairGuide'])->name('warranty.document.repairGuide.destroy')->middleware('role:admin,kythuatvien');
        Route::post('/repair-guides/{id}/attach-documents', [TechnicalDocumentController::class, 'attachDocumentsToRepairGuide'])->name('warranty.document.repairGuide.attachDocuments')->middleware('role:admin,kythuatvien');
        Route::delete('/repair-guides/{id}/documents/{documentId}', [TechnicalDocumentController::class, 'detachDocumentFromRepairGuide'])->name('warranty.document.repairGuide.detachDocument')->middleware('role:admin,kythuatvien');

        // Technical documents CRUD
        Route::get('/documents', [TechnicalDocumentController::class, 'indexDocuments'])->name('warranty.document.documents.index')->middleware('role:admin,kythuatvien');
        Route::get('/documents/create', [TechnicalDocumentController::class, 'createDocument'])->name('warranty.document.documents.create')->middleware('role:admin,kythuatvien');
        Route::post('/documents', [TechnicalDocumentController::class, 'storeDocument'])->name('warranty.document.documents.store')->middleware('role:admin,kythuatvien');
        Route::get('/documents/edit/{id}', [TechnicalDocumentController::class, 'editDocument'])->name('warranty.document.documents.edit')->middleware('role:admin,kythuatvien');
        Route::get('/documents/{id}', [TechnicalDocumentController::class, 'showDocument'])->name('warranty.document.documents.show')->middleware('role:admin,kythuatvien');
        Route::put('/documents/{id}', [TechnicalDocumentController::class, 'updateDocument'])->name('warranty.document.documents.update')->middleware('role:admin,kythuatvien');
        Route::delete('/documents/{id}', [TechnicalDocumentController::class, 'destroyDocument'])->name('warranty.document.documents.destroy')->middleware('role:admin,kythuatvien');
        Route::get('/documents-by-model', [TechnicalDocumentController::class, 'getDocumentsByModel'])->name('warranty.document.documents.byModel');

        // ... (Existing Technical Document Routes)
        Route::get('/documents-by-model', [TechnicalDocumentController::class, 'getDocumentsByModel'])->name('warranty.document.documents.byModel');

        // --- Document Sharing Routes (Admin) ---
        Route::prefix('share')->group(function () {
            Route::post('/create', [DocumentShareController::class, 'store'])->name('warranty.document.share.store');
            Route::get('/list/{document_version_id}', [DocumentShareController::class, 'index'])->name('warranty.document.share.index');
            Route::post('/revoke/{id}', [DocumentShareController::class, 'revoke'])->name('warranty.document.share.revoke');
        });

        // --- Common Error Management Routes ---
        Route::prefix('errors')->group(function () {
            Route::get('/', [CommonErrorController::class, 'index'])->name('warranty.document.errors.index');
            Route::get('/create', [CommonErrorController::class, 'create'])->name('warranty.document.errors.create');
            Route::post('/', [CommonErrorController::class, 'store'])->name('warranty.document.errors.store');
            Route::get('/{id}/edit', [CommonErrorController::class, 'edit'])->name('warranty.document.errors.edit');
            Route::put('/{id}', [CommonErrorController::class, 'update'])->name('warranty.document.errors.update');
            Route::delete('/{id}', [CommonErrorController::class, 'destroy'])->name('warranty.document.errors.destroy');
        });
    });
}); // End Middleware group

// --- Public Document Share Routes (No Auth Required) ---
Route::prefix('shared-docs')->group(function () {
    Route::get('/{token}', [DocumentShareController::class, 'publicShow'])->name('document.share.public_show');
    Route::post('/{token}/auth', [DocumentShareController::class, 'publicAuth'])->name('document.share.public_auth');
    Route::get('/{token}/download', [DocumentShareController::class, 'download'])->name('document.share.download');
});

//Permissions
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/phanquyentaikhoan', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('/admin/phanquyentaikhoan/taotaikhoan', [PermissionController::class, 'CreateUser'])->name('roles.createuser');
    Route::post('/admin/phanquyentaikhoan/capnhat', [PermissionController::class, 'update'])->name('permissions.update');
    Route::post('/admin/phanquyennhom/capnhat', [PermissionController::class, 'StoreRole'])->name('roles.store');
    Route::post('/admin/phanquyennhom/taomoi', [PermissionController::class, 'CreateRole'])->name('roles.create');
    Route::get('/admin/phanquyennhom', [PermissionController::class, 'IndexRole'])->name('permission.roles');
    Route::get('/admin/phanquyennhom/chinhsua/{manhom}', [PermissionController::class, 'Detail'])->name('permission.detail');
    Route::delete('/admin/phanquyennhom/xoa/{id}', [PermissionController::class, 'Delete'])->name('permission.delete');
});

// Request Agency (Yêu cầu lắp đặt đại lý)
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    // Quản lý xác nhận đại lý lần đầu - Phải đặt trước resource để tránh conflict
    Route::get('/requestagency/manage-agencies', [RequestAgencyController::class, 'manageAgencies'])->name('requestagency.manage-agencies');
    Route::get('/requestagency/confirm-agency/{id}', [RequestAgencyController::class, 'confirmAgencyForm'])->name('requestagency.confirm-agency-form');
    Route::post('/requestagency/confirm-agency/{id}', [RequestAgencyController::class, 'confirmAgency'])->name('requestagency.confirm-agency');
    Route::get('/requestagency/find-installation-order', [RequestAgencyController::class, 'findInstallationOrder'])->name('requestagency.find-installation-order');
    Route::get('/requestagency/installation-order', [RequestAgencyController::class, 'redirectInstallationOrder'])->name('requestagency.installation-order');
    // Resource routes
    Route::resource('requestagency', RequestAgencyController::class);
    Route::post('/requestagency/{id}/update-status', [RequestAgencyController::class, 'updateStatus'])->name('requestagency.update-status');
});

// User Agency (Quản lý tài khoản đại lý)
Route::middleware(['auth', CheckBrandSession::class, CheckCookieLogin::class])->group(function () {
    Route::post('/useragency/{id}/reset-password', [UserAgencyController::class, 'resetPassword'])->name('useragency.reset-password');
    Route::post('/useragency/{id}/toggle-status', [UserAgencyController::class, 'toggleStatus'])->name('useragency.toggle-status');
    Route::resource('useragency', UserAgencyController::class);
});

// hỗ trợ
Route::get('/formerror', [TechSupportController::class, 'Index'])->name('formerror');
Route::post('/submiterror1', [TechSupportController::class, 'SubmitError1'])->name('submiterror1');
Route::get('/listproblem', [TechSupportController::class, 'ListProblem'])->name('listproblem');
Route::get('/detailproblem', [TechSupportController::class, 'DetailProblem'])->name('detailproblem');
Route::get('/updatestatus', [TechSupportController::class, 'UpdateStatus'])->name('updatestatus');
// Cronjob routes for report commands (public routes for scheduled tasks)
Route::match(['GET', 'POST'], '/reports/save-snapshot/{type?}', [ReportCommandController::class, 'runSaveSnapshot']);

Route::match(['GET', 'POST'], '/reports/send-email/{type?}', [ReportCommandController::class, 'runSendReportEmail']);
Route::match(['GET', 'POST'], '/reports/save-overdue-history/{type?}', [ReportCommandController::class, 'runSaveOverdueHistory']);
// =====================================================
// PUBLIC DOCUMENT SHARE (SUBDOMAIN docs.kuchenvietnam.vn)
// =====================================================
Route::domain('docs.kuchenvietnam.vn')->group(function () {

    // Xem tài liệu (public)
    Route::get('/{token}', [DocumentShareController::class, 'publicShow'])
        ->name('docs.share.show');

    // Xác thực mật khẩu (nếu có)
    Route::post('/{token}/auth', [DocumentShareController::class, 'publicAuth'])
        ->name('docs.share.auth');

    // Download file (nếu được phép)
    Route::get('/{token}/download', [DocumentShareController::class, 'download'])
        ->name('docs.share.download');
});

// Route::get('/clear-cache', function () {

//     // Clear các cache của Laravel
//     Artisan::call('clear-compiled');
//     Artisan::call('cache:clear');
//     Artisan::call('config:clear');
//     Artisan::call('route:clear');
//     Artisan::call('view:clear');
//     Artisan::call('optimize:clear');

//     // Reset OPCACHE nếu server cho phép
//     if (function_exists('opcache_reset')) {
//         opcache_reset();
//         $opcache = 'OPCACHE RESET';
//     } else {
//         $opcache = 'OPCACHE NOT ENABLED';
//     }

//     return response()->json([
//         'status' => 'OK',
//         'message' => 'Đã clear toàn bộ cache Laravel',
//         'opcache' => $opcache,
//         'note' => 'Composer dump-autoload cần chạy bằng SSH'
//     ]);
// });

// Route::get('/test-model', function () {
//     return class_exists(\App\Models\KyThuat\WarrantyUploadError::class)
//         ? 'MODEL OK'
//         : 'MODEL NOT FOUND';
// });
// Route::get('/debug-app-path', function () {
//     return [
//         'base_path' => base_path(),
//         'app_path' => app_path(),
//         'models_exist' => file_exists(app_path('Models/KyThuat/WarrantyUploadError.php')),
//     ];
// });
