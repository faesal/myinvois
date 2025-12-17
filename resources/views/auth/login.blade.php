<!doctype html>
<html lang="en">
<head>
    <title>MySyncTax | Login</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Font & Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900&display=swap">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Custom Login Style -->
    <link rel="stylesheet" href="https://preview.colorlib.com/theme/bootstrap/login-form-17/css/style.css">
</head>
<body>
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center"></div>
        <div class="row justify-content-center">
            <div class="col-md-12 col-lg-10">
                <div class="wrap d-md-flex">
                    <div class="text-wrap p-4 p-lg-5 text-center d-flex align-items-center order-md-last">
                        <div class="text w-100">
                            <!--<center>
                                <img src="{{ asset('images/logo.png') }}" style="width: auto; max-width: 180px;">
                            </center>-->
                            <h2>Welcome to MySynctax</h2>
                            <p>Don't have an account?</p>
                            <a href="{{ url('/developer/register') }}" class="btn btn-white btn-outline-white">Sign Up</a>
                        </div>
                    </div>

                    <div class="login-wrap p-4 p-lg-5">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <strong>Error:</strong>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="" method="POST" class="signin-form" autocomplete="off" id="loginform">
                            @csrf
                            <div class="form-group mb-3">
                                <label class="label" for="username">Email</label>
                                <input type="text" name="email" class="form-control" value="{{ old('username') }}" placeholder="Email" required>
                            </div>

                            <div class="form-group mb-3">
                                <label class="label" for="password">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="form-control btn-primary">Sign In</button>
                            </div>

                            <div class="form-group d-md-flex">
                                <div class="w-50 text-left">
                                    <label class="checkbox-wrap checkbox-primary mb-0">Remember Me
                                        <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="w-50 text-md-right">
                                    <a href="">Forgot Password?</a>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script src="{{ asset('template/popper.js') }}"></script>
<script src="{{ asset('template/main.js') }}"></script>

@if (request()->get('demologin'))

@endif

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success_popup'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Account Created!',
        text: '{{ session("success_popup") }}',
        confirmButtonColor: '#1e293b',
        confirmButtonText: 'OK'
    });
</script>
@endif

</body>
</html>

<style>
.text-wrap {
    background: linear-gradient(to right, #62cff4, #2c67f2);
}
.btn-primary {
    background: linear-gradient(to right, #62cff4, #2c67f2);
    border: none;
    color: white;
}
</style>
