@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Prescription</h4>
            <div>
                <span class="badge bg-{{ $prescription->sync_status === 'synced' ? 'success' : ($prescription->sync_status === 'error' ? 'danger' : 'warning') }} me-2">
                    {{ ucfirst($prescription->sync_status) }}
                </span>
                <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="card-body">
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            <form action="{{ route('prescriptions.update', $prescription) }}" method="POST" id="prescriptionForm">
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
                    <select name="patient_id" id="patient_id" 
                        class="form-control @error('patient_id') is-invalid @enderror" 
                        required {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" 
                                {{ old('patient_id', $prescription->patient_id) == $patient->id ? 'selected' : '' }}>
                                {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                            </option>
                        @endforeach
                    </select>
                    @error('patient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Medications Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">Medications</h5>
                        @if($prescription->sync_status !== 'synced')
                            <button type="button" class="btn btn-primary btn-sm" id="addMedication">
                                <i class="fas fa-plus"></i> Add Medication
                            </button>
                        @endif
                    </div>
                    <div class="card-body">
                        <div id="medications-container">
                            @foreach($prescription->medications as $index => $medication)
                                <div class="medication-item mb-4 pb-3 border-bottom" data-index="{{ $index }}">
                                    <!-- Medication Type Toggle -->
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <label class="d-block mb-2">Medication Type <span class="text-danger">*</span></label>
                                            <div class="btn-group medication-type-toggle" role="group">
                                                <input type="radio" class="btn-check" 
                                                    name="medications[{{ $index }}][type]" 
                                                    value="odoo" 
                                                    id="type-odoo-{{ $index }}" 
                                                    {{ $medication->type === 'odoo' ? 'checked' : '' }}
                                                    {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-primary" for="type-odoo-{{ $index }}">
                                                    <i class="fas fa-box-open"></i> Odoo Product
                                                </label>

                                                <input type="radio" class="btn-check" 
                                                    name="medications[{{ $index }}][type]" 
                                                    value="custom" 
                                                    id="type-custom-{{ $index }}"
                                                    {{ $medication->type === 'custom' ? 'checked' : '' }}
                                                    {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                <label class="btn btn-outline-primary" for="type-custom-{{ $index }}">
                                                    <i class="fas fa-pills"></i> Custom
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Medication Fields -->
                                    <div class="medication-fields">
                                        <!-- Odoo Product Fields -->
                                        <div class="odoo-fields" {{ $medication->type === 'custom' ? 'style=display:none' : '' }}>
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label>Select Medication <span class="text-danger">*</span></label>
                                                    <select name="medications[{{ $index }}][product_id]" 
                                                        class="form-control medication-select"
                                                        {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                        <option value="">Select Medication</option>
                                                        @foreach($medications as $med)
                                                            <option value="{{ $med['id'] }}" 
                                                                {{ $medication->type === 'odoo' && $medication->product == $med['id'] ? 'selected' : '' }}>
                                                                {{ $med['name'] }} ({{ $med['default_code'] }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Custom Medication Fields -->
                                        <div class="custom-fields" {{ $medication->type === 'odoo' ? 'style=display:none' : '' }}>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label>Medication Name <span class="text-danger">*</span></label>
                                                    <input type="text" 
                                                        name="medications[{{ $index }}][custom_name]" 
                                                        class="form-control"
                                                        value="{{ $medication->type === 'custom' ? $medication->product : '' }}"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>Strength/Form</label>
                                                    <input type="text" 
                                                        name="medications[{{ $index }}][custom_strength]" 
                                                        class="form-control"
                                                        value="{{ $medication->custom_strength ?? '' }}"
                                                        placeholder="e.g., 500mg tablet"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Common Fields -->
                                        <div class="common-fields">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label>Quantity <span class="text-danger">*</span></label>
                                                    <input type="number" 
                                                        name="medications[{{ $index }}][quantity]" 
                                                        class="form-control" 
                                                        required 
                                                        min="1"
                                                        value="{{ $medication->quantity }}"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label>Dosage <span class="text-danger">*</span></label>
                                                    <input type="text" 
                                                        name="medications[{{ $index }}][dosage]" 
                                                        class="form-control" 
                                                        required 
                                                        placeholder="e.g., 1 tablet"
                                                        value="{{ $medication->dosage }}"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                                </div>
                                            </div>
                                            
                                            <!-- Schedule Fields -->
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label>Every</label>
                                                    <input type="number" 
                                                        name="medications[{{ $index }}][every]" 
                                                        class="form-control" 
                                                        min="1"
                                                        value="{{ $medication->every }}"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label>Period</label>
                                                    <select name="medications[{{ $index }}][period]" 
                                                        class="form-control"
                                                        {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                        <option value="">Select Period</option>
                                                        @foreach(['hours', 'days', 'weeks', 'months'] as $period)
                                                            <option value="{{ $period }}" 
                                                                {{ $medication->period === $period ? 'selected' : '' }}>
                                                                {{ ucfirst($period) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <label>&nbsp;</label>
                                                    <div class="form-check mt-2">
                                                        <input type="checkbox" 
                                                            class="form-check-input" 
                                                            name="medications[{{ $index }}][as_needed]" 
                                                            id="as_needed_{{ $index }}"
                                                            {{ $medication->as_needed ? 'checked' : '' }}
                                                            {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                        <label class="form-check-label" for="as_needed_{{ $index }}">As needed</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <label>Additional Directions</label>
                                                    <textarea 
                                                        name="medications[{{ $index }}][directions]" 
                                                        class="form-control" 
                                                        rows="2" 
                                                        placeholder="Additional instructions or directions"
                                                        {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}>{{ $medication->directions }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    @if($prescription->sync_status !== 'synced')
                                        <div class="text-end mt-3">
                                            <button type="button" class="btn btn-danger btn-sm remove-medication">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group text-end">
                    <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary" 
                            {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                        Update Prescription
                    </button>
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
    // Initialize medication type toggles for existing items
    $('.medication-type-toggle input[type="radio"]').each(function() {
        const medicationItem = $(this).closest('.medication-item');
        if ($(this).is(':checked')) {
            handleMedicationTypeToggle(medicationItem, $(this).val());
        }
    });

    // Initialize Select2 for patient selection
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize Select2 for existing medication selects
    $('.medication-select').each(function() {
        initializeMedicationSelect($(this));
    });

    // Handle medication type toggle
    $(document).on('change', '.medication-item input[type="radio"]', function() {
        const medicationItem = $(this).closest('.medication-item');
        const type = $(this).val();
        handleMedicationTypeToggle(medicationItem, type);
    });

    // Add new medication
    $('#addMedication').click(function() {
        const currentIndex = $('.medication-item').length;
        const newItem = createNewMedicationItem(currentIndex);
        $('#medications-container').append(newItem);
        
        // Initialize Select2 on the new medication select
        initializeMedicationSelect(newItem.find('.medication-select'));
    });

    // Remove medication
    $(document).on('click', '.remove-medication', function() {
        if ($('.medication-item').length > 1) {
            $(this).closest('.medication-item').remove();
        } else {
            alert('At least one medication is required.');
        }
    });

    // Form validation
    $('#prescriptionForm').on('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            alert('Please fill in all required fields.');
        }
    });

    // Clear validation state on input
    $(document).on('input change', '.is-invalid', function() {
        $(this).removeClass('is-invalid');
    });

    // Handle stock availability changes
    $(document).on('change', '.medication-select', function() {
        handleStockAvailability($(this));
    });
});

// Handle medication type toggle
function handleMedicationTypeToggle(medicationItem, type) {
    const odooFields = medicationItem.find('.odoo-fields');
    const customFields = medicationItem.find('.custom-fields');

    if (type === 'custom') {
        odooFields.hide().find('select').prop('required', false);
        customFields.show().find('input[name$="[custom_name]"]').prop('required', true);
        odooFields.find('select').val(null).trigger('change');
    } else {
        odooFields.show().find('select').prop('required', true);
        customFields.hide().find('input').prop('required', false);
        customFields.find('input').val('');
    }
}

// Create new medication item
function createNewMedicationItem(index) {
    const template = $('.medication-item').first().clone();
    
    // Clean up Select2 and form elements
    template.find('.select2').remove();
    template.find('select')
        .removeAttr('data-select2-id')
        .find('option')
        .removeAttr('data-select2-id');
    
    // Reset form values
    template.find('input:not([type="radio"]), textarea').val('');
    template.find('select').val('');
    template.find('.is-invalid').removeClass('is-invalid');
    template.find('.stock-message').remove();
    
    // Update indices and names
    template.attr('data-index', index);
    template.find('input, select, textarea').each(function() {
        const name = $(this).attr('name');
        if (name) {
            $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
        }
        $(this).removeAttr('data-select2-id');
    });
    
    // Update IDs and labels
    template.find('[id]').each(function() {
        const oldId = $(this).attr('id');
        const newId = oldId.replace(/\d+$/, index);
        $(this).attr('id', newId);
        template.find(`label[for="${oldId}"]`).attr('for', newId);
    });

    // Reset medication type toggle
    template.find('.medication-type-toggle input[type="radio"][value="odoo"]')
        .prop('checked', true);
    handleMedicationTypeToggle(template, 'odoo');

    return template;
}

// Initialize Select2 for medication selection
function initializeMedicationSelect(element) {
    element.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Medication'
    });
}

// Form validation
function validateForm() {
    let isValid = true;
    $('.is-invalid').removeClass('is-invalid');
    
    // Validate each medication item
    $('.medication-item').each(function() {
        const item = $(this);
        const type = item.find('input[name$="[type]"]:checked').val();
        
        if (type === 'odoo') {
            if (!item.find('select[name$="[product_id]"]').val()) {
                item.find('select[name$="[product_id]"]').addClass('is-invalid');
                isValid = false;
            }
        } else {
            if (!item.find('input[name$="[custom_name]"]').val()) {
                item.find('input[name$="[custom_name]"]').addClass('is-invalid');
                isValid = false;
            }
        }
        
        ['quantity', 'dosage'].forEach(field => {
            const input = item.find(`[name$="[${field}]"]`);
            if (!input.val()) {
                input.addClass('is-invalid');
                isValid = false;
            }
        });
    });
    
    return isValid;
}

// Handle stock availability
function handleStockAvailability(selectElement) {
    const selected = selectElement.find(':selected');
    const available = selected.data('available');
    const quantityInput = selectElement.closest('.medication-item')
        .find('input[name$="[quantity]"]');
    
    selectElement.siblings('.stock-message').remove();
    
    if (available !== undefined) {
        if (available <= 0) {
            selectElement.after('<div class="text-danger small mt-1 stock-message">Out of stock</div>');
            quantityInput.attr('max', 0);
        } else {
            quantityInput.attr('max', available);
            if (available < 5) {
                selectElement.after(`<div class="text-warning small mt-1 stock-message">Low stock: ${available} remaining</div>`);
            }
        }
    }
}
</script>
@endpush