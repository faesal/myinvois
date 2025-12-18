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

        margin: 30px 0;

        border-bottom: 1px solid #e5e7eb;

    }

    .detail-label {

        font-weight: 600;

        color: #6b7280;

        font-size: 13px;

        text-transform: uppercase;

        letter-spacing: 0.5px;

        margin-bottom: 4px;

    }

    .detail-value {

        font-size: 15px;

        color: #1f2937;

        padding: 8px 0;

    }

</style>



<div class="container-fluid">



    <!-- ⭐ FIX: Proper white card panel ⭐ -->

    <div class="card p-4 shadow-sm mb-4">



        <div class="d-flex justify-content-between align-items-center mb-4">

            <h3 class="mb-0">{{ $company->registration_name }}</h3>

            <a href="{{ route('developer.companies.edit', $company->id_customer) }}" class="btn btn-primary">

                <i class="fa-solid fa-pen-to-square"></i> Edit Account

            </a>

        </div>



        <!-- ================================================================== -->

        <!-- COMPANY INFORMATION -->

        <!-- ================================================================== -->

        <div class="section-title">

            <i class="fa-solid fa-building"></i>

            LHDN Account Information

        </div>



        <div class="row mb-4">



            <div class="col-md-6 mb-3">

                <div class="detail-label">Registration Name</div>

                <div class="detail-value">{{ $company->registration_name }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">TIN Number</div>

                <div class="detail-value">{{ $company->tin_no }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Identification Type</div>

                <div class="detail-value">{{ $company->identification_type }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Identification Number</div>

                <div class="detail-value">{{ $company->identification_no }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Phone</div>

                <div class="detail-value">{{ $company->phone }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Email</div>

                <div class="detail-value">{{ $company->email }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">City</div>

                <div class="detail-value">{{ $company->city_name }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Postal Zone</div>

                <div class="detail-value">{{ $company->postal_zone }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">State Code</div>

                <div class="detail-value">{{ $company->country_subentity_code }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Address Line 1</div>

                <div class="detail-value">{{ $company->address_line_1 }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">Address Line 2</div>

                <div class="detail-value">{{ $company->address_line_2 }}</div>

            </div>



            @if($company->address_line_3)

            <div class="col-md-6 mb-3">

                <div class="detail-label">Address Line 3</div>

                <div class="detail-value">{{ $company->address_line_3 }}</div>

            </div>

            @endif



        </div>



        <div class="divider"></div>



        <!-- ================================================================== -->

        <!-- LHDN CLIENT KEYS -->

        <!-- ================================================================== -->

        <div class="section-title">

            <i class="fa-solid fa-key"></i>

            LHDN Client Keys

        </div>



        <div class="info-box mb-3">

            <i class="fa-solid fa-circle-info"></i>

            These are LHDN keys used for MyInvois authentication. Keep them confidential at all times.

        </div>



        <div class="row mb-3">

            <div class="col-md-4 mb-3">

                <div class="detail-label">Client Key 1</div>

                <div class="detail-value">{{ $company->secret_key1 ?? '-' }}</div>

            </div>



            <div class="col-md-4 mb-3">

                <div class="detail-label">Client Key 2</div>

                <div class="detail-value">{{ $company->secret_key2 ?? '-' }}</div>

            </div>



            <div class="col-md-4 mb-3">

                <div class="detail-label">Client Key 3</div>

                <div class="detail-value">{{ $company->secret_key3 ?? '-' }}</div>

            </div>

        </div>



        <div class="divider"></div>



        <!-- ================================================================== -->

        <!-- DEVELOPER API CREDENTIALS -->

        <!-- ================================================================== -->



        <div class="section-title">

            <i class="fa-solid fa-code"></i>

            Developer API Credentials

        </div>



        <div class="warning-box mb-3">

            <i class="fa-solid fa-triangle-exclamation"></i>

            These credentials link this company to your MySyncTax integration.

        </div>



        <div class="row mb-3">

            <div class="col-md-6 mb-3">

                <div class="detail-label">MySyncTax API Key</div>

                <div class="detail-value">{{ $connection->mysynctax_key ?? '-' }}</div>

            </div>



            <div class="col-md-6 mb-3">

                <div class="detail-label">MySyncTax API Secret</div>

                <div class="detail-value">{{ $connection->mysynctax_secret ?? '-' }}</div>

            </div>

        </div>



        <div class="security-box mb-4">

            <i class="fa-solid fa-shield-halved"></i>

            These credentials must not be shared publicly. They are tied to your developer configuration.

        </div>



        <!-- ================================================================== -->

        <!-- ACTION BUTTONS -->

        <!-- ================================================================== -->

        <div class="d-flex justify-content-between mb-2">

            <a href="{{ route('developer.companies.index') }}" class="btn btn-light">

                <i class="fa-solid fa-arrow-left"></i> Back to List

            </a>

        </div>



    </div> <!-- END CARD -->



</div>



@endsection

