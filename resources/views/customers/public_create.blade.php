<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySyncTax Customer Registration</title>
    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Custom CSS -->
    <style>
        body {
            font-family: 'Inter', sans-serif; /* Using Inter font */
            padding-top: 56px; /* Space for fixed navbar */
            background-color: #f8f9fa; /* Light grey background */
        }
        .navbar {
            border-radius: 0 0 10px 10px; /* Rounded bottom corners for navbar */
            box-shadow: 0 2px 4px rgba(0,0,0,.1); /* Subtle shadow for navbar */
        }
        .container {
            margin-top: 20px;
            padding: 20px;
            background-color: #ffffff; /* White background for content area */
            border-radius: 10px; /* Rounded corners for content container */
            box-shadow: 0 0 15px rgba(0,0,0,.05); /* Soft shadow for content */
        }
        .form-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            border-radius: 8px; /* Slightly more rounded buttons */
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .btn-success {
            background-color: #198754;
            border-color: #198754;
            border-radius: 8px;
        }
        .btn-success:hover {
            background-color: #157347;
            border-color: #146c43;
        }
        .modal-content {
            border-radius: 1rem; /* Rounded corners for modal */
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">MySyncTax</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
           <!-- <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
            </div>
    -->
        </div>
    </nav>

    <!-- Main Content Container -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="form-card">
                    <h2 class="text-center mb-4">Customer Registration</h2>
                    <form action="{{ url('/public_store') }}" method="POST">
                        {{-- Laravel CSRF token for security --}}
                        {{ csrf_field() }}

                        {{-- Hidden input for TIN if coming from another page --}}
                        <input type="hidden" name="tin_no" value="{{ request('tin_no') }}">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="registration_name" class="form-label">Registration Name <span class="text-danger">*</span></label>
                                <input type="text" name="registration_name" id="registration_name" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tin_no_field" class="form-label">TIN No <span class="text-danger">*</span></label>
                                <input type="text" name="tin_no" id="tin_no_field" class="form-control rounded-md" required>
                            </div>
                                <div class="col-md-6 mb-3">
                                <label for="identification_type" class="form-label">Business Identification Type (Choose IC. For Enterprise) <span class="text-danger">*</span></label>
                                <select name="identification_type" id="identification_type" class="form-control rounded-md" required>
                                <option value="">Please Choose</option>    
                                <option value="NRIC">IC</option>
                                    <option value="BRN">Business Registration</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="identification_no" class="form-label">Business Identification No (IC. No. For Enterprise)<span class="text-danger">*</span></label>
                                <input type="text" name="identification_no" id="identification_no" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sst_registration" class="form-label">SST Registration <span class="text-danger">*</span></label>
                                <input type="text" name="sst_registration" id="sst_registration" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="phone" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="email" class="form-control rounded-md" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="city_name" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" name="city_name" id="city_name" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="postal_zone" class="form-label">Postal Zone <span class="text-danger">*</span></label>
                                <input type="text" name="postal_zone" id="postal_zone" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="country_subentity_code" class="form-label">State Code <span class="text-danger">*</span></label>
                                <select name="country_subentity_code" id="country_subentity_code" class="form-control rounded-md" required>
                                    <option value="01">Johor</option>
                                    <option value="02">Kedah</option>
                                    <option value="03">Kelantan</option>
                                    <option value="04">Melaka</option>
                                    <option value="05">Negeri Sembilan</option>
                                    <option value="06">Pahang</option>
                                    <option value="07">Perak</option>
                                    <option value="08">Perlis</option>
                                    <option value="09">Pulau Pinang</option>
                                    <option value="10">Sabah</option>
                                    <option value="11">Sarawak</option>
                                    <option value="12">Selangor</option>
                                    <option value="13">Terengganu</option>
                                    <option value="14">Wilayah Persekutuan Kuala Lumpur</option>
                                    <option value="15">Wilayah Persekutuan Labuan</option>
                                    <option value="16">Wilayah Persekutuan Putrajaya</option>
                                </select>
                            </div>
                           
                            <div class="col-12 mb-3">
                                <label for="address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                                <input type="text" name="address_line_1" id="address_line_1" class="form-control rounded-md" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="address_line_2" class="form-label">Address Line 2 <span class="text-danger">*</span></label>
                                <input type="text" name="address_line_2" id="address_line_2" class="form-control rounded-md" required>
                            </div>
                            <div class="col-12 mb-3">
                                <label for="address_line_3" class="form-label">Address Line 3</label>
                                <input type="text" name="address_line_3" id="address_line_3" class="form-control rounded-md">
                            </div>
                        </div>

                        {{-- New dropdown for subscription duration --}}
                            <div class="col-md-4 mb-3">
                                <label for="subscribe_for" class="form-label">Subscribe <span class="text-danger">*</span></label>
                                <select name="subscribe_for" id="subscribe_for" class="form-control rounded-md" required>
                                    <option value="">Select Duration</option>
                                    <option value="3_month">3 Month</option>
                                    <option value="6_month">6 Month</option>
                                    <option value="12_month">12 Month</option>
                                </select>
                            </div>

                        <div class="text-end">
                            <button type="button" id="registerBtn" class="btn btn-success px-4 rounded-md">Register Customer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <!-- Response Modal -->
    <div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="responseModalLabel">Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Dynamic content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-md" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
    <!-- Bootstrap Bundle with Popper (JS for dropdowns, toggles, modals etc.) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eD/beNVyZrt7F7A" crossorigin="anonymous"></script>

    <script>
        $(document).ready(function () {
            $('#registerBtn').click(function () {
                var form = $('form');
                
                $.ajax({
                    url: form.attr('action'),
                    method: form.attr('method'),
                    data: form.serialize(),
                    success: function (response) {
                        $('#responseModal .modal-body').text('Customer registered successfully!');
                        $('#responseModal').modal('show');
                        form[0].reset(); // Optional: Reset the form
                    },
                    error: function (xhr) {
                        $('#responseModal .modal-body').text('Error: ' + (xhr.responseText || 'Something went wrong.'));
                        $('#responseModal').modal('show');
                    }
                });
            });
        });
    </script>
</body>
</html>

