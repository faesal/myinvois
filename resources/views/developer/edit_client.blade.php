{{-- Requirement 2: Switch Layout based on User Role --}}
@extends(Auth::user()->role === 'admin' ? 'layouts.adminLayout' : 'layouts.developerLayout')

@section('title', 'Edit Account Settings')

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
    .setting-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 15px;
        transition: all 0.2s;
    }
</style>

<div class="container-fluid">

    <div class="card p-4 shadow-sm mb-4">
        <h3 class="mb-4">Edit Account</h3>

        <form id="mainClientForm" action="{{ route('developer.client.update', $client->id_customer) }}" method="POST">
            @csrf

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

            <div class="section-title">
                <i class="fa-solid fa-building"></i>
                LHDN Account Information
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Registration Name *</label>
                    <input type="text" name="registration_name" class="form-control" 
                        value="{{ old('registration_name', $client->registration_name) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">TIN No *</label>
                    <input type="text" name="tin_no" class="form-control" 
                        value="{{ old('tin_no', $client->tin_no) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Identification Type *</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ $client->identification_type }}" readonly>
                    <input type="hidden" name="identification_type" value="{{ $client->identification_type }}">
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Identification Number *</label>
                    <input type="text" name="identification_no" class="form-control" 
                        value="{{ old('identification_no', $client->identification_no) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                        value="{{ old('phone', $client->phone) }}" required>
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" 
                        value="{{ old('email', $client->email) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="city_name" class="form-control" 
                        value="{{ old('city_name', $client->city_name) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Postal Zone *</label>
                    <input type="text" name="postal_zone" class="form-control" 
                        value="{{ old('postal_zone', $client->postal_zone) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">State Code *</label>
                    <select name="country_subentity_code" id="country_subentity_code" class="form-control" required>
                        @php
                            $states = [
                                '01'=>'Johor','02'=>'Kedah','03'=>'Kelantan','04'=>'Melaka','05'=>'Negeri Sembilan',
                                '06'=>'Pahang','07'=>'Perak','08'=>'Perlis','09'=>'Pulau Pinang','10'=>'Selangor',
                                '11'=>'Terengganu','12'=>'Sabah','13'=>'Sarawak','14'=>'W.P. Kuala Lumpur',
                                '15'=>'W.P. Labuan','16'=>'W.P. Putrajaya'
                            ];
                        @endphp
                        @foreach($states as $code => $name)
                            <option value="{{ $code }}" {{ old('country_subentity_code', $client->country_subentity_code) == $code ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address_line_1" class="form-control" 
                        value="{{ old('address_line_1', $client->address_line_1) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 2 *</label>
                    <input type="text" name="address_line_2" class="form-control" 
                        value="{{ old('address_line_2', $client->address_line_2) }}" required>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Address Line 3</label>
                    <input type="text" name="address_line_3" class="form-control" 
                        value="{{ old('address_line_3', $client->address_line_3) }}">
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-title">
                <i class="fa-solid fa-key"></i>
                LHDN Client Keys
            </div>

            <div class="info-box mb-3">
                <i class="fa-solid fa-circle-info"></i>
                These keys are required for LHDN MyInvois authentication.
            </div>

            <div class="mb-3">
                <label class="form-label">Client ID</label>
                <input type="text" name="secret_key1" class="form-control" value="{{ old('secret_key1', $client->secret_key1) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Client Key 1</label>
                <input type="text" name="secret_key2" class="form-control" value="{{ old('secret_key2', $client->secret_key2) }}">
            </div>

            <div class="mb-3">
                <label class="form-label">Client Key 2</label>
                <input type="text" name="secret_key3" class="form-control" value="{{ old('secret_key3', $client->secret_key3) }}">
            </div>

            <div class="row mt-4">
                <div class="col-md-6 mb-3">
                    <label class="form-label">ERP LHDN Start Date</label>
                    <input type="date" name="erp_lhdn_start" class="form-control" value="{{ old('erp_lhdn_start', $client->erp_lhdn_start) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">ERP LHDN End Date</label>
                    <input type="date" name="erp_lhdn_end" class="form-control" value="{{ old('erp_lhdn_end', $client->erp_lhdn_end) }}">
                </div>
            </div>
            <div class="divider"></div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="section-title mb-0">
                    <i class="fa-solid fa-code"></i>
                    MySyncTax Developer Credentials
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" id="regenerateKeysBtn">
                    <i class="fa-solid fa-rotate"></i> Regenerate Keys
                </button>
            </div>

            <div class="warning-box mb-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i>
                <strong>Notice:</strong> These credentials identify your integration. Regenerating these keys will immediately block access for any system using the old keys. You will need to update your application code with the new keys.
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Key</label>
                    <div class="input-group">
                        <input type="text" id="mysynctax_key_display" class="form-control bg-light text-secondary fw-bold" value="{{ $client->mysynctax_key ?? $connection->mysynctax_key ?? '' }}" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#mysynctax_key_display" title="Copy to clipboard">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Secret</label>
                    <div class="input-group">
                        <input type="text" id="mysynctax_secret_display" class="form-control bg-light text-secondary fw-bold" value="{{ $client->mysynctax_secret ?? $connection->mysynctax_secret ?? '' }}" readonly>
                        <button class="btn btn-outline-secondary copy-btn" type="button" data-clipboard-target="#mysynctax_secret_display" title="Copy to clipboard">
                            <i class="fa-regular fa-copy"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="security-box mb-4">
                <i class="fa-solid fa-shield-halved"></i>
                These credentials must not be shared publicly.
            </div>

            <div class="divider"></div>

            <div class="section-title">
                <i class="fa-solid fa-code-branch"></i> API Version
            </div>
            <p class="text-muted small">Select the MyInvois API version for this client.</p>

            <div class="card bg-light border-0 p-3 mb-4">
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input api-version-radio" type="radio" name="is_version" id="ver_1_0" value="1.0" {{ ($client->is_version ?? '1.0') == '1.0' ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="ver_1_0">
                            Version 1.0
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input api-version-radio" type="radio" name="is_version" id="ver_1_1" value="1.1" {{ ($client->is_version ?? '1.0') == '1.1' ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold" for="ver_1_1">
                            Version 1.1
                        </label>
                    </div>
                </div>
            </div>

            <div class="row mb-4 mt-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Intermediary Start Date</label>
                    <input type="date" name="intermediary_start" class="form-control" value="{{ old('intermediary_start', $client->intermediary_start) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Intermediary End Date</label>
                    <input type="date" name="intermediary_end" class="form-control" value="{{ old('intermediary_end', $client->intermediary_end) }}">
                </div>
            </div>
            <div class="divider"></div>

            <div class="section-title">
                <i class="fa-solid fa-calendar-check"></i> Consolidation Frequency 
                <div class="form-check form-switch ms-2 d-inline-block">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleConsolidation" 
                           {{ ($consolidation->is_enabled ?? 1) ? 'checked' : '' }}>
                </div>
            </div>
            <p class="text-muted small">Select how often you want to consolidate einvoice data</p>

            <div id="consolidationWrapper">
                <div class="mb-3">
                    @php
                        $isDaily = $consolidation->is_daily ?? 0;
                        $isWeekly = $consolidation->is_weekly ?? 0;
                        $isMonthly = $consolidation->is_monthly ?? 0;
                        $specificDate = $consolidation->is_spesific_date ?? '';
                        $isSpecific = !empty($specificDate);
                        $isSendEmail = $consolidation->is_send_email ?? 0;

                        if(!$isDaily && !$isWeekly && !$isMonthly && !$isSpecific) {
                            $isDaily = 1;
                        }
                    @endphp

                    <div class="list-group">
                        <label class="list-group-item d-flex gap-3 py-3 border rounded mb-2">
                            <input class="form-check-input flex-shrink-0" type="radio" name="freq" value="daily" {{ $isDaily ? 'checked' : '' }}>
                            <span class="pt-1 text-dark fw-bold">Daily <small class="text-muted fw-normal ms-2">Consolidate at end of each day</small></span>
                        </label>
                        <label class="list-group-item d-flex gap-3 py-3 border rounded mb-2">
                            <input class="form-check-input flex-shrink-0" type="radio" name="freq" value="weekly" {{ $isWeekly ? 'checked' : '' }}>
                            <span class="pt-1 text-dark fw-bold">Weekly <small class="text-muted fw-normal ms-2">Consolidate every Sunday at midnight</small></span>
                        </label>
                        <label class="list-group-item d-flex gap-3 py-3 border rounded mb-2">
                            <input class="form-check-input flex-shrink-0" type="radio" name="freq" value="monthly" {{ $isMonthly ? 'checked' : '' }}>
                            <span class="pt-1 text-dark fw-bold">Monthly <small class="text-muted fw-normal ms-2">Consolidate on the last day of each month</small></span>
                        </label>
                        <label class="list-group-item d-flex gap-3 py-3 border rounded mb-2">
                            <input class="form-check-input flex-shrink-0" type="radio" name="freq" value="specific" {{ $isSpecific ? 'checked' : '' }}>
                            <div class="d-flex align-items-center gap-2">
                                <span class="pt-1 text-dark fw-bold">Specific Date</span>
                                <input type="number" id="specific_date_input" class="form-control form-control-sm w-25" 
                                       placeholder="Date (1-28)" min="1" max="31" value="{{ $specificDate }}">
                                <small class="text-muted">Consolidate on the specific date every month</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-12">
                        <label class="fw-bold small mb-2">Additional Options</label>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="email_notif" {{ $isSendEmail ? 'checked' : '' }}>
                            <label class="form-check-label small" for="email_notif">Send email notification when consolidated invoice is generated</label>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded border mb-4">
                    <div class="small text-muted mb-2"><i class="fa-solid fa-eye me-1"></i> Preview Schedule</div>
                    <div class="row small">
                        <div class="col-6">Next consolidation: <span class="fw-bold">Pending Scheduler...</span></div>
                        <div class="col-6 text-end">Auto-calculated by system</div>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="section-title">
                <i class="fa-solid fa-shield-halved"></i> IP Whitelist Management
                <div class="form-check form-switch ms-2 d-inline-block">
                    <input class="form-check-input" type="checkbox" role="switch" 
                           id="toggleIpWhitelist" 
                           name="is_ip_whitelist_enabled" 
                           value="1"
                           {{ ($client->is_ip_whitelist_enabled ?? 0) ? 'checked' : '' }}>
                </div>
            </div>
            <p class="text-muted small">Manage IP addresses that are allowed to access einvoice services</p>

            <div id="ipWhitelistWrapper">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-sm" id="newIpAddress" placeholder="Enter IP address (e.g. 192.168.1.1)">
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control form-control-sm" id="newIpDesc" placeholder="Description (optional)">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-dark btn-sm w-100" id="addIpBtn">+ Add IP</button>
                    </div>
                </div>

                <div class="border rounded p-0 overflow-hidden mb-3">
                    <table class="table table-hover mb-0 small">
                        <tbody id="ipTableBody">
                            @if(isset($ip_list) && count($ip_list) > 0)
                                @foreach($ip_list as $ip)
                                    <tr class="border-bottom" data-id="{{ $ip->id_ip_managment }}">
                                        <td class="ps-3 py-3">
                                            <i class="fa-regular fa-square-check text-dark me-2"></i> {{ $ip->whitelist_ip }}
                                        </td>
                                        <td class="text-muted">{{ $ip->ip_description }}</td>
                                        <td><span class="badge bg-success-subtle text-success border border-success">Active</span></td>
                                        <td class="text-end pe-3">
                                            <button type="button" class="btn btn-sm text-danger p-0 delete-ip" data-id="{{ $ip->id_ip_managment }}">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr id="noIpRow">
                                    <td colspan="4" class="text-center text-muted py-3">
                                        No IP addresses currently whitelisted.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between align-items-center small">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="blockAttempts" checked disabled>
                        <label class="form-check-label text-muted" for="blockAttempts">Automatically block IPs after 5 failed attempts (Global Policy)</label>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            <div class="d-flex justify-content-between mb-2">
                <a href="{{ route('developer.dashboard') }}" class="btn btn-light px-4">Cancel</a>
                <button type="button" id="masterUpdateBtn" class="btn btn-primary px-4">Update Account</button>
            </div>

        </form>
    </div> 
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    // 1. Setup Variables
    const clientId = "{{ $client->id_customer }}";

    // 🌟 SEMAK ENVIRONMENT .ENV DI SINI 🌟
    @php
        $myinvoisEnv = env('MYINVOIS_ENVIROMENT'); // Default ke preprod jika kosong
        $myinvoisUrl = $myinvoisEnv === 'prod' 
            ? env('MYINVOIS_PROD_MYTAX') 
            : env('MYINVOIS_PREPROD_MYTAX');
    @endphp
    const myInvoisLink = "{{ $myinvoisUrl }}";

    // Common SweetAlert Toast Configuration
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });

    // ---------------------------------------------------------
    // DYNAMIC MANDATORY LOGIC (NEW)
    // ---------------------------------------------------------
    function toggleMandatoryFields() {
        // Condition 1: Intermediary Dates mandatory if API 1.1
        const isVersion11 = $('#ver_1_1').is(':checked');
        const $intStart = $('input[name="intermediary_start"]');
        const $intEnd = $('input[name="intermediary_end"]');

        if (isVersion11) {
            $intStart.prop('required', true).closest('.col-md-6').find('.form-label').html('Intermediary Start Date <span class="text-danger">*</span>');
            $intEnd.prop('required', true).closest('.col-md-6').find('.form-label').html('Intermediary End Date <span class="text-danger">*</span>');
        } else {
            $intStart.prop('required', false).closest('.col-md-6').find('.form-label').text('Intermediary Start Date');
            $intEnd.prop('required', false).closest('.col-md-6').find('.form-label').text('Intermediary End Date');
        }

        // Condition 2: ERP Dates mandatory if Client Keys are filled
        const key1 = $('input[name="secret_key1"]').val().trim();
        const key2 = $('input[name="secret_key2"]').val().trim();
        const key3 = $('input[name="secret_key3"]').val().trim();
        const hasKeys = (key1 !== "" || key2 !== "" || key3 !== "");

        const $erpStart = $('input[name="erp_lhdn_start"]');
        const $erpEnd = $('input[name="erp_lhdn_end"]');

        if (hasKeys) {
            $erpStart.prop('required', true).closest('.col-md-6').find('.form-label').html('ERP LHDN Start Date <span class="text-danger">*</span>');
            $erpEnd.prop('required', true).closest('.col-md-6').find('.form-label').html('ERP LHDN End Date <span class="text-danger">*</span>');
        } else {
            $erpStart.prop('required', false).closest('.col-md-6').find('.form-label').text('ERP LHDN Start Date');
            $erpEnd.prop('required', false).closest('.col-md-6').find('.form-label').text('ERP LHDN End Date');
        }
    }

    // Run on page load
    toggleMandatoryFields();

    // Trigger on input/change events
    $('.api-version-radio').on('change', toggleMandatoryFields);
    $('input[name="secret_key1"], input[name="secret_key2"], input[name="secret_key3"]').on('input', toggleMandatoryFields);


    // Simple clipboard copy functionality for user convenience
    $('.copy-btn').click(function() {
        let targetId = $(this).data('clipboard-target');
        let copyText = $(targetId);
        
        copyText.select();
        document.execCommand("copy");
        
        Toast.fire({ icon: 'success', title: 'Copied to clipboard' });
    });

    if ($('#version11Modal').length === 0) {
        const modalHtml = `
            <div class="modal fade" id="version11Modal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
                    <div class="modal-content">
                        <div class="modal-header border-bottom pb-3">
                            <h5 class="modal-title d-flex align-items-center fs-5 text-dark fw-bold">
                                <i class="fa-solid fa-circle-info text-secondary me-2"></i> MySynctax Intermediary Details
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body pt-4" style="text-align: left; font-size: 13px;">
                            
                            <div class="bg-primary bg-opacity-10 border rounded p-3 mb-4">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded text-secondary me-3" style="min-width: 35px; text-align: center;">
                                        <i class="fa-solid fa-lock"></i>
                                    </div>
                                    <div>
                                        <strong class="d-block text-dark fw-bold">Please add below information into MyInvois (LHDN) intermediary.</strong>
                                        <a href="${myInvoisLink}" target="_blank" class="text-primary text-decoration-none">${myInvoisLink}</a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center">
                                    <div class="bg-secondary bg-opacity-25 p-2 rounded text-secondary me-3" style="min-width: 35px; text-align: center;">
                                        <i class="fa-regular fa-file-pdf"></i>
                                    </div>
                                    <a href="{{ asset('assets/pdf/manual_intermidiary_mysynctax.pdf') }}" target="_blank" class="text-primary text-decoration-none fw-bold">Click here (Manual Guidance to add intermediary)</a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 11px;">Intermediary TIN</label>
                                <div class="border rounded p-2 d-flex align-items-center bg-white shadow-sm">
                                    <i class="fa-regular fa-id-card text-secondary mx-2"></i>
                                    <span class="ms-2 text-dark">{{ env('INTERMEDIARY_TIN') }}</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 11px;">Intermediary BRN</label>
                                <div class="border rounded p-2 d-flex align-items-center bg-white shadow-sm">
                                    <i class="fa-solid fa-building text-secondary mx-2"></i>
                                    <span class="ms-2 text-dark">{{ env('INTERMEDIARY_BRN') }}</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="text-muted small fw-bold text-uppercase mb-1" style="font-size: 11px;">Intermediary Taxpayer</label>
                                <div class="border rounded p-2 d-flex align-items-center bg-white shadow-sm">
                                    <i class="fa-solid fa-user-tie text-secondary mx-2"></i>
                                    <span class="ms-2 text-dark">{{ env('INTERMEDIARY_TAXPAYER') }}</span>
                                </div>
                            </div>

                            <div class="d-flex" style="color: #e74c3c; font-size: 12px;">
                                <i class="fa-solid fa-triangle-exclamation mt-1 me-2" style="color: #f39c12;"></i>
                                <span>This intermediary is authorized to perform e-Invoice submissions on behalf of the taxpayer under the MyInvois 1.1 framework.</span>
                            </div>

                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn text-white px-4" style="background-color: #3b4351;" data-bs-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('body').append(modalHtml);
    }

    function updateToggleState(triggerSelector, wrapperSelector, isInit = false) {
        const $trigger = $(triggerSelector);
        const $wrapper = $(wrapperSelector);
        const isChecked = $trigger.is(':checked');
        const $inputs = $wrapper.find('input, select, button');

        if (isChecked) {
            if(isInit) $wrapper.show();
            else $wrapper.slideDown();
            $inputs.prop('disabled', false);
        } else {
            if(isInit) $wrapper.hide();
            else $wrapper.slideUp();
            $inputs.prop('disabled', true);
        }
    }

    updateToggleState('#toggleConsolidation', '#consolidationWrapper', true);
    updateToggleState('#toggleIpWhitelist', '#ipWhitelistWrapper', true);

    // ---------------------------------------------------------
    // API KEY REGENERATION
    // ---------------------------------------------------------
    $('#regenerateKeysBtn').click(function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Regenerate API Keys?',
            text: "Warning: Your current keys will stop working instantly. This cannot be undone.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Regenerate'
        }).then((result) => {
            if (result.isConfirmed) {
                let $btn = $(this);
                let originalText = $btn.html();
                $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');

                $.ajax({
                    url: "{{ route('developer.client.regenerate_keys', $client->id_customer) }}",
                    method: "POST",
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        $('#mysynctax_key_display').val(response.new_key);
                        $('#mysynctax_secret_display').val(response.new_secret);
                        
                        $btn.prop('disabled', false).html(originalText);
                        
                        Swal.fire(
                            'Regenerated!',
                            'Your new API keys are ready to use. Please update your integration code.',
                            'success'
                        );
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalText);
                        let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to regenerate keys.';
                        Swal.fire('Error', msg, 'error');
                    }
                });
            }
        });
    });

    // ---------------------------------------------------------
    // API VERSION
    // ---------------------------------------------------------
    $('.api-version-radio').on('change', function() {
        let version = $(this).val();

        if (version === '1.1') {
            $('#version11Modal').modal('show');
        }

        $.ajax({
            url: "{{ route('client.settings.update_version', '') }}/" + clientId,
            method: "POST",
            data: { _token: "{{ csrf_token() }}", version: version },
            success: function(response) {
                Toast.fire({ icon: 'success', title: 'API Version Updated to ' + version });
            },
            error: function(xhr) {
                let msg = xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update version';
                Swal.fire('Error', msg, 'error');
                if (version === '1.0') $('#ver_1_1').prop('checked', true);
                else $('#ver_1_0').prop('checked', true);
            }
        });
    });

    // ---------------------------------------------------------
    // CONSOLIDATION SETTINGS
    // ---------------------------------------------------------
    function saveConsolidationSettings() {
        let isEnabled = $('#toggleConsolidation').is(':checked') ? 1 : 0;
        let freq = $('input[name="freq"]:checked').val() || 'daily';
        let specificDate = $('#specific_date_input').val();
        let emailNotif = $('#email_notif').is(':checked') ? 1 : 0;

        $.ajax({
            url: "{{ route('client.settings.consolidate', '') }}/" + clientId,
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                is_enabled: isEnabled, freq: freq, specific_date: specificDate, email_notif: emailNotif
            },
            success: function(response) { Toast.fire({ icon: 'success', title: 'Settings Saved' }); },
            error: function(xhr) { Swal.fire('Error', 'Failed to save settings', 'error'); }
        });
    }

    $('#toggleConsolidation').on('change', function() {
        updateToggleState(this, '#consolidationWrapper');
        saveConsolidationSettings();
    });
    $('input[name="freq"]').on('change', saveConsolidationSettings);
    $('#specific_date_input').on('blur', saveConsolidationSettings);
    $('#email_notif').on('change', saveConsolidationSettings);

    // ---------------------------------------------------------
    // IP WHITELIST
    // ---------------------------------------------------------
    $('#toggleIpWhitelist').on('change', function() {
        updateToggleState(this, '#ipWhitelistWrapper');
        let isEnabled = $(this).is(':checked') ? 1 : 0;
        $.ajax({
            url: "{{ route('client.settings.ip_toggle', '') }}/" + clientId,
            method: "POST",
            data: { _token: "{{ csrf_token() }}", is_enabled: isEnabled },
            success: function() { Toast.fire({ icon: 'success', title: 'IP Whitelist Status Updated' }); },
            error: function() { Swal.fire('Error', 'Failed to auto-save IP Whitelist status', 'error'); }
        });
    });

    $('#mainClientForm').on('submit', function() {
        $('#masterUpdateBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...');
    });

    $('#addIpBtn').click(function() {
        let ip = $('#newIpAddress').val();
        let desc = $('#newIpDesc').val();
        let $btn = $(this);

        if(!ip) { Swal.fire('Required', 'Please enter an IP address', 'warning'); return; }

        $btn.prop('disabled', true);
        $.ajax({
            url: "{{ route('client.settings.ip.store', '') }}/" + clientId,
            method: "POST",
            data: { _token: "{{ csrf_token() }}", ip: ip, desc: desc },
            success: function(response) {
                $('#noIpRow').remove();
                $('#ipTableBody').append(`
                    <tr class="border-bottom" data-id="${response.id}">
                        <td class="ps-3 py-3"><i class="fa-regular fa-square-check text-dark me-2"></i> ${ip}</td>
                        <td class="text-muted">${desc || ''}</td>
                        <td><span class="badge bg-success-subtle text-success border border-success">Active</span></td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm text-danger p-0 delete-ip" data-id="${response.id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
                $('#newIpAddress').val(''); $('#newIpDesc').val('');
                $btn.prop('disabled', false);
                Toast.fire({ icon: 'success', title: 'IP Added Successfully' });
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                Swal.fire('Error', xhr.responseJSON?.message || 'Failed to add IP', 'error');
            }
        });
    });

    $(document).on('click', '.delete-ip', function() {
        let id = $(this).data('id');
        let $row = $(this).closest('tr');

        Swal.fire({
            title: 'Remove IP?',
            text: "Access for this IP will be revoked immediately.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "{{ route('client.settings.ip.delete', '') }}/" + id,
                    method: "DELETE",
                    data: { _token: "{{ csrf_token() }}" },
                    success: function() {
                        $row.fadeOut(300, function() { $(this).remove(); });
                        Toast.fire({ icon: 'success', title: 'IP Removed' });
                    },
                    error: function() { Swal.fire('Error', 'Failed to remove IP', 'error'); }
                });
            }
        });
    });

    @if(session('success'))
        Toast.fire({ icon: 'success', title: "{{ session('success') }}" });
    @endif
});
</script>
@endsection