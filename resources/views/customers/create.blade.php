<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Customer Registration - TIN Check</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa; /* Light background */
        }
        .navbar-brand {
            font-weight: 700;
            color: #0d6efd !important; /* Bootstrap primary blue */
        }
        .form-card {
            background-color: #ffffff; /* White background for the card */
            border-radius: 0.75rem; /* Rounded corners */
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); /* Soft shadow */
            padding: 2rem; /* Internal padding */
            margin-top: 3rem; /* Space from the header */
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            border-radius: 0.5rem; /* Slightly rounded buttons */
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
        }
        .form-control {
            border-radius: 0.5rem; /* Rounded input fields */
            padding: 0.75rem 1rem;
        }
        h5.text-primary {
            color: #0d6efd !important;
            font-weight: 600;
        }
        /* Custom styles for centering the button */
        .form-button-container {
            display: flex;
            justify-content: center;
            width: 100%; /* Ensure it takes full width of its parent */
            margin-top: 1rem; /* Space between input and button */
        }
        /* Media query for smaller screens to adjust button width */
        @media (max-width: 575.98px) {
            .form-card {
                padding: 1.5rem; /* Reduce padding on smaller screens */
                margin-top: 1.5rem;
            }
            .form-button-container .btn {
                width: 100% !important; /* Ensure button is full width on very small screens */
            }
        }
    </style>
</head>
<body class="bg-light p-4">

 <!-- Bootstrap Navbar (Header) -->
 <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">MySyncTax</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <!-- You can add navigation links here if needed -->
                    <!-- <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li> -->
                </ul>
            </div>
        </div>
    </nav>


@if(!request()->isMethod('post') )
<div class="container">
    <div class="form-card mx-auto mb-4" style="max-width: 600px;" id="tin-check-form">
   
        <h5 class="mb-3 text-primary">Step 1: Enter TIN Number</h5>
        <form action="{{ url('/checkTinNo') }}" method="POST" class="row g-3" id="tinCheckForm">
            {{ csrf_field() }}
            <div class="col-12">
                <input type="text" name="tin_no_check" class="form-control" placeholder="Enter TIN No" required>
            </div><BR>
            <CENTER>
                <button type="submit" class="btn btn-primary w-100">Submit</button>
            </CENTER>
        </form>
    </div>
</div>
@endif
<script>
    $(document).ready(function() {
        $('.form-card').hide();
        @if(request()->isMethod('post') )
        $('#tin-check-form').hide();
        @endif
        $('#tinCheckForm').on('submit', function(e) {
            e.preventDefault(); // Prevent form submission

            const tinNo = $('input[name="tin_no"]').val();
            if (tinNo) {
                // Simulate TIN check and show registration form
                $('#tin-check-form').hide(); // Hide TIN check form
                $('.form-card').show(); // Show registration form
            }
        });

       
        // Initially hide registration form
       
    });
</script>

  
    
<br>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    $(document).ready(function() {
        // Check if customer data exists

        @if(isset($tin_no))

        $('input[name="tin_no"]').prop('readonly', true);
        $('input[name="tin_no"]').val("{{ $tin_no }}");
        @endif
        
        @if(isset($customer))
            // Populate form fields with customer data
            $('input[name="registration_name"]').prop('readonly', true);
            $('input[name="identification_no"]').prop('readonly', true);
            $('select[name="identification_type"]').prop('disabled', true);
            $('input[name="sst_registration"]').prop('readonly', true);
            $('input[name="phone"]').prop('readonly', true);
            $('input[name="tin_no"]').prop('readonly', true);
            $('input[name="email"]').prop('readonly', true);
            $('input[name="city_name"]').prop('readonly', true);
            $('input[name="postal_zone"]').prop('readonly', true);
            $('select[name="country_subentity_code"]').prop('disabled', true);
            $('input[name="address_line_1"]').prop('readonly', true);
            $('input[name="address_line_2"]').prop('readonly', true);
            $('input[name="address_line_3"]').prop('readonly', true);
            $('input[name="registration_name"]').val("{{ $customer->registration_name }}");
            $('input[name="tin_no"]').val("{{ $customer->tin_no }}");
            $('input[name="identification_no"]').val("{{ $customer->identification_no }}");
            $('select[name="identification_type"]').val("{{ $customer->identification_type }}");
            $('input[name="sst_registration"]').val("{{ $customer->sst_registration }}");
            $('input[name="phone"]').val("{{ $customer->phone }}");
            $('input[name="email"]').val("{{ $customer->email }}");
            $('input[name="city_name"]').val("{{ $customer->city_name }}");
            $('input[name="postal_zone"]').val("{{ $customer->postal_zone }}");
            $('select[name="country_subentity_code"]').val("{{ $customer->country_subentity_code }}");
            $('input[name="address_line_1"]').val("{{ $customer->address_line_1 }}");
            $('input[name="address_line_2"]').val("{{ $customer->address_line_2 }}");
            $('input[name="address_line_3"]').val("{{ $customer->address_line_3 }}");
        @endif

        
    });
</script>
@if(request()->isMethod('post'))
    <!-- If TIN not found -->
    <div class="form-card mx-auto" style="max-width: 900px;">
     
      <form action="{{ url('/storecustomer') }}" method="POST">
        
        {{ csrf_field() }}

        <input type="hidden" name="tin_no" value="{{ request('tin_no') }}">

        <div class="row">
         
        <div class="col-md-6 mb-3">
                                <label for="registration_name" class="form-label">Registration Name <span class="text-danger">*</span></label>
                                <input type="text" name="registration_name" id="registration_name" class="form-control rounded-md" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tin_no_field" class="form-label">TIN No <span class="text-danger">*</span></label>
                                <input type="text" name="tin_no" id="tin_no" class="form-control rounded-md" required>
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
            <label class="form-label">SST Registration <span class="text-danger">*</span></label>
            <input type="text" name="sst_registration" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Phone <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" required>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$" title="Please enter a valid email address">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">City <span class="text-danger">*</span></label>
            <input type="text" name="city_name" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Postal Zone <span class="text-danger">*</span></label>
            <input type="text" name="postal_zone" class="form-control" required>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">State Code <span class="text-danger">*</span></label>
            
        <select name="country_subentity_code" class="form-control w-auto" required>
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
            <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
            <input type="text" name="address_line_1" class="form-control" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Address Line 2 <span class="text-danger">*</span></label>
            <input type="text" name="address_line_2" class="form-control" required>
          </div>
          <div class="col-12 mb-3">
            <label class="form-label">Address Line 3</label>
            <input type="text" name="address_line_3" class="form-control">
          </div>
        </div>

        <div class="text-end">
        @if(!isset($customer)) 
          <button type="submit" class="btn btn-success px-4">Register Customer</button>
        @else
        <a href="{{ url('/presubmit/') }}/{{$customer->id_customer}}" class="btn btn-success px-4">Next Step</a>
        @endif
        </div>
      </form>
    </div>

  </div>
@endif
</body>
</html>
