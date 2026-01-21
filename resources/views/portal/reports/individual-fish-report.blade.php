@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Individual Fish Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Individual Fish Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">

                                <form class="staff-list-form" method="GET" action="{{ route('individual.fish.report') }}">

                                @csrf
                                    <div class="row sifu-filter-area">
                                        <!-- Event Selector -->
                                        <div class="col-md-6">
                                            <label for="event_id">Select Event</label>
                                            <select name="event_id" id="event_id" class="form-control">
                                                <option value="">All Events</option>
                                                @foreach($events as $event)
                                                    <option value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                                        {{ $event->name ?? '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <!-- Species Selector -->
                                        <div class="col-md-6">
                                            <label for="species">Select Species</label>
                                            <select name="species[]" id="species" multiple="multiple" class="form-control select2">
                                            <!-- Options populated via JS -->
                                            </select>
                                        </div>

                                        <!-- Category Selector -->
                                        <div class="col-md-6">
                                            <label>Select Category</label>
                                            <select class="form-control select2" multiple="multiple" name="angler_category[]">
                                            <option value="adult" {{ collect(request('angler_category'))->contains('adult') ? 'selected' : '' }}>Adult</option>
                                                <option value="junior" {{ collect(request('angler_category'))->contains('junior') ? 'selected' : '' }}>Junior</option>
                                                <option value="senior" {{ collect(request('angler_category'))->contains('senior') ? 'selected' : '' }}>Senior</option>
                                                <option value="female" {{ collect(request('angler_category'))->contains('female') ? 'selected' : '' }}>Female</option>
                                                <option value="veteran" {{ collect(request('angler_category'))->contains('veteran') ? 'selected' : '' }}>Veteran</option>
                                            </select>
                                        </div>

                                        <!-- Rank Number -->
                                        <div class="col-md-6">
                                            <label>Rank number</label>
                                            <input type="text" name="rank_number" value="{{ request('rank_number') }}" class="form-control" placeholder="Enter Rank Number">
                                        </div>

                                        <!-- Submit Button -->
                                        <div class="col-md-12 mt-3">
                                            <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                        </div>
                                    </div>
                                </form>

                                <!-- Tables will be injected here -->
                                <div id="report-tables" class="mt-4"></div>

                                <div class="table-responsive">
                                    @if(!empty($filteredCatches))
                                        @foreach($filteredCatches as $specieId => $catches)

                                            <h4 class="mt-4">{{ optional(optional($catches->first())->specie)->name ?? 'No Data' }}
                                            </h4>
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                <tr>
                                                    <th>Rank</th>
                                                    <th>Category</th>
                                                    <th>Biggest Fish</th>
                                                    <th>Angler Name</th>
                                                    <th>Team Name</th>
                                                    <th>Fork Length (mm)</th>
                                                    <th>Fish Photo</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                @foreach($catches->sortByDesc('fork_length')->values() as $index => $catch)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{{ ucfirst($catch->angler->category ?? '-') }}</td>
                                                        <td>{{ $catch->specie->name ?? '-' }}</td>
                                                        <td>{{ $catch->angler->name ?? '-' }}</td>
                                                        <td>{{ $catch->team->name ?? '-' }}</td>
                                                        <td>{{ $catch->fork_length }}</td>
                                                        <td>
                                                            @if($catch->getFirstMediaUrl('event_fish_images'))
                                                                <a  href="{{ $catch->getFirstMediaUrl('event_fish_images') }}"
                                                                    class="glightbox"
                                                                    data-gallery="fish-{{ $specieId }}">     {{-- same gallery per species if you like --}}
                                                                    <img src="{{ $catch->getFirstMediaUrl('event_fish_images') }}"
                                                                         style="width:200px; height:130px;object-fit:contain;"
                                                                         width="80" class="img-thumbnail" style="cursor:pointer;">
                                                                </a>
                                                            @else
                                                                No Photo
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                                </tbody>
                                            </table>
                                        @endforeach

                                    @else
                                        <table class="table table-bordered table-striped">
                                            <thead>
                                            <tr>
                                                <th>Rank</th>
                                                <th>Category</th>
                                                <th>Biggest Fish</th>
                                                <th>Angler Name</th>
                                                <th>Team Name</th>
                                                <th>Fork Length (mm)</th>
                                                <th>Fish Photo</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td class="text-center" colspan="7">No Event Catch Yet!</td>
                                            </tr>
                                            </tbody>
                                    @endif

                                </div>
                            </div><!--end card-body-->
                        </div><!--end card-->
                    </div> <!-- end col -->
                </div> <!-- end row -->

            </div><!-- container -->

        </div>
        <!-- end page content -->
    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            const eventSelect = $('#event_id');
            const speciesSelect = $('#species');
            const selectedSpecies = @json(request('species', []));

            function loadSpecies(eventId) {
                speciesSelect.empty(); // clear previous options
                speciesSelect.append(`<option value="">All Species</option>`);

                if (eventId) {
                    $.ajax({
                        url: `{{ route('get.species.by.event') }}`,
                        method: 'GET',
                        data: { event_id: eventId },
                        success: function (data) {
                            if (data.species && data.species.length > 0) {
                                data.species.forEach(specie => {
                                    const isSelected = selectedSpecies.includes(specie.id.toString());
                                    speciesSelect.append(
                                        `<option value="${specie.id}" ${isSelected ? 'selected' : ''}>
                                        ${specie.name}
                                    </option>`
                                    );
                                });
                                speciesSelect.trigger('change'); // in case select2
                            }
                        },
                        error: function (err) {
                            console.error('Error fetching species:', err);
                        }
                    });
                }
            }

            eventSelect.on('change', function () {
                const selectedEventId = $(this).val();
                loadSpecies(selectedEventId);
            });

            const initialEventId = eventSelect.val();
            if (initialEventId) {
                loadSpecies(initialEventId);
            }
        });
    </script>

@endsection
