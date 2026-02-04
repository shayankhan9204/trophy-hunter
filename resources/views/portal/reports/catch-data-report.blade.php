@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Catch Data Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('team.ranking.report') }}">Reports</a></li>
                                    <li class="breadcrumb-item active">Catch Data Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="{{ route('catch.data.report') }}" class="mb-4">
                                    <div class="row align-items-end">
                                        <div class="col-md-6">
                                            <label for="event_id">Select Event</label>
                                            <select name="event_id" id="event_id" class="form-control" onchange="this.form.submit()">
                                                <option value="">-- Choose an event --</option>
                                                @foreach($events as $ev)
                                                    <option value="{{ $ev->id }}" {{ ($eventId ?? '') == $ev->id ? 'selected' : '' }}>
                                                        {{ $ev->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <button type="submit" class="btn btn-gradient-primary">Load Report</button>
                                        </div>
                                    </div>
                                </form>

                                @if($eventId && $event)
                                    <div class="mb-3">
                                        <h4>{{ $event->name ?? '' }}</h4>
                                        <p class="text-muted small">Data grouped by: Team → Species → Fork Length → Angler → Date/Time</p>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="text-muted">Paginated for faster loading</span>
                                        <button type="button" id="delete-selected" class="btn btn-danger">Delete Selected</button>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="catch-data-table" class="table table-bordered table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">
                                                        <input type="checkbox" id="select-all-catches" title="Select all on this page">
                                                    </th>
                                                    <th>Team #</th>
                                                    <th>Team Name</th>
                                                    <th>Specie</th>
                                                    <th>Fork Length (mm)</th>
                                                    <th>Angler</th>
                                                    <th>Date/Time</th>
                                                    <th>Tag Type</th>
                                                    <th>Tag No</th>
                                                    <th>Line Class</th>
                                                    <th>Points</th>
                                                    <th>Measure Photo</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                @endif

                                @if($eventId && !$event)
                                    <div class="alert alert-warning">Event not found.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    @if($eventId && $event)
    <script>
        $(document).ready(function () {
            var table = $('#catch-data-table').DataTable({
                ajax: {
                    url: '{{ route('catch.data.report') }}',
                    data: { event_id: '{{ $eventId }}' }
                },
                columns: [
                    { data: 'select', name: 'select', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'team_uid', name: 'team_uid' },
                    { data: 'team_name', name: 'team_name' },
                    { data: 'specie_name', name: 'specie_name' },
                    { data: 'fork_length', name: 'fork_length' },
                    { data: 'angler_name', name: 'angler_name' },
                    { data: 'catch_timestamp', name: 'catch_timestamp' },
                    { data: 'tag_type', name: 'tag_type' },
                    { data: 'tag_no', name: 'tag_no' },
                    { data: 'line_class', name: 'line_class' },
                    { data: 'points', name: 'points' },
                    { data: 'measure_photo', name: 'measure_photo', orderable: false, searchable: false }
                ],
                pageLength: 25,
                lengthMenu: [[25, 50, 100, 200], [25, 50, 100, 200]],
                order: [[1, 'asc'], [3, 'asc'], [4, 'asc'], [5, 'asc'], [6, 'asc']],
                dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip',
                processing: true,
                language: {
                    emptyTable: 'No catch data for this event.',
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>'
                }
            });

            table.on('draw.dt', function () {
                if (typeof GLightbox !== 'undefined') {
                    GLightbox({ selector: '.glightbox' });
                }
            });

            $('#catch-data-table').on('change', '#select-all-catches', function () {
                var checked = $(this).prop('checked');
                $('#catch-data-table tbody .catch-checkbox').prop('checked', checked);
            });

            $('#catch-data-table').on('change', '.catch-checkbox', function () {
                var total = $('#catch-data-table tbody .catch-checkbox').length;
                var checked = $('#catch-data-table tbody .catch-checkbox:checked').length;
                $('#select-all-catches').prop('checked', total > 0 && total === checked);
            });

            $('#delete-selected').on('click', function () {
                var selected = [];
                $('.catch-checkbox:checked').each(function () {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    toastr.warning('Please select at least one catch to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete ' + selected.length + ' selected catch(es)? This cannot be undone.')) {
                    return;
                }

                $.ajax({
                    url: '{{ route('event.catch.delete') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        catch_ids: selected
                    },
                    success: function (response) {
                        toastr.success(response.message || 'Selected catches deleted successfully.');
                        table.ajax.reload();
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error deleting catches.';
                        toastr.error(msg);
                    }
                });
            });
        });
    </script>
    @endif
@endsection
