@extends('layouts.portal.app')

@section('content')

    <div class="page-wrapper sifu-cform sifu-dashboard">
        <div class="page-content">

            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Dashboard</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">
                                            Trophy Hunter</a></li>
                                    </li>
                                    <li class="breadcrumb-item active">Dashboard</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="media">
                                    <div class="media-body align-self-center">
                                        <h1 class="mt-0 mb-1" style="font-size:30px;color:black;">Dashboard Under
                                            Maintenance</h1>
                                        <p class="text-muted mb-0 font-14 w-50">Our dashboard is currently undergoing
                                            maintenance to enhance performance and improve your experience. We
                                            appreciate your patience and will have it back online soon.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- <div class="row">
                        <div class="col-xl-9">
                            <div class="card">
                                <div class="card-body">
                                    <div class="media">
                                        <img src="{{ asset('images/logo-sm.png') }}" alt="" class="thumb-md rounded-circle mr-3">
                                        <div class="media-body align-self-center">
                                            <h4 class="mt-0 mb-1">Welcome, Sifututor Admin!</h4>
                                            <p class="text-muted mb-0 font-14">SifuTutor, your ultimate companion in mastering new skills and knowledge! SifuTutor seamlessly connects you with expert tutors in various subjects, offering personalized and interactive sessions tailored to your unique learning needs. </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row first-box">
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="icon-info">
                                                        <img src="{{ asset('images/income.png') }}">
                                                    </div>
                                                    <h3 class="mb-0">Income</h3>
                                                    <p>This Month</p>
                                                    <h4>RM 150.00</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="icon-info">
                                                        <img src="{{ asset('images/expense.png') }}">
                                                    </div>
                                                    <h3 class="mb-0">Expense</h3>
                                                    <p>This Month</p>
                                                    <h4>RM 150.00</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="icon-info">
                                                        <img src="{{ asset('images/income-collected.png') }}">
                                                    </div>
                                                    <h3 class="mb-0">Income Collected</h3>
                                                    <p>This Month</p>
                                                    <h4>RM 150.00</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12">
                                                    <div class="icon-info">
                                                        <img src="{{ asset('images/invoice.png') }}">
                                                    </div>
                                                    <h3 class="mb-0">Avg per Invoice</h3>
                                                    <p>This Month</p>
                                                    <h4>RM 150.00</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row second-box">
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex align-items-end justify-content-between">
                                                    <h3>Active <br>Staffs</h3>
                                                    <p>2</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex align-items-end justify-content-between">
                                                    <h3>Active <br>Tutors</h3>
                                                    <p>2</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex align-items-end justify-content-between">
                                                    <h3>Active <br>Students</h3>
                                                    <p>2</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-3">
                                    <div class="card crm-data-card">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 d-flex align-items-end justify-content-between">
                                                    <h3>This month <br>new students</h3>
                                                    <p>2</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="header-title mt-0">Revenue vs Expenses</h4>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                     <div id="apex_line1" class="apex-charts"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="header-title mt-0">Expenses Category</h4>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                     <div id="apex_pie1" class="apex-charts"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="header-title mt-0">Cash Flow</h4>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                     <div id="apex_mixed2" class="apex-charts"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h4 class="header-title mt-0">Unpaid Sales Invoice</h4>
                                            <div class="row">
                                                <div class="col-lg-12">
                                                     <div id="apex_line3" class="apex-charts"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="header-title mt-0 mb-3">Last User Activity</h4>
                                    <div class="crm-dash-activity">
                                        <div class="activity">
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                            <div class="activity-info">
                                                <div class="icon-info-activity">
                                                    <i class="mdi mdi-account-arrow-left-outline bg-soft-success"></i>
                                                </div>
                                                <div class="activity-info-text">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="m-0">Sifututor Admin viewed View dashboard at home page</h6>
                                                    </div>
                                                    <p class="text-muted mt-1">2025-02-14 15:37:15</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->

            </div>

        </div>

    </div>

@endsection
