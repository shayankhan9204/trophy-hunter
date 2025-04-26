@extends('layouts.portal.app')


@section('content')

    <div class="page-wrapper sifu-cform">

        <!-- Page Content-->
        <div class="page-content">

            <div class="container-fluid">

                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Add Notification</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Notifications List</a>
                                    </li>
                                    <li class="breadcrumb-item active">Add Notification</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="add-notification-form">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Notification Type</label>
                                                <select class="form-control custom-select" name="notification_type">
                                                    <option value="">Select Notification Type</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Progress Report Month</label>
                                                <select class="form-control custom-select" name="progress_report_month">
                                                    <option value="">Select Month</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Tutor</label>
                                                <select class="form-control custom-select select2" name="tutor">
                                                    <option value="">Select Tutor</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Student</label>
                                                <select class="form-control custom-select select2" name="student">
                                                    <option value="">Select Student</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Subject</label>
                                                <select class="form-control custom-select select2" name="subject">
                                                    <option value="">Select Subject</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                </form>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->

            </div>
        </div>
        <!-- end page content -->
    </div>

@endsection
