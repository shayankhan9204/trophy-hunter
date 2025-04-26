@extends('layouts.portal.app')
@section('content')

    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Teams</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('team.index') }}">Team Management</a>
                                    </li>
                                    <li class="breadcrumb-item active">Teams</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div>

                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <div class="float-right">
                                <button class="btn btn-gradient-primary" id="openCsvUploadModal">
                                    Upload CSV
                                </button>

                                <a href="{{ route('download.sample.teams.csv') }}" class="btn btn-dark">
                                    Download Sample CSV
                                </a>
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
                                            <th>Team UID</th>
                                            <th>Team Name</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($teams as $key => $team)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $team->team_uid ?? '' }}</td>
                                                <td>{{ $team->name ?? '' }}</td>
                                                <td>
                                                    <span class="uitooltip sifu-ticon" data-toggle="tooltip"
                                                          data-placement="top" data-original-title="Edit">
                                                      <a href="{{ route('team.edit' , ['id' => $team->id]) }}"
                                                         class="mr-2"><i class="fas fa-pencil-alt"></i></a>
                                                    </span>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr class="text-center">
                                                <td colspan="4">No Teams Yet!</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->

            </div>

            <!-- CSV Upload Modal -->
            <div class="modal fade" id="csvUploadModal" tabindex="-1" role="dialog"
                 aria-labelledby="csvUploadModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('upload.team') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Upload Anglers CSV</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Choose CSV File</label>
                                    <input type="file" class="form-control" name="csv_file" id="csv_file" accept=".csv"
                                           required>
                                    <small class="text-muted">CSV Format: Team Name, Angler Name, Category, Email,
                                        Phone</small>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary">Upload</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
        <!-- end page content -->
    </div>

@endsection

@section('script')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const openBtn = document.getElementById('openCsvUploadModal');
            const modalElement = document.getElementById('csvUploadModal');

            if (openBtn && modalElement) {
                openBtn.addEventListener('click', function () {
                    const csvModal = new bootstrap.Modal(modalElement);
                    csvModal.show();
                });
            }
        });
    </script>

@endsection
