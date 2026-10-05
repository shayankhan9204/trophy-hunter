@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">
            <div class="container-fluid">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="page-title-box">
                            <h4 class="page-title">I'M SAFE Report</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item active">I'M SAFE Report</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form class="im-safe-report-form">
                                    <div class="row sifu-filter-area">
                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="event_id" class="form-label">Select Event</label>
                                                <select name="event_id" id="event_id" class="form-control">
                                                    <option value="">Select Event</option>
                                                    @foreach($events as $event)
                                                        <option
                                                            value="{{ $event->id }}" {{ request('event_id') == $event->id ? 'selected' : '' }}>
                                                            {{ $event->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label for="date_id">Select Date</label>
                                                <select name="date_id" id="date_id" class="form-control">
                                                    <option value="">Select Date</option>
                                                    @foreach($dates as $date)
                                                        <option value="{{ $date->id }}" {{ request('date_id') == $date->id ? 'selected' : '' }}>
                                                            {{ \Carbon\Carbon::parse($date->date)->format('Y-m-d') }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group mb-3">
                                                <label>Select Time Intervals</label>
                                                <select name="intervals[]" id="interval_select" multiple="multiple" class="form-control select2">
                                                    <!-- Options populated via JS -->
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
                                    <table class="im-safe-datatables table table-striped table-bordered" id="im-safe-table">
                                        <thead>
                                            <tr>
                                                <th>Team Name</th>
                                                <th>Angler Name</th>
                                                <th>Phone Number</th>
                                                <th>Location Code</th>
                                                <th>Timestamp</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
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
            const table = $('#im-safe-table').DataTable({
                ajax: {
                    url: '{{ route('im.safe.report') }}',
                    data: function (request) {
                        request.event_id = $('#event_id').val();
                        request.date_id = $('#date_id').val();
                        request.intervals = $('#interval_select').val() || [];
                    }
                },
                columns: [
                    {data: 'team_name', name: 'team_name'},
                    {data: 'angler_name', name: 'angler_name'},
                    {data: 'phone_number', name: 'phone_number'},
                    {data: 'location_code', name: 'location_code'},
                    {data: 'time_stamp', name: 'time_stamp'}
                ],
                dom: '<"row"<"col-sm-6"l><"col-sm-6"B>>frtip',
                buttons: ['copy', 'excel', 'pdf', 'csv', 'colvis'],
                searching: true,
                ordering: false,
                paging: false,
                language: {
                    emptyTable: "No data found for the selected criteria"
                }
            });

            function fetchIntervals(dateId) {
                let $select = $('#interval_select');
                $select.empty();
                $select.val(null).trigger('change');
                
                if (!dateId) {
                    return;
                }
                
                $.ajax({
                    url: '{{ url('event-date-intervals') }}/' + dateId,
                    type: 'GET',
                    success: function(intervals) {
                        intervals.forEach(function(interval) {
                            let newOption = new Option(interval, interval, false, false);
                            $select.append(newOption);
                        });
                        $select.trigger('change');
                    },
                });
            }

            $('#event_id').on('change', function () {
                const eventId = $(this).val();
                const $dateSelect = $('#date_id');

                $dateSelect.empty().append(new Option('Select Date', '', true, true));
                $('#interval_select').empty().val(null).trigger('change');
                table.clear().draw();

                if (!eventId) {
                    return;
                }

                $.getJSON('{{ url('/api/event-dates') }}/' + eventId, function (dates) {
                    dates.forEach(function (date) {
                        $dateSelect.append(new Option(date.date, date.id, false, false));
                    });
                });
            });

            $('#date_id').on('change', function() {
                fetchIntervals($(this).val());
                table.ajax.reload();
            });

            $('.im-safe-report-form').on('submit', function (e) {
                e.preventDefault();
                table.ajax.reload();
            });

            if ($('#date_id').val()) {
                fetchIntervals($('#date_id').val());
                table.ajax.reload();
            } else {
                table.clear().draw();
            }
        });
    </script>
@endsection
