<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Verify Supplier - MySyncTax</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .app-container {
            max-width: 480px;
            margin: 0 auto;
            background: #fff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }

        .stepper-container {
            display: flex;
            justify-content: space-between;
            position: relative;
            padding: 0 10px;
        }
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 2;
            width: 60px;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #e9ecef;
            color: #adb5bd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .step-circle.active {
            background-color: #0F172A;
            color: #fff;
            border-color: #0F172A;
        }
        .step-label {
            font-size: 12px;
            color: #6c757d;
        }

        .info-banner {
            background-color: #F0F9FF;
            border-radius: 8px;
            padding: 12px 16px;
            display: flex;
            align-items: start;
            margin-bottom: 20px;
        }
        .info-icon {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-right: 12px;
            margin-top: -2px;
        }
        .info-text {
            color: #475569;
            font-size: 13px;
            line-height: 1.4;
        }

        .supplier-card {
            background-color: #F8F9FB;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }
        .supplier-icon {
            font-size: 24px;
            color: #64748B;
            margin-right: 15px;
        }
        .selection-label {
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            align-items: center;
            background: #fff;
            margin-bottom: 12px;
        }
        .selection-label:hover {
            border-color: #adb5bd;
            background-color: #f8f9fa;
        }
        .btn-check:checked + .selection-label {
            border-color: #0F172A;
            border-width: 2px;
            background-color: #fff;
        }
        .radio-circle {
            height: 20px;
            width: 20px;
            border: 2px solid #ccc;
            border-radius: 50%;
            margin-right: 15px;
            position: relative;
        }
        .btn-check:checked + .selection-label .radio-circle {
            border-color: #0F172A;
        }
        .btn-check:checked + .selection-label .radio-circle::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background-color: #0F172A;
            border-radius: 50%;
        }
        .form-control-lg-custom {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-size: 15px;
        }
        .tin-help-text {
            font-size: 13px;
            color: #64748B;
            margin-bottom: 8px;
            display: block;
        }

        .btn-continue {
            background-color: #E2E8F0;
            color: #475569;
            font-weight: 600;
            border: none;
            padding: 15px;
            border-radius: 8px;
            width: 100%;
        }
        .btn-continue:hover {
            background-color: #cbd5e1;
            color: #334155;
        }

        .part-header {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }
        .part-body {
            padding: 25px 20px;
            flex-grow: 1;
        }
        .part-footer {
            padding: 20px;
            border-top: 1px solid #e9ecef;
            background-color: #fff;
        }

        .form-card-step2 {
            background-color: #ffffff;
            border-radius: 0.75rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            padding: 2rem;
            margin-top: 2rem;
        }
    </style>
</head>
<body>

