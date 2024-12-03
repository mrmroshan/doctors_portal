@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Info Boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-user-md"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Doctors</span>
                    <span class="info-box-number">{{ $doctorsCount ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-pills"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Prescriptions</span>
                    <span class="info-box-number">{{ $prescriptionsCount ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-sync"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Syncs</span>
                    <span class="info-box-number">{{ $pendingSyncs ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Sync Errors</span>
                    <span class="info-box-number">{{ $syncErrors ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <section class="col-lg-7 connectedSortable">
            <!-- Sync Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-sync mr-1"></i>
                        Sync Status
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Add sync status table or chart here -->
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-history mr-1"></i>
                        Recent Activity
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <!-- Add activity items here -->
                    </ul>
                </div>
            </div>
        </section>

        <!-- Right col -->
        <section class="col-lg-5 connectedSortable">
            <!-- System Status -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-server mr-1"></i>
                        System Status
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Add system status indicators here -->
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bolt mr-1"></i>
                        Quick Actions
                    </h3>
                </div>
                <div class="card-body">
                    <!-- Add quick action buttons here -->
                </div>
            </div>
        </section>
    </div>
</div>
@endsection