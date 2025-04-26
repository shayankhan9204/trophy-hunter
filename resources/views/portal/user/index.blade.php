@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Users</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('user.index') }}">User Management</a></li>
                                    <li class="breadcrumb-item active">Users</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div>

                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <div class="float-right">
                                @can('user-add')
                                <a href="{{ route('user.create') }}" class="btn btn-gradient-primary">Add User</a>
                                @endcan
                            </div>
                        </div>
                    </div>

                </div>
                {{--  <!-- end page title end breadcrumb -->  --}}

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="staff-list-form">
                                    <div class="row sifu-filter-area">
                                        <div class="col-md-12">
                                            <div class="form-group mb-3">
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Search </span>
                                                    </div>
                                                    <input class="form-control" type="text" id="search" name="search"
                                                        placeholder="Enter Name, Email">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                                <a href="{{ route('user.index') }}" class="btn btn-danger" type="reset">Reset</a>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="sifu-datatable table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
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

            <div class="modal fade" id="reset-password" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title mt-0" id="mySmallModalLabel">Reset Password</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        </div>
                        <div class="modal-body">
                            <form action="" method="POST" class="users-reset-pass">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <div class="input-group mb-3">
                                                    <span class="resetpass-form-icon">
                                                        <a href="javascript:void(0);" id="togglePassword"><i class="fas fa-eye"></i></a>
                                                    </span>
                                                <input type="password" name="password" class="form-control" id="respassword" placeholder="Current Password" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <a class="btn btn-gradient-primary" id="resetpass-btn" type="submit">Reset Password</a>
                            </form>
                            <form action="{{ route('user.reset.password') }}" method="POST" class="users-change-pass">
                                @csrf
                                <input type="hidden" id="user-id" name="id" value="" />
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <input class="form-control" type="text" name="new_password" placeholder="New Password">
                                        </div>
                                    </div>
                                </div>
                                <a class="btn btn-danger" id="backpwd-btn" href="javascript:void(0);"> Back </a>
                                <button class="btn btn-gradient-primary" type="submit">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- end page content -->
    </div>
@endsection


@section('script')
    <script>
        $(document).ready(function() {
            // Handle password modal opening
            $(document).on("click", ".view-password", function() {
                let password = $(this).data("password");
                $("#respassword").val(password); // Set password field in modal
            });

            $('#reset-password').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var userId = button.data('id'); // Extract user ID from data-id attribute
                $('#user-id').val(userId); // Set the user ID in the hidden input field
            });
        });


        $(document).ready(function() {
            const userColumns = [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'role',
                    name: 'role'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ];

            {{--var table = initializeDataTable('.sifu-datatable', '{{ route('user.index') }}', userColumns);--}}

            var table = $('.sifu-datatable').DataTable({
                ajax: '{{ route('user.index') }}',
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


            // Submit filter form
                $('.staff-list-form').on('submit', function(e) {
                    e.preventDefault();
                                    $('#search-loaderr').show();

                    $.ajax({
                        url: '{{ route('user.index') }}',
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

                // Function to initialize DataTable
                function initializeDataTable(selector, url, columns) {
                    return $(selector).DataTable({
                        processing: true,
                        serverSide: false,
                        ajax: {
                            url: url,
                            method: 'GET',
                            data: function(d) {
                                d.search = $('#search').val();

                            }
                        },
                        columns: columns,
                        order: [
                            [0, 'desc']
                        ],
                        cache: false
                    });
                }

        });
    </script>
@endsection
