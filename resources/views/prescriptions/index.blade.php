@extends('layouts.app')

@section('content')
<div class="container">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Prescriptions</h2>
        <a href="{{ route('prescriptions.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Prescription
        </a>
    </div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('prescriptions.index') }}" method="GET" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search patient name..." 
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="sync_status" class="form-control">
                    <option value="">All Sync Status</option>
                    <option value="pending" {{ request('sync_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="synced" {{ request('sync_status') == 'synced' ? 'selected' : '' }}>Synced</option>
                    <option value="error" {{ request('sync_status') == 'error' ? 'selected' : '' }}>Error</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="date" class="form-control" 
                       value="{{ request('date') }}"
                       placeholder="Filter by date">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-secondary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

    @if(session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif

    <!-- Prescriptions List -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Medications</th>
                        <th>Sync Status</th>
                        <th>Odoo Order</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prescriptions as $prescription)
                        <tr>
                            <td>{{ $prescription->id }}</td>
                            <td>{{ $prescription->prescription_date->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ route('patients.show', $prescription->patient) }}">
                                    {{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}
                                </a>
                            </td>
                            <td>
                                @foreach($prescription->medications as $medication)
                                    <div class="mb-1">
                                        @if($medication->type === 'odoo')
                                            {{ $medication->product_name ?? 'Unknown Product' }}
                                            @if($medication->product_code)
                                                <small class="text-muted">({{ $medication->product_code }})</small>
                                            @endif
                                        @else
                                            {{ $medication->product }}
                                            <small class="text-muted">(Custom)</small>
                                        @endif
                                        <br>
                                        <small class="text-muted">
                                            Qty: {{ $medication->quantity }} - 
                                            {{ $medication->dosage }}
                                            @if($medication->every && $medication->period)
                                                every {{ $medication->every }} {{ $medication->period }}
                                            @endif
                                            @if($medication->as_needed)
                                                (as needed)
                                            @endif
                                        </small>
                                    </div>
                                @endforeach
                            </td>
                            <td>
                                @switch($prescription->sync_status)
                                    @case('synced')
                                        <span class="badge bg-success">Synced</span>
                                        @break
                                    @case('error')
                                        <span class="badge bg-danger" 
                                              title="{{ $prescription->sync_error }}">
                                            Error
                                        </span>
                                        @break
                                    @default
                                        <span class="badge bg-warning text-dark">Pending</span>
                                @endswitch
                            </td>
                            <td>
                                @if($prescription->sync_status === 'synced' && $prescription->odoo_order_name)
                                    <div>
                                        <strong>{{ $prescription->odoo_order_name }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $prescription->odoo_order_id }}</small>
                                    </div>
                                @elseif($prescription->sync_status === 'pending')
                                    <span class="badge bg-warning text-dark">Pending Sync</span>
                                @elseif($prescription->sync_status === 'error')
                                    <span class="badge bg-danger">Sync Failed</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('prescriptions.show', $prescription) }}" 
                                       class="btn btn-sm btn-info" 
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($prescription->sync_status !== 'synced')
                                        <a href="{{ route('prescriptions.edit', $prescription) }}" 
                                           class="btn btn-sm btn-primary" 
                                           title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('prescriptions.destroy', $prescription) }}" 
                                              method="POST" 
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="btn btn-sm btn-danger" 
                                                    title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this prescription?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if($prescription->sync_status === 'error')
                                        <button type="button" 
                                                class="btn btn-sm btn-warning" 
                                                title="Retry Sync"
                                                onclick="retrySync({{ $prescription->id }})">
                                            <i class="fas fa-sync"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No prescriptions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $prescriptions->withQueryString()->links() }}
    </div>
</div>

@push('scripts')
<script>
function retrySync(prescriptionId) {
    const button = $(event.target).closest('button');
    button.prop('disabled', true)
          .html('<i class="fas fa-spinner fa-spin"></i>');

    $.ajax({
        url: `/prescriptions/${prescriptionId}/resync`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Sync failed: ' + response.message);
                button.prop('disabled', false)
                      .html('<i class="fas fa-sync"></i>');
            }
        },
        error: function(xhr) {
            alert('Error occurred during sync. Please try again.');
            button.prop('disabled', false)
                  .html('<i class="fas fa-sync"></i>');
        }
    });
}
</script>
@endpush
@endsection