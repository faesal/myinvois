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

        {{-- ADDED ID "mainClientForm" HERE --}}
        <form id="mainClientForm" action="{{ route('developer.client.update', $client->id_customer) }}" method="POST">
            @csrf
            {{-- REMOVED @method('POST') as it is default, but keeping csrf is crucial --}}

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

            <div class="divider"></div>

            <div class="section-title">
                <i class="fa-solid fa-code"></i>
                MySyncTax Developer Credentials
            </div>

            <div class="warning-box mb-3">
                <i class="fa-solid fa-circle-exclamation me-1"></i>
                These credentials identify your integration with MySyncTax. Not editable.
            </div>

            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Key</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ $connection->mysynctax_key ?? '' }}" readonly>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">MySyncTax API Secret</label>
                    <input type="text" class="form-control bg-light text-secondary" value="{{ $connection->mysynctax_secret ?? '' }}" readonly>
                </div>
            </div>

            <div class="security-box mb-4">
                <i class="fa-solid fa-shield-halved"></i>
                These credentials must not be shared publicly.
            </div>

            <div class="divider"></div>

            {{-- 
                =========================================
                DYNAMIC CONSOLIDATION SETTINGS
                =========================================
            --}}
            <div class="section-title">
                <i class="fa-solid fa-calendar-check"></i> Consolidation Frequency 
                <div class="form-check form-switch ms-2 d-inline-block">
                    {{-- FIX: Check database value for saved state --}}
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleConsolidation" 
                           {{ ($consolidation->is_enabled ?? 1) ? 'checked' : '' }}>
                </div>
            </div>
            <p class="text-muted small">Select how often you want to consolidate einvoice data</p>

            <div id="consolidationWrapper">
                <div class="mb-3">
                    {{-- Determine Current Setting --}}
                    @php
                        $isDaily = $consolidation->is_daily ?? 0;
                        $isWeekly = $consolidation->is_weekly ?? 0;
                        $isMonthly = $consolidation->is_monthly ?? 0;
                        $specificDate = $consolidation->is_spesific_date ?? '';
                        $isSpecific = !empty($specificDate);
                        $isSendEmail = $consolidation->is_send_email ?? 0;

                        // Default to Daily if nothing is set in DB yet
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
                        {{-- This is static preview logic, but fine for UI --}}
                        <div class="col-6">Next consolidation: <span class="fw-bold">Pending Scheduler...</span></div>
                        <div class="col-6 text-end">Auto-calculated by system</div>
                    </div>
                </div>
            </div>

            <div class="divider"></div>

            {{-- 
                =========================================
                DYNAMIC IP WHITELIST
                =========================================
            --}}
            <div class="section-title">
                <i class="fa-solid fa-shield-halved"></i> IP Whitelist Management
                <div class="form-check form-switch ms-2 d-inline-block">
                    {{-- FIX: Check DB value + Add NAME attribute so it submits with form --}}
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
                            {{-- DYNAMIC DATA LOADED HERE --}}
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
                {{-- NOTE: This button ID 'masterUpdateBtn' is hooked to JS below --}}
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

    // ---------------------------------------------------------
    // HELPER FUNCTION: Handle Visual & Logic Toggle
    // ---------------------------------------------------------
    function updateToggleState(triggerSelector, wrapperSelector, isInit = false) {
        const $trigger = $(triggerSelector);
        const $wrapper = $(wrapperSelector);
        const isChecked = $trigger.is(':checked');
        const $inputs = $wrapper.find('input, select, button'); // Find all interactive elements

        if (isChecked) {
            // IF CHECKED: Show wrapper, enable inputs
            if(isInit) {
                $wrapper.show(); // No animation on page load
            } else {
                $wrapper.slideDown(); // Animate
            }
            $inputs.prop('disabled', false);
        } else {
            // IF UNCHECKED: Hide wrapper, disable inputs
            if(isInit) {
                $wrapper.hide(); // No animation on page load
            } else {
                $wrapper.slideUp(); // Animate
            }
            $inputs.prop('disabled', true);
        }
    }

    // 2. INITIAL UI STATE (Run on Page Load)
    updateToggleState('#toggleConsolidation', '#consolidationWrapper', true);
    updateToggleState('#toggleIpWhitelist', '#ipWhitelistWrapper', true);


    // 3. UI TOGGLES (Event Listeners)
    $('#toggleConsolidation').on('change', function() {
        updateToggleState(this, '#consolidationWrapper');
    });

    $('#toggleIpWhitelist').on('change', function() {
        updateToggleState(this, '#ipWhitelistWrapper');
    });


    // ---------------------------------------------------------
    // DATA LOGIC (Remains Unchanged)
    // ---------------------------------------------------------

    // 4. MASTER UPDATE BUTTON LOGIC
    $('#masterUpdateBtn').click(function(e) {
        e.preventDefault();
        const $btn = $(this);
        
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...');

        // Gather Consolidation Data
        // Note: Even if inputs are disabled/hidden, we can still grab the values manually if needed, 
        // but typically user sets them before hiding.
        let freq = $('input[name="freq"]:checked').val(); 
        let specificDate = $('#specific_date_input').val();
        let emailNotif = $('#email_notif').is(':checked') ? 1 : 0;
        let isConsolidateEnabled = $('#toggleConsolidation').is(':checked') ? 1 : 0;

        // Step A: Save Consolidation Settings via AJAX
        $.ajax({
            url: "{{ route('client.settings.consolidate', '') }}/" + clientId,
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                freq: freq,
                specific_date: specificDate,
                email_notif: emailNotif,
                is_enabled: isConsolidateEnabled
            },
            success: function(response) {
                // Step B: Submit the Main Form
                $('#mainClientForm').submit();
            },
            error: function(xhr) {
                console.error("Consolidate Error:", xhr.responseText);
                $btn.prop('disabled', false).text('Update Account');
                
                let msg = xhr.responseJSON?.message || 'Failed to save consolidation settings.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // 5. IP WHITELIST - ADD IP LOGIC
    $('#addIpBtn').click(function() {
        let ip = $('#newIpAddress').val();
        let desc = $('#newIpDesc').val();
        let $btn = $(this);

        if(!ip) {
            Swal.fire('Required', 'Please enter an IP address', 'warning');
            return;
        }

        $btn.prop('disabled', true);

        $.ajax({
            url: "{{ route('client.settings.ip.store', '') }}/" + clientId,
            method: "POST",
            data: {
                _token: "{{ csrf_token() }}",
                ip: ip,
                desc: desc
            },
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
                
                $('#newIpAddress').val('');
                $('#newIpDesc').val('');
                $btn.prop('disabled', false);

                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                Toast.fire({ icon: 'success', title: 'IP Added Successfully' });
            },
            error: function(xhr) {
                $btn.prop('disabled', false);
                let msg = xhr.responseJSON?.message || 'Failed to add IP';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // 6. IP WHITELIST - DELETE IP LOGIC
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
                    success: function(response) {
                        $row.fadeOut(300, function() { $(this).remove(); });
                        const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
                        Toast.fire({ icon: 'success', title: 'IP Removed' });
                    },
                    error: function(xhr) {
                        Swal.fire('Error', 'Failed to remove IP', 'error');
                    }
                });
            }
        });
    });
});
</script>
@endsection