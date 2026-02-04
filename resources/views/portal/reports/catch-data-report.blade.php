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
                                        <span class="text-muted">{{ $catches->count() }} catch(es)</span>
                                        <button type="button" id="delete-selected" class="btn btn-danger">Delete Selected</button>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">
                                                        <input type="checkbox" id="select-all-catches" title="Select all">
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
                                            <tbody>
                                                @forelse($catches as $catch)
                                                    <tr>
                                                        <td class="text-center">
                                                            <input type="checkbox" class="catch-checkbox" value="{{ $catch->id }}">
                                                        </td>
                                                        <td>{{ $catch->team->team_uid ?? '-' }}</td>
                                                        <td>{{ $catch->team->name ?? '-' }}</td>
                                                        <td>{{ $catch->specie->name ?? '-' }}</td>
                                                        <td>{{ $catch->fork_length ?? '-' }}</td>
                                                        <td>{{ $catch->angler->name ?? '-' }}</td>
                                                        <td>{{ $catch->catch_timestamp ?? '-' }}</td>
                                                        <td>{{ $catch->tag_type ?? '-' }}</td>
                                                        <td>{{ $catch->tag_no ?? '-' }}</td>
                                                        <td>{{ $catch->line_class ?? '-' }}</td>
                                                        <td>{{ $catch->points ?? '-' }}</td>
                                                        <td>
                                                            @php
                                                                $photoUrl = $catch->getFirstMediaUrl('event_fish_images');
                                                            @endphp
                                                            @if($photoUrl)
                                                                <a href="{{ $photoUrl }}" class="glightbox" data-gallery="catch-report">
                                                                    <img src="{{ $photoUrl }}" alt="Measure" class="img-thumbnail"
                                                                         style="width:80px;height:60px;object-fit:contain;cursor:pointer;">
                                                                </a>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="12" class="text-center">No catch data for this event.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
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
    <script>
        $(document).ready(function () {
            $('#select-all-catches').on('change', function () {
                $('.catch-checkbox').prop('checked', $(this).prop('checked'));
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
                        location.reload();
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error deleting catches.';
                        toastr.error(msg);
                    }
                });
            });
        });
    </script>
@endsection
