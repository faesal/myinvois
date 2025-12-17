@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4 fw-bold">{{ isset($customer) ? 'Edit' : 'Create' }} Customer</h4>

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
                    <button type="button" id="saveCustomerBtn" class="btn btn-success">Save Customer</button>
                </div>

            </div>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function popupSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: message,
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: '#22c55e',
        customClass: {
            popup: 'myAlertPopup',
            title: 'myAlertTitle',
            htmlContainer: 'myAlertText',
            confirmButton: 'myAlertButton',
            icon: 'myAlertIcon'
        }
    });
}
</script>

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
           success: (response) => {
    if (response.success) {
        popupSuccess(response.message);
        
        setTimeout(() => {
            window.location.href = "{{ url('/customer/listing_customer') }}";
        }, 1200);
    } else {
        popupError(response.message);
    }

    $('#saveCustomerBtn').prop('disabled', false).text('Save Customer');
},

error: (xhr) => {
    let msg = "Failed to save customer!";
    if (xhr.responseJSON && xhr.responseJSON.message) {
        msg = xhr.responseJSON.message;
    }

    popupError(msg);
    $('#saveCustomerBtn').prop('disabled', false).text('Save Customer');
}

        });
    });
});
</script>
@endsection

<style>
    /* Popup background + rounding */
    .myAlertPopup {
        border-radius: 20px !important;
        padding: 35px 30px !important;
        width: 450px !important;
    }

    /* Title styling */
    .myAlertTitle {
        font-size: 26px !important;
        font-weight: 700 !important;
        margin-top: 15px !important;
        margin-bottom: 10px !important;
        color: #3f3f3f !important;
    }

    /* Subtitle text */
    .myAlertText {
        font-size: 17px !important;
        color: #5f5f5f !important;
        margin-bottom: 25px !important;
    }

    /* Confirm button */
    .myAlertButton {
        background-color: #22c55e !important;
        border-radius: 8px !important;
        padding: 10px 30px !important;
        font-size: 16px !important;
        font-weight: 600 !important;
        box-shadow: 0 0 0 3px rgba(34,197,94,0.3) !important;
    }

    /* Fix SweetAlert icon to match your thin green circle */
    .myAlertIcon .swal2-success-ring {
        border-color: #b7e7c2 !important; /* lighter green ring */
        border-width: 4px !important;
    }

    .myAlertIcon .swal2-success-line-tip,
    .myAlertIcon .swal2-success-line-long {
        background-color: #22c55e !important;
    }
</style>

