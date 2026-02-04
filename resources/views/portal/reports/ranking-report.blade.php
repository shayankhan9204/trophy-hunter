@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Ranking Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Ranking Report</li>
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
                                        <div class="col-md-12">
                                            <div class="form-group mb-3">
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
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="team-ranking-datatables table table-striped table-bordered">
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

            $('.staff-list-form').on('submit', function(e) {
                e.preventDefault();
                let eventId = $('#event_id').val();

                if (table) table.destroy();

                table = $('.team-ranking-datatables').DataTable({
                    ajax: {
                        url: '{{ route('team.ranking.report') }}',
                        data: {
                            event_id: eventId
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
                        { data: 'is_summary_row', visible: false }
                    ],
                    rowCallback: function (row, data) {
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
                        emptyTable: "Sorry! No catch data found for this event"
                    }
                });
                table.on('draw.dt', function () {
                    lightbox.destroy();
                    lightbox = GLightbox({ selector: '.glightbox' });
                });
            });
        });

    </script>
@endsection
