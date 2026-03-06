@extends($layout)

@section('title', 'Edit Customer')

@section('content')
<div class="container-fluid py-4">
    <div class="card border-0 shadow-sm">
        {{-- Form Header --}}
        <div class="card-header bg-white border-bottom py-3">
            <div class="d-flex align-items-center">
                <i class="ph-pencil-line me-2 fs-2 text-primary"></i>
                <div>
                    <h5 class="fw-bold mb-0">Edit Customer: {{ $customer->registration_name }}</h5>
                    <p class="text-muted small mb-0">Update information for this customer record</p>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <form action="{{ route('manage_customer.update', $customer->id_customer) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Section 1: Basic Information --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-info me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Basic Information</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Registration Name <span class="text-danger">*</span></label>
                            <input type="text" name="registration_name" class="form-control" value="{{ $customer->registration_name }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">TIN No. <span class="text-danger">*</span></label>
                            <input type="text" name="tin_no" class="form-control" value="{{ $customer->tin_no }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="{{ $customer->phone }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ $customer->email }}" required>
                        </div>
                    </div>
                </div>

                {{-- Section 2: Identification Details --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-identification-card me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Identification Details</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Identification Type <span class="text-danger">*</span></label>
                            <select name="identification_type" class="form-select" required>
                                <option value="NRIC" {{ $customer->identification_type == 'NRIC' ? 'selected' : '' }}>NRIC</option>
                                <option value="BRN" {{ $customer->identification_type == 'BRN' ? 'selected' : '' }}>BRN</option>
                                <option value="PASSPORT" {{ $customer->identification_type == 'PASSPORT' ? 'selected' : '' }}>PASSPORT</option>
                                <option value="ARMY" {{ $customer->identification_type == 'ARMY' ? 'selected' : '' }}>ARMY</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Identification No. <span class="text-danger">*</span></label>
                            <input type="text" name="identification_no" class="form-control" value="{{ $customer->identification_no }}" required>
                        </div>
                    </div>
                </div>

                {{-- Section 3: Business Details --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-briefcase me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Business Details</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Business Description <span class="text-danger">*</span></label>
                            <textarea name="business_description" class="form-control" rows="3" required>{{ $customer->business_description }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_selfbill_supplier" value="1" id="selfBillSwitch" {{ $customer->is_selfbill_supplier == 1 ? 'checked' : '' }}>
                                <label class="form-check-label fw-medium" for="selfBillSwitch">Self-bill Supplier</label>
                            </div>
                        </div>
                        {{-- UPDATED: SST Registration is now a text input --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">SST Registration No.</label>
                            <input type="text" name="sst_registration" class="form-control" value="{{ $customer->sst_registration }}" placeholder="Enter SST Number (if applicable)">
                        </div>
                    </div>
                </div>

                {{-- Section 4: Address Information --}}
                <div class="mb-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-map-pin me-2 text-muted"></i>
                        <h6 class="fw-bold mb-0 text-uppercase tracking-wide">Address Information</h6>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" name="address_line_1" class="form-control" value="{{ $customer->address_line_1 }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 2</label>
                            <input type="text" name="address_line_2" class="form-control" value="{{ $customer->address_line_2 }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Address Line 3</label>
                            <input type="text" name="address_line_3" class="form-control" value="{{ $customer->address_line_3 }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" name="city_name" class="form-control" value="{{ $customer->city_name }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Postal Zone</label>
                            <input type="text" name="postal_zone" class="form-control" value="{{ $customer->postal_zone }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Country</label>
                            <select name="country_code" class="form-select" required>
                                <option value="MYS" selected>Malaysia</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">State/Province</label>
                            <select name="country_subentity_code" class="form-select">
                                <option value="">Select State</option>
                                <option value="01" {{ $customer->country_subentity_code == '01' ? 'selected' : '' }}>Johor</option>
                                <option value="02" {{ $customer->country_subentity_code == '02' ? 'selected' : '' }}>Kedah</option>
                                <option value="03" {{ $customer->country_subentity_code == '03' ? 'selected' : '' }}>Kelantan</option>
                                <option value="04" {{ $customer->country_subentity_code == '04' ? 'selected' : '' }}>Melaka</option>
                                <option value="05" {{ $customer->country_subentity_code == '05' ? 'selected' : '' }}>Negeri Sembilan</option>
                                <option value="06" {{ $customer->country_subentity_code == '06' ? 'selected' : '' }}>Pahang</option>
                                <option value="07" {{ $customer->country_subentity_code == '07' ? 'selected' : '' }}>Penang</option>
                                <option value="08" {{ $customer->country_subentity_code == '08' ? 'selected' : '' }}>Perak</option>
                                <option value="09" {{ $customer->country_subentity_code == '09' ? 'selected' : '' }}>Perlis</option>
                                <option value="10" {{ $customer->country_subentity_code == '10' ? 'selected' : '' }}>Selangor</option>
                                <option value="11" {{ $customer->country_subentity_code == '11' ? 'selected' : '' }}>Terengganu</option>
                                <option value="12" {{ $customer->country_subentity_code == '12' ? 'selected' : '' }}>Sabah</option>
                                <option value="13" {{ $customer->country_subentity_code == '13' ? 'selected' : '' }}>Sarawak</option>
                                <option value="14" {{ $customer->country_subentity_code == '14' ? 'selected' : '' }}>W.P. Kuala Lumpur</option>
                                <option value="15" {{ $customer->country_subentity_code == '15' ? 'selected' : '' }}>W.P. Labuan</option>
                                <option value="16" {{ $customer->country_subentity_code == '16' ? 'selected' : '' }}>W.P. Putrajaya</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Form Footer --}}
                <div class="card-footer bg-light border-top d-flex justify-content-between align-items-center py-3">
                    <span class="text-muted small">* Required fields must be completed</span>
                    <div class="d-flex gap-2">
                        <a href="{{ route('manage_customer.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4 shadow-sm">Update Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection