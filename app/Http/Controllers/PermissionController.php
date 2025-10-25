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
            'full_name' => 'required|string|max:255',
            'password' => 'required|string',
            'zone' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        }

        // 1. Mã hóa mật khẩu mới bằng md5 để so sánh
        $hashedPassword = md5($request->password);

        // 2. Kiểm tra xem mật khẩu đã tồn tại hay chưa
        $passwordExists = User::where('password', $hashedPassword)->first();

        // 3. Nếu mật khẩu đã tồn tại, trả về lỗi
        if ($passwordExists) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu đã được sử dụng, vui lòng chọn mật khẩu khác.'
            ]);
        }

        // Nếu mật khẩu là duy nhất, tiến hành tạo user
        User::create([
            'full_name' => $request->full_name,
            'password' => $hashedPassword, // Sử dụng lại mật khẩu đã mã hóa
            'zone' => $request->zone
        ]);

        if ($existingUser) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu này đã được sử dụng bởi tài khoản khác. Vui lòng chọn mật khẩu khác.',
            ]);
        }

        try {
            DB::beginTransaction();

            User::create([
                'full_name' => $request->full_name,
                'password' => $hashedPassword,
                'zone' => $request->zone,
                'position' => '' // Position sẽ được cập nhật khi cấp quyền
            ]);

            DB::commit();

            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo tài khoản: ' . $e->getMessage(),
            ]);
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
            'role_name' => 'required|string|max:255',
            'role_description' => 'required|string',
            'permissions' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ],);
        }

        $role = Role::find($request->role_id);

        if ($role) {
            $role->name = $request->role_name;
            $role->description = $request->role_description;
            $role->save();

            $role->permissions()->sync($request->permissions ?? []);
        }
        return response()->json(['success' => true]);
    }
}
