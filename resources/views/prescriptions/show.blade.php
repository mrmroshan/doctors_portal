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
                    <p class="mb-1"><strong>Created:</strong> {{ $prescription->created_at->format('M d, Y H:i') }}</p>
                    <p class="mb-1"><strong>Doctor:</strong> {{ $prescription->doctor->name }}</p>
                    @if($prescription->sync_status === 'synced')
                        <p class="mb-1"><strong>Odoo Order ID:</strong> {{ $prescription->odoo_order_id }}</p>
                    @endif
                </div>
            </div>

            <!-- Medication Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5>Medication Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tr>
                                <th>Medication</th>
                                <td>{{ $prescription->product }}</td>
                                <th>Quantity</th>
                                <td>{{ $prescription->quantity }}</td>
                            </tr>
                            <tr>
                                <th>Dosage</th>
                                <td colspan="3">
                                    {{ $prescription->getDosageInstructions() }}
                                    @if($prescription->as_needed)
                                        <span class="badge bg-info">As Needed</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Directions</th>
                                <td colspan="3">{{ $prescription->directions }}</td>
                            </tr>
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
                <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary">
                    Back to List
                </a>
            </div>
        </div>
    </div>
</div>
@endsection