@extends('layouts.portal.app')

@php
    $autoLoadReport = count($selectedEventIds ?? []) > 0 && !empty($specieId) && ($maxFishPerEvent ?? 0) >= 1;
@endphp

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
                                <form class="multi-event-bag-form">
                                    <div class="row sifu-filter-area">
                                        <div class="col-md-6">
                                            <label for="event_ids">Select Events (Multiple)</label>
                                            <select name="event_ids[]" id="event_ids" class="form-control" multiple="multiple">
                                                @foreach($events as $ev)
                                                    <option value="{{ $ev->id }}" {{ in_array($ev->id, $selectedEventIds ?? []) ? 'selected' : '' }}>
                                                        {{ $ev->name }}
                                                    </option>
                                                @endforeach
                                            </select>
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
                                        </div>

                                        <div class="col-md-12 mt-3">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">Generate Report</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table id="multi-event-bag-datatable" class="multi-event-bag-datatables table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Rank</th>
                                            <th>Event</th>
                                            <th>Team Number</th>
                                            <th>Team Name</th>
                                            <th>Fork length</th>
                                            <th>Points</th>
                                        </tr>
                                        </thead>
                                    </table>
                                </div>
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

            const autoLoadReport = @json($autoLoadReport);
            const $reportTable = $('#multi-event-bag-datatable');

            function reportDataTableInitialised() {
                return $reportTable.length && $.fn.dataTable && $.fn.dataTable.isDataTable($reportTable[0]);
            }

            $('.multi-event-bag-form').on('submit', function (e) {
                e.preventDefault();

                const eventIds = $('#event_ids').val();
                const specieId = $('#specie_id').val();

                if (!eventIds || !eventIds.length || !specieId) {
                    if (reportDataTableInitialised()) {
                        $reportTable.DataTable().clear().destroy();
                    }
                    return;
                }

                if (reportDataTableInitialised()) {
                    $reportTable.DataTable().ajax.reload(null, false);
                    return;
                }

                const dt = $reportTable.DataTable({
                    destroy: true,
                    ajax: {
                        url: '{{ route('multi.event.ranking.report') }}',
                        cache: false,
                        data: function (d) {
                            d.event_ids = $('#event_ids').val();
                            d.specie_id = $('#specie_id').val();
                            d.max_fish_per_event = $('#max_fish_per_event').val() || 1;
                        }
                    },
                    columns: [
                        { data: 'rank', name: 'rank' },
                        { data: 'event', name: 'event' },
                        { data: 'team_number', name: 'team_number' },
                        { data: 'team_name', name: 'team_name' },
                        { data: 'fork_length', name: 'fork_length' },
                        { data: 'points', name: 'points' }
                    ],
                    dom: '<"row"<"col-sm-6"l><"col-sm-6"B>>frtip',
                    buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                    searching: false,
                    ordering: false,
                    language: {
                        emptyTable: 'Sorry! No catch data found for these selections'
                    }
                });
            });

            if (autoLoadReport) {
                $('.multi-event-bag-form').trigger('submit');
            }
        });
    </script>
@endsection
