@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create New Prescription</h4>
            <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('prescriptions.store') }}" method="POST" id="prescriptionForm">
                @csrf
                
                <!-- Prescription Date -->
                <div class="form-group mb-3">
                    <label for="prescription_date">Prescription Date <span class="text-danger">*</span></label>
                    <input type="date" name="prescription_date" id="prescription_date" 
                        class="form-control @error('prescription_date') is-invalid @enderror"
                        value="{{ old('prescription_date', date('Y-m-d')) }}" required>
                    @error('prescription_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Patient Selection -->
                <div class="form-group mb-3">
                    <label for="patient_id">Patient <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="patient_id" id="patient_id" 
                            class="form-control @error('patient_id') is-invalid @enderror" required>
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" 
                                    {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" 
                            data-bs-target="#newPatientModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    @error('patient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Medications Section -->
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h5 class="mb-0">Medications</h5>
                        <button type="button" class="btn btn-primary btn-sm" id="addMedication">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="medications-container">
                            <!-- Medication Template -->
                            <div class="medication-item mb-4 pb-3 border-bottom" data-index="0">
                                <!-- Medication Type Toggle -->
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label class="d-block mb-2">Medication Type <span class="text-danger">*</span></label>
                                        <div class="btn-group medication-type-toggle" role="group">
                                            <input type="radio" class="btn-check" name="medications[0][type]" 
                                                value="odoo" id="type-odoo-0" checked>
                                            <label class="btn btn-outline-primary" for="type-odoo-0">
                                                <i class="fas fa-box-open"></i> Odoo Product
                                            </label>

                                            <input type="radio" class="btn-check" name="medications[0][type]" 
                                                value="custom" id="type-custom-0">
                                            <label class="btn btn-outline-primary" for="type-custom-0">
                                                <i class="fas fa-pills"></i> Custom
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Medication Fields -->
                                <div class="medication-fields">
                                    <!-- Odoo Product Fields -->
                                    <div class="odoo-fields">
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label>Select Medication <span class="text-danger">*</span></label>
                                                <select name="medications[0][product_id]" class="form-control medication-select">
                                                    <option value="">Select Medication</option>
                                                    @foreach($medications as $medication)
                                                        <option value="{{ $medication['id'] }}">
                                                            {{ $medication['name'] }}
                                                            ({{ $medication['default_code'] ?? 'N/A' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Custom Medication Fields -->
                                    <div class="custom-fields" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Medication Name <span class="text-danger">*</span></label>
                                                <input type="text" name="medications[0][custom_name]" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Strength/Form</label>
                                                <input type="text" name="medications[0][custom_strength]" 
                                                    class="form-control" placeholder="e.g., 500mg tablet">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Common Fields -->
                                    <div class="common-fields">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Quantity <span class="text-danger">*</span></label>
                                                <input type="number" name="medications[0][quantity]" 
                                                    class="form-control" required min="1">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Dosage <span class="text-danger">*</span></label>
                                                <input type="text" name="medications[0][dosage]" 
                                                    class="form-control" required placeholder="e.g., 1 tablet">
                                            </div>
                                        </div>
                                        
                                        <!-- Schedule Fields -->
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label>Every</label>
                                                <input type="number" name="medications[0][every]" 
                                                    class="form-control" min="1">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label>Period</label>
                                                <select name="medications[0][period]" class="form-control">
                                                    <option value="">Select Period</option>
                                                    <option value="hours">Hour(s)</option>
                                                    <option value="days">Day(s)</option>
                                                    <option value="weeks">Week(s)</option>
                                                    <option value="months">Month(s)</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label>&nbsp;</label>
                                                <div class="form-check mt-2">
                                                    <input type="checkbox" class="form-check-input" 
                                                        name="medications[0][as_needed]" id="as_needed_0">
                                                    <label class="form-check-label" for="as_needed_0">As needed</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label>Additional Directions</label>
                                                <textarea name="medications[0][directions]" class="form-control" 
                                                    rows="2" placeholder="Additional instructions or directions"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Remove Button -->
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group text-end">
                    <button type="submit" class="btn btn-primary">Create Prescription</button>
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
    // Initialize Select2 for patient selection
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Initialize first medication select
    initializeMedicationSelect($('.medication-select').first());

    let medicationIndex = 0;

    // Handle medication type toggle
    $(document).on('change', '.medication-item input[type="radio"]', function() {
        const medicationItem = $(this).closest('.medication-item');
        const type = $(this).val();
        handleMedicationTypeToggle(medicationItem, type);
    });

    // Add new medication
    $('#addMedication').click(function() {
        medicationIndex++;
        const newItem = createNewMedicationItem(medicationIndex);
        $('#medications-container').append(newItem);
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
});

// Initialize Select2 for medication selection
function initializeMedicationSelect(element) {
    element.select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Select Medication'
    });
}

// Handle medication type toggle
function handleMedicationTypeToggle(medicationItem, type) {
    const odooFields = medicationItem.find('.odoo-fields');
    const customFields = medicationItem.find('.custom-fields');

    if (type === 'custom') {
        odooFields.hide().find('select').prop('required', false);
        customFields.show().find('input[name$="[custom_name]"]').prop('required', true);
        // Clear Odoo selection
        odooFields.find('select').val(null).trigger('change');
    } else {
        odooFields.show().find('select').prop('required', true);
        customFields.hide().find('input').prop('required', false);
        // Clear custom fields
        customFields.find('input').val('');
    }
}

// Create new medication item
function createNewMedicationItem(index) {
    const template = $('.medication-item').first().clone();
    
    // Clean up the template
    template.find('.select2-container').remove();
    template.find('.is-invalid').removeClass('is-invalid');
    template.find('.invalid-feedback').remove();
    
    // Clear all input values
    template.find('input[type="text"], input[type="number"], textarea').val('');
    template.find('select').val('');
    template.find('input[type="checkbox"]').prop('checked', false);
    
    // Update IDs and names
    template.find('[name], [id], [for]').each(function() {
        const element = $(this);
        ['name', 'id', 'for'].forEach(attr => {
            if (element.attr(attr)) {
                element.attr(attr, element.attr(attr).replace(/\[\d+\]|[0-9]+$/, match => {
                    return match.includes('[') ? `[${index}]` : index;
                }));
            }
        });
    });

    // Reset radio buttons
    template.find('input[type="radio"][value="odoo"]').prop('checked', true);
    template.find('input[type="radio"][value="custom"]').prop('checked', false);

    // Reset fields visibility
    template.find('.odoo-fields').show();
    template.find('.custom-fields').hide();

    return template;
}

// Update form validation
function validateForm() {
    let isValid = true;
    
    $('.medication-item').each(function() {
        const item = $(this);
        const type = item.find('input[type="radio"]:checked').val();
        
        // Validate type-specific fields
        if (type === 'odoo') {
            const select = item.find('.medication-select');
            if (!select.val()) {
                select.addClass('is-invalid');
                isValid = false;
            }
        } else {
            const customName = item.find('input[name$="[custom_name]"]');
            if (!customName.val()) {
                customName.addClass('is-invalid');
                isValid = false;
            }
        }

        // Validate required common fields
        const requiredFields = ['quantity', 'dosage'];
        requiredFields.forEach(field => {
            const input = item.find(`input[name$="[${field}]"]`);
            if (!input.val()) {
                input.addClass('is-invalid');
                isValid = false;
            }
        });
    });

    return isValid;
}

// Handle stock availability
$(document).on('change', '.medication-select', function() {
    const selected = $(this).find(':selected');
    const available = selected.data('available');
    const quantityInput = $(this).closest('.medication-item').find('input[name$="[quantity]"]');
    
    // Remove any existing messages
    $(this).siblings('.stock-message').remove();
    
    if (available !== undefined) {
        if (available <= 0) {
            $(this).after('<div class="text-danger small mt-1 stock-message">Out of stock</div>');
            quantityInput.attr('max', 0);
        } else {
            quantityInput.attr('max', available);
            if (available < 5) {
                $(this).after(`<div class="text-warning small mt-1 stock-message">Low stock: ${available} remaining</div>`);
            }
        }
    }
});
</script>
@endpush