@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Edit Event Catch</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event List</a></li>
                                    <li class="breadcrumb-item active">Edit Event Catch</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('event.catch.update') }}" method="POST" enctype="multipart/form-data" class="add-user-form">
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Name</label>
                                                <h3>{{ $event->name ?? '' }}</h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <div class="d-flex justify-content-between flex-row mb-3">
                                                    <h4 class="mt-4">Event Catch</h4>
                                                    <button type="button" id="delete-selected" class="btn btn-danger mt-2">Delete Selected</button>
                                                </div>

                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th><input type="checkbox" id="select-all" title="Select all"></th>
                                                            <th>Team #</th>
                                                            <th>Team Name</th>
                                                            <th>Angler</th>
                                                            <th>Catch Time</th>
                                                            <th>Specie</th>
                                                            <th>Fork Length (mm)</th>
                                                            <th>Points</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($event->catches as $catch)
                                                            @php
                                                                $teamAnglers = optional($teamsInEvent->firstWhere('id', $catch->team_id))->anglers ?? collect();
                                                            @endphp
                                                            <tr>
                                                                <td><input type="checkbox" class="catch-checkbox" value="{{ $catch->id }}"></td>
                                                                <td>{{ $catch->team->team_uid ?? '-' }}</td>
                                                                <td>{{ $catch->team->name ?? '-' }}</td>
                                                                <td>
                                                                    <select name="angler_id[{{ $catch->id }}]" class="form-control form-control-sm">
                                                                        @foreach($teamAnglers as $u)
                                                                            <option value="{{ $u->id }}" {{ (int)$catch->angler_id === (int)$u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                </td>
                                                                <td>{{ $catch->catch_timestamp ?? '-' }}</td>
                                                                <td>{{ $catch->specie->name ?? '-' }}</td>
                                                                <td>
                                                                    <input type="number" name="fork_length[{{ $catch->id }}]" value="{{ $catch->fork_length }}" class="form-control form-control-sm">
                                                                </td>
                                                                <td>
                                                                    <input type="number" name="points[{{ $catch->id }}]" value="{{ $catch->points }}" class="form-control form-control-sm" step="any">
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="8" class="text-center">No Catch Data Yet!</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-gradient-primary" type="submit">Update Catches</button>
                                </form>

                                <hr class="my-4">

                                <h4 class="mb-3">Add New Catch</h4>
                                <form action="{{ route('event.catch.store') }}" method="POST" id="add-catch-form">
                                    @csrf
                                    <input type="hidden" name="event_id" value="{{ $event->id }}">

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="new_team_id">Team</label>
                                                <select name="team_id" id="new_team_id" class="form-control" required>
                                                    <option value="">-- Select team --</option>
                                                    @foreach($teamsInEvent as $t)
                                                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->team_uid ?? $t->id }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="new_angler_id">Angler</label>
                                                <select name="angler_id" id="new_angler_id" class="form-control" required>
                                                    <option value="">-- Select team first --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="new_specie_id">Species</label>
                                                <select name="specie_id" id="new_specie_id" class="form-control" required>
                                                    <option value="">-- Select species --</option>
                                                    @foreach($event->species as $s)
                                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="new_fork_length">Fork Length (mm)</label>
                                                <input type="number" name="fork_length" id="new_fork_length" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="new_points">Points</label>
                                                <input type="number" name="points" id="new_points" class="form-control" step="any" value="0">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label for="new_catch_timestamp">Catch Date/Time</label>
                                                <input type="text" name="catch_timestamp" id="new_catch_timestamp" class="form-control" placeholder="e.g. 2025-01-15 12:00:00">
                                            </div>
                                        </div>
                                    </div>

                                    @if($event->is_tagged)
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="new_tag_type">Tag Type / Capture</label>
                                                    <select name="tag_type" id="new_tag_type" class="form-control">
                                                        <option value="">-- Select --</option>
                                                        <option value="tagged">Tagged</option>
                                                        <option value="captured">Captured</option>
                                                        <option value="released">Released</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="new_tag_no">Tag Number</label>
                                                    <input type="text" name="tag_no" id="new_tag_no" class="form-control">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="new_line_class">Line Class</label>
                                                    <select name="line_class" id="new_line_class" class="form-control">
                                                        <option value="">-- Select --</option>
                                                        <option value="6kg">6kg</option>
                                                        <option value="8kg">8kg</option>
                                                        <option value="10kg">10kg</option>
                                                        <option value="15kg">15kg</option>
                                                        <option value="24kg">24kg</option>
                                                        <option value="37kg">37kg</option>
                                                        <option value="60kg">60kg</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <button type="submit" class="btn btn-success">Create Catch</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            var eventId = {{ $event->id }};
            var anglersUrl = '{{ route('event.catch.anglers') }}';

            $('#select-all').on('change', function () {
                $('.catch-checkbox').prop('checked', $(this).prop('checked'));
            });

            $('#delete-selected').on('click', function () {
                var selected = $('.catch-checkbox:checked').map(function () { return $(this).val(); }).get();
                if (selected.length === 0) {
                    alert('Please select at least one catch to delete.');
                    return;
                }
                if (!confirm('Are you sure you want to delete selected catches?')) return;
                $.ajax({
                    url: '{{ route('event.catch.delete') }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', catch_ids: selected },
                    success: function (response) {
                        toastr.success(response.message);
                        location.reload();
                    },
                    error: function () { toastr.error('Error deleting catches.'); }
                });
            });

            $('#new_team_id').on('change', function () {
                var teamId = $(this).val();
                var $angler = $('#new_angler_id');
                $angler.html('<option value="">Loading...</option>');
                if (!teamId) {
                    $angler.html('<option value="">-- Select team first --</option>');
                    return;
                }
                $.get(anglersUrl, { event_id: eventId, team_id: teamId })
                    .done(function (res) {
                        var opts = '<option value="">-- Select angler --</option>';
                        (res.anglers || []).forEach(function (a) {
                            opts += '<option value="' + a.id + '">' + (a.name || '') + '</option>';
                        });
                        $angler.html(opts);
                    })
                    .fail(function () {
                        $angler.html('<option value="">Error loading anglers</option>');
                    });
            });
        });
    </script>
@endsection
