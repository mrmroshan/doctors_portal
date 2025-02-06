@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card w-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Edit Prescription</h4>
            <a href="{{ route('prescriptions.show', $prescription) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('prescriptions.update', $prescription) }}" method="POST" id="prescriptionForm">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <!-- Prescription Date -->
                        <div class="form-group">
                            <label for="prescription_date">Prescription Date <span class="text-danger">*</span></label>
                            <input type="date" name="prescription_date" id="prescription_date" 
                                class="form-control @error('prescription_date') is-invalid @enderror"
                                value="{{ old('prescription_date', $prescription->prescription_date->format('Y-m-d')) }}" 
                                {{ $prescription->sync_status === 'synced' ? 'readonly' : 'required' }}>
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
                                    class="form-control @error('patient_id') is-invalid @enderror"
                                    {{ $prescription->sync_status === 'synced' ? 'disabled' : 'required' }}>
                                    <option value="">Select Patient</option>
                                    @foreach($patients as $patient)
                                        <option value="{{ $patient->id }}" 
                                            {{ old('patient_id', $prescription->patient_id) == $patient->id ? 'selected' : '' }}>
                                            {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                                        </option>
                                    @endforeach
                                </select>
                                @if($prescription->sync_status !== 'synced')
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" 
                                        data-bs-target="#newPatientModal">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @endif
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
                            @foreach($prescription->medications as $index => $medication)
                                <tr class="medication-item" data-index="{{ $index }}">
                                    <td>
                                        <div class="medication-search-container">
                                            <input type="text" 
                                                class="form-control medication-search" 
                                                placeholder="Start typing medication name (min. 4 characters)..."
                                                value="{{ $medication->product_name }}"
                                                {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}
                                                autocomplete="off">
                                            
                                            <input type="hidden" 
                                                name="medications[{{ $index }}][type]" 
                                                class="medication-type" 
                                                value="{{ $medication->type }}">
                                            <input type="hidden" 
                                                name="medications[{{ $index }}][product_id]" 
                                                class="medication-product-id" 
                                                value="{{ $medication->type === 'odoo' ? $medication->product : '' }}">
                                            <input type="hidden" 
                                                name="medications[{{ $index }}][custom_name]" 
                                                class="medication-custom-name" 
                                                value="{{ $medication->type === 'custom' ? $medication->product : '' }}">
                                            
                                            <div class="medication-results" style="display: none;">
                                                <div class="list-group"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" 
                                            name="medications[{{ $index }}][quantity]" 
                                            class="form-control" 
                                            value="{{ $medication->quantity }}"
                                            {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }} 
                                            min="1">
                                    </td>
                                    <td>
                                        <input type="text" 
                                            name="medications[{{ $index }}][dosage]" 
                                            class="form-control" 
                                            value="{{ $medication->dosage }}"
                                            {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }} 
                                            placeholder="e.g., 1 tablet">
                                    </td>
                                    <td>
                                        <div class="row">
                                            <div class="col-4">
                                                <input type="number" 
                                                    name="medications[{{ $index }}][every]" 
                                                    class="form-control" 
                                                    value="{{ $medication->every }}"
                                                    {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }} 
                                                    min="1">
                                            </div>
                                            <div class="col-4">
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
                                            <div class="col-4">
                                                <div class="form-check mt-2">
                                                    <input type="checkbox" 
                                                        class="form-check-input" 
                                                        name="medications[{{ $index }}][as_needed]" 
                                                        id="as_needed_{{ $index }}"
                                                        {{ $medication->as_needed ? 'checked' : '' }}
                                                        {{ $prescription->sync_status === 'synced' ? 'disabled' : '' }}>
                                                    <label class="form-check-label" for="as_needed_{{ $index }}">
                                                        As needed
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <textarea 
                                            name="medications[{{ $index }}][directions]" 
                                            class="form-control" 
                                            rows="2" 
                                            {{ $prescription->sync_status === 'synced' ? 'readonly' : '' }}
                                            placeholder="Additional instructions or directions">{{ $medication->directions }}</textarea>
                                    </td>
                                    <td>
                                        @if($prescription->sync_status !== 'synced')
                                            <button type="button" class="btn btn-danger btn-sm remove-medication">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($prescription->sync_status !== 'synced')
                        <button type="button" class="btn btn-primary btn-sm" id="addMedication">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    @endif
                </div>

                <!-- Submit Button -->
                <div class="form-group text-end mt-3">
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

