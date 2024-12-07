@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Prescription Details</h3>
            <div>
                <span class="badge bg-{{ $prescription->sync_status === 'synced' ? 'success' : ($prescription->sync_status === 'error' ? 'danger' : 'warning') }}">
                    {{ ucfirst($prescription->sync_status) }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Patient Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Patient Information</h5>
                    <p class="mb-1"><strong>Name:</strong> {{ $prescription->patient->full_name }}</p>
                    <p class="mb-1"><strong>Phone:</strong> {{ $prescription->patient->phone }}</p>
                    <p class="mb-1"><strong>Date of Birth:</strong> {{ $prescription->patient->date_of_birth->format('M d, Y') }}</p>
                </div>
                <div class="col-md-6">
                    <h5>Prescription Information</h5>
                    <p class="mb-1"><strong>Prescription Date:</strong> {{ date('M d, Y', strtotime($prescription->prescription_date)) }}</p>
                    <p class="mb-1"><strong>Created:</strong> {{ $prescription->created_at->format('M d, Y H:i') }}</p>
                    <p class="mb-1"><strong>Doctor:</strong> Dr. {{ $prescription->doctor->name }}</p>
                    @if($prescription->sync_status === 'synced')
                        <p class="mb-1"><strong>Odoo Order ID:</strong> {{ $prescription->odoo_order_id }}</p>
                    @endif
                </div>
            </div>

            <!-- Medications Details -->
            <div class="row mb-4">
                <div class="col-12">
                    @if(session('warning'))
                        <div class="alert alert-warning">
                            {{ session('warning') }}
                        </div>
                    @endif
                    
                    <h5>Medications</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Quantity</th>
                                    <th>Dosage</th>
                                    <th>Schedule</th>
                                    <th>Additional Directions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prescription->medications as $medication)
                                    <tr>
                                        <td>
                                            {{ $medication->product_name ?? 'Unknown Product' }}
                                            @if($medication->product_code)
                                                <br>
                                                <small class="text-muted">({{ $medication->product_code }})</small>
                                            @endif
                                        </td>
                                        <td>{{ $medication->quantity }}</td>
                                        <td>{{ $medication->dosage }}</td>
                                        <td>
                                            @if($medication->every && $medication->period)
                                                Every {{ $medication->every }} {{ $medication->period }}
                                                <br>
                                            @endif
                                            @if($medication->as_needed)
                                                <span class="badge bg-info">As Needed</span>
                                            @endif
                                        </td>
                                        <td>{{ $medication->directions }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Error Information -->
            @if($prescription->sync_status === 'error')
                <div class="alert alert-danger">
                    <h6>Sync Error:</h6>
                    <p class="mb-0">{{ $prescription->sync_error }}</p>
                </div>
            @endif

            <!-- Action Buttons -->
            <div class="mt-4">
                @if($prescription->sync_status !== 'synced')
                    <a href="{{ route('prescriptions.edit', $prescription) }}" class="btn btn-primary">
                        Edit Prescription
                    </a>
                @endif
                
                @if(auth()->user()->isAdmin())
                    <button type="button" class="btn btn-warning" id="resyncButton" 
                            data-prescription-id="{{ $prescription->id }}"
                            @if($prescription->sync_status === 'synced') disabled @endif>
                        Resync with Odoo
                    </button>
                @endif

                <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#resyncButton').click(function() {
        const button = $(this);
        const prescriptionId = button.data('prescription-id');
        
        button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Syncing...');

        $.ajax({
            url: `/prescriptions/${prescriptionId}/resync`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Sync failed: ' + response.message);
                    button.prop('disabled', false).text('Resync with Odoo');
                }
            },
            error: function(xhr) {
                alert('Error occurred during sync. Please try again.');
                button.prop('disabled', false).text('Resync with Odoo');
            }
        });
    });
});
</script>
@endpush