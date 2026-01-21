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
                                <a  href="javascript:void(0)"
                                onclick="exportTeams()"
                                    class="btn btn-gradient-primary">
                                    Export Teams
                                </a>

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

                                <form method="GET" action="{{ route('team.index') }}">
                                    <div class="d-flex mb-3 justify-content-between align-items-end">
                                        <div class="w-75">
                                            <label for="event_id" class="form-label">Select Event</label>
                                            <select name="event_id" id="event_id" class="form-control" onchange="this.form.submit()">
                                                <option value="">All Events</option>
                                                @foreach($events as $event)
                                                    <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                                        {{ $event->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <a href="javascript:void(0)" class="btn btn-danger"
                                           onclick="confirmDelete({{ request('event_id') ?? 'null' }})">
                                            Delete All
                                        </a>
                                    </div>
                                </form>

                                <form id="delete-form" method="POST" action="{{ route('delete.event.teams') }}" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="event_id" id="delete_event_id">
                                </form>

                                <div class="table-responsive">
                                    <table class="sifu-datatable table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Team UID</th>
                                            <th>Team Name</th>
                                            <th>Team Memebers</th>
                                            <th>Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($teams as $key => $team)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $team->team_uid ?? '' }}</td>
                                                <td>{{ $team->name ?? '' }}</td>
                                                <td>{{ $team->anglers->count() ?? '-' }}</td>
                                                <td>
                                                    <span class="uitooltip sifu-ticon" data-toggle="tooltip"
                                                          data-placement="top" data-original-title="Edit">
                                                      <a href="{{ route('team.edit' , ['id' => $team->id]) }}"
                                                         class="mr-2"><i class="fas fa-pencil-alt"></i></a>
                                                    </span>

                                                    <span class="uitooltip sifu-ticon" data-toggle="tooltip"
                                                          data-placement="top" data-original-title="Delete">
                                                        <a href="#" class="mr-2 text-danger" onclick="singleConfirmDelete({{ $team->id }})">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
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
                                <button type="button" class="btn-close" data-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="csv_file" class="form-label">Select Event</label>
                                    <select name="event_id" class="form-control" required>
                                        <option disabled selected value="">Select event</option>
                                        @foreach($events as $event)
                                            <option value="{{ $event->id }}">
                                                {{ $event->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

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
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
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
        function exportTeams () {
            const eventId = document.getElementById('event_id').value;

            if (!eventId) {
                alert('Please select an event first.');
                return;
            }

            const url = "{{ route('teams.export') }}" + '?event_id=' + encodeURIComponent(eventId);

            window.location.href = url;
        }

        function singleConfirmDelete(teamId) {
            const eventSelect = document.querySelector('[name="event_id"]');
            const selectedEventId = eventSelect.value;

            if (!selectedEventId) {
                alert('Please select an event first.');
                return;
            }

            const confirmed = confirm('Are you sure you want to remove this team from the selected event?');
            if (confirmed) {
                const baseUrl = "{{ url('team-detach') }}";
                const url = `${baseUrl}?team_id=${teamId}&event_id=${selectedEventId}`;
                window.location.href = url;
            }
        }
    </script>

    <script>
        function confirmDelete(eventId) {
            if (!eventId) {
                alert("Please select an event first.");
                return;
            }

            if (confirm("Are you sure you want to delete all teams for this event?")) {
                document.getElementById('delete_event_id').value = eventId;
                document.getElementById('delete-form').submit();
            }
        }
    </script>

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
