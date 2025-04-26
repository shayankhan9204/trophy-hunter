<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator as Validate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{

    public function index()
    {
        $roles = Role::all();
        return view('portal.user-role.index', compact('roles'));

    }

    public function create()
    {
        return view('portal.user-role.create');
    }

    public function store(Request $request)
    {
        $validator = Validate::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'permissions' => 'required|array|min:1',
        ], [
            'permissions.required' => 'Please select a permission.',
            'permissions.min' => 'Please select at least one permission.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator);
        }
        try {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

            foreach ($request->permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName], ['guard_name' => 'web']);
                $role->givePermissionTo($permission);
                $permission->assignRole($role);
            }

            return redirect()->route('user.role.index')->with('success', 'Role created successfully!');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();

        }
    }

    public function edit($id)
    {
        $role = Role::where('id', $id)->first();
        return view('portal.user-role.edit', compact('role'));

    }

    public function update(Request $request, $role)
    {
        $role_id = $request->route('role');
        if ($role_id == null) {
            $role_id = $role;
        }
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role_id,
            'permissions' => 'array',
        ]);

        if (count($request->permissions) == 0) {
            return redirect()->back()->with('error', 'Please select its permissions!');

        }

        $role = Role::where('id', $role_id)->first();
        $role->update(['name' => $request->name]);

        $role->syncPermissions($request->permissions);

        return redirect()->route('user.role.index')->with('success', 'Role updated successfully!');

    }

    public function delete($id)
    {
        try {
            $roles = Role::where('id', $id)->get();


            if ($roles->isEmpty()) {
                return redirect()->back()->with('error', 'Role not found');
            }

            foreach ($roles as $role) {
                $role->delete();
            }

            return redirect()->back()->with('success', 'User Roles Deleted Successfully');
        } catch (\Exception $exception) {
            return redirect()->back()->with('error', 'Something went wrong')->withInput();
        }
    }


}
