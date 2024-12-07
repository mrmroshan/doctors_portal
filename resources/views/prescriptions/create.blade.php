@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4>Create New Prescription</h4>
        </div>

        <div class="card-body">
            <form action="{{ route('prescriptions.store') }}" method="POST">
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning">
                        {{ session('warning') }}
                    </div>
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

                <!-- Patient Selection with Create Option -->
                <div class="form-group mb-3">
                    <label for="patient_id">Patient <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <select name="patient_id" id="patient_id" class="form-control @error('patient_id') is-invalid @enderror">
                            <option value="">Select Patient</option>
                            @foreach($patients as $patient)
                                <option value="{{ $patient->id }}" {{ old('patient_id') == $patient->id ? 'selected' : '' }}>
                                    {{ $patient->first_name }} {{ $patient->last_name }} - {{ $patient->phone }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#newPatientModal">
                            <i class="fas fa-plus"></i> New Patient
                        </button>
                    </div>
                    @error('patient_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Dynamic Medications List -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Medications</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="addMedication">
                            <i class="fas fa-plus"></i> Add Medication
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="medications-container">
                            <!-- Template for medication items -->
                            <div class="medication-item mb-3 border-bottom pb-3" data-index="0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label>Medication <span class="text-danger">*</span></label>
                                            <select name="medications[0][product]" class="form-control medication-select" required>
                                                <option value="">Select Medication</option>
                                                @foreach($medications as $medication)
                                                    <option value="{{ $medication['id'] }}" 
                                                        data-price="{{ $medication['list_price'] }}"
                                                        data-available="{{ $medication['qty_available'] }}">
                                                        {{ $medication['name'] }} 
                                                        ({{ $medication['default_code'] }}) - 
                                                        Stock: {{ $medication['qty_available'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label>Quantity <span class="text-danger">*</span></label>
                                            <input type="number" name="medications[0][quantity]" 
                                                class="form-control" required min="1">
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label>Dosage <span class="text-danger">*</span></label>
                                            <input type="text" name="medications[0][dosage]" 
                                                class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label>Every</label>
                                            <input type="number" name="medications[0][every]" 
                                                class="form-control period-every" min="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label>Period</label>
                                            <select name="medications[0][period]" class="form-control period-select">
                                                <option value="">Select Period</option>
                                                @foreach(['hour', 'hours', 'day', 'days', 'week', 'weeks'] as $period)
                                                    <option value="{{ $period }}">{{ ucfirst($period) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input type="checkbox" class="form-check-input" 
                                                name="medications[0][as_needed]" value="1">
                                            <label class="form-check-label">Take as needed</label>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label>Additional Directions <span class="text-danger">*</span></label>
                                            <textarea name="medications[0][directions]" class="form-control" 
                                                rows="2" required></textarea>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-danger remove-medication">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Create Prescription</button>
                    <a href="{{ route('prescriptions.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- New Patient Modal -->
<div class="modal fade" id="newPatientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="newPatientForm">
                    @csrf
                    <div class="form-group mb-3">
                        <label>First Name <span class="text-danger">*</span></label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Last Name <span class="text-danger">*</span></label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Phone <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="form-group mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group mb-3">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
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
    // Initialize select2 for patient
    $('#patient_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Patient',
        allowClear: true,
        width: '100%'
    });

    // Initialize select2 for first medication
    initializeMedicationSelect($('.medication-select').first());

    // Handle dynamic medication addition
    let medicationIndex = 0;
    
    $('#addMedication').click(function() {
        medicationIndex++;
        
        // Clone the first medication item
        let template = $('.medication-item').first().clone();
        
        // Clean up the cloned template
        template.find('.select2-container').remove();
        template.find('.text-warning, .text-danger').remove();
        
        // Update indices and clear values
        template.find('input, select, textarea').each(function() {
            let name = $(this).attr('name');
            if (name) {
                $(this).attr('name', name.replace('[0]', `[${medicationIndex}]`));
            }
            if ($(this).hasClass('medication-select')) {
                $(this).val(null);
            } else if ($(this).attr('type') === 'checkbox') {
                $(this).prop('checked', false);
            } else {
                $(this).val('');
            }
        });

        // Update data index
        template.attr('data-index', medicationIndex);
        
        // Append the template
        $('#medications-container').append(template);
        
        // Initialize select2 on the new medication select
        initializeMedicationSelect(template.find('.medication-select'));
    });

    // Handle remove medication
    $(document).on('click', '.remove-medication', function() {
        if ($('.medication-item').length > 1) {
            $(this).closest('.medication-item').remove();
        } else {
            alert('At least one medication is required.');
        }
    });

    // Handle medication selection change
    $(document).on('change', '.medication-select', function() {
        const selected = $(this).find(':selected');
        const available = selected.data('available');
        const quantityInput = $(this).closest('.medication-item')
            .find('input[name$="[quantity]"]');
        
        // Remove existing messages
        $(this).siblings('.text-warning, .text-danger').remove();
        
        if (!selected.val()) return;

        // Set max quantity and show warnings
        if (available !== undefined) {
            quantityInput.attr('max', available);
            
            if (available <= 0) {
                $(this).after('<div class="text-danger small">Out of stock</div>');
            } else if (available < 5) {
                $(this).after(`<div class="text-warning small">Low stock: ${available} units available</div>`);
            }
        }
    });

    // Form validation
    $('form').on('submit', function(e) {
        let isValid = true;

        // Validate medications exist
        if ($('.medication-item').length === 0) {
            alert('Please add at least one medication.');
            isValid = false;
        }

        // Validate period fields
        $('.period-every').each(function() {
            const every = $(this).val();
            const period = $(this).closest('.row').find('.period-select').val();
            
            if ((every && !period) || (!every && period)) {
                alert('Both "Every" and "Period" fields must be filled if one is provided.');
                isValid = false;
                return false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });

    // Handle new patient creation
    $('#savePatient').click(function() {
        const button = $(this);
        const form = $('#newPatientForm');
        const formData = new FormData(form[0]);
        
        button.prop('disabled', true);
        
        $.ajax({
            url: '/api/patients',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    // Add new patient to select
                    const newOption = new Option(
                        `${response.patient.full_name} - ${response.patient.phone}`,
                        response.patient.id,
                        true,
                        true
                    );
                    
                    $('#patient_id')
                        .find(`option[value='${response.patient.id}']`).remove()
                        .end()
                        .append(newOption)
                        .trigger('change');

                    // Reset and close
                    form[0].reset();
                    $('#newPatientModal').modal('hide');
                }
            },
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to create patient';
                alert(message);
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});

// Helper function to initialize medication select
function initializeMedicationSelect(element) {
    if (element.hasClass('select2-hidden-accessible')) {
        element.select2('destroy');
    }
    
    element.select2({
        theme: 'bootstrap-5',
        placeholder: 'Select Medication',
        allowClear: true,
        width: '100%'
    });
}
</script>
@endpush