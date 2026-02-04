@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Teams Profiles Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('team.ranking.report') }}">Reports</a></li>
                                    <li class="breadcrumb-item active">Teams Profiles Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="{{ route('team.profiles.report') }}" class="mb-4">
                                    <div class="row align-items-end">
                                        <div class="col-md-4">
                                            <label for="event_id">Select Event</label>
                                            <select name="event_id" id="event_id" class="form-control" onchange="this.form.submit()">
                                                <option value="">-- Choose an event --</option>
                                                @foreach($events as $event)
                                                    <option value="{{ $event->id }}" {{ ($eventId ?? '') == $event->id ? 'selected' : '' }}>
                                                        {{ $event->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="team_search">Search by Team Name or Number</label>
                                            <input type="text" name="team_search" id="team_search" class="form-control"
                                                   placeholder="e.g. Team 1 or team name..."
                                                   value="{{ $teamSearch ?? '' }}">
                                        </div>
                                        <div class="col-md-4">
                                            <button type="submit" class="btn btn-gradient-primary">Load Report</button>
                                        </div>
                                    </div>
                                </form>

                                @if($eventId && $teamsWithUsers->isEmpty())
                                    <div class="alert alert-info">No teams or users found for this event.</div>
                                @endif

                                @if($eventId && $teamsWithUsers->isNotEmpty())
                                    <div class="teams-profiles-list">
                                        @foreach($teamsWithUsers as $item)
                                            <div class="card mb-4 border">
                                                <div class="card-header bg-light">
                                                    <h5 class="mb-0">
                                                        <strong>Team {{ $item->team->team_uid ?? 'N/A' }}</strong>
                                                        — {{ $item->team->name ?? 'N/A' }}
                                                        <span class="badge badge-primary ml-2">{{ $item->users->count() }} user(s)</span>
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        @foreach($item->users as $userData)
                                                            <div class="col-md-6 col-lg-4 mb-4">
                                                                <div class="card h-100 border-secondary">
                                                                    <div class="card-body">
                                                                        <h6 class="card-title text-primary">{{ $userData->user->name ?? 'N/A' }}</h6>
                                                                        <p class="mb-1 small"><strong>Angler #:</strong> {{ $userData->angular_uid ?? 'N/A' }}</p>
                                                                        <p class="mb-1 small"><strong>Email:</strong> {{ $userData->user->email ?? '-' }}</p>
                                                                        <p class="mb-1 small"><strong>Phone:</strong> {{ $userData->user->phone ?? '-' }}</p>
                                                                        <p class="mb-2 small"><strong>Category:</strong> {{ $userData->user->category ?? '-' }}</p>

                                                                        @php $profile = $userData->user->profile; @endphp
                                                                        @if($profile)
                                                                            <hr class="my-2">
                                                                            <p class="mb-1 small"><strong>Insurer:</strong> {{ $profile->insurer_name ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Policy #:</strong> {{ $profile->policy_number ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Renewal:</strong> {{ $profile->renewal_date ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Boat Reg:</strong> {{ $profile->boat_registration ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Boat Length:</strong> {{ $profile->boat_length ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Boat Maker:</strong> {{ $profile->boat_maker ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Boat Color:</strong> {{ $profile->boat_color ?? '-' }}</p>
                                                                            <p class="mb-1 small"><strong>Emergency:</strong> {{ $profile->emergency_contact_name ?? '-' }} {{ $profile->emergency_contact_number ? '(' . $profile->emergency_contact_number . ')' : '' }}</p>
                                                                        @else
                                                                            <p class="text-muted small">No profile data</p>
                                                                        @endif

                                                                        <hr class="my-2">
                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <div class="text-center">
                                                                                <small class="d-block text-muted">Profile Photo</small>
                                                                                <a href="{{ $userData->user->profile_picture }}" class="glightbox" data-gallery="user-{{ $userData->user->id }}">
                                                                                    <img src="{{ $userData->user->profile_picture }}" alt="Profile" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">
                                                                                </a>
                                                                            </div>
                                                                            <div class="text-center">
                                                                                <small class="d-block text-muted">Boat Photo</small>
                                                                                <a href="{{ $userData->user->boat_photo }}" class="glightbox" data-gallery="user-{{ $userData->user->id }}">
                                                                                    <img src="{{ $userData->user->boat_photo }}" alt="Boat" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">
                                                                                </a>
                                                                            </div>
                                                                            <div class="text-center">
                                                                                <small class="d-block text-muted">Insurance</small>
                                                                                <a href="{{ $userData->user->insurance_certificate }}" class="glightbox" data-gallery="user-{{ $userData->user->id }}">
                                                                                    <img src="{{ $userData->user->insurance_certificate }}" alt="Insurance" class="img-thumbnail" style="width:80px;height:80px;object-fit:cover;">
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
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
