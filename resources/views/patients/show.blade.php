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
                                    @if($patient->created_at)
                                        {{ \Carbon\Carbon::parse($patient->created_at)->format('M d, Y H:i') }}
                                    @endif
                                </dd>

                                <dt class="col-sm-4">Last Updated</dt>
                                <dd class="col-sm-8">
                                    @if($patient->updated_at)
                                        {{ \Carbon\Carbon::parse($patient->updated_at)->format('M d, Y H:i') }}
                                    @endif
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
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patient->prescriptions->sortByDesc('created_at') as $prescription)
                                        <tr>
                                            <td>
                                                @if($prescription->prescription_date)
                                                    {{ \Carbon\Carbon::parse($prescription->prescription_date)->format('M d, Y') }}
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>{{ $prescription->product }}</td>
                                            <td>
                                                {{ $prescription->dosage }}
                                                @if($prescription->every && $prescription->period)
                                                    every {{ $prescription->every }} {{ $prescription->period }}
                                                @endif
                                                @if($prescription->as_needed)
                                                    (as needed)
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $prescription->sync_status === 'synced' ? 'success' : ($prescription->sync_status === 'error' ? 'danger' : 'warning') }}">
                                                    {{ ucfirst($prescription->sync_status) }}
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="{{ route('prescriptions.show', $prescription) }}" 
                                                       class="btn btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    @if($prescription->sync_status !== 'synced')
                                                        <a href="{{ route('prescriptions.edit', $prescription) }}" 
                                                           class="btn btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('prescriptions.destroy', $prescription) }}" 
                                                              method="POST" 
                                                              class="d-inline"
                                                              onsubmit="return confirm('Are you sure you want to delete this prescription?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
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

            <!-- Notes or Additional Information Card -->
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
document.addEventListener('DOMContentLoaded', function() {
    // Add any JavaScript functionality here
    
    // Example: Confirm deletion
    const deleteForms = document.querySelectorAll('form[onsubmit]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to delete this prescription?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
@endpush
@endsection