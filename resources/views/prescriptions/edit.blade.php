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
                        value="{{ old('prescription_date', $prescription->prescription_date) }}" 
                        required {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                    @error('prescription_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Patient Selection -->
                <div class="form-group mb-3">
                    <label for="patient_id">Patient</label>
                    <select name="patient_id" id="patient_id" class="form-control" required 
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
                                                <input type="text" 
                                                    name="medications[{{ $index }}][product]" 
                                                    class="form-control" 
                                                    value="{{ old("medications.$index.product", $medication->product) }}"
                                                    required
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
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
                                        <button type="button" class="btn btn-sm btn-danger remove-medication">Remove</button>
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
    // Initialize select2
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Patient',
        allowClear: true,
        width: '100%'
    });

    // Handle dynamic medication addition
    let medicationIndex = {{ count($prescription->medications) - 1 }};
    
    $('#addMedication').click(function() {
        medicationIndex++;
        const template = $('.medication-item').first().clone();
        
        // Update all name attributes with new index
        template.find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('[0]', `[${medicationIndex}]`));
            }
            $(this).val(''); // Clear values
            $(this).prop('checked', false); // Uncheck checkboxes
        });
        
        template.attr('data-index', medicationIndex);
        $('#medications-container').append(template);
    });

    // Handle medication removal
    $(document).on('click', '.remove-medication', function() {
        if ($('.medication-item').length > 1) {
            $(this).closest('.medication-item').remove();
        } else {
            alert('At least one medication is required.');
        }
    });

    // Handle period validation
    $(document).on('change', '.period-every, .period-select', function() {
        const row = $(this).closest('.row');
        const every = row.find('.period-every').val();
        const period = row.find('.period-select').val();
        
        if (every && !period) {
            row.find('.period-select').prop('required', true);
        } else if (!every && period) {
            row.find('.period-every').prop('required', true);
        } else {
            row.find('.period-select, .period-every').prop('required', false);
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        // Validate period fields
        let isValid = true;
        $('.period-every').each(function() {
            const row = $(this).closest('.row');
            const every = $(this).val();
            const period = row.find('.period-select').val();
            
            if ((every && !period) || (!every && period)) {
                isValid = false;
                alert('Both "Every" and "Period" fields must be filled if one is provided.');
                e.preventDefault();
                return false;
            }
        });

        return isValid;
    });
});
</script>
@endpush