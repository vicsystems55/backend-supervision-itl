<?php

namespace App\Http\Controllers;

use Log;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserAccountsController extends Controller
{
    /**
     * Display a listing of users with their roles and permissions.
     */
    public function index(Request $request): JsonResponse
    {


        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search', '');

            $users = User::with(['roles', 'permissions'])
                ->when($search, function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'users' => $users,
                'total' => $users->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
 * Get only technicians (Technical Lead, Technical Assistant, Driver)
 */
public function technicians(Request $request): JsonResponse
{
    try {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search', '');
        $role = $request->get('role', '');
        $status = $request->get('status', '');

        $users = User::with(['roles', 'permissions'])
            ->when($role, function ($query) use ($role) {
                // Specify the guard
                $query->role($role, 'api');
            }, function ($query) {
                // Specify the guard for multiple roles
                $query->role(['Technician Lead', 'Technician Assistant', 'Driver'], 'api');
            })
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($status === 'active', function ($query) {
                $query->whereNull('deleted_at');
            })
            ->when($status === 'inactive', function ($query) {
                $query->whereNotNull('deleted_at');
            })
            ->orderBy('name')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'users' => $users,
            'total' => $users->total(),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch technicians',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Display the specified user with detailed role and permission information.
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with(['roles', 'permissions'])->findOrFail($id);

            // Get all available roles and permissions for the UI
            $allRoles = Role::all();
            $allPermissions = Permission::all();

            return response()->json([
                'success' => true,
                'user' => $user,
                'available_roles' => $allRoles,
                'available_permissions' => $allPermissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

public function store(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|integer|exists:roles,id',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        \Log::info('User created', ['user_id' => $user->id, 'email' => $user->email]);

        // Debug: Check what role ID we received
        \Log::info('Role ID from request', ['role_id' => $validated['role'] ?? 'none']);

        // Assign single role if provided
        if (isset($validated['role'])) {
            $role = Role::find($validated['role']);

            Log::info('Role found', [
                'role_id' => $role ? $role->id : 'not found',
                'role_name' => $role ? $role->name : 'none',
                'guard_name' => $role ? $role->guard_name : 'none'
            ]);

            if ($role) {
                try {
                    $user->assignRole($role->name);
                    \Log::info('Role assigned successfully', [
                        'user_id' => $user->id,
                        'role_name' => $role->name
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to assign role', [
                        'user_id' => $user->id,
                        'role_name' => $role->name,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            } else {
                \Log::warning('Role not found', ['role_id' => $validated['role']]);
            }
        }

        // Assign permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();

            \Log::info('Permissions found', [
                'permission_ids' => $validated['permissions'],
                'permissions_count' => $permissions->count()
            ]);

            if ($permissions->isNotEmpty()) {
                try {
                    $permissionNames = $permissions->pluck('name');
                    $user->givePermissionTo($permissionNames);
                    \Log::info('Permissions assigned successfully', [
                        'user_id' => $user->id,
                        'permissions' => $permissionNames->toArray()
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to assign permissions', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
            }
        }

        // Reload user with roles and permissions to verify
        $user->load(['roles', 'permissions']);

        \Log::info('Final user state', [
            'user_id' => $user->id,
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->permissions->pluck('name')
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $user,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('User creation failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to create user',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update the specified user account.
     */
public function update(Request $request, $id): JsonResponse
{
    try {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role' => 'sometimes|integer|exists:roles,id', // Single role ID
            'permissions' => 'sometimes|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        DB::beginTransaction();

        // Update basic user info
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        $user->save();

        // Sync single role if provided
        if (isset($validated['role'])) {
            $role = Role::find($validated['role']);
            if ($role) {
                $user->syncRoles([$role->name]); // Spatie method - replaces all roles
            }
        }

        // Sync permissions if provided
        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->pluck('name');
            $user->syncPermissions($permissions); // Spatie method - replaces all permissions
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'user' => $user->load(['roles', 'permissions']),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Reset user password to default.
     */
    public function resetPassword($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $defaultPassword = 'ITL@2025';
            $user->password = Hash::make($defaultPassword);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully to default',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign roles to a user.
     */
    public function assignRoles(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'exists:roles,id',
            ]);

            $roles = Role::whereIn('id', $validated['roles'])->pluck('name');
            $user->syncRoles($roles);

            return response()->json([
                'success' => true,
                'message' => 'Roles assigned successfully',
                'user' => $user->load('roles'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign permissions to a user.
     */
    public function assignPermissions(Request $request, $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validated = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id',
            ]);

            $permissions = Permission::whereIn('id', $validated['permissions'])->pluck('name');
            $user->syncPermissions($permissions);

            return response()->json([
                'success' => true,
                'message' => 'Permissions assigned successfully',
                'user' => $user->load('permissions'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available roles and permissions for assignment.
     */
    public function getRolePermissions(): JsonResponse
    {
        try {
            $roles = Role::all();
            $permissions = Permission::all();

            return response()->json([
                'success' => true,
                'roles' => $roles,
                'permissions' => $permissions,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles and permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a user account.
     */
    public function deactivate($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // You might want to add a 'deactivated_at' timestamp instead of deleting
            $user->delete(); // or $user->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'User deactivated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reactivate a user account.
     */
    public function reactivate($id): JsonResponse
    {
        try {
            // If using soft deletes
            $user = User::withTrashed()->findOrFail($id);
            $user->restore();

            // If using is_active flag
            // $user = User::findOrFail($id);
            // $user->update(['is_active' => true]);

            return response()->json([
                'success' => true,
                'message' => 'User reactivated successfully',
                'user' => $user->load(['roles', 'permissions']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reactivate user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
