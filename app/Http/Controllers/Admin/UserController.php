<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $warehouseId = $request->query('warehouse_id');
        $search = $request->query('search');

        $query = User::with('warehouse');

        if ($role) {
            $query->where('role', $role);
        }
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }
        if ($search) {
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('name', $like, "%{$search}%")
                  ->orWhere('email', $like, "%{$search}%")
                  ->orWhere('phone', $like, "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->paginate(15)->withQueryString();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'warehouses', 'role', 'warehouseId', 'search'));
    }

    public function create()
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        return view('admin.users.create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,manager,operator,dispatcher',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', "Staff member '{$user->name}' [Role: {$user->role}] created successfully.");
    }

    public function edit(User $user)
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        return view('admin.users.edit', compact('user', 'warehouses'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:admin,manager,operator,dispatcher',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'phone' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        // Prevent admin from deactivating their own account
        if ($user->id === auth()->id() && !$validated['is_active']) {
            return back()->withErrors(['error' => 'You cannot deactivate your own administrative account.']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', "Staff member '{$user->name}' updated successfully.");
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot deactivate your own account.']);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.users.index')
            ->with('info', "Staff account for '{$user->name}' has been {$statusStr}.");
    }
}
