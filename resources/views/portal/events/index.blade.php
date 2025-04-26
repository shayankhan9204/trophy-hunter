@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Events</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event
                                            Management</a>
                                    </li>
                                    <li class="breadcrumb-item active">Events</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <div class="float-right">
                                <a href="{{ route('event.create') }}" class="btn btn-gradient-primary">Add Event</a>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">

                                <div class="table-responsive">
                                    <table class="sifu-datatable table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Start Time</th>
                                            <th>End Time</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>

                                        <tbody>

                                        </tbody>
                                    </table>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->

            </div><!-- container -->

        </div>
    </div>
@endsection

@section('script')

    <script>
        $(document).ready(function () {
            const userColumns = [{
                data: 'id',
                name: 'id'
            },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'date',
                    name: 'date'
                },
                {
                    data: 'location',
                    name: 'location'
                },
                {
                    data: 'start_time',
                    name: 'start_time'
                },
                {
                    data: 'end_time',
                    name: 'end_time'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ];


            var table = $('.sifu-datatable').DataTable({
                ajax: '{{ route('event.index') }}',
                columns: userColumns,
                lengthChange: true,
                searching: false,
                dom: '<"row"<"col-sm-6"l><"col-sm-6"B>>' + 'frtip',
                buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                order: [
                    [0, 'desc']
                ]
            });

            table.buttons().container().appendTo('.dataTables_wrapper .col-sm-6:eq(1)');
        });

    </script>

@endsection

