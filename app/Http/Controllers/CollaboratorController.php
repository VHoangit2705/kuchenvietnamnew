<?php

namespace App\Http\Controllers;

use App\Models\KyThuat\WarrantyCollaborator;
use App\Models\KyThuat\Province;
use App\Models\KyThuat\District;
use App\Models\KyThuat\Wards;
use Illuminate\Http\Request;
use App\Models\Kho\Agency;
use Illuminate\Support\Facades\Validator;

class CollaboratorController extends Controller
{
    static $pageSize = 50;
    public function __construct()
    {
        $this->middleware('permission:Xem danh sách CTV')->only(['Index']);
        $this->middleware('permission:Cập nhật CTV')->only(['CreateCollaborator', 'DeleteCollaborator']);
    }
    
    public function Index(Request $request)
    {
        $query = WarrantyCollaborator::query();

        if ($request->filled('province')) {
            $query->where('province_id', $request->province);
        }

        if ($request->filled('district')) {
            $query->where('district_id', $request->district);
        }

        if ($request->filled('ward')) {
            $query->where('ward_id', $request->ward);
        }

        if ($request->filled('full_name')) {
            $query->where('full_name', 'like', '%' . $request->full_name . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        $data = $query->orderBy('id', 'desc')->paginate(self::$pageSize);
        $lstProvince = Province::all();

        if ($request->ajax()) {
            return view('collaborator.tablecontent', compact('data'))->render(); // không cần json()
        }

        return view('collaborator.index', compact('data', 'lstProvince'));
    }

    public function getByID(Request $request)
    {
        $id = $request->id;
        $collaborator = WarrantyCollaborator::find($id);

        if (!$collaborator) {
            return response()->json(['message' => 'Không tìm thấy cộng tác viên']);
        }
        $lstProvince = Province::all();
        $lstDistricts = District::getByProvinceID($collaborator->province_id);
        $lstWards = Wards::getByDistrictID($collaborator->district_id);
        return response()->json([
            'message' => 'Lấy dữ liệu thành công',
            'data' => [
                'collaborator' => $collaborator,
                'provinces' => $lstProvince,
                'districts' => $lstDistricts,
                'wards' => $lstWards,
            ]
        ]);
    }
    
    public function getCollaboratorByID($id)
    {
        $collaborator = WarrantyCollaborator::find($id);
        return response()->json($collaborator);
    }

    public function GetDistrictByProvinveId($province_id)
    {
        $districts = District::getByProvinceID($province_id);
        return response()->json($districts);
    }

    public function GetWardByDistrictId($district_id)
    {
        $wards = Wards::getByDistrictID($district_id);
        return response()->json($wards);
    }

    public function CreateCollaborator(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            // 'date_of_birth' => 'required|date',
            'phone' => 'required|digits_between:9,12',
            'province' => 'required|string',
            'province_id' => 'required|string',
            'district' => 'required',
            'district_id' => 'required',
            'ward_id' => 'required',
            'ward' => 'required|string',
            'address' => 'required|string|max:1024',
        ]);
        $validated['create_by'] = session('user');
        if ($request->id) {
            WarrantyCollaborator::where('id', $request->id)->update($validated);
            return response()->json(['success' => true, 'message' => 'Cập nhật thành công']);
        }
        WarrantyCollaborator::create($validated);
        return response()->json(['success' => true, 'message' => 'Thêm mới thành công']);
    }

    public function DeleteCollaborator($id)
    {
        $item = WarrantyCollaborator::find($id);
        if ($item) {
            $item->delete();
            return response()->json(['success' => true, 'message' => 'Xoá thành công']);
        }
        return response()->json(['success' => false, 'message' => 'Lỗi trong quá trình xoá']);
    }
    
    public function UpdateCollaborator(Request $request){
        $collab = WarrantyCollaborator::find($request->id);
        if($collab){
            $collab->sotaikhoan = $request->sotaikhoan;
            $collab->chinhanh = $request->chinhanh;
            $collab->cccd = $request->cccd;
            $collab->ngaycap = $request->ngaycap;
            $collab->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thành công',
            'data' => $collab
        ]);
    }
    
    public function UpdateAgency(Request $request){
        $validator = Validator::make($request->all(), [
            'agency_name' => 'required',
            'agency_address' => 'nullable',
            'agency_phone' => 'required',
            'agency_paynumber' => 'nullable',
            'agency_branch' => 'nullable',
            'agency_cccd' => 'nullable',
            'agency_release_date' => 'nullable',
        ]);
        if ($validator->fails()) { return; }
        Agency::updateOrCreate(
            ['phone' => $request->agency_phone],
            [
                'name'       => $request->agency_name,
                'address'    => $request->agency_address,
                'sotaikhoan' => $request->agency_paynumber,
                'chinhanh'   => $request->agency_branch,
                'cccd'       => $request->agency_cccd,
                'ngaycap'    => $request->agency_release_date,
                'create_by'  => session('user'),
            ]
        );
    }
}
