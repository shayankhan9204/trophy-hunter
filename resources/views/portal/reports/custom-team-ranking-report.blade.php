@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Custom Team Ranking Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Custom Team Ranking Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="staff-list-form">
                                    <div class="row sifu-filter-area">
                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="event_id" class="form-label">Select Event</label>
                                                <select name="event_id" id="event_id" class="form-control" required>
                                                    <option value="">Select Event</option>
                                                    @foreach($events as $event)
                                                        <option value="{{ $event->id }}">
                                                            {{ $event->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group mb-3">
                                                <label for="species" class="form-label">Select Species</label>
                                                <select name="species[]" id="species" multiple="multiple" class="form-control select2">
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12">
                                            <div id="species-fish-counts" class="mb-3"></div>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="custom-team-ranking-datatables table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Team Number</th>
                                            <th>Team Name</th>
                                            <th>Angler Number</th>
                                            <th>Angler Name</th>
                                            <th>Specie</th>
                                            <th>Fork Length</th>
                                            <th>Points</th>
                                            <th>Measure Photo</th>
                                            <th>Release Video</th>
                                        </tr>
                                        </thead>
                                    </table>
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
        $(document).ready(function() {
            let table;
            let availableSpecies = [];

            const eventSelect = $('#event_id');
            const speciesSelect = $('#species');
            const speciesFishCountsContainer = $('#species-fish-counts');

            function renderSpeciesFishCountInputs() {
                const selectedIds = speciesSelect.val() || [];
                const savedCounts = {};

                $('.species-fish-count').each(function() {
                    savedCounts[$(this).data('specie-id')] = $(this).val();
                });

                speciesFishCountsContainer.empty();

                if (selectedIds.length === 0) {
                    return;
                }

                speciesFishCountsContainer.append('<label class="form-label d-block">Fish to include per species</label>');
                const row = $('<div class="row"></div>');
                speciesFishCountsContainer.append(row);

                selectedIds.forEach(function(specieId) {
                    const specie = availableSpecies.find(s => s.id.toString() === specieId.toString());
                    const specieName = specie ? specie.name : 'Species ' + specieId;
                    const existingValue = savedCounts[specieId] || '';

                    row.append(`
                        <div class="col-md-4 mb-3">
                            <label for="species_count_${specieId}" class="form-label">${specieName}</label>
                            <input type="number"
                                   id="species_count_${specieId}"
                                   name="species_fish_counts[${specieId}]"
                                   class="form-control species-fish-count"
                                   data-specie-id="${specieId}"
                                   min="1"
                                   placeholder="Number of fish"
                                   value="${existingValue}"
                                   required>
                        </div>
                    `);
                });
            }

            function loadSpecies(eventId) {
                speciesSelect.empty();
                availableSpecies = [];

                if (eventId) {
                    $.ajax({
                        url: `{{ route('get.species.by.event') }}`,
                        method: 'GET',
                        data: { event_id: eventId },
                        success: function(data) {
                            if (data.species && data.species.length > 0) {
                                availableSpecies = data.species;
                                data.species.forEach(function(specie) {
                                    speciesSelect.append(
                                        `<option value="${specie.id}">${specie.name}</option>`
                                    );
                                });
                                speciesSelect.trigger('change');
                            }
                        },
                        error: function(err) {
                            console.error('Error fetching species:', err);
                        }
                    });
                }
            }

            function collectSpeciesFishCounts() {
                const speciesFishCounts = {};

                $('.species-fish-count').each(function() {
                    const specieId = $(this).attr('data-specie-id');
                    speciesFishCounts[specieId] = $(this).val();
                });

                return speciesFishCounts;
            }

            function validateForm() {
                const eventId = eventSelect.val();
                const species = speciesSelect.val() || [];
                const speciesFishCounts = collectSpeciesFishCounts();

                if (!eventId) {
                    alert('Please select an event.');
                    return false;
                }

                if (species.length === 0) {
                    alert('Please select at least one species.');
                    return false;
                }

                for (const specieId of species) {
                    if (!speciesFishCounts[specieId] || parseInt(speciesFishCounts[specieId], 10) < 1) {
                        alert('Please enter the number of fish to include for each selected species.');
                        return false;
                    }
                }

                return true;
            }

            function initTable() {
                return $('.custom-team-ranking-datatables').DataTable({
                    ajax: {
                        url: '{{ route('custom.team.ranking.report') }}',
                        cache: false,
                        data: function(d) {
                            d.event_id = eventSelect.val();
                            d.species = speciesSelect.val() || [];
                            d.species_fish_counts = collectSpeciesFishCounts();
                            d._ = Date.now();
                        }
                    },
                    columns: [
                        { data: 'rank', name: 'rank' },
                        { data: 'team_number', name: 'team_number' },
                        { data: 'team_name', name: 'team_name' },
                        { data: 'angler_number', name: 'angler_number' },
                        { data: 'angler_name', name: 'angler_name' },
                        { data: 'specie', name: 'specie' },
                        { data: 'fork_length', name: 'fork_length' },
                        { data: 'points', name: 'points' },
                        { data: 'fish_photo', name: 'fish_photo' },
                        { data: 'release_video', name: 'release_video' },
                        { data: 'is_summary_row', visible: false }
                    ],
                    rowCallback: function(row, data) {
                        if (data.is_summary_row) {
                            $(row).css('font-weight', 'bold');
                            $(row).addClass('table-success');
                        }
                    },
                    dom: '<"row"<"col-sm-6"l><"col-sm-6"B>>frtip',
                    buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                    searching: false,
                    ordering: false,
                    language: {
                        emptyTable: "Sorry! No catch data found for the selected criteria"
                    }
                });
            }

            function resetTable() {
                if ($.fn.DataTable.isDataTable('.custom-team-ranking-datatables')) {
                    $('.custom-team-ranking-datatables').DataTable().clear().destroy();
                    table = null;
                }
            }

            eventSelect.on('change', function() {
                resetTable();
                loadSpecies($(this).val());
                speciesFishCountsContainer.empty();
            });

            speciesSelect.on('change', renderSpeciesFishCountInputs);

            $('.staff-list-form').on('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) {
                    return;
                }

                if ($.fn.DataTable.isDataTable('.custom-team-ranking-datatables')) {
                    table.ajax.reload(null, false);
                } else {
                    table = initTable();
                    table.on('draw.dt', function() {
                        lightbox.destroy();
                        lightbox = GLightbox({ selector: '.glightbox' });
                    });
                }
            });
        });
    </script>
@endsection
