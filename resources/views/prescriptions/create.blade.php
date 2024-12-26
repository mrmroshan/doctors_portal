@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card w-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Create New Prescription</h4>
            <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('prescriptions.store') }}" method="POST" id="prescriptionForm">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <!-- Prescription Date -->
                        <div class="form-group">
                            <label for="prescription_date">Prescription Date <span class="text-danger">*</span></label>
                            <input type="date" name="prescription_date" id="prescription_date" 
                                class="form-control @error('prescription_date') is-invalid @enderror"
                                value="{{ old('prescription_date', date('Y-m-d')) }}" required>
                            @error('prescription_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <!-- Patient Selection -->
                        <div class="form-group">
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
                    </div>
                </div>

                <!-- Medications Section -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Quantity</th>
                                <th>Dosage</th>
                                <th>Schedule</th>
                                <th>Directions</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="medications-container">
                            <!-- Medication Template -->
                            <tr class="medication-item" data-index="0">
                                <td>
                                    <!-- Medication Type Toggle -->
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

                                    <!-- Odoo Product Fields -->
                                    <div class="odoo-fields mt-2">
                                        <select name="medications[0][product_id]" class="form-control medication-select">
                                            <option value="">Select Medication</option>
                                            @foreach($medications as $medication)
                                                <option value="{{ $medication['id'] }}">
                                                {{ $medication['id'] }} - {{ $medication['name'] }} - {{  $medication['price'] }} 
                                                    ({{ $medication['default_code'] ?? 'N/A' }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Custom Medication Fields -->
                                    <div class="custom-fields mt-2" style="display: none;">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="text" name="medications[0][custom_name]" 
                                                    class="form-control" placeholder="Medication Name">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="medications[0][custom_strength]" 
                                                    class="form-control" placeholder="Strength/Form">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="medications[0][quantity]" 
                                        class="form-control" required min="1">
                                </td>
                                <td>
                                    <input type="text" name="medications[0][dosage]" 
                                        class="form-control" required placeholder="e.g., 1 tablet">
                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-4">
                                            <input type="number" name="medications[0][every]" 
                                                class="form-control" min="1">
                                        </div>
                                        <div class="col-4">
                                            <select name="medications[0][period]" class="form-control">
                                                <option value="">Select Period</option>
                                                <option value="hours">Hour(s)</option>
                                                <option value="days">Day(s)</option>
                                                <option value="weeks">Week(s)</option>
                                                <option value="months">Month(s)</option>
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <div class="form-check mt-2">
                                                <input type="checkbox" class="form-check-input" 
                                                    name="medications[0][as_needed]" id="as_needed_0">
                                                <label class="form-check-label" for="as_needed_0">As needed</label>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <textarea name="medications[0][directions]" class="form-control" 
                                        rows="2" placeholder="Additional instructions or directions"></textarea>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-medication">
                                        <i class="fas fa-trash"></i> Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-primary btn-sm" id="addMedication">
                        <i class="fas fa-plus"></i> Add Medication
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="form-group text-end mt-3">
                    <button type="submit" class="btn btn-primary">Create Prescription</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- New Patient Modal -->
<div class="modal fade" id="newPatientModal" tabindex="-1" aria-labelledby="newPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPatientModalLabel">Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="newPatientForm">
                @csrf

                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>
                    <!-- Add more patient fields as needed -->
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="savePatient">Save Patient</button>
            </div>
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




// Handle new patient form
$('#newPatientModal').on('shown.bs.modal', function() {
        $('#newPatientForm').trigger('reset');
    });

    $('#savePatient').click(function() {
        const formData = $('#newPatientForm').serialize();
        // Send AJAX request to save the new patient
        // and update the patient dropdown when successful
        $.ajax({
            url: '{{ route('api.patients.store') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Add the new patient to the patient dropdown
                    var newOption = $('<option>', {
                        value: response.patient.id,
                        text: response.patient.text
                    });
                    $('#patient_id').append(newOption);

                    // Close the modal
                    $('#newPatientModal').modal('hide');

                    // Display a success message
                    alert('Patient created successfully!');
                } else {
                    // Display an error message
                    alert(response.message);
                }
            },
            error: function(xhr, status, error) {
                // Handle the error
                console.error(error);
                alert('An error occurred while creating the patient.');
            }
        });
    });



</script>
@endpush