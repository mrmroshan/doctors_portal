@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Edit Prescription</h3>
            <span class="badge bg-{{ $prescription->sync_status === 'synced' ? 'success' : ($prescription->sync_status === 'error' ? 'danger' : 'warning') }}">
                {{ ucfirst($prescription->sync_status) }}
            </span>
        </div>
        
        <div class="card-body">
            <form action="{{ route('prescriptions.update', $prescription) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Patient Information (Read-only if synced) -->
                <div class="form-group mb-3">
                    <label for="patient_id">Patient</label>
                    <select name="patient_id" id="patient_id" class="form-control @error('patient_id') is-invalid @enderror" 
                            {{ $prescription->sync_status === 'synced' ? 'disabled' : 'required' }}>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" 
                                {{ (old('patient_id', $prescription->patient_id) == $patient->id) ? 'selected' : '' }}>
                                {{ $patient->full_name }} - {{ $patient->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Medication Details -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="product">Medication <span class="text-danger">*</span></label>
                            <input type="text" name="product" id="product" 
                                class="form-control @error('product') is-invalid @enderror"
                                value="{{ old('product', $prescription->product) }}" required
                                {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                            @error('product')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="quantity">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" id="quantity" 
                                class="form-control @error('quantity') is-invalid @enderror"
                                value="{{ old('quantity', $prescription->quantity) }}" required min="1"
                                {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Dosage Instructions -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="dosage">Dosage <span class="text-danger">*</span></label>
                            <input type="text" name="dosage" id="dosage" 
                                class="form-control @error('dosage') is-invalid @enderror"
                                value="{{ old('dosage', $prescription->dosage) }}" required
                                placeholder="e.g., 1 tablet">
                            @error('dosage')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="every">Every</label>
                            <input type="number" name="every" id="every" 
                                class="form-control @error('every') is-invalid @enderror"
                                value="{{ old('every', $prescription->every) }}" min="1">
                            @error('every')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label for="period">Period</label>
                            <select name="period" id="period" class="form-control @error('period') is-invalid @enderror">
                                <option value="">Select Period</option>
                                @foreach(['hour', 'hours', 'day', 'days', 'week', 'weeks'] as $period)
                                    <option value="{{ $period }}" 
                                        {{ old('period', $prescription->period) == $period ? 'selected' : '' }}>
                                        {{ ucfirst($period) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('period')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- As Needed Checkbox -->
                <div class="form-group mb-3">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="as_needed" name="as_needed" 
                            value="1" {{ old('as_needed', $prescription->as_needed) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="as_needed">Take as needed</label>
                    </div>
                </div>

                <!-- Directions -->
                <div class="form-group mb-3">
                    <label for="directions">Additional Directions <span class="text-danger">*</span></label>
                    <textarea name="directions" id="directions" rows="3" 
                        class="form-control @error('directions') is-invalid @enderror" 
                        required>{{ old('directions', $prescription->directions) }}</textarea>
                    @error('directions')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Submit Buttons -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" 
                            {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                        Update Prescription
                    </button>
                    <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize select2 for better patient selection
        $('#patient_id').select2({
            placeholder: 'Select Patient',
            allowClear: true
        });

        // Handle period field enabling/disabling based on 'every' field
        const everyInput = document.getElementById('every');
        const periodSelect = document.getElementById('period');

        everyInput.addEventListener('input', function() {
            periodSelect.disabled = !this.value;
            if (!this.value) {
                periodSelect.value = '';
            }
        });

        // Initial state
        periodSelect.disabled = !everyInput.value;
    });
</script>
@endpush
@endsection