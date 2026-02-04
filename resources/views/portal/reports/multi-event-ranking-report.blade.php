@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Multi Event Bag Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('team.ranking.report') }}">Reports</a></li>
                                    <li class="breadcrumb-item active">Multi Event Bag Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="{{ route('multi.event.ranking.report') }}" class="mb-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="event_ids">Select Events (Multiple)</label>
                                            <select name="event_ids[]" id="event_ids" class="form-control select2" multiple="multiple">
                                                @foreach($events as $ev)
                                                    <option value="{{ $ev->id }}" {{ in_array($ev->id, $selectedEventIds ?? []) ? 'selected' : '' }}>
                                                        {{ $ev->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">Select one or more events</small>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="specie_id">Select Species</label>
                                            <select name="specie_id" id="specie_id" class="form-control">
                                                <option value="">-- Choose a species --</option>
                                                @foreach($species as $specie)
                                                    <option value="{{ $specie->id }}" {{ ($specieId ?? '') == $specie->id ? 'selected' : '' }}>
                                                        {{ $specie->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label for="max_fish_per_event">Max Fish per Event</label>
                                            <input type="number" name="max_fish_per_event" id="max_fish_per_event" 
                                                   class="form-control" min="1" value="{{ $maxFishPerEvent ?? 1 }}">
                                            <small class="text-muted">Top N highest-point fish per event</small>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-gradient-primary">Generate Report</button>
                                        </div>
                                    </div>
                                </form>

                                @if(($eventSections ?? collect())->isNotEmpty())
                                    <div class="mt-4">
                                        <h5 class="mb-3">Events</h5>
                                        <p class="text-muted small mb-4">
                                            Each event section shows participating teams and the top {{ $maxFishPerEvent }} fish per team
                                            (selected by highest points, tie-breaker fork length).
                                        </p>

                                        @foreach($eventSections as $section)
                                            <div class="card mb-4 border">
                                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                    <strong>{{ $section['event_name'] }}</strong>
                                                    <span class="text-muted small">Max fish per team: {{ $maxFishPerEvent }}</span>
                                                </div>
                                                <div class="card-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-striped table-hover mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Team #</th>
                                                                    <th>Team Name</th>
                                                                    <th>Fish Included</th>
                                                                    <th>Event Points</th>
                                                                    <th>Details</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($section['teams'] as $t)
                                                                    <tr>
                                                                        <td>{{ $t['team_uid'] }}</td>
                                                                        <td>{{ $t['team_name'] }}</td>
                                                                        <td>{{ $t['fish_count'] }}</td>
                                                                        <td><strong>{{ number_format($t['points'], 2) }}</strong></td>
                                                                        <td>
                                                                            <button type="button" class="btn btn-sm btn-info"
                                                                                    data-toggle="collapse"
                                                                                    data-target="#event-{{ $section['event_id'] }}-team-{{ $t['team_id'] }}">
                                                                                View Fish
                                                                            </button>
                                                                        </td>
                                                                    </tr>
                                                                    <tr class="collapse" id="event-{{ $section['event_id'] }}-team-{{ $t['team_id'] }}">
                                                                        <td colspan="5">
                                                                            <div class="p-3 bg-light">
                                                                                @if(!empty($t['fish']))
                                                                                    <table class="table table-sm mb-0">
                                                                                        <thead>
                                                                                            <tr>
                                                                                                <th>Catch ID</th>
                                                                                                <th>Fork Length (mm)</th>
                                                                                                <th>Points</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @foreach($t['fish'] as $f)
                                                                                                <tr>
                                                                                                    <td>{{ $f['catch_id'] }}</td>
                                                                                                    <td>{{ $f['fork_length'] ?? '-' }}</td>
                                                                                                    <td>{{ number_format((float)($f['points'] ?? 0), 2) }}</td>
                                                                                                </tr>
                                                                                            @endforeach
                                                                                        </tbody>
                                                                                    </table>
                                                                                @else
                                                                                    <span class="text-muted">No fish of the selected species for this team in this event.</span>
                                                                                @endif
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                @empty
                                                                    <tr>
                                                                        <td colspan="5" class="text-center text-muted">No teams found for this event.</td>
                                                                    </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($teamRankings->isNotEmpty())
                                    <div class="mt-4">
                                        <h5>Overall Team Rankings (All Selected Events)</h5>
                                        <p class="text-muted small">
                                            Total points are the sum of the selected fish across events (only events the team participated in are counted).
                                        </p>

                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Rank</th>
                                                        <th>Team #</th>
                                                        <th>Team Name</th>
                                                        <th>Events (Participated / Selected)</th>
                                                        <th>Total Points</th>
                                                        <th>Details</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($teamRankings as $ranking)
                                                        <tr>
                                                            <td><strong>{{ $ranking['rank'] }}</strong></td>
                                                            <td>{{ $ranking['team_uid'] }}</td>
                                                            <td>{{ $ranking['team_name'] }}</td>
                                                            <td>
                                                                {{ $ranking['events_participated'] }} / {{ $ranking['total_events'] }}
                                                                @if($ranking['events_participated'] < $ranking['total_events'])
                                                                    <span class="badge badge-warning ml-1">Partial</span>
                                                                @endif
                                                            </td>
                                                            <td><strong>{{ number_format($ranking['total_points'], 2) }}</strong></td>
                                                            <td>
                                                                <button type="button" class="btn btn-sm btn-info" 
                                                                        data-toggle="collapse" 
                                                                        data-target="#details-{{ $ranking['team_id'] }}">
                                                                    View Details
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <tr class="collapse" id="details-{{ $ranking['team_id'] }}">
                                                            <td colspan="6">
                                                                <div class="p-3 bg-light">
                                                                    <h6>Breakdown by Event:</h6>
                                                                    <table class="table table-sm mb-0">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Event</th>
                                                                                <th>Fish Count</th>
                                                                                <th>Top Fish Fork Length (mm)</th>
                                                                                <th>Points</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            @foreach($ranking['fish_details'] as $detail)
                                                                                <tr>
                                                                                    <td>{{ $detail['event_name'] }}</td>
                                                                                    <td>{{ $detail['fish_count'] }}</td>
                                                                                    <td>{{ $detail['top_fish_fork_length'] }}</td>
                                                                                    <td>{{ number_format($detail['points'], 2) }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @elseif(request()->has('event_ids') && request()->has('specie_id'))
                                    <div class="alert alert-info mt-4">
                                        No teams found matching the criteria. Please check your selections.
                                    </div>
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
            $('#event_ids').select2({
                placeholder: 'Select one or more events',
                width: '100%'
            });
        });
    </script>
@endsection
