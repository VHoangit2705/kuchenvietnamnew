<?php

namespace App\Http\Controllers;

use App\Models\Kho\UserAgency;
use App\Models\Kho\Agency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\Paginator;

Paginator::useBootstrap();

class UserAgencyController extends Controller
{
    static $pageSize = 50;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = UserAgency::with('agency');

        // Filter theo tên đại lý
        if ($request->has('agency_name') && $request->agency_name) {
            $query->whereHas('agency', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->agency_name . '%');
            });
        }

        // Filter theo số điện thoại đại lý
        if ($request->has('agency_phone') && $request->agency_phone) {
            $query->whereHas('agency', function($q) use ($request) {
                $q->where('phone', 'like', '%' . $request->agency_phone . '%');
            });
        }

        // Filter theo username (số điện thoại đăng nhập)
        if ($request->has('username') && $request->username) {
            $query->where('username', 'like', '%' . $request->username . '%');
        }

        // Filter theo fullname (tên user)
        if ($request->has('fullname') && $request->fullname) {
            $query->where('fullname', 'like', '%' . $request->fullname . '%');
        }

        // Filter theo trạng thái tài khoản
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter theo đã xác minh (dựa trên isVerified)
        if ($request->has('verified') && $request->verified !== '') {
            if ($request->verified == '1') {
                // Đã xác minh: status = 1, có phone_verified_at và agency_id != null
                $query->where('status', 1)
                      ->whereNotNull('phone_verified_at')
                      ->whereNotNull('agency_id');
            } else {
                // Chưa xác minh: còn lại
                $query->where(function($q) {
                    $q->where('status', '!=', 1)
                      ->orWhereNull('phone_verified_at')
                      ->orWhereNull('agency_id');
                });
            }
        }

        // Sắp xếp: mới nhất trước
        $query->orderByDesc('created_at');

        // Phân trang
        $users = $query->paginate(self::$pageSize)->withQueryString();

        // Đếm số lượng theo trạng thái (dùng cùng logic với filter)
        $counts = [
            'all' => UserAgency::count(),
            'verified' => UserAgency::where('status', 1)
                ->whereNotNull('phone_verified_at')
                ->whereNotNull('agency_id')
                ->count(),
            'unverified' => UserAgency::where(function($q) {
                    $q->where('status', '!=', 1)
                      ->orWhereNull('phone_verified_at')
                      ->orWhereNull('agency_id');
                })->count(),
            'active' => UserAgency::where('status', 1)->count(),
            'inactive' => UserAgency::where('status', 2)->count(),
        ];

        return view('useragency.index', compact('users', 'counts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Lấy danh sách đại lý (nếu cần dùng sau này)
        $agencies = Agency::orderBy('name')->get();
        return view('useragency.create', compact('agencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:20|unique:mysql3.user_agency,username',
            'password' => 'required|string|min:6|confirmed',
            'fullname' => 'required|string|max:255',
            'agency_id' => 'nullable|exists:mysql3.agency,id',
            'status' => 'required|in:0,1,2',
        ], [
            'username.unique' => 'Số điện thoại này đã được đăng ký!',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp!',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Mã hóa mật khẩu bằng MD5 (theo yêu cầu hệ thống)
        $hashedPassword = md5($request->password);

        // Khi đại lý mới đăng ký: luôn để phone_verified_at = null (Chưa xác minh)
        UserAgency::create([
            'username' => $request->username,
            'password' => $hashedPassword,
            'fullname' => $request->fullname,
            'agency_id' => $request->agency_id,
            'status' => $request->status,
            'phone_verified_at' => null,
        ]);

        return redirect()->route('useragency.index')
            ->with('success', 'Tạo tài khoản đại lý thành công!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = UserAgency::with('agency')->findOrFail($id);
        return view('useragency.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = UserAgency::with('agency')->findOrFail($id);
        $agencies = Agency::orderBy('name')->get();
        return view('useragency.edit', compact('user', 'agencies'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = UserAgency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:20|unique:mysql3.user_agency,username,' . $id,
            'password' => 'nullable|string|min:6|confirmed',
            'fullname' => 'required|string|max:255',
            'status' => 'required|in:0,1,2',
            'agency_name' => 'nullable|string|max:255',
            'agency_phone' => 'nullable|string|max:20',
            'agency_address' => 'nullable|string|max:255',
            'agency_bank_name' => 'nullable|string|max:255',
            'agency_sotaikhoan' => 'nullable|string|max:100',
            'agency_chinhanh' => 'nullable|string|max:255',
            'agency_cccd' => 'nullable|string|max:20',
        ], [
            'username.unique' => 'Số điện thoại này đã được đăng ký!',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp!',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'username' => $request->username,
            'fullname' => $request->fullname,
            'status' => $request->status,
        ];

        // Chỉ cập nhật mật khẩu nếu có nhập
        if ($request->filled('password')) {
            $updateData['password'] = md5($request->password);
        }

        // Nếu chuyển sang trạng thái active (1) và chưa có phone_verified_at, set nó
        if ($request->status == 1 && !$user->phone_verified_at) {
            $updateData['phone_verified_at'] = now();
        }

        // Nếu chuyển sang trạng thái không active (0 hoặc 2), xóa phone_verified_at
        if (in_array((int)$request->status, [0, 2], true)) {
            $updateData['phone_verified_at'] = null;
        }

        $user->update($updateData);

        // Cập nhật thông tin cá nhân của đại lý (nếu có liên kết agency)
        if ($user->agency_id) {
            $agency = Agency::find($user->agency_id);
            if ($agency) {
                $agencyUpdate = [];
                if ($request->filled('agency_name') && $request->agency_name !== $agency->name) {
                    $agencyUpdate['name'] = $request->agency_name;
                }
                if ($request->filled('agency_phone') && $request->agency_phone !== $agency->phone) {
                    $agencyUpdate['phone'] = $request->agency_phone;
                }
                if ($request->filled('agency_address') && $request->agency_address !== $agency->address) {
                    $agencyUpdate['address'] = $request->agency_address;
                }
                if ($request->filled('agency_bank_name') && $request->agency_bank_name !== $agency->bank_name_agency) {
                    $agencyUpdate['bank_name_agency'] = $request->agency_bank_name;
                }
                if ($request->filled('agency_sotaikhoan') && $request->agency_sotaikhoan !== $agency->sotaikhoan) {
                    $agencyUpdate['sotaikhoan'] = $request->agency_sotaikhoan;
                }
                if ($request->filled('agency_chinhanh') && $request->agency_chinhanh !== $agency->chinhanh) {
                    $agencyUpdate['chinhanh'] = $request->agency_chinhanh;
                }
                if ($request->filled('agency_cccd') && $request->agency_cccd !== $agency->cccd) {
                    $agencyUpdate['cccd'] = $request->agency_cccd;
                }

                if (!empty($agencyUpdate)) {
                    $agency->update($agencyUpdate);
                }
            }
        }

        return redirect()->route('useragency.index')
            ->with('success', 'Cập nhật tài khoản đại lý thành công!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = UserAgency::findOrFail($id);
        $user->delete();

        return redirect()->route('useragency.index')
            ->with('success', 'Xóa tài khoản đại lý thành công!');
    }

    /**
     * Reset password cho tài khoản
     */
    public function resetPassword(Request $request, string $id)
    {
        $user = UserAgency::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:6|confirmed',
        ], [
            'new_password.confirmed' => 'Mật khẩu xác nhận không khớp!',
            'new_password.min' => 'Mật khẩu phải có ít nhất 6 ký tự!',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user->password = md5($request->new_password);
        $user->save();

        return redirect()->route('useragency.show', $id)
            ->with('success', 'Đặt lại mật khẩu thành công!');
    }

    /**
     * Kích hoạt/xóa kích hoạt tài khoản
     */
    public function toggleStatus(string $id)
    {
        $user = UserAgency::findOrFail($id);
        // Nếu đang kích hoạt (1) -> chuyển sang khóa/vô hiệu hóa (2)
        // Nếu đang ở trạng thái khác (0 hoặc 2) -> chuyển sang kích hoạt (1)
        $user->status = $user->status == 1 ? 2 : 1;
        
        // Nếu kích hoạt (1) và chưa có phone_verified_at, set nó
        if ($user->status == 1 && !$user->phone_verified_at) {
            $user->phone_verified_at = now();
        }
        
        // Nếu vô hiệu hóa/khóa (2), xóa phone_verified_at
        if ($user->status == 2) {
            $user->phone_verified_at = null;
        }
        
        $user->save();

        return response()->json([
            'success' => true,
            'message' => $user->status == 1 ? 'Kích hoạt tài khoản thành công!' : 'Vô hiệu hóa tài khoản thành công!',
            'status' => $user->status
        ]);
    }
}
