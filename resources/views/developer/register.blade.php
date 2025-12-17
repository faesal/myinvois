<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Registration - MySyncTax</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f2f4f7;
            font-family: "Inter", sans-serif;
            padding-top: 40px;
        }

        .register-wrapper {
            max-width: 540px;
            margin: auto;
        }

        .register-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0px 4px 30px rgba(0, 0, 0, 0.06);
        }

        .register-card h2 {
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
        }

        .register-subtitle {
            text-align: center;
            color: #667085;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 500;
            color: #344054;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px 14px;
        }

        .btn-create {
            width: 100%;
            padding: 12px;
            background: #1e293b;
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-create:hover {
            background: #334155;
        }

        .signin-text {
            text-align: center;
            margin-top: 18px;
            color: #475569;
        }

        .signin-text a {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
        }

        .checkbox-text {
            font-size: 14px;
            color: #475569;
        }
    </style>
</head>

<body>

<div class="register-wrapper">
    <div class="register-card">

        <!-- TITLE -->
        <h2>Create your account</h2>
        <p class="register-subtitle">Join MySyncTax and start syncing today</p>

        <!-- SUCCESS ALERT -->
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- VALIDATION ERROR ALERT -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>Registration Failed</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- FORM -->
        <form action="{{ route('developer.register.submit') }}" method="POST">
            @csrf

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" name="name" class="form-control" required autofocus value="{{ old('name') }}">
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Last Name (optional)</label>
                    <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                @error('email')
                    <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
                @error('password')
                <small class="text-danger">{{ $message }}</small>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="agree" required>
                <label class="form-check-label checkbox-text" for="agree">
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>

            <button class="btn-create">Create Account</button>
        </form>

        <p class="signin-text">
            Already have an account?
            <a href="{{url('/login')}}">Sign in</a>
        </p>

    </div>
</div>


</body>
</html>
