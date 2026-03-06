@extends($layout)

@section('title', 'Add New Customer')

@section('content')
{{-- Load Phosphor Icons via CDN --}}
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        {{-- Form Header --}}
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center">
                <i class="ph ph-user-circle-plus me-2 fs-2 text-primary"></i>
                <div>
                    <h5 class="fw-bold mb-0">Customer Information Form</h5>
                    <p class="text-muted small mb-0">Please fill in all required fields marked with <span class="text-danger">*</span></p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('manage_customer.store') }}" method="POST">
                @csrf

                {{-- 
                    CRITICAL HIDDEN FIELDS:
                    1. connection_integrate: Links the new customer to the correct supplier's LHDN code.
                    2. lhdn_cust_id: Retains the unique supplier selection for the redirect after saving.
                --}}
                <input type="hidden" name="connection_integrate" value="{{ $selectedLhdnCode }}">
                <input type="hidden" name="lhdn_cust_id" value="{{ $lhdnCustId }}">

                {{-- Section 1: Basic Information --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph ph-info me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Basic Information</h6>
                    </div>

                    {{-- Context Alert --}}
                    @if($selectedLhdnCode)
                        <div class="alert alert-info border-0 shadow-sm small d-flex align-items-center mb-4">
                            <i class="ph ph-link-simple me-2"></i>
                            This customer will be automatically linked to: <strong>{{ strtoupper($selectedLhdnCode) }}</strong>
                        </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Customer ID <span class="text-danger">*</span></label>
                            <input type="text" name="customer_id" class="form-control" placeholder="Enter customer ID" required value="{{ old('customer_id') }}">
                            <div class="form-text mt-1 small">Unique identifier for the customer</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Registration Name <span class="text-danger">*</span></label>
                            <input type="text" name="registration_name" class="form-control" placeholder="Legal business name" required value="{{ old('registration_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Unique ID</label>
                            <input type="text" name="unique_id" class="form-control bg-light" placeholder="System-generated unique ID" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">TIN No. <span class="text-danger">*</span></label>
                            <input type="text" name="tin_no" class="form-control" placeholder="Tax Identification Number" required value="{{ old('tin_no') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="Enter phone number" required value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="customer@example.com" required value="{{ old('email') }}">
                            <div class="form-text mt-1 small">Must be a valid email address</div>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Identification Details --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph ph-identification-card me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Identification Details</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Identification Type <span class="text-danger">*</span></label>
                            <select name="identification_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="NRIC" {{ old('identification_type') == 'NRIC' ? 'selected' : '' }}>NRIC</option>
                                <option value="BRN" {{ old('identification_type') == 'BRN' ? 'selected' : '' }}>BRN</option>
                                <option value="PASSPORT" {{ old('identification_type') == 'PASSPORT' ? 'selected' : '' }}>PASSPORT</option>
                                <option value="ARMY" {{ old('identification_type') == 'ARMY' ? 'selected' : '' }}>ARMY</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Identification No. <span class="text-danger">*</span></label>
                            <input type="text" name="identification_no" class="form-control" placeholder="Enter identification number" required value="{{ old('identification_no') }}">
                        </div>
                    </div>
                </div>

                {{-- Section 3: Business Details --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph ph-briefcase me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Business Details</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Business Description <span class="text-danger">*</span></label>
                            <textarea name="business_description" class="form-control" rows="3" placeholder="Enter business description" required>{{ old('business_description') }}</textarea>
                            <div class="form-text mt-1 small">Maximum 500 characters</div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2 pt-1">
                                <input class="form-check-input" type="checkbox" name="is_selfbill_supplier" value="1" id="selfBillSwitch" {{ old('is_selfbill_supplier') ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="selfBillSwitch">Self-bill Supplier</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">SST Registration No.</label>
                            <input type="text" name="sst_registration" class="form-control" placeholder="e.g. W10-1234-5678" value="{{ old('sst_registration') }}">
                            <div class="form-text mt-1 small">Leave blank if not applicable</div>
                        </div>
                    </div>
                </div>

                {{-- Section 4: Address Information --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph ph-map-pin me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Address Information</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" name="address_line_1" class="form-control" placeholder="Building/Floor/House No." required value="{{ old('address_line_1') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 2</label>
                            <input type="text" name="address_line_2" class="form-control" placeholder="Street/Area Name" value="{{ old('address_line_2') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 3</label>
                            <input type="text" name="address_line_3" class="form-control" placeholder="Additional details" value="{{ old('address_line_3') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city_name" class="form-control" placeholder="City name" required value="{{ old('city_name') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Postal Zone</label>
                            <input type="text" name="postal_zone" class="form-control" placeholder="ZIP/Postal code" value="{{ old('postal_zone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Country <span class="text-danger">*</span></label>
                            <select name="country_code" class="form-select" required>
                                <option value="MYS" selected>Malaysia</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">State/Province</label>
                            <select name="country_subentity_code" class="form-select">
                                <option value="">Select State</option>
                                <option value="01">Johor</option>
                                <option value="02">Kedah</option>
                                <option value="03">Kelantan</option>
                                <option value="04">Melaka</option>
                                <option value="05">Negeri Sembilan</option>
                                <option value="06">Pahang</option>
                                <option value="07">Penang</option>
                                <option value="08">Perak</option>
                                <option value="09">Perlis</option>
                                <option value="10">Selangor</option>
                                <option value="11">Terengganu</option>
                                <option value="12">Sabah</option>
                                <option value="13">Sarawak</option>
                                <option value="14">W.P. Kuala Lumpur</option>
                                <option value="15">W.P. Labuan</option>
                                <option value="16">W.P. Putrajaya</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Form Footer --}}
                <div class="card-footer bg-light border-top d-flex justify-content-between align-items-center py-3">
                    <span class="text-muted small">* Required fields must be completed</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('manage_customer.index', ['lhdn_cust_id' => $lhdnCustId]) }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Submit Form</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection