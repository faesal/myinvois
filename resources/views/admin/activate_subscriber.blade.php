<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activate Subscriber | MySynctax</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }
        .btn-dark {
            background-color: #111827;
            border-color: #111827;
        }
        .btn-dark:hover {
            background-color: #1f2937;
            border-color: #1f2937;
        }
    </style>
</head>
<body>

    <div class="container-fluid d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        
        <div class="card border-0 shadow-lg" style="max-width: 600px; width: 100%; border-radius: 12px;">
            
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h4 class="fw-bold text-dark mb-1">Activate Subscriber [{{ $subscriber->registration_name }}]</h4>
                <p class="text-muted small">Activation for MySynctax subscriber</p>
            </div>
            
            <div class="card-body px-4 py-3">
                <form action="{{ route('admin.subscribers.activate_submit', $subscriber->id_customer) }}" method="POST">
                    @csrf

                    <div class="bg-light p-3 rounded mb-4 border">
                        <div class="mb-3">
                            <label class="small text-uppercase text-muted fw-bold" style="font-size: 10px;">Developer Name</label>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-user text-secondary me-2"></i>
                                <span class="fw-bold text-dark">{{ $subscriber->developer_name ?? 'Unknown Developer' }}</span>
                            </div>
                        </div>
                        <div>
                            <label class="small text-uppercase text-muted fw-bold" style="font-size: 10px;">Subscriber Name</label>
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-building text-secondary me-2"></i>
                                <span class="fw-bold text-dark">{{ $subscriber->registration_name }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        {{-- Start Date --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Start Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" name="start_date" id="start_date" 
                                       class="form-control" 
                                       value="{{ $startDate }}" required>
                            </div>
                        </div>

                        {{-- End Date --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">End Date</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="fa-solid fa-check"></i></span>
                                <input type="date" name="end_date" id="end_date" 
                                       class="form-control bg-light" 
                                       value="{{ $endDate }}" readonly>
                            </div>
                            <div class="text-muted fst-italic" style="font-size: 11px; margin-top: 4px;">
                                Auto-populated to 1 year later
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-light border d-flex align-items-center p-3 mb-4">
                        <i class="fa-solid fa-circle-info text-dark fs-5 me-3"></i>
                        <div class="small text-muted" style="line-height: 1.4;">
                            Once activated, the subscriber will have access to MySynctax services for the specified duration. This action is logged.
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <a href="{{ route('admin.subscribers.index') }}" class="btn btn-white border px-4 shadow-sm">Cancel</a>
                        
                        <button type="submit" class="btn btn-dark px-4">
                            <i class="fa-solid fa-check me-2"></i> Confirm Activation
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            // Logic: Auto calculate 1 year from start date
            startDateInput.addEventListener('change', function() {
                if (this.value) {
                    const start = new Date(this.value);
                    
                    // Add 1 Year
                    const end = new Date(start);
                    end.setFullYear(start.getFullYear() + 1);

                    // Format to YYYY-MM-DD
                    const yyyy = end.getFullYear();
                    const mm = String(end.getMonth() + 1).padStart(2, '0');
                    const dd = String(end.getDate()).padStart(2, '0');

                    endDateInput.value = `${yyyy}-${mm}-${dd}`;
                }
            });
        });
    </script>
</body>
</html>