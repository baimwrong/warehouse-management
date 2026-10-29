@extends('layouts.app')

@section('title', 'Staff & User Management')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people-fill text-primary me-2"></i> Staff &amp; User Administration</h4>
        <p class="text-muted fs-14 mb-0">Manage warehouse operators, dispatchers, managers, and access permissions</p>
    </div>
    <div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-person-plus me-1"></i> Add New Staff Member
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card card-wms mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email, or phone..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select form-select-sm">
                    <option value="">-- All Roles --</option>
                    <option value="admin" {{ $role === 'admin' ? 'selected' : '' }}>Super Admin</option>
                    <option value="manager" {{ $role === 'manager' ? 'selected' : '' }}>Warehouse Manager</option>
                    <option value="operator" {{ $role === 'operator' ? 'selected' : '' }}>Inventory Operator</option>
                    <option value="dispatcher" {{ $role === 'dispatcher' ? 'selected' : '' }}>Lead Dispatcher</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="warehouse_id" class="form-select form-select-sm">
                    <option value="">-- All Facilities --</option>
                    @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ $warehouseId == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-filter"></i>
                </button>
            </div>
            <div class="col-md-1">
                <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card card-wms">
    <div class="table-responsive">
        <table class="table table-wms align-middle mb-0">
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th>Role &amp; Scope</th>
                    <th>Assigned Facility</th>
                    <th>Contact Phone</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2.5 fs-13 fw-bold" style="width: 34px; height: 34px;">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ $user->name }}</div>
                                <div class="text-muted fs-11 font-monospace">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        @if($user->role === 'admin')
                            <span class="badge bg-danger text-white">Super Admin</span>
                        @elseif($user->role === 'manager')
                            <span class="badge bg-primary text-white">Warehouse Manager</span>
                        @elseif($user->role === 'dispatcher')
                            <span class="badge bg-warning text-dark">Lead Dispatcher</span>
                        @else
                            <span class="badge bg-secondary text-white">Inventory Operator</span>
                        @endif
                    </td>
                    <td>
                        @if($user->warehouse)
                            <span class="fw-semibold text-dark">{{ $user->warehouse->name }}</span>
                            <div class="fs-11 text-muted font-monospace">{{ $user->warehouse->code }}</div>
                        @else
                            <span class="text-muted fs-12">&bull; Global (All Facilities)</span>
                        @endif
                    </td>
                    <td class="fs-13 text-muted">
                        {{ $user->phone ?? 'N/A' }}
                    </td>
                    <td>
                        @if($user->is_active)
                            <span class="badge badge-soft-success">Active</span>
                        @else
                            <span class="badge badge-soft-danger">Deactivated</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-secondary px-2 py-1 fs-12">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.toggle', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Change active status for this user?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }} px-2 py-1 fs-12">
                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No staff members found matching criteria.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