@if($prescription->sync_status !== 'synced')
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
@endif

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
/* Same styles as create view */
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
    let searchTimeout;

    // Initialize Select2 for patient selection
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    // Add new medication
    $('#addMedication').click(function() {
        const currentIndex = $('.medication-item').length;
        const newItem = createNewMedicationItem(currentIndex);
        $('#medications-container').append(newItem);
    });

    // Remove medication
    $(document).on('click', '.remove-medication', function() {
        if ($('.medication-item').length > 1) {
            $(this).closest('.medication-item').remove();
        } else {
            alert('At least one medication is required.');
        }
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
            $.ajax({
                url: '{{ route("api.medications.search") }}',
                method: 'GET',
                data: { search: searchTerm },
                success: function(response) {
                    const listGroup = resultsContainer.find('.list-group');
                    listGroup.empty();
                    
                    if (response.success && response.data.length > 0) {
                        response.data.forEach(med => {
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

    // Form validation
    $('#prescriptionForm').on('submit', function(e) {
        e.preventDefault();
        if (validateForm()) {
            this.submit();
        }
    });

    // Create new medication item
    function createNewMedicationItem(index) {
        const template = $('.medication-item').first().clone();
        
        // Clean up the template
        template.find('.is-invalid').removeClass('is-invalid');
        template.find('.medication-results').hide();
        
        // Clear all input values
        template.find('input[type="text"], input[type="number"], textarea').val('');
        template.find('.medication-product-id').val('');
        template.find('.medication-type').val('custom');
        
        // Update indices
        template.attr('data-index', index);
        template.find('[name]').each(function() {
            const name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace(/\[\d+\]/, `[${index}]`));
            }
        });

        return template;
    }

    // Form validation function
    function validateForm() {
        let isValid = true;
        const errors = [];

        // Check if patient is selected
        if (!$('#patient_id').val()) {
            isValid = false;
            $('#patient_id').addClass('is-invalid');
            errors.push('Please select a patient');
        }

        // Check if prescription date is set
        if (!$('#prescription_date').val()) {
            isValid = false;
            $('#prescription_date').addClass('is-invalid');
            errors.push('Please select prescription date');
        }

        // Check medications
        $('.medication-item').each(function(index) {
            const medicationInput = $(this).find('.medication-search');
            const quantityInput = $(this).find('input[name$="[quantity]"]');
            const dosageInput = $(this).find('input[name$="[dosage]"]');

            if (!medicationInput.val()) {
                isValid = false;
                medicationInput.addClass('is-invalid');
                errors.push(`Please enter medication name for item ${index + 1}`);
            }

            // Validate quantity (now optional)
            if (quantityInput.val() && quantityInput.val() < 1) {
                isValid = false;
                quantityInput.addClass('is-invalid');
                errors.push(`Please enter valid quantity for item ${index + 1}`);
            }

            // Validate dosage (now optional)
            if (dosageInput.val() && !dosageInput.val().trim()) {
                isValid = false;
                dosageInput.addClass('is-invalid');
                errors.push(`Please enter dosage for item ${index + 1}`);
            }
        });

        if (!isValid) {
            alert('Please correct the following errors:\n' + errors.join('\n'));
        }

        return isValid;
    }

    // Handle new patient form
    $('#newPatientModal').on('shown.bs.modal', function() {
        $('#newPatientForm').trigger('reset');
    });

    $('#savePatient').click(function() {
        const formData = $('#newPatientForm').serialize();
        $.ajax({
            url: '{{ route('api.patients.store') }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    const newOption = new Option(response.patient.text, response.patient.id, true, true);
                    $('#patient_id').append(newOption).trigger('change');
                    $('#newPatientModal').modal('hide');
                    alert('Patient created successfully!');
                } else {
                    alert(response.message);
                }
            },
            error: function(xhr) {
                console.error('Error creating patient:', xhr);
                alert('Failed to create patient. Please try again.');
            }
        });
    });
});
</script>
@endpush