@if(!request()->isMethod('post'))
<form action="{{ url('/checkTinNo') }}" method="POST" id="tinCheckForm" class="h-100">
    {{ csrf_field() }}
    
    <div class="app-container" id="step1-container">
        
        <div class="part-header">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Verify TIN</h4>
                <span class="badge bg-light text-dark border">Step 1 of 3</span>
            </div>

            <div class="stepper-container">
                <div style="position: absolute; top: 20px; left: 40px; right: 40px; height: 2px; background: #e9ecef; z-index: 1;"></div>
                
                <div class="step-item">
                    <div class="step-circle active">1</div>
                    <span class="step-label">Verify</span>
                </div>
                <div class="step-item">
                    <div class="step-circle">2</div>
                    <span class="step-label">Details</span>
                </div>
                <div class="step-item">
                    <div class="step-circle">3</div>
                    <span class="step-label">Confirm</span>
                </div>
            </div>
        </div>
        <div class="part-body">
            @if(isset($invoice_unique_id))
                <input type="hidden" name="invoice_unique_id" value="{{ $invoice_unique_id }}">
            @endif

            <div class="info-banner">
                <i class="bi bi-question-circle info-icon"></i>
                <div class="info-text">
                    Your information will be used to generate a valid e-Invoice submission to LHDN.
                </div>
            </div>

            <div class="supplier-card">
                <div class="d-flex align-items-start mb-3">
                    <i class="bi bi-building supplier-icon"></i>
                    <div>
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 11px;">SUPPLIER NAME</small>
                        <h6 class="fw-bold mb-0">
                            {{ $supplier->registration_name ?? 'Supplier Not Found' }}
                        </h6>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-text" style="font-size: 18px; color: #64748B; margin-left: 3px;"></i>
                    <span class="text-muted ms-2" style="font-size: 14px;">
                        Invoice Ref: {{ $invoice->invoice_no ?? 'N/A' }}
                    </span>
                </div>
            </div>

            <h6 class="mb-3 text-secondary">Who is this invoice for?</h6>
            
            <input type="radio" class="btn-check" name="customer_type" id="type_personal" value="personal" checked>
            <label class="selection-label" for="type_personal">
                <div class="radio-circle"></div>
                <span class="fw-medium">Personal Individual</span>
            </label>

            <input type="radio" class="btn-check" name="customer_type" id="type_business" value="business">
            <label class="selection-label" for="type_business">
                <div class="radio-circle"></div>
                <span class="fw-medium">Business / Company</span>
            </label>
            
            <input type="radio" class="btn-check" name="customer_type" id="type_local" value="local">
            <label class="selection-label" for="type_local">
                <div class="radio-circle"></div>
                <span class="fw-medium">Local (Without TIN No)</span>
            </label>

            <input type="radio" class="btn-check" name="customer_type" id="type_foreigner" value="foreigner">
            <label class="selection-label" for="type_foreigner">
                <div class="radio-circle"></div>
                <span class="fw-medium">Foreigner (Without TIN No)</span>
            </label>

            <div class="mt-4">
                <label class="form-label text-secondary mb-1 fw-bold">Enter TIN Number</label>
                <small class="tin-help-text">You may obtain your TIN from LHDN MyTax portal.</small>
                
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-card-heading"></i></span>
                    <input type="text" id="tin_input" name="tin_no_check" class="form-control form-control-lg-custom border-start-0" placeholder="[ e.g. A123456789 ]" required>
                </div>
            </div>
        </div>
        <div class="part-footer">
            <button type="submit" class="btn btn-continue">
                Continue
            </button>
        </div>
    </div>
</form>
@endif

@if(request()->isMethod('post'))
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand text-primary fw-bold" href="#">MySyncTax</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="form-card-step2 mx-auto" style="max-width: 900px;">
        <h4 class="mb-4 text-primary">Step 2: Customer Details</h4>
        <form action="{{ url('/storecustomer') }}" method="POST">
            {{ csrf_field() }}
            <input type="hidden" name="tin_no" value="{{ request('tin_no_check') }}">
            <input type="hidden" name="customer_type" value="{{ request('customer_type') }}">
            @if(isset($invoice_unique_id))
                <input type="hidden" name="invoice_unique_id" value="{{ $invoice_unique_id }}">
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="registration_name" class="form-label">Registration Name <span class="text-danger">*</span></label>
                    <input type="text" name="registration_name" id="registration_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tin_no_display" class="form-label">TIN No <span class="text-danger">*</span></label>
                    <input type="text" id="tin_no_display" class="form-control" value="{{ request('tin_no_check') }}" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="identification_type" class="form-label">Business ID Type <span class="text-danger">*</span></label>
                    <select name="identification_type" id="identification_type" class="form-control" required>
                        @if(request('customer_type') == 'foreigner')
                            <option value="PASSPORT" selected>Passport</option>
                        @elseif(request('customer_type') == 'business')
                            <option value="BRN" selected>Business Registration No</option>
                        @elseif(request('customer_type') == 'personal')
                            <option value="">Please Choose</option>    
                            <option value="NRIC">NRIC (IC)</option>
                            <option value="ARMY">Army ID</option>
                            <option value="PASSPORT">Passport</option>
                        @elseif(request('customer_type') == 'local')
                            <option value="">Please Choose</option>    
                            <option value="BRN">Business Registration No</option>
                            <option value="NRIC">NRIC (IC)</option>
                            <option value="ARMY">Army ID</option>
                        @else
                            <option value="">Please Choose</option>    
                            <option value="NRIC">NRIC (IC)</option>
                            <option value="BRN">Business Registration No</option>
                            <option value="ARMY">Army ID</option>
                            <option value="PASSPORT">Passport</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="identification_no" class="form-label">ID Number <span class="text-danger">*</span></label>
                    <input type="text" name="identification_no" id="identification_no" class="form-control" required>
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
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-12 mt-3 mb-2"><h6 class="text-muted border-bottom pb-2">Address Details</h6></div>
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
                <div class="col-md-6 mb-3">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" name="city_name" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Postal Zone <span class="text-danger">*</span></label>
                    <input type="text" name="postal_zone" class="form-control" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">State <span class="text-danger">*</span></label>
                    <select name="country_subentity_code" class="form-control" required>
                        <option value="12">Selangor</option>
                        <option value="14">W.P. Kuala Lumpur</option>
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
                        <option value="13">Terengganu</option>
                        <option value="15">W.P. Labuan</option>
                        <option value="16">W.P. Putrajaya</option>
                    </select>
                </div>
            </div>
            <div class="text-end mt-4">
                @if(!isset($customer)) 
                    <button type="submit" class="btn btn-success px-5 py-2 fw-bold">Register Customer</button>
                @else
                    <a href="{{ url('/presubmit/') }}/{{$customer->id_customer}}" class="btn btn-success px-5 py-2">Next Step</a>
                @endif
            </div>
        </form>
    </div>
