<!-- New Patient Modal -->
<div class="modal fade" id="newPatientModal" tabindex="-1" aria-labelledby="newPatientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newPatientModalLabel">Add New Patient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="newPatientForm" action="{{ route('patients.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <!-- First Name -->
                    <div class="form-group mb-3">
                        <label for="first_name">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>

                    <!-- Last Name -->
                    <div class="form-group mb-3">
                        <label for="last_name">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>

                    <!-- Phone -->
                    <div class="form-group mb-3">
                        <label for="phone">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" required>
                    </div>

                    <!-- Email -->
                    <div class="form-group mb-3">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <!-- Date of Birth -->
                    <div class="form-group mb-3">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth">
                    </div>

                    <!-- Gender -->
                    <div class="form-group mb-3">
                        <label for="gender">Gender</label>
                        <select class="form-control" id="gender" name="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <!-- Address -->
                    <div class="form-group mb-3">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Patient</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Handle form submission via AJAX
    $('#newPatientForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    // Add new patient to the select dropdown
                    const newOption = new Option(
                        `${response.patient.first_name} ${response.patient.last_name} - ${response.patient.phone}`,
                        response.patient.id,
                        true,
                        true
                    );
                    $('#patient_id').append(newOption).trigger('change');
                    
                    // Close modal and reset form
                    $('#newPatientModal').modal('hide');
                    $('#newPatientForm')[0].reset();
                    
                    // Show success message
                    alert('Patient added successfully');
                }
            },
            error: function(xhr) {
                let errors = xhr.responseJSON.errors;
                let errorMessage = 'Please correct the following errors:\n';
                
                for (let field in errors) {
                    errorMessage += `\n${errors[field].join('\n')}`;
                }
                
                alert(errorMessage);
            }
        });
    });

    // Reset form when modal is closed
    $('#newPatientModal').on('hidden.bs.modal', function() {
        $('#newPatientForm')[0].reset();
        $('#newPatientForm').find('.is-invalid').removeClass('is-invalid');
        $('#newPatientForm').find('.invalid-feedback').remove();
    });
});
</script>
@endpush