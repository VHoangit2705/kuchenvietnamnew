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

Route::get('/login', [loginController::class, 'Index'])->name("login.form");
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/login', [loginController::class, 'Login'])->name("login");

// Password change routes
Route::middleware('auth')->group(function () {
    Route::post('/password/change', [loginController::class, 'changePassword'])->name('password.change');
    Route::get('/password/check-expiry', [loginController::class, 'checkPasswordExpiry'])->name('password.check-expiry');
});

Route::middleware('auth')->get('/keep-alive', function () {
    return response()->json(['status' => 'alive']);
});

// Home
Route::middleware(['auth', \App\Http\Middleware\CheckCookieLogin::class])->group(function () {
    Route::get('/', [HomeController::class, 'Index'])->name("home");
    Route::get('/baohanh/capnhat', [HomeController::class, 'UpdateWarrantyEnd'])->name('capnhat');
    Route::get('/baohanh/capnhatactiv', [HomeController::class, 'UpdateWarrantyActive'])->name('capnhatkichhoatbaohanh');
    Route::get('/baohanh/hurom', [WarrantyController::class, 'IndexHurom'])->name("warranty.hurom"); //Hurom
    Route::get('/baohanh/kuchen', [WarrantyController::class, 'IndexKuchen'])->name("warranty.kuchen"); //Kuchen
});

// Warranty
Route::middleware(['auth', \App\Http\Middleware\CheckBrandSession::class, \App\Http\Middleware\CheckCookieLogin::class])->group(function () {
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
    Route::post('/baohanh/kiemtrabaohanh/order', [WarrantyController::class, 'FindWarrantyByOrderCode'])->name("warranty.findbyorder"); // tra cứu theo mã đơn hàng
    Route::post('/baohanh/kiemtranhanh', [WarrantyController::class, 'FindWarrantyQR'])->name("warranty.findqr"); // tra cứu qr
    Route::post('/baohanh/kiemtrabaohanhold', [WarrantyController::class, 'findWarantyOld'])->name("warranty.findold");
    Route::match(['GET', 'POST'], '/baohanh/phieubaohanh', [WarrantyController::class, 'FormWarrantyCard'])->name('warranty.formcard');
    Route::post('/baohanh/phieubaohanh/taophieu', [WarrantyController::class, 'CreateWarrany'])->name('warranty.createwarranty');
    Route::post('/getcollaborator', [WarrantyController::class, 'getCollaboratorByPhoneNumber'])->name('getcollaborator');
    Route::get('/baohanh/themanhsanpham', [WarrantyController::class, 'TakePhotoWarranty'])->name("warranty.takephoto");
    Route::post('/baohanh/savemedia', [WarrantyController::class, 'StoreMedia'])->name('warranty.storemedia');
    Route::get('/baohanh/linhkiensua/{sophieu}', [WarrantyController::class, 'GetComponents'])->name('warranty.getcomponent');
    //Cảnh báo khóa nhập hộ ca bảo hành
    Route::get('/baohanh/anomaly-alerts', [WarrantyController::class, 'AnomalyAlertsPage'])->name('warranty.anomaly.page');
    Route::get('/baohanh/anomaly-alerts/api', [WarrantyController::class, 'getAnomalyAlerts'])->name('warranty.anomaly.alerts');
    Route::post('/baohanh/anomaly-alerts/{id}/resolve', [WarrantyController::class, 'resolveAnomalyAlert'])->name('warranty.anomaly.resolve');
    Route::post('/baohanh/anomaly-alerts/{id}/unblock', [WarrantyController::class, 'unblockStaff'])->name('warranty.anomaly.unblock');
    Route::delete('/baohanh/anomaly-alerts/{id}', [WarrantyController::class, 'deleteAnomalyAlert'])->name('warranty.anomaly.delete');
});

// Collaborator
Route::middleware(['auth', \App\Http\Middleware\CheckBrandSession::class, \App\Http\Middleware\CheckCookieLogin::class])->group(function () {
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
    Route::post('/dieuphoi/chitiet/update-address', [CollaboratorInstallController::class, 'UpdateDetailCustomerAddress'])->name('dieuphoi.update.address');
    Route::post('/dieuphoi/chitiet/filter', [CollaboratorInstallController::class, 'Filter'])->name('collaborators.filter');
    // Route::post('/upload-excel', [CollaboratorInstallController::class, 'ImportExcel'])->name('upload-excel'); // Import old data
    Route::post('/upload-excel-sync', [ImportExcelSyncController::class, 'ImportExcelSync'])->name('upload-excel-sync'); // Sync data with upsert
    Route::get('/dieuphoi/baocaothongke', [ExportReportController::class, 'ReportCollaboratorInstall'])->name('collaborator.export');
    Route::get('/dieuphoi/baocaothongke/preview', [ExportReportController::class, 'ReportCollaboratorInstallPreview'])->name('collaborator.export.preview');
});

// Report
Route::middleware(['auth', \App\Http\Middleware\CheckBrandSession::class, \App\Http\Middleware\CheckCookieLogin::class])->group(function () {
    Route::get('/baohanh/baocao', [ReportController::class, "Index"])->name('baocao');
    Route::get('/sanpham', [ReportController::class, 'RecommentProduct'])->name('baocao.sanpham');
    Route::get('/linhkien', [ReportController::class, 'RecommentProductPart'])->name('baocao.linhkien');
    Route::get('/nhanvien', [ReportController::class, 'RecommentStaff'])->name('baocao.nhanvien');
    Route::get('/xuatbaocao', [ReportController::class, 'GetExportExcel'])->name('xuatbaocao');
});

// Public route to view report PDF (for email links)
Route::get('/reports/view/{filename}', [ReportController::class, 'viewReportPdf']);

//Code Warranty
Route::middleware(['auth', \App\Http\Middleware\CheckBrandSession::class, \App\Http\Middleware\CheckCookieLogin::class])->group(function () {
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
Route::match(['GET', 'POST'], '/reports/send-email/{type?}', [ReportCommandController::class, 'runSendReportEmail']);
Route::match(['GET', 'POST'], '/reports/save-overdue-history/{type?}', [ReportCommandController::class, 'runSaveOverdueHistory']);
