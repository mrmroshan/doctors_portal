@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <h1 class="mb-4">Dashboard</h1>

    <!-- Info Boxes -->
    <div class="row">
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-shopping-cart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">New Orders</span>
                    <span class="info-box-number">150</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-chart-bar"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Bounce Rate</span>
                    <span class="info-box-number">53<small>%</small></span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-warning"><i class="fas fa-user-plus"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">User Registrations</span>
                    <span class="info-box-number">44</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-3">
            <div class="info-box">
                <span class="info-box-icon bg-danger"><i class="fas fa-chart-pie"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Unique Visitors</span>
                    <span class="info-box-number">65</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main row -->
    <div class="row">
        <!-- Left col -->
        <section class="col-lg-7 connectedSortable">
            <!-- Custom tabs (Charts with tabs)-->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-pie mr-1"></i>
                        Sales
                    </h3>
                </div>
                <div class="card-body">
                    <div class="tab-content p-0">
                        <!-- Add your chart content here -->
                        <div class="chart tab-pane active" id="revenue-chart" style="position: relative; height: 300px;">
                            <canvas id="revenue-chart-canvas" height="300" style="height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TO DO List -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="ion ion-clipboard mr-1"></i>
                        To Do List
                    </h3>
                </div>
                <div class="card-body">
                    <ul class="todo-list" data-widget="todo-list">
                        <li>
                            <span class="handle">
                                <i class="fas fa-ellipsis-v"></i>
                                <i class="fas fa-ellipsis-v"></i>
                            </span>
                            <div class="icheck-primary d-inline ml-2">
                                <input type="checkbox" value="" name="todo1" id="todoCheck1">
                                <label for="todoCheck1"></label>
                            </div>
                            <span class="text">Design a nice theme</span>
                            <small class="badge badge-danger"><i class="far fa-clock"></i> 2 mins</small>
                        </li>
                        <!-- Add more todo items as needed -->
                    </ul>
                </div>
            </div>
        </section>

        <!-- Right col -->
        <section class="col-lg-5 connectedSortable">
            <!-- Map card -->
            <div class="card bg-gradient-primary">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        Visitors
                    </h3>
                </div>
                <div class="card-body">
                    <div id="world-map" style="height: 250px; width: 100%;"></div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="card bg-gradient-success">
                <div class="card-header border-0">
                    <h3 class="card-title">
                        <i class="far fa-calendar-alt"></i>
                        Calendar
                    </h3>
                </div>
                <div class="card-body pt-0">
                    <div id="calendar" style="width: 100%"></div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection