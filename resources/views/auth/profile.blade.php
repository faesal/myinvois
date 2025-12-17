@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4 fw-bold">{{ isset($customer) ? 'Edit' : 'Create' }} Profile</h4>

    <form id="customerForm">
        @csrf
        @if(isset($customer))
            <input type="hidden" name="id_customer" value="{{ $customer->id_customer }}">
        @endif

        <div class="card">
            <div class="card-body row g-3">

                {{-- Registration Name --}}
                <div class="col-md-6">
                    <label class="form-label">Registration Name <span class="text-danger">*</span></label>
                    <input type="text" name="registration_name" class="form-control" required>
                </div>

                {{-- TIN Number --}}
                <div class="col-md-6">
                    <label class="form-label">TIN Number <span class="text-danger">*</span></label>
                    <input type="text" name="tin_no" class="form-control" required>
                </div>

                {{-- Identification No --}}
                <div class="col-md-6">
                    <label class="form-label">Identification No <span class="text-danger">*</span></label>
                    <input type="text" name="identification_no" class="form-control" required>
                </div>

                {{-- Identification Type --}}
                <div class="col-md-6">
                    <label class="form-label">Identification Type <span class="text-danger">*</span></label>
                    <select name="identification_type" class="form-select" required>
                        <option value="">Select Type</option>
                        <option value="NRIC">NRIC</option>
                        <option value="BRN">BRN</option>
                    </select>
                </div>

                {{-- SST Registration --}}
                <div class="col-md-6">
                    <label class="form-label">SST Registration</label>
                    <input type="text" name="sst_registration" class="form-control">
                </div>

                {{-- Phone --}}
                <div class="col-md-6">
                    <label class="form-label">Phone <span class="text-danger">*</span></label>
                    <input type="tel" name="phone" class="form-control" required pattern="^\d{8,}$">
                </div>

                {{-- Email --}}
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                {{-- Address Lines --}}
                <div class="col-md-12">
                    <label class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                    <input type="text" name="address_line_1" class="form-control" required>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Address Line 2</label>
                    <input type="text" name="address_line_2" class="form-control">
                </div>

                <div class="col-md-12">
                    <label class="form-label">Address Line 3</label>
                    <input type="text" name="address_line_3" class="form-control">
                </div>

                {{-- City & Postal --}}
                <div class="col-md-6">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <input type="text" name="city_name" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Postal Code <span class="text-danger">*</span></label>
                    <input type="text" name="postal_zone" class="form-control" required pattern="^\d{5}$">
                </div>

                {{-- State & Country --}}
                <div class="col-md-6">
                    <label class="form-label">State Code <span class="text-danger">*</span></label>
                    <select name="country_subentity_code" class="form-select" required>
                        <option value="">-- Select State --</option>
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
                        <option value="14">W.P. Kuala Lumpur</option>
                        <option value="15">W.P. Labuan</option>
                        <option value="16">W.P. Putrajaya</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Country Code</label>
                    <input type="text" name="country_code" class="form-control" value="MYS" readonly>
                </div>

                {{-- Submit --}}
                <div class="text-end mt-4">
                    <button type="button" id="saveCustomerBtn" class="btn btn-success">Save Profile</button>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
    // Autofill for edit
    @if(isset($customer))
        let data = @json($customer);
        for (const key in data) {
            $('[name="'+key+'"]').val(data[key]);
        }
    @endif

    $('#saveCustomerBtn').click(function() {
        const form = document.getElementById('customerForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        $.ajax({
            url: "{{ url('/customer/add_customer') }}",
            method: "POST",
            data: $('#customerForm').serialize(),
            beforeSend: () => {
                $('#saveCustomerBtn').prop('disabled', true).text('Saving...');
            },
            success: () => {
                alert("Customer saved!");
                window.location.href = "{{ url('/customer/listing_customer') }}";
            },
            error: (xhr) => {
                alert("Failed to save customer!");
                $('#saveCustomerBtn').prop('disabled', false).text('Save Customer');
            }
        });
    });
});
</script>
@endsection
