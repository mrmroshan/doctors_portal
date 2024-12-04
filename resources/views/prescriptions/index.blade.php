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
                <div class="col-md-4">
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
                           value="{{ request('date') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Prescriptions List -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Sync Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <!-- Replace the existing table body with this updated version -->
<tbody>
    @forelse($prescriptions as $prescription)
        <tr>
            <td>{{ $prescription->prescription_date }}</td>
            <td>
                <a href="{{ route('patients.show', $prescription->patient) }}">
                    {{ $prescription->patient->full_name }}
                </a>
            </td>
            <td>
                <!-- Display all medications -->
                {{ $prescription->medications_list }}
            </td>
            <td>
                <!-- Show count of medications -->
                {{ $prescription->medications->count() }} 
                {{ Str::plural('medication', $prescription->medications->count()) }}
            </td>
            <td>
                @switch($prescription->sync_status)
                    @case('synced')
                        <span class="badge bg-success">Synced</span>
                        @break
                    @case('error')
                        <span class="badge bg-danger" title="{{ $prescription->sync_error }}">Error</span>
                        @break
                    @default
                        <span class="badge bg-warning text-dark">Pending</span>
                @endswitch
            </td>
            <td>
                <div class="btn-group">
                    <a href="{{ route('prescriptions.show', $prescription) }}" 
                       class="btn btn-sm btn-info" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    @if($prescription->sync_status !== 'synced')
                        <a href="{{ route('prescriptions.edit', $prescription) }}" 
                           class="btn btn-sm btn-primary" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
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
    // This will be implemented when we work on the sync system
    alert('Sync retry functionality will be implemented in Phase 4');
}
</script>
@endpush
@endsection