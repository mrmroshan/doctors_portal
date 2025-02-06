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
                                    <!-- Replace the existing medication selection with new search input -->
                                    <div class="medication-search-container">
                                        <input type="text" 
                                            class="form-control medication-search" 
                                            placeholder="Start typing medication name (min. 4 characters)..."
                                            autocomplete="off">
                                        
                                        <!-- Hidden fields to store the selected medication data -->
                                        <input type="hidden" name="medications[0][type]" class="medication-type" value="custom">
                                        <input type="hidden" name="medications[0][product_id]" class="medication-product-id">
                                        <input type="hidden" name="medications[0][custom_name]" class="medication-custom-name">
                                        
                                        <!-- Results container -->
                                        <div class="medication-results" style="display: none;">
                                            <div class="list-group">
                                                <!-- Results will be populated here -->
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Selected medication display -->
                                    <div class="selected-medication mt-2" style="display: none;">
                                        <div class="alert alert-info mb-0">
                                            <i class="fas fa-check-circle"></i> 
                                            <span class="medication-name"></span>
                                            <button type="button" class="btn btn-link btn-sm float-end clear-medication">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" name="medications[0][quantity]" 
                                        class="form-control" min="1">
                                </td>
                                <td>
                                    <input type="text" name="medications[0][dosage]" 
                                        class="form-control" placeholder="e.g., 1 tablet">
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
<style>
.medication-search-container {
    position: relative;
}

.medication-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    max-height: 300px;
    overflow-y: auto;
    background: white;
    border: 1px solid rgba(0,0,0,.125);
    border-radius: 0.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.medication-result-item {
    cursor: pointer;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(0,0,0,.125);
}

.medication-result-item:last-child {
    border-bottom: none;
}

.medication-result-item:hover {
    background-color: #f8f9fa;
}

.selected-medication {
    margin-top: 0.5rem;
}

.selected-medication .alert {
    margin-bottom: 0;
    padding: 0.5rem 1rem;
}

.clear-medication {
    padding: 0;
    color: #dc3545;
    text-decoration: none;
}

