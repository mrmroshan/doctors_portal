@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <!-- Patient Information Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Patient Information</h4>
                    <div>
                        @if(!auth()->user()->isDoctor() || $patient->hasDoctor(auth()->user()))
                            <a href="{{ route('patients.edit', $patient) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i> Edit Patient
                            </a>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Personal Details</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Full Name</dt>
                                <dd class="col-sm-8">{{ $patient->first_name }} {{ $patient->last_name }}</dd>

                                <dt class="col-sm-4">Date of Birth</dt>
                                <dd class="col-sm-8">
                                    @if($patient->date_of_birth)
                                        {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M d, Y') }}
                                        ({{ \Carbon\Carbon::parse($patient->date_of_birth)->age }} years)
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Phone</dt>
                                <dd class="col-sm-8">
                                    <a href="tel:{{ $patient->phone }}">{{ $patient->phone }}</a>
                                </dd>

                                <dt class="col-sm-4">Email</dt>
                                <dd class="col-sm-8">
                                    @if($patient->email)
                                        <a href="mailto:{{ $patient->email }}">{{ $patient->email }}</a>
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Address</dt>
                                <dd class="col-sm-8">
                                    @if($patient->address)
                                        {{ $patient->address }}
                                    @else
                                        <span class="text-muted">Not provided</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-md-6">
                            <h5 class="border-bottom pb-2">Medical Information</h5>
                            <dl class="row">
                                <dt class="col-sm-4">Assigned Doctors</dt>
                                <dd class="col-sm-8">
                                    @if($patient->doctors->count() > 0)
                                        <ul class="list-unstyled">
                                            @foreach($patient->doctors as $doctor)
                                                <li>{{ $doctor->name }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">No doctors assigned</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Created Date</dt>
                                <dd class="col-sm-8">
                                    {{ $patient->created_at->format('M d, Y H:i') }}
                                </dd>

                                <dt class="col-sm-4">Last Updated</dt>
                                <dd class="col-sm-8">
                                    {{ $patient->updated_at->format('M d, Y H:i') }}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prescriptions Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Prescriptions History</h4>
                    <a href="{{ route('prescriptions.create', ['patient_id' => $patient->id]) }}" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> New Prescription
                    </a>
                </div>

                <div class="card-body">
                    @if($patient->prescriptions->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Medications</th>
                                        <th>Status</th>
                                        <th>Order Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patient->prescriptions->sortByDesc('created_at') as $prescription)
                                        <tr>
                                            <td>{{ $prescription->prescription_date->format('M d, Y') }}</td>
                                            <td>
                                            @foreach($prescription->medications as $medication)
                                                <div class="mb-2">
                                                    @if($medication->type === 'odoo')
                                                        <strong>
                                                            @if(isset($medicationsLookup[$medication->product]))
                                                                {{ $medicationsLookup[$medication->product]['name'] }}
                                                                <small class="text-muted">
                                                                    ({{ $medicationsLookup[$medication->product]['default_code'] }})
                                                                </small>
                                                            @else
                                                                {{ $medication->product }}
                                                                <small class="text-muted">(Product not found in Odoo)</small>
                                                            @endif
                                                        </strong>
                                                    @else
                                                        <strong>{{ $medication->product }}</strong>
                                                        <small class="text-muted">(Custom)</small>
                                                    @endif
                                                    <br>
                                                    <small class="text-muted">
                                                        Qty: {{ $medication->quantity }} - 
                                                        {{ $medication->dosage }}
                                                        @if($medication->every && $medication->period)
                                                            every {{ $medication->every }} {{ $medication->period }}
                                                        @endif
                                                        @if($medication->as_needed)
                                                            (as needed)
                                                        @endif
                                                    </small>
                                                    @if($medication->directions)
                                                        <br>
                                                        <small class="text-muted">
                                                            {{ $medication->directions }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @endforeach
                                            </td>
                                            <td>
                                                @switch($prescription->sync_status)
                                                    @case('synced')
                                                        <span class="badge bg-success">Synced</span>
                                                        @break
                                                    @case('error')
                                                        <span class="badge bg-danger" 
                                                              title="{{ $prescription->sync_error }}">
                                                            Error
                                                        </span>
                                                        @break
                                                    @default
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                @endswitch
                                            </td>

                                            <td>
                                                @switch($prescription->order_status)
                                                    
                                                    @case('Draft')
                                                        <span class="badge bg-warning">Draft</span>
                                                        @break
                                                    @case('Confirmed')
                                                        <span class="badge bg-success">Confirmed</span>
                                                        @break
                                                    @case('Done')
                                                        <span class="badge bg-success">Done</span>
                                                        @break
                                                    @case('Cancelled')
                                                        <span class="badge bg-danger">Cancelled</span>
                                                        @break
                                                    @case('Error')
                                                        <span class="badge bg-danger">Error</span>
                                                        @break
                                                    @case('Warning')
                                                        <span class="badge bg-warning">Warning</span>
                                                        @break                                    
                                                    @default
                                                        <span class="badge bg-warning text-dark">Pending</span>
                                                @endswitch
                                            </td>

                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('prescriptions.show', $prescription) }}" 
                                                       class="btn btn-info" 
                                                       title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($prescription->sync_status !== 'synced')
                                                        <a href="{{ route('prescriptions.edit', $prescription) }}" 
                                                           class="btn btn-primary" 
                                                           title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('prescriptions.destroy', $prescription) }}" 
                                                              method="POST" 
                                                              class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" 
                                                                    class="btn btn-danger" 
                                                                    title="Delete"
                                                                    onclick="return confirm('Are you sure you want to delete this prescription?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if($prescription->sync_status === 'error')
                                                        <button type="button" 
                                                                class="btn btn-warning" 
                                                                title="Retry Sync"
                                                                onclick="retrySync({{ $prescription->id }})">
                                                            <i class="fas fa-sync"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <p>No prescriptions found for this patient.</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes Card -->
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Notes</h4>
                </div>
                <div class="card-body">
                    @if($patient->notes)
                        {!! nl2br(e($patient->notes)) !!}
                    @else
                        <p class="text-muted text-center">No notes available for this patient.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .card {
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    
    .table td, .table th {
        vertical-align: middle;
    }
    
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
    }
    
    dt {
        font-weight: 600;
    }
    
    dd {
        margin-bottom: 0.5rem;
    }
</style>
@endpush

@push('scripts')
<script>
function retrySync(prescriptionId) {
    if (!confirm('Are you sure you want to retry syncing this prescription?')) {
        return;
    }

    const button = event.target.closest('button');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(`/prescriptions/${prescriptionId}/resync`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Sync failed: ' + data.message);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-sync"></i>';
        }
    })
    .catch(error => {
        alert('Error occurred during sync. Please try again.');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-sync"></i>';
    });
}
</script>
@endpush
@endsection