</div>
@endif

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        
        // --- 1. Auto-fill and Lock TIN logic based on 4 Customer Types ---
        @if(!request()->isMethod('post'))
            $('input[name="customer_type"]').on('change', function() {
                var selectedType = $(this).val();
                var tinInput = $('#tin_input');

                if (selectedType === 'local') {
                    tinInput.val('EI00000000010').prop('readonly', true);
                } else if (selectedType === 'foreigner') {
                    tinInput.val('EI00000000020').prop('readonly', true);
                } else {
                    tinInput.val('').prop('readonly', false);
                }
            });

            // Trigger change on page load to format default selection correctly
            $('input[name="customer_type"]:checked').trigger('change');
            
            // Enforce Alphanumeric on TIN Input (Step 1)
            $('#tin_input').on('input', function() {
                var node = $(this);
                node.val(node.val().replace(/[^a-zA-Z0-9]/g, ''));
            });
        @endif

        // --- 2. Auto Select & Lock ID Type (Step 2) ---
        @if(request()->isMethod('post'))
            var customerType = "{{ request('customer_type') }}";
            
            if(customerType === 'business') {
                $('#identification_type').val('BRN');
                $('#identification_type').css({'pointer-events': 'none', 'background-color': '#e9ecef'}).attr('tabindex', '-1');
            } else if (customerType === 'foreigner') {
                $('#identification_type').val('PASSPORT');
                $('#identification_type').css({'pointer-events': 'none', 'background-color': '#e9ecef'}).attr('tabindex', '-1');
            }
        @endif

        // --- 3. Pre-fill Data (If Customer Exists) ---
        @if(isset($customer))
            $('input[name="registration_name"]').val("{{ $customer->registration_name }}").prop('readonly', true);
            $('input[name="identification_no"]').val("{{ $customer->identification_no }}").prop('readonly', true);
            $('select[name="identification_type"]').val("{{ $customer->identification_type }}").prop('disabled', true);
            $('input[name="sst_registration"]').val("{{ $customer->sst_registration }}").prop('readonly', true);
            $('input[name="phone"]').val("{{ $customer->phone }}").prop('readonly', true);
            $('input[name="email"]').val("{{ $customer->email }}").prop('readonly', true);
            $('input[name="city_name"]').val("{{ $customer->city_name }}").prop('readonly', true);
            $('input[name="postal_zone"]').val("{{ $customer->postal_zone }}").prop('readonly', true);
            $('select[name="country_subentity_code"]').val("{{ $customer->country_subentity_code }}").prop('disabled', true);
            $('input[name="address_line_1"]').val("{{ $customer->address_line_1 }}").prop('readonly', true);
            $('input[name="address_line_2"]').val("{{ $customer->address_line_2 }}").prop('readonly', true);
            $('input[name="address_line_3"]').val("{{ $customer->address_line_3 }}").prop('readonly', true);
            $('#tin_no_display').val("{{ $customer->tin_no }}");
        @endif
    });
</script>

</body>
</html>