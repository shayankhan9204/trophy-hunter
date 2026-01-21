@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">Event Login Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Event Login Report</li>
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
                                                <select name="event_id" id="event_id" class="form-control"
                                                        onchange="this.form.submit()">
                                                    <option value="">All Events</option>
                                                    @foreach($events as $event)
                                                        <option
                                                            value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                                            {{ $event->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="species">Select Dates</label>
                                            <select name="dates[]" id="species" multiple="multiple"
                                                    class="form-control select2">
                                                @forelse($dates as $date)
                                                    <option
                                                        value="{{ $date['date'] }}">{{ $date['date'] ?? '' }}</option>
                                                @empty
                                                @endforelse
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label>Exceptions Checkboxes</label><br>
                                            <label><input type="checkbox" name="check_type" value="out"> Not Signed
                                                Out</label>
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group filters-btns">
                                                <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                                <div class="table-responsive">
                                    <table class="extra-fish-datatables table table-striped table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Team Number</th>
                                            <th>Team Name</th>
                                            <th>Angler Number</th>
                                            <th>Angler Name</th>
                                            <th>Angler Phone Number</th>
                                            <th>Date</th>
                                            <th>Signed In Time</th>
                                            <th>Signed Out Time</th>
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

    </div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            let table;

            $('.staff-list-form').on('submit', function (e) {
                e.preventDefault();
                let eventId = $('#event_id').val();
                let dates = $('#species').val();

                let checkTypes = [];
                $('input[name="check_type"]:checked').each(function () {
                    checkTypes.push($(this).val());
                });

                if (table) table.destroy();

                table = $('.extra-fish-datatables').DataTable({
                    ajax: {
                        url: '{{ route('event.login.report') }}',
                        data: {
                            event_id: eventId,
                            dates: dates,
                            check_type: checkTypes
                        }
                    },
                    columns: [
                        {data: 'team_number', name: 'team_number'},
                        {data: 'team_name', name: 'team_name'},
                        {data: 'angler_number', name: 'angler_number'},
                        {data: 'angler_name', name: 'angler_name'},
                        {data: 'angler_phone_number', name: 'angler_phone_number'},
                        {data: 'date', name: 'date'},
                        {data: 'check_time_in', name: 'check_time_in'},
                        {data: 'check_time_out', name: 'check_time_out'},
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
            });
        });

    </script>
@endsection
