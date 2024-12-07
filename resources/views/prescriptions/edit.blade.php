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
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('prescriptions.update', $prescription) }}" method="POST">
                @csrf
                @method('PUT')
                
                <!-- Prescription Date -->
                <div class="form-group mb-3">
                    <label for="prescription_date">Prescription Date <span class="text-danger">*</span></label>
                    <input type="date" name="prescription_date" id="prescription_date" 
                        class="form-control @error('prescription_date') is-invalid @enderror"
                        value="{{ old('prescription_date', $prescription->prescription_date->format('Y-m-d')) }}" 
                        required {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                    @error('prescription_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Patient Selection -->
                <div class="form-group mb-3">
                    <label for="patient_id">Patient <span class="text-danger">*</span></label>
                    <select name="patient_id" id="patient_id" class="form-control select2" required 
                            {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" 
                                {{ old('patient_id', $prescription->patient_id) == $patient->id ? 'selected' : '' }}>
                                {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Medications -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Medications</h5>
                        @if($prescription->sync_status !== 'synced')
                            <button type="button" class="btn btn-sm btn-primary" id="addMedication">
                                <i class="fas fa-plus"></i> Add Medication
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="medications-container">
                            @foreach($prescription->medications as $index => $medication)
                                <div class="medication-item mb-3 border-bottom pb-3" data-index="{{ $index }}">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Medication <span class="text-danger">*</span></label>
                                                <select name="medications[{{ $index }}][product]" 
                                                        class="form-control medication-select" 
                                                        required 
                                                        {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                    <option value="">Select Medication</option>
                                                    @foreach($medications as $med)
                                                        <option value="{{ $med['id'] }}" 
                                                            {{ old("medications.$index.product", $medication->product) == $med['id'] ? 'selected' : '' }}
                                                            data-name="{{ $med['name'] }}"
                                                            data-code="{{ $med['default_code'] }}">
                                                            {{ $med['name'] }} ({{ $med['default_code'] }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label>Quantity <span class="text-danger">*</span></label>
                                                <input type="number" 
                                                    name="medications[{{ $index }}][quantity]" 
                                                    class="form-control" 
                                                    value="{{ old("medications.$index.quantity", $medication->quantity) }}"
                                                    required min="1"
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Dosage <span class="text-danger">*</span></label>
                                                <input type="text" 
                                                    name="medications[{{ $index }}][dosage]" 
                                                    class="form-control" 
                                                    value="{{ old("medications.$index.dosage", $medication->dosage) }}"
                                                    required
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Every</label>
                                                <input type="number" 
                                                    name="medications[{{ $index }}][every]" 
                                                    class="form-control period-every" 
                                                    value="{{ old("medications.$index.every", $medication->every) }}"
                                                    min="1"
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Period</label>
                                                <select name="medications[{{ $index }}][period]" 
                                                        class="form-control period-select"
                                                        {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                    <option value="">Select Period</option>
                                                    @foreach(['hour', 'hours', 'day', 'days', 'week', 'weeks'] as $period)
                                                        <option value="{{ $period }}" 
                                                            {{ old("medications.$index.period", $medication->period) == $period ? 'selected' : '' }}>
                                                            {{ ucfirst($period) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-check mb-3">
                                                <input type="checkbox" 
                                                    class="form-check-input" 
                                                    name="medications[{{ $index }}][as_needed]" 
                                                    value="1" 
                                                    {{ old("medications.$index.as_needed", $medication->as_needed) ? 'checked' : '' }}
                                                    {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                <label class="form-check-label">Take as needed</label>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label>Additional Directions <span class="text-danger">*</span></label>
                                                <textarea name="medications[{{ $index }}][directions]" 
                                                    class="form-control" 
                                                    rows="2" 
                                                    required
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>{{ old("medications.$index.directions", $medication->directions) }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                    @if($prescription->sync_status !== 'synced')
                                        <button type="button" class="btn btn-sm btn-danger remove-medication">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary" 
                            {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                        Update Prescription
                    </button>
                    <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
    // Initialize select2 for patient
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Patient',
        allowClear: true,
        width: '100%'
    });

    // Initialize select2 for existing medication selects
    initializeMedicationSelect();

    // Handle dynamic medication addition
    let medicationIndex = {{ count($prescription->medications) }};
    const medications = @json($medications);
    
    $('#addMedication').click(function() {
        const template = `
            <div class="medication-item mb-3 border-bottom pb-3" data-index="${medicationIndex}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Medication <span class="text-danger">*</span></label>
                            <select name="medications[${medicationIndex}][product]" 
                                    class="form-control medication-select" 
                                    required>
                                <option value="">Select Medication</option>
                                ${medications.map(med => `
                                    <option value="${med.id}" 
                                            data-name="${med.name}"
                                            data-code="${med.default_code}">
                                        ${med.name} (${med.default_code})
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input type="number" 
                                name="medications[${medicationIndex}][quantity]" 
                                class="form-control" 
                                required min="1">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label>Dosage <span class="text-danger">*</span></label>
                            <input type="text" 
                                name="medications[${medicationIndex}][dosage]" 
                                class="form-control" 
                                required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label>Every</label>
                            <input type="number" 
                                name="medications[${medicationIndex}][every]" 
                                class="form-control period-every" 
                                min="1">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group mb-3">
                            <label>Period</label>
                            <select name="medications[${medicationIndex}][period]" 
                                    class="form-control period-select">
                                <option value="">Select Period</option>
                                ${['hour', 'hours', 'day', 'days', 'week', 'weeks'].map(period => `
                                    <option value="${period}">${period.charAt(0).toUpperCase() + period.slice(1)}</option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="form-check mb-3">
                            <input type="checkbox" 
                                class="form-check-input" 
                                name="medications[${medicationIndex}][as_needed]" 
                                value="1">
                            <label class="form-check-label">Take as needed</label>
                        </div>
                        <div class="form-group mb-3">
                            <label>Additional Directions <span class="text-danger">*</span></label>
                            <textarea name="medications[${medicationIndex}][directions]" 
                                class="form-control" 
                                rows="2" 
                                required></textarea>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger remove-medication">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
        `;

        $('#medications-container').append(template);
        initializeMedicationSelect();
        medicationIndex++;
    });

    // Handle medication removal
    $(document).on('click', '.remove-medication', function() {
        $(this).closest('.medication-item').remove();
    });

    function initializeMedicationSelect() {
        $('.medication-select').not('.select2-hidden-accessible').select2({
            theme: 'bootstrap-5',
            placeholder: 'Select Medication',
            allowClear: true,
            width: '100%'
        });
    }
});
</script>
@endpush