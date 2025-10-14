<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
class TechSupportController extends Controller
{
    public function index(){
        return view('submiterror');
    }
    
    public function SubmitError(Request $request)
    {
        //Kiểm tra dữ liệu đầu vào
        $request->validate([
            'yeucau' => 'required|string|max:255',
            'mota' => 'required|string',
            'hinh_anh.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        try {
            DB::connection('mysql2')->beginTransaction();
            $data = [
                'error_name' => $request->input('yeucau'),
                'description' => $request->input('mota'),
                'create_by' => 1,
                'update_by' => 1,
                'update_at' => null
                //'create_at' => Carbon::now('Asia/Ho_Chi_Minh'),
            ];
            $errorId = DB::connection('mysql2')->table('tech_support')->insertGetId($data);
            // Lưu các hình ảnh đã tải lên
            $uploadedFiles = [];
            if ($request->hasFile('hinh_anh')) {
                foreach ($request->file('hinh_anh') as $file) {
                    $path = $file->store('error_imgs', 'public');
                    $uploadedFiles[] = $path;
                    DB::connection('mysql2')->table('tech_support_img')->insert([
                        'id_error' => $errorId,
                        'error_img' => $path,
                    ]);
                }
            }
            // Hoàn tất giao dịch
            DB::connection('mysql2')->commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Yêu cầu đã được ghi nhận!',
                'error_id' => $errorId,
                'files' => $uploadedFiles,
            ], 200);
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Đã xảy ra lỗi trong quá trình xử lý!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ListProblem(){
        $data = DB::connection('mysql2')->table('tech_support')->orderBy('create_at', 'desc')->get();
        return view('listproblem', compact('data'));
    }

    public function DetailProblem(Request $request){
        $id = $request->query('id');
        if (!$id) {
            return redirect()->back()->with('error', 'ID không hợp lệ');
        }
        $data = DB::connection('mysql2')->table('tech_support')->where('id', $id)->first();

        if (!$data) {
            return redirect()->back()->with('error', 'Không tìm thấy lỗi.');
        }

        $images = DB::connection('mysql2')->table('tech_support_img')->where('id_error', $id)->get();
        // Trả về view với thông tin lỗi
        return view('detailproblem', compact('data', 'images'));
    }

    public function UpdateStatus(Request $request)
    {
        $id = $request->query('id');
        $solution = $request->query('solution');
        if (!$id) {
            return redirect()->back()->with('error', 'ID không hợp lệ.');
        }
        $record = DB::connection('mysql2')->table('tech_support')->where('id', $id)->first();
        if (!$record) {
            return redirect()->back()->with('error', 'Không tìm thấy bản ghi.');
        }
        DB::connection('mysql2')->table('tech_support')
            ->where('id', $id)
            ->update(['status' => 1, 'solution' => $solution, 'update_at' => Carbon::now('Asia/Ho_Chi_Minh')]);

        return redirect()->back()->with('success', 'Trạng thái đã được cập nhật.');
    }
}
