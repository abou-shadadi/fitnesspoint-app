<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;

class CheckFeatureOperation
{
    public function handle($request, Closure $next, $featureKey)
    {
        $user = $request->user();
        $operation = $this->getOperationFromRequest($request);

        // Check if user has a role and the role is active
        if ($user->role && $user->role->status === 'active') {
            // Get permissions associated with the user's role
            $permissions = Permission::where('role_id', $user->role->id)
                ->whereHas('feature', function ($query) use ($featureKey) {
                    $query->where('key', $featureKey);
                })
                ->where('status', 'active')
                ->get();

            // Check permissions
            foreach ($permissions as $permission) {
                // If the permission allows the operation
                if ($permission->$operation) {
                    return $next($request);
                }
            }
        }

        // Return appropriate error response
        if (!$user->role) {
            return response()->json(['error' => 'User has no role assigned'], 403);
        } elseif ($user->role->status !== 'active') {
            return response()->json(['error' => 'User role is inactive'], 403);
        } else {
            return response()->json(['error' => 'User does not have permission to perform this operation'], 403);
        }
    }

    private function getOperationFromRequest($request)
    {
        switch ($request->method()) {
            case 'GET':
                return 'read';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return null;
        }
    }
}
