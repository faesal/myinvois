@extends('layouts.developerLayout')

@section('content')

<style>
    .section-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-box {
        background: #eef6ff;
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 14px;
        color: #2563eb;
    }

    .warning-box {
        background: #fff8e6;
        padding: 12px 16px;
        border-radius: 6px;
        border-left: 4px solid #facc15;
        font-size: 14px;
        color: #92400e;
    }

    .security-box {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        border-left: 4px solid #10b981;
        font-size: 14px;
    }

    .divider {
        margin: 25px 0;
        border-bottom: 1px solid #e5e7eb;
    }
</style>

<div class="container-fluid">

    <div class="card p-4 shadow-sm mb-4">

        <h3 class="mb-4">Edit Profile</h3>

        <form action="{{ route('developer.profile.update') }}" method="POST">
            @csrf
            @method('PUT')

            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- ============================================================
                SYSTEM INFORMATION
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-id-badge"></i>
                System Information
            </div>

            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">User ID</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ $user->id }}" readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">UUID</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ $user->UUID }}" readonly>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Role</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ ucfirst($user->role) }}" readonly>
                </div>
            </div>

            <div class="divider"></div>

            <!-- ============================================================
                BASIC PROFILE INFORMATION
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-user"></i>
                Basic Information
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Full Name *</label>
                    <input type="text"
                           name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $user->name) }}"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email"
                           name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $user->email) }}"
                           required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="text"
                           name="phone"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $user->phone) }}"
                           required>

                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <!-- PHONE NUMBER WARNING -->
            <div class="warning-box mb-3">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Phone number must contain <strong>only digits</strong> and have a
                <strong>minimum of 9 digits</strong>.
            </div>

            <div class="divider"></div>

            <!-- ============================================================
                PASSWORD
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-lock"></i>
                Change Password (Optional)
            </div>

            <div class="info-box mb-3">
                Leave password fields empty if you do not wish to change your password.
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password"
                           name="password_confirmation"
                           class="form-control">
                </div>
            </div>

            <div class="divider"></div>

            <!-- ============================================================
                ACCOUNT METADATA
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-clock"></i>
                Account Information
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Created At</label>
                    <input type="text" class="form-control bg-light text-secondary"
                           value="{{ $user->created_at }}" readonly>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Updated</label>
                    <input type="text" class="form-control bg-light text-secondary"
                           value="{{ $user->updated_at }}" readonly>
                </div>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('developer.dashboard') }}" class="btn btn-light">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary px-4">
                    Update Profile
                </button>
            </div>

        </form>

    </div>

</div>

@endsection
