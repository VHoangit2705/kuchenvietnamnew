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
        $user = User::findOrFail($request->user_id);
        $user->roles()->sync($request->roles); // Gán role cho user

        return redirect()->back()->with('success', 'Cập nhật quyền thành công!');
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

        User::create([
            'full_name' => $request->full_name,
            'password' => md5($request->password),
            'zone'=>$request->zone
        ]);

        return response()->json(['success' => true]);
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
