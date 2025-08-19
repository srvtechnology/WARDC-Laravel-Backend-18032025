<?php

namespace App\Http\Controllers;

use App\Models\RoleModel;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\PermissionNew;

class RoleController extends Controller
{


    public function index()
    {
        try {
            $roles = RoleModel::with('permissions:id,module,action')
                          ->select(['id', 'name', 'created_at'])
                          ->orderBy('id','desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $roles
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name'
            ]);

            $role = RoleModel::create([
                'name' => $validated['name'],
                'guard_name' => 'admin'
            ]);

            // audit_log('add', 'role', $role->id, request()->all());

            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = RoleModel::with('permissions:id,module,action')
                         ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function updatePermissions(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer|exists:permissions_new,id'
            ]);

            $role = RoleModel::findOrFail($id);

            DB::transaction(function () use ($role, $validated) {
                $role->permissions()->sync($validated['permission_ids']);
            });


            // audit_log('add', 'role', $role->id, [
            // 'permissions' => json_encode($request->all()),
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Permissions updated successfully',
                'data' => $role->load('permissions:id,module,action')
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'Validation failed'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $role = RoleModel::findOrFail($id);

            DB::transaction(function () use ($role) {
                $role->permissions()->detach();
                $role->delete();
            });

             // audit_log('add', 'role', $role->id, [
             //    'deleted_id' => $role->id,
             //    ]);

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], 500);
        }
    }







public function getUserPermissions(Request $request)
{
    try {
        $user = auth()->user(); // Assuming you're using auth

        if (!$user || !$user->role_id) {
            return response()->json([
                'success' => false,
                'message' => 'User or role not found.'
            ], 404);
        }

        // Load role and its permissions
        $role = $user->role()->with('permissions:id,module,action')->first();

        return response()->json([
            'success' => true,
            'data' => $role->permissions
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch permissions',
            'error' => $e->getMessage()
        ], 500);
    }
}



public function permission_index(){
     return PermissionNew::all();
}







}
