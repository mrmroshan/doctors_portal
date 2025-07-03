@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Doctor Dashboard</h1>



    <!-- Info Boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">My Patients</span>
                    <span class="info-box-number">{{ $patientCount ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-file-medical"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Today's Prescriptions</span>
                    <span class="info-box-number">{{ $todayPrescriptions ?? 0 }}</span>
                </div>
            </div>
        </div>
        <!--
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Orders</span>
                    <span class="info-box-number">{{ $pendingOrders ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-capsules"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Available Medications</span>
                    <span class="info-box-number">{{ $medicationsCount ?? 0 }}</span>
                </div>
            </div>
        </div>
        -->
    </div>

    <!-- Right col -->
    <section class="col-lg-12 connectedSortable">
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-bolt mr-1"></i>
                    Quick Actions
                </h3>
            </div>
            <div class="card-body">

                <div class="d-grid gap-2">
                    <a href="{{ route('prescriptions.create') }}" class="btn btn-primary btn-block mb-6">
                        <i class="fas fa-file-medical mr-2"></i> New Prescription
                    </a>

                    <a href="{{ route('patients.create') }}" class="btn btn-success btn-block mb-6">
                        <i class="fas fa-user-plus mr-2"></i> Add New Patient
                    </a>
                    <!--
                    <a href="#" class="btn btn-info btn-block">
                        <i class="fas fa-pills mr-2"></i> View Medications
                    </a>
                    -->
                </div>
            </div>
        </div>

        <!-- Sync Status -->
        <!--
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sync mr-1"></i>
                    Order Sync Status
                </h3>
            </div>
            <div class="card-body">
            </div>
        </div>
        -->
    </section>

    


    <!-- Main row -->
    <div class="row">

        <!-- Left col -->
        <section class="col-lg-6 connectedSortable">

            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-file-medical mr-1"></i>
                        Recent Prescriptions
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('prescriptions.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New Prescription
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPrescriptions as $prescription)
                                <tr>
                                    <td>{{ $prescription->patient->first_name }} {{ $prescription->patient->last_name }}</td>
                                    <td>{{ $prescription->created_at->format('Y-m-d') }}</td>
                                    <td>                                        
                                        @if ($prescription->order_status === 'Pending')
                                            <span class="badge badge-warning">Pending</span>
                                        @elseif ($prescription->order_status === 'Draft')
                                            <span class="badge badge-warning">Draft</span>
                                        @elseif ($prescription->order_status === 'Done')
                                            <span class="badge badge-success">Done</span>
                                        @elseif ($prescription->order_status === 'Confirmed')
                                            <span class="badge badge-success">Confirmed</span>
                                        @elseif ($prescription->order_status === 'Cancelled')
                                            <span class="badge badge-danger">Cancelled</span>
                                        @elseif ($prescription->order_status === 'Error')
                                            <span class="badge badge-danger">Error</span>
                                        @else
                                            <span class="badge badge-warning">{{ $prescription->order_status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('prescriptions.show', $prescription->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent prescriptions found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            
        </section>

        <!-- Right col -->
        <section class="col-lg-6 connectedSortable">

            <!-- Recent Patients -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-1"></i>
                        Recent Patients
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add New Patient
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    {{-- <th>Last Visit</th> --}}
                                    <th>Prescriptions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentPatients as $patient)
                                <tr>
                                    <td>{{ $patient->first_name }} {{ $patient->last_name }}</td>
                                    {{-- <td>{{ $patient->lastVisit ? $patient->lastVisit->format('Y-m-d') : 'N/A' }}</td> --}}
                                    <td>{{ $patient->prescriptions->count() }}</td>
                                    <td>
                                        <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No recent patients found.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </section>





        
    </div>
</div>
@endsection