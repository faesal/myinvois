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



    <!-- ⭐ ADDED A PROPER CARD PANEL HERE ⭐ -->

    <div class="card p-4 shadow-sm mb-4">



        <h3 class="mb-4">Edit Account</h3>



        <form action="{{ route('developer.companies.update', $company->id_customer) }}" method="POST">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif


            @csrf

            @method('POST')



            <!-- ============================================================

                COMPANY INFORMATION 

            ============================================================ -->

            <div class="section-title">

                <i class="fa-solid fa-building"></i>

                LHDN Account Information

            </div>



            <div class="row mb-3">



                <div class="col-md-6 mb-3">

                    <label class="form-label">Registration Name *</label>

                    <input type="text" name="registration_name" class="form-control" value="{{ old('registration_name', $company->registration_name) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">TIN No *</label>

                    <input type="text" name="tin_no" class="form-control" value="{{ old('tin_no', $company->tin_no) }}" required>

                </div>



                <div class="col-md-6 mb-3">
                    <label class="form-label">Identification Type *</label>

                    <!-- Visible (readonly) -->
                    <input type="text"
                        class="form-control bg-light text-secondary"
                        value="{{ $company->identification_type }}"
                        readonly>

                    <!-- Hidden (actual submitted value) -->
                    <input type="hidden"
                        name="identification_type"
                        value="{{ $company->identification_type }}">
                </div>




                <div class="col-md-6 mb-3">

                    <label class="form-label">Identification Number *</label>

                    <input type="text" name="identification_no" class="form-control" value="{{ old('identification_no', $company->identification_no) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Phone *</label>

                    <input type="text" name="phone"
                        class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $company->phone) }}" required>

                    @error('phone')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror


                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Email *</label>

                    <input type="email" name="email" class="form-control" value="{{ old('email', $company->email) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">City *</label>

                    <input type="text" name="city_name" class="form-control" value="{{ old('city_name', $company->city_name) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Postal Zone *</label>

                    <input type="text" name="postal_zone" class="form-control" value="{{ old('postal_zone', $company->postal_zone) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label for="country_subentity_code" class="form-label">State Code <span class="text-danger">*</span></label>

                    <select name="country_subentity_code" id="country_subentity_code" class="form-control rounded-md" required>

                        <option value="01" {{ old('country_subentity_code', $company->country_subentity_code) == '01' ? 'selected' : '' }}>Johor</option>

                        <option value="02" {{ old('country_subentity_code', $company->country_subentity_code) == '02' ? 'selected' : '' }}>Kedah</option>

                        <option value="03" {{ old('country_subentity_code', $company->country_subentity_code) == '03' ? 'selected' : '' }}>Kelantan</option>

                        <option value="04" {{ old('country_subentity_code', $company->country_subentity_code) == '04' ? 'selected' : '' }}>Melaka</option>

                        <option value="05" {{ old('country_subentity_code', $company->country_subentity_code) == '05' ? 'selected' : '' }}>Negeri Sembilan</option>

                        <option value="06" {{ old('country_subentity_code', $company->country_subentity_code) == '06' ? 'selected' : '' }}>Pahang</option>

                        <option value="07" {{ old('country_subentity_code', $company->country_subentity_code) == '07' ? 'selected' : '' }}>Perak</option>

                        <option value="08" {{ old('country_subentity_code', $company->country_subentity_code) == '08' ? 'selected' : '' }}>Perlis</option>

                        <option value="09" {{ old('country_subentity_code', $company->country_subentity_code) == '09' ? 'selected' : '' }}>Pulau Pinang</option>

                        <option value="10" {{ old('country_subentity_code', $company->country_subentity_code) == '10' ? 'selected' : '' }}>Sabah</option>

                        <option value="11" {{ old('country_subentity_code', $company->country_subentity_code) == '11' ? 'selected' : '' }}>Sarawak</option>

                        <option value="12" {{ old('country_subentity_code', $company->country_subentity_code) == '12' ? 'selected' : '' }}>Selangor</option>

                        <option value="13" {{ old('country_subentity_code', $company->country_subentity_code) == '13' ? 'selected' : '' }}>Terengganu</option>

                        <option value="14" {{ old('country_subentity_code', $company->country_subentity_code) == '14' ? 'selected' : '' }}>Wilayah Persekutuan Kuala Lumpur</option>

                        <option value="15" {{ old('country_subentity_code', $company->country_subentity_code) == '15' ? 'selected' : '' }}>Wilayah Persekutuan Labuan</option>

                        <option value="16" {{ old('country_subentity_code', $company->country_subentity_code) == '16' ? 'selected' : '' }}>Wilayah Persekutuan Putrajaya</option>

                    </select>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Address Line 1 *</label>

                    <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $company->address_line_1) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Address Line 2 *</label>

                    <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $company->address_line_2) }}" required>

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">Address Line 3</label>

                    <input type="text" name="address_line_3" class="form-control" value="{{ old('address_line_3', $company->address_line_3) }}">

                </div>



            </div>



            <div class="divider"></div>



            <!-- LHDN CLIENT KEYS -->

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

                <input type="text" name="secret_key1" class="form-control" value="{{ old('secret_key1', $company->secret_key1) }}">

            </div>



            <div class="mb-3">

                <label class="form-label">Client Key 2</label>

                <input type="text" name="secret_key2" class="form-control" value="{{ old('secret_key2', $company->secret_key2) }}">

            </div>



            <div class="mb-3">

                <label class="form-label">Client Key 3</label>

                <input type="text" name="secret_key3" class="form-control" value="{{ old('secret_key3', $company->secret_key3) }}">

            </div>



            <div class="divider"></div>



            <!-- MYSYNCTAX DEVELOPER CREDENTIALS -->

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

                    <input 

                        type="text" 

                        class="form-control bg-light text-secondary" 

                        value="{{ $connection->mysynctax_key ?? '' }}" 

                        readonly

                    >

                </div>



                <div class="col-md-6 mb-3">

                    <label class="form-label">MySyncTax API Secret</label>

                    <input 

                        type="text" 

                        class="form-control bg-light text-secondary" 

                        value="{{ $connection->mysynctax_secret ?? '' }}" 

                        readonly

                    >

                </div>



            </div>





            <div class="security-box mb-4">

                <i class="fa-solid fa-shield-halved"></i>

                These credentials must not be shared publicly.

            </div>



            <!-- ACTION BUTTONS -->

            <div class="d-flex justify-content-between mb-2">

                <a href="{{ route('developer.companies.index') }}" class="btn btn-light">Cancel</a>

                <button type="submit" class="btn btn-primary px-4">Update Account</button>

            </div>



        </form>



    </div> <!-- END CARD -->



</div>



@endsection

