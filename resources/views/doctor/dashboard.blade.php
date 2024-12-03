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
    </div>

    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <section class="col-lg-7 connectedSortable">
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
                                <!-- Add prescription items here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Patient List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-users mr-1"></i>
                        Recent Patients
                    </h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Last Visit</th>
                                    <th>Prescriptions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Add patient items here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right col -->
        <section class="col-lg-5 connectedSortable">
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
                        <a href="{{ route('prescriptions.create') }}" class="btn btn-primary btn-lg mb-2">
                            <i class="fas fa-file-medical mr-2"></i> New Prescription
                        </a>
                        <a href="{{ route('patients.create') }}" class="btn btn-success btn-lg mb-2">
                            <i class="fas fa-user-plus mr-2"></i> Add New Patient
                        </a>
                        <a href="#" class="btn btn-info btn-lg">
                            <i class="fas fa-pills mr-2"></i> View Medications
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sync Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sync mr-1"></i>
                        Order Sync Status
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Add sync status indicators here -->
                </div>
            </div>
        </section>
    </div>
</div>
@endsection