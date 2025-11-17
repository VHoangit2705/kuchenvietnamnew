<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\KyThuat\Role;
use App\Models\KyThuat\Permission;
use App\Models\KyThuat\User;
use App\Models\KyThuat\RolePermission;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $users = User::all();
        $roles = Role::all();
        $selectedUser = null;

        if ($request->has('user_id')) {
            $selectedUser = User::with('roles')->find($request->user_id);
        }

        return view('permissions.index', compact('users', 'roles', 'selectedUser'));
    }

    public function update(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'roles' => 'array',
                'roles.*' => 'exists:roles,id'
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            DB::beginTransaction();

            $user = User::findOrFail($request->user_id);
            
            // Sync roles cập nhật mảng rỗng để xóa hết quyền (có thể rỗng)
            $user->roles()->sync($request->roles ?? []);
            
            // Cập nhật position dựa trên role được chọn
            if (!empty($request->roles)) {
                // Lấy description của role đầu tiên được chọn để ghi vào position
                $firstRole = Role::find($request->roles[0]);
                if ($firstRole && $firstRole->description) {
                    $user->position = $firstRole->description;
                } else {
                    $user->position = '';
                }
            } else {
                // Nếu không có role nào được chọn, xóa position
                $user->position = '';
            }
            $user->save();

            DB::commit();

            return redirect()->back()->with('success', 'Cập nhật quyền thành công!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Có lỗi xảy ra khi cập nhật quyền: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function IndexRole()
    {
        $listRoles = Role::all();
        return view('permissions.roles', compact('listRoles'));
    }

    public function Delete($id)
    {
        DB::beginTransaction();
        try {
            $role = Role::findOrFail($id);
            $role->permissions()->detach();
            $role->users()->detach();
            $role->delete();
            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xoá vai trò.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    ///
    public function Detail($manhom)
    {
        $role = Role::with('permissions')->findOrFail($manhom);
        $listPermissions = Permission::all()->groupBy('description');
        return view('permissions.edit', compact('role', 'listPermissions'));
    }

    public function CreateUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|max:255|unique:users,email',
            'full_name' => 'required|string|max:255',
            'password' => 'required|string|min:6',
            'zone' => 'required|string'
        ], [
            'username.required' => 'Tên đăng nhập là bắt buộc.',
            'username.unique' => 'Tên đăng nhập này đã được sử dụng.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'full_name.required' => 'Tên đầy đủ là bắt buộc.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'zone.required' => 'Chi nhánh là bắt buộc.'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors
            ], 422);
        }

        // Kiểm tra username trùng lặp
        $existingUsername = User::where('username', $request->username)->first();
        if ($existingUsername) {
            return response()->json([
                'success' => false,
                'message' => 'Tên đăng nhập này đã được sử dụng. Vui lòng chọn tên đăng nhập khác.',
            ], 422);
        }

        // Kiểm tra email trùng lặp
        $existingEmail = User::where('email', $request->email)->first();
        if ($existingEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Email này đã được sử dụng. Vui lòng chọn email khác.',
            ], 422);
        }

        // Kiểm tra mật khẩu trùng lặp
        $hashedPassword = md5($request->password);
        $existingUser = User::where('password', $hashedPassword)->first();

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu này đã được sử dụng bởi tài khoản khác. Vui lòng chọn mật khẩu khác.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            User::create([
                'username' => $request->username,
                'email' => $request->email,
                'full_name' => $request->full_name,
                'password' => $hashedPassword,
                'zone' => $request->zone,
                'position' => '' // Position sẽ được cập nhật khi cấp quyền
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tài khoản đã được tạo thành công.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Xử lý lỗi database cụ thể
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                if (strpos($e->getMessage(), 'username') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Tên đăng nhập này đã được sử dụng.',
                    ], 422);
                } elseif (strpos($e->getMessage(), 'email') !== false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Email này đã được sử dụng.',
                    ], 422);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo tài khoản: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function CreateRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:255',
            'role_description' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        }

        $role = Role::where('name', $request->role_name)->first();
        if($role){
            return response()->json([
                'success' => false,
                'message' => "Nhóm quyền đã được sử dụng.",
            ]);
        }

        Role::create([
            'name' => $request->role_name,
            'description' => $request->role_description,
        ]);

        return response()->json(['success' => true]);
    }
    public function StoreRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|exists:roles,id',
            'role_name' => 'required|string|max:255',
            'role_description' => 'required|string',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ], [
            'role_id.required' => 'ID nhóm quyền là bắt buộc.',
            'role_id.exists' => 'Nhóm quyền không tồn tại.',
            'role_name.required' => 'Tên nhóm quyền là bắt buộc.',
            'role_name.max' => 'Tên nhóm quyền không được vượt quá 255 ký tự.',
            'role_description.required' => 'Mô tả nhóm quyền là bắt buộc.',
            'permissions.array' => 'Danh sách quyền không hợp lệ.',
            'permissions.*.exists' => 'Một hoặc nhiều quyền không tồn tại.'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $firstError = $errors->first();
            return response()->json([
                'success' => false,
                'message' => $firstError,
                'errors' => $errors
            ], 422);
        }

        try {
            DB::beginTransaction();

            $role = Role::findOrFail($request->role_id);

            // Kiểm tra tên nhóm quyền trùng lặp (trừ chính nó)
            $existingRole = Role::where('name', $request->role_name)
                                ->where('id', '!=', $request->role_id)
                                ->first();

            if ($existingRole) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tên nhóm quyền này đã được sử dụng. Vui lòng chọn tên khác.',
                ], 422);
            }

            $role->name = $request->role_name;
            $role->description = $request->role_description;
            $role->save();

            $role->permissions()->sync($request->permissions ?? []);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật nhóm quyền thành công.'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Xử lý lỗi database cụ thể
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tên nhóm quyền này đã được sử dụng.',
                ], 422);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật nhóm quyền: ' . $e->getMessage(),
            ], 500);
        }
    }
}