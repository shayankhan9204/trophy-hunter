@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Species</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('specie.index') }}">Species</a></li>
                                    <li class="breadcrumb-item active">Species</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div>

                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <div class="float-right">
                                <a href="{{ route('specie.create') }}" class="btn btn-gradient-primary">Add Specie</a>
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
                                                <th>Formula</th>
                                                <th>Validation Rule</th>
                                                <th>Minimum Validation Rule</th>
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
                    data: 'formula',
                    name: 'formula'
                },
                {
                    data: 'validation_rule',
                    name: 'validation_rule'
                },
                {
                    data: 'min_validation_rule',
                    name: 'min_validation_rule'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ];

            var table = $('.sifu-datatable').DataTable({
                ajax: '{{ route('specie.index') }}',
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