.clear-medication:hover {
    color: #bd2130;
}
</style>
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
    let searchTimeout;

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

    // Form validation and submission
    $('#prescriptionForm').on('submit', function(e) {
        e.preventDefault();
        
        let isValid = true;
        const errors = [];

        // Validate prescription date
        if (!$('#prescription_date').val()) {
            isValid = false;
            errors.push('Prescription date is required');
            $('#prescription_date').addClass('is-invalid');
        }

        // Validate patient selection
        if (!$('#patient_id').val()) {
            isValid = false;
            errors.push('Patient selection is required');
            $('#patient_id').addClass('is-invalid');
        }

        // Validate medications
        $('.medication-item').each(function(index) {
            const item = $(this);
            const searchInput = item.find('.medication-search');
            const type = item.find('.medication-type').val();
            const productId = item.find('.medication-product-id').val();
            const quantity = item.find('input[name^="medications"][name$="[quantity]"]').val();
            const dosage = item.find('input[name^="medications"][name$="[dosage]"]').val();
            const directions = item.find('textarea[name^="medications"][name$="[directions]"]').val();

            // Check if medication is selected (either Odoo product or custom)
            if (type === 'odoo' && !productId) {
                isValid = false;
                errors.push(`Medication #${index + 1}: Please select a valid medication`);
                searchInput.addClass('is-invalid');
            } else if (type === 'custom' && !searchInput.val().trim()) {
                isValid = false;
                errors.push(`Medication #${index + 1}: Please enter a medication name`);
                searchInput.addClass('is-invalid');
            }

            // Validate quantity (now optional)
            if (quantity && quantity < 1) {
                isValid = false;
                errors.push(`Medication #${index + 1}: Quantity must be at least 1 if provided`);
                item.find('input[name^="medications"][name$="[quantity]"]').addClass('is-invalid');
            }

            // Validate dosage (now optional)
            if (dosage && !dosage.trim()) {
                isValid = false;
                errors.push(`Medication #${index + 1}: Dosage is required if provided`);
                item.find('input[name^="medications"][name$="[dosage]"]').addClass('is-invalid');
            }

            // Validate directions
            if (!directions) {
                isValid = false;
                errors.push(`Medication #${index + 1}: Directions are required`);
                item.find('textarea[name^="medications"][name$="[directions]"]').addClass('is-invalid');
            }
        });

        // If the form is valid, prepare medication data and submit
        if (isValid) {
            $('.medication-item').each(function() {
                const container = $(this);
                const searchInput = container.find('.medication-search');
                const typeInput = container.find('.medication-type');
                const productIdInput = container.find('.medication-product-id');
                const customNameInput = container.find('.medication-custom-name');
                
                // Check if this is a selected Odoo product or custom medication
                if (productIdInput.val()) {
                    // This is an Odoo product
                    typeInput.val('odoo');
                    customNameInput.val('');
                } else {
                    // This is a custom medication
                    typeInput.val('custom');
                    customNameInput.val(searchInput.val().trim());
                    productIdInput.val(''); // Ensure product_id is empty for custom medications
                }
            });
            
            // Submit the form
            this.submit();
        } else {
            // Show error messages
            let errorMessage = 'Please correct the following errors:\n';
            errors.forEach(error => {
                errorMessage += `\n- ${error}`;
            });
            alert(errorMessage);
        }
    });

    // Clear validation state on input
    $(document).on('input change', '.is-invalid', function() {
        $(this).removeClass('is-invalid');
    });

    // Handle medication search
    $(document).on('input', '.medication-search', function() {
        const searchInput = $(this);
        const resultsContainer = searchInput.siblings('.medication-results');
        const searchTerm = searchInput.val().trim();
        
        clearTimeout(searchTimeout);

        if (searchTerm.length < 4) {
            resultsContainer.hide();
            return;
        }

        searchTimeout = setTimeout(() => {
            searchMedications(searchTerm, searchInput);
        }, 300);
    });

    // Handle medication selection
    $(document).on('click', '.medication-result-item', function() {
        const item = $(this);
        const container = item.closest('.medication-search-container');
        const searchInput = container.find('.medication-search');
        
        // Update hidden fields for Odoo product
        container.find('.medication-type').val('odoo');
        container.find('.medication-product-id').val(item.data('id'));
        container.find('.medication-custom-name').val('');
        
        // Update the search input with the selected medication name
        searchInput.val(item.data('name'));
        
        // Hide the results dropdown
        item.closest('.medication-results').hide();
    });

    // When typing in search input, reset product ID if the text doesn't match selected product
    $(document).on('input', '.medication-search', function() {
        const searchInput = $(this);
        const container = searchInput.closest('.medication-search-container');
        const productIdInput = container.find('.medication-product-id');
        
        // If user modifies the text, assume it's now a custom medication
        productIdInput.val('');
        container.find('.medication-type').val('custom');
    });

    // Handle clear selection
    $(document).on('click', '.clear-medication', function() {
        const container = $(this).closest('td');
        const searchContainer = container.find('.medication-search-container');
        
        // Reset fields
        searchContainer.find('.medication-type').val('custom');
        searchContainer.find('.medication-product-id').val('');
        searchContainer.find('.medication-custom-name').val('');
        searchContainer.find('.medication-search').val('');
    });

    function searchMedications(term, searchInput) {
        $.ajax({
            url: '{{ route('api.medications.search') }}',
            method: 'GET',
            data: { search: term },
            success: function(response) {
                const resultsContainer = searchInput.siblings('.medication-results');
                const listGroup = resultsContainer.find('.list-group');
                
                listGroup.empty();
                
                if (response.success && response.data.length > 0) {
                    response.data.forEach(med => {
                        // Simplified display without quantity and price
                        listGroup.append(`
                            <div class="list-group-item medication-result-item" 
                                data-id="${med.id}"
                                data-name="${med.name}"
                                data-code="${med.default_code || ''}">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${med.name}</strong>
                                        ${med.default_code ? `<br><small class="text-muted">Code: ${med.default_code}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                    resultsContainer.show();
                } else {
                    listGroup.append(`
                        <div class="list-group-item text-muted">
                            No matching medications found. You can use this as a custom medication.
                        </div>
                    `);
                    resultsContainer.show();
                }
            },
            error: function(xhr, status, error) {
                console.error('Search failed:', error);
                const listGroup = resultsContainer.find('.list-group');
                listGroup.empty().append(`
                    <div class="list-group-item text-danger">
                        ${xhr.responseJSON?.error || 'Error searching medications. Please try again.'}
                    </div>
                `);
                resultsContainer.show();
            }
        });
    }
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