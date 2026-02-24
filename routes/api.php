<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\APIController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarrantyRequestController;
use App\Http\Controllers\Api\Warranty_Upload_Error_Controller;

Route::post('/poserror', [APIController::class, 'PostError'])->name('poserror');

Route::post('/login', [AuthController::class, 'Login']);
Route::post('/gettoken', [AuthController::class, 'CreateToken']);
Route::middleware([\App\Http\Middleware\CheckApiToken::class])->group(function () {
    Route::get('/products', [ProductController::class, 'getLstProduct']);
});

use App\Http\Controllers\Api\CleanupSyncedMediaController;

//api backup ảnh và video
Route::get('/geturlvideos', [WarrantyRequestController::class, 'GetVideos']);
Route::post('/updatevideo', [WarrantyRequestController::class, 'UpdateVideos']); // cập nhật trường video
Route::get('/geturlimages', [WarrantyRequestController::class, 'GetImages']);
Route::post('/updateimage', [WarrantyRequestController::class, 'UpdateImages']); // cập nhật trường ảnh

// dọn dẹp file local đã được đồng bộ lên Drive
Route::get('/cleanup-synced-media', [CleanupSyncedMediaController::class, 'run']);

//api backup ảnh và video cho hình ảnh lỗi (warranty_upload_error)
Route::get('/geturlvideoserror', [Warranty_Upload_Error_Controller::class, 'GetVideosError']);
Route::post('/updatevideoerror', [Warranty_Upload_Error_Controller::class, 'UpdateVideosError']);
Route::get('/geturlimageserror', [Warranty_Upload_Error_Controller::class, 'GetImagesError']);
Route::post('/updateimageerror', [Warranty_Upload_Error_Controller::class, 'UpdateImagesError']);

Route::get('/gethuromproducts', [WarrantyRequestController::class, 'GetHuromPrducts']); // cập nhật trường ảnh

// API HỆ THỐNG
Route::post('/token', [AuthController::class, 'getToken']);
Route::middleware('auth:api')->get('/listproduct', [ProductController::class, 'getProduct']);
Route::middleware('auth:api')->get('/getwarrantyrequest', [WarrantyRequestController::class, 'getWarrantyRequestByPhoneNumber']);
Route::middleware('auth:api')->post('/createwarranty', [WarrantyRequestController::class, 'createWarranty']);


Route::get('/getdistricts', [ProductController::class, 'getDistrictByIdProvince']);
Route::get('/getwards', [ProductController::class, 'getWardByIdDistrict']);
