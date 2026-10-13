@extends('layouts.guest')

@section('title', 'Sign In')

@section('content')
<div class="card card-wms shadow-sm">
    <div class="card-body p-4 p-sm-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 bg-primary bg-opacity-10 text-primary rounded-circle mb-3">
                <i class="bi bi-boxes fs-1"></i>
            </div>
            <h4 class="fw-bold mb-1">WMS Enterprise</h4>
            <p class="text-muted fs-14">PostgreSQL 18 &amp; Laravel 13 Edition</p>
        </div>

        @if($errors->any())
        <div class="alert alert-danger py-2 px-3 fs-13 mb-3">
            <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="email" class="form-label fs-13 fw-semibold">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email', 'admin@wms.internal') }}" required autofocus>
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <label for="password" class="form-label fs-13 fw-semibold mb-0">Password</label>
                </div>
                <div class="input-group mt-1">
                    <span class="input-group-text bg-white"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" name="password" id="password" class="form-control" value="password" required>
                </div>
            </div>

            <div class="mb-4 form-check">
                <input type="checkbox" name="remember" class="form-check-input" id="remember" checked>
                <label class="form-check-label fs-13 text-muted" for="remember">Keep me logged in</label>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Warehouse
            </button>
        </form>

        <!-- Quick 1-Click Role Switcher Demo Bar -->
        <div class="mt-4 pt-3 border-top text-center">
            <span class="fs-12 text-muted d-block mb-2">Preset Demo Accounts (Default Password: <code>password</code>)</span>
            <div class="d-flex flex-wrap justify-content-center gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary fs-11" onclick="fillCreds('admin@wms.internal')">
                    Admin
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary fs-11" onclick="fillCreds('manager@wms.internal')">
                    Manager
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary fs-11" onclick="fillCreds('operator@wms.internal')">
                    Operator
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary fs-11" onclick="fillCreds('dispatcher@wms.internal')">
                    Dispatcher
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function fillCreds(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
    }
</script>
@endsection
