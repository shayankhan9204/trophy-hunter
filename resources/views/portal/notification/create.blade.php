@extends('layouts.portal.app')


@section('content')

    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Add Notification</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('notification.index') }}">Notifications List</a>
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
                                <form class="add-notification-form" action="{{ route('notification.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Event</label>
                                                <select class="form-control custom-select" name="event_id">
                                                    <option value="" selected disabled>Select Event</option>
                                                    @foreach($events as $event)
                                                        <option value="{{ $event->id }}">{{ $event->name ?? '' }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Notification Title</label>
                                                <input type="text" class="form-control" placeholder="Enter Title" name="title">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Notification Message</label>
                                                <textarea name="message" class="form-control" rows="4" placeholder="Enter Notification Message"></textarea>
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
