@extends('layouts.app')

@section('content')
@if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
<div class="container">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2>Patients</h2>
        </div>
        <div class="col-md-6 text-end  d-flex justify-content-end">
            <a href="{{ route('patients.create') }}" class="btn btn-primary">Add New Patient</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('patients.index') }}" method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="{{ request('search') }}">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ route('patients.index', ['sort' => 'first_name', 'direction' => $sortField === 'first_name' && $sortDirection === 'asc' ? 'desc' : 'asc']) }}">
                                Name
                                @if($sortField === 'first_name')
                                    <i class="fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                @endif
                            </a>
                        </th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Date of Birth</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($patients as $patient)
                    <tr>
                        <td>{{ $patient->first_name }} {{ $patient->last_name }}</td>
                        <td>{{ $patient->email }}</td>
                        <td>{{ $patient->phone }}</td>
                        <td>{{ $patient->date_of_birth->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('patients.edit', $patient) }}" class="btn btn-sm btn-primary">Edit</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <style>
    .pagination svg {
        width: 20px;
        height: 20px;
    }
    .pagination span, .pagination a {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.875rem !important;
    }
</style>
            

            {{ $patients->links() }}
            <!-- {{ $patients->links('vendor.pagination.custom') }} -->


        </div>
    </div>
</div>
@endsection