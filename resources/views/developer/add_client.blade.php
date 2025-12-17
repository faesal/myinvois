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

    <!-- ⭐ FIX: Wrap entire content in Bootstrap card ⭐ -->
    <div class="card p-4 shadow-sm mb-4">

        <h3 class="mb-4">Add New Client</h3>

        <form action="{{ route('developer.client.store') }}" method="POST">
            @csrf

            <!-- ============================================================
                CLIENT INFORMATION
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-building"></i>
                Client Information
            </div>

            <div class="row mb-3">

                <div class="col-md-6 mb-3">
                    <label class="form-label">Registration Name *</label>
                    <input type="text" name="registration_name" class="form-control"
                           value="{{ old('registration_name') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">TIN No *</label>
                    <input type="text" name="tin_no" class="form-control"
                           value="{{ old('tin_no') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Identification Type *</label>
                    <select name="identification_type" class="form-control" required>
                        <option value="">Please Choose</option>
                        <option value="IC"  {{ old('identification_type') == 'IC' ? 'selected' : '' }}>IC</option>
                        <option value="BRN" {{ old('identification_type') == 'BRN' ? 'selected' : '' }}>BRN</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Identification Number *</label>
                    <input type="text" name="identification_no" class="form-control"
                           value="{{ old('identification_no') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" class="form-control"
                           value="{{ old('phone') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control"
                           value="{{ old('email') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="city_name" class="form-control"
                           value="{{ old('city_name') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Postal Zone *</label>
                    <input type="text" name="postal_zone" class="form-control"
                           value="{{ old('postal_zone') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="country_subentity_code" class="form-label">State Code *</label>
                    <select name="country_subentity_code" id="country_subentity_code"
                            class="form-control rounded-md" required>
                        <option value="01" {{ old('country_subentity_code') == '01' ? 'selected' : '' }}>Johor</option>
                        <option value="02" {{ old('country_subentity_code') == '02' ? 'selected' : '' }}>Kedah</option>
                        <option value="03" {{ old('country_subentity_code') == '03' ? 'selected' : '' }}>Kelantan</option>
                        <option value="04" {{ old('country_subentity_code') == '04' ? 'selected' : '' }}>Melaka</option>
                        <option value="05" {{ old('country_subentity_code') == '05' ? 'selected' : '' }}>Negeri Sembilan</option>
                        <option value="06" {{ old('country_subentity_code') == '06' ? 'selected' : '' }}>Pahang</option>
                        <option value="07" {{ old('country_subentity_code') == '07' ? 'selected' : '' }}>Perak</option>
                        <option value="08" {{ old('country_subentity_code') == '08' ? 'selected' : '' }}>Perlis</option>
                        <option value="09" {{ old('country_subentity_code') == '09' ? 'selected' : '' }}>Pulau Pinang</option>
                        <option value="10" {{ old('country_subentity_code') == '10' ? 'selected' : '' }}>Sabah</option>
                        <option value="11" {{ old('country_subentity_code') == '11' ? 'selected' : '' }}>Sarawak</option>
                        <option value="12" {{ old('country_subentity_code') == '12' ? 'selected' : '' }}>Selangor</option>
                        <option value="13" {{ old('country_subentity_code') == '13' ? 'selected' : '' }}>Terengganu</option>
                        <option value="14" {{ old('country_subentity_code') == '14' ? 'selected' : '' }}>Wilayah Persekutuan Kuala Lumpur</option>
                        <option value="15" {{ old('country_subentity_code') == '15' ? 'selected' : '' }}>Wilayah Persekutuan Labuan</option>
                        <option value="16" {{ old('country_subentity_code') == '16' ? 'selected' : '' }}>Wilayah Persekutuan Putrajaya</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address_line_1" class="form-control"
                           value="{{ old('address_line_1') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 2 *</label>
                    <input type="text" name="address_line_2" class="form-control"
                           value="{{ old('address_line_2') }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 3</label>
                    <input type="text" name="address_line_3" class="form-control"
                           value="{{ old('address_line_3') }}">
                </div>

            </div>

            <div class="divider"></div>

            <!-- ============================================================
                LHDN CLIENT KEYS
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-key"></i>
                LHDN Client Keys
            </div>

            <div class="info-box mb-3">
                <i class="fa-solid fa-circle-info"></i>
                These keys are required for LHDN MyInvois authentication.
            </div>

            <div class="mb-3">
                <label class="form-label">Client Key 1</label>
                <input type="text" name="secret_key1" class="form-control"
                       value="{{ old('secret_key1') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Client Key 2</label>
                <input type="text" name="secret_key2" class="form-control"
                       value="{{ old('secret_key2') }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Client Key 3</label>
                <input type="text" name="secret_key3" class="form-control"
                       value="{{ old('secret_key3') }}">
            </div>

            <div class="divider"></div>

            <!-- ============================================================
                MYSYNCTAX DEVELOPER CREDENTIALS
            ============================================================ -->
            <div class="section-title">
                <i class="fa-solid fa-code"></i>
                MySyncTax Developer Credentials
            </div>

            <div class="warning-box mb-3">
                These credentials identify your integration with MySyncTax. Not editable.
            </div>

            <div class="row mb-3">

                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Key</label>
                    {{ $connection->mysynctax_key ?? '' }}
                   
                    <small class="text-muted">Auto-generated</small>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Secret</label>

                    {{ $connection->mysynctax_secret ?? '' }}
                   
                    <small class="text-muted">Auto-generated</small>
                </div>

            </div>

            <div class="security-box mb-4">
                <i class="fa-solid fa-shield-halved"></i>
                These credentials must not be shared publicly.
            </div>

            <!-- ============================================================
                ACTION BUTTONS
            ============================================================ -->
            <div class="d-flex justify-content-between mb-2">
                <a href="{{ route('developer.dashboard') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">Save</button>
            </div>

        </form>

    </div> <!-- END CARD -->

</div>

@endsection
