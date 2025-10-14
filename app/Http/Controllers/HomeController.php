<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Kho\Product;
use App\Models\KyThuat\WarrantyRequest;
use App\Models\Kho\WarrantyActive;

class HomeController extends Controller
{
    public function Index()
    {
        return view("home");
    }

    
    // hàm cập nhật ngày hết hạn cho tất cả các phiếu bảo hành trong csdl
    public function UpdateWarrantyEnd()
    {
        $warranties = WarrantyRequest::all();
        $countUpdated = 0;
        $countError = 0;
        $errors = [];

        foreach ($warranties as $warranty) {
            try {
                $product = Product::where('product_name', $warranty->product)->first();

                if ($product && $warranty->shipment_date) {
                    $shipmentDate = Carbon::parse($warranty->shipment_date);
                    $warrantyEnd = $shipmentDate->copy()->addMonths($product->month);

                    $warranty->warranty_end = $warrantyEnd->format('Y-m-d');
                    $warranty->save();

                    $countUpdated++;
                }
            } catch (\Exception $e) {
                $countError++;
                $errors[] = [
                    'id' => $warranty->id,
                    'error' => $e->getMessage(),
                ];
                // Tiếp tục với bản ghi tiếp theo
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cập nhật hoàn thành. $countUpdated phiếu thành công, $countError phiếu lỗi.",
            'errors' => $errors,
        ]);
    }

    public function UpdateWarrantyActive()
    {
        $data = WarrantyActive::getProductInfo();
        $countUpdated = 0;
        $countError = 0;
        $errors = [];

        foreach ($data as $item) {
            try {
                $warranty_end = Carbon::parse($item->shipment_date)->addMonths((int)$item->month)->toDateString();

                // Tìm bản ghi bằng model và cập nhật
                $record = WarrantyActive::find($item->id);
                if ($record) {
                    $record->product = $item->product_name;
                    $record->product_image = $item->image;
                    $record->warranty_end = $warranty_end;
                    $record->view = $item->view;
                    $record->save();

                    $countUpdated++;
                } else {
                    $countError++;
                    $errors[] = [
                        'id' => $item->id,
                        'error' => 'Không tìm thấy bản ghi theo ID',
                    ];
                }
            } catch (\Exception $e) {
                $countError++;
                $errors[] = [
                    'id' => $item->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Cập nhật hoàn thành. $countUpdated phiếu thành công, $countError phiếu lỗi.",
            'errors' => $errors,
        ]);
    }
}