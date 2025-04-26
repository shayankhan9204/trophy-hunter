@extends('layouts.portal.app')

@section('content')

    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">

                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Notifications</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);">App
                                            Management</a></li>
                                    <li class="breadcrumb-item active">Notifications</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->

                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="notification-form">
                                    <div class="row sifu-filter-area">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">User UID</span>
                                                    </div>
                                                    <input type="text" id="filterUserUID" name="user_uid" class="form-control" placeholder="Filter by User UID">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">User  Name</span>
                                                    </div>
                                                    <input type="text" id="filterUserName" name="user_name" class="form-control" placeholder="Filter by User Name">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Date</span>
                                                    </div>
                                                    <input type="date" id="filterDate" name="notify_date" class="form-control">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">
                                                    Search
                                                </button>
                                                <a class="btn btn-danger"  href="{{ route('notifications') }}"> Reset </a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="sifu-datatable table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>User UID</th>
                                            <th>User Name</th>
                                            <th>User Type</th>
                                            <th>Title</th>
                                            <th>Message</th>
                                            <th>Notify Date</th>
                                            {{--                                    <th>Action</th>--}}
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <!-- Data will be inserted here by DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->

            </div>

        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            const notiColumns = [{
                data: 'id',
                name: 'id'
                },
                {
                    data: 'user_uid',
                    name: 'user_uid'
                },
                {
                    data: 'user_name',
                    name: 'user_name'
                },
                {
                    data: 'type',
                    name: 'type'
                },
                {
                    data: 'title',
                    name: 'title'
                },
                {
                    data: 'message',
                    name: 'message'
                },
                {
                    data: 'notify_date',
                    name: 'notify_date'
                },
                // {
                //     data: 'action',
                //     name: 'action',
                //     orderable: false,
                //     searchable: false
                // }
            ];


            {{--var table = initializeDataTable('.sifu-datatable', '{{ route('notifications') }}', notiColumns);--}}

            var table = $('.sifu-datatable').DataTable({
                ajax: '{{ route('notifications') }}',
                columns: notiColumns,
                lengthChange: true,
                searching: false,
                dom: '<"row"<"col-sm-6"l><"col-sm-6"B>>' + 'frtip',
                                buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                order: [
                    [0, 'desc']
                ],
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'user_uid', name: 'user_uid'},
                    {data: 'user_name', name: 'user_name'},
                    {data: 'type', name: 'type'},
                    {data: 'title', name: 'title'},
                    {data: 'message', name: 'message'},
                    {data: 'notify_date', name: 'notify_date'}
                ]
            });

            $('#filterUserUID').on('keyup', function () {
                table.column(1).search(this.value).draw(); // User UID column index = 1
            });

            $('#filterUserName').on('keyup', function () {
                table.column(2).search(this.value).draw(); // User Name column index = 2
            });

            $('#filterDate').on('change', function () {
                table.column(6).search(this.value).draw(); // Notify Date column index = 6
            });

            table.buttons().container().appendTo('.dataTables_wrapper .col-sm-6:eq(1)');


            // Submit filter form
            $('.notification-form').on('submit', function(e) {
                e.preventDefault();
                $('#search-loaderr').show();

                $.ajax({
                    url: '{{ route('notifications') }}',
                    method: 'GET',
                    data: $(this).serialize(),
                    success: function(response) {
                        var table = $('.sifu-datatable').DataTable();
                        table.clear();
                        if (response.data && response.data.length > 0) {
                            table.rows.add(response.data);
                        } else {
                            table.rows.add([]);
                        }

                        $('#search-loaderr').hide();
                        table.draw();
                    },
                    error: function(error) {
                        $('#search-loaderr').hide();
                        console.log('Error:', error);
                    }
                });
            });


        });
    </script>
@endsection
