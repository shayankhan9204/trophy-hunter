@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Delete Catch Media</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event List</a></li>
                                    <li class="breadcrumb-item active">Delete Catch Media</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="event_id"><strong>Select Event</strong></label>
                                    <select id="event_id" name="event_id" class="form-control select2">
                                        <option value="">-- Choose an event --</option>
                                      
                                        @foreach($events as $ev)
                                            <option value="{{ $ev->id }}">{{ $ev->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div id="media-options-section" class="mb-4" style="display: none;">
                                    <h5 class="mb-3">Media types to delete</h5>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="delete_measure_photo" name="delete_measure_photo" value="1">
                                        <label class="form-check-label" for="delete_measure_photo">Fish measure photo</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="delete_glory_photo" name="delete_glory_photo" value="1">
                                        <label class="form-check-label" for="delete_glory_photo">Glory photo</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="delete_release_video" name="delete_release_video" value="1">
                                        <label class="form-check-label" for="delete_release_video">Release video</label>
                                    </div>
                                </div>

                                <div id="catches-section" style="display: none;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">Catches (sort by fish size, then select/unselect rows)</h5>
                                        <button type="button" id="btn-delete-media" class="btn btn-danger">Delete selected media</button>
                                    </div>
                                    <div class="table-responsive">
                                        <table id="catches-table" class="table table-bordered table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 40px;">
                                                        <input type="checkbox" id="select-all-catches" title="Select all">
                                                    </th>
                                                    <th>Team #</th>
                                                    <th>Team Name</th>
                                                    <th>Angler</th>
                                                    <th>Specie</th>
                                                    <th data-type="num">Fork Length (mm)</th>
                                                    <th class="text-center">Measure</th>
                                                    <th class="text-center">Glory</th>
                                                    <th class="text-center">Video</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                    <p class="text-muted small mt-2">Tip: Sort by "Fork Length" to group by size, use "Select all" then uncheck bigger fish to keep their media.</p>
                                </div>

                                <div id="no-catches-message" class="alert alert-info" style="display: none;">
                                    No catches found for this event.
                                </div>

                                <div id="loading-catches" class="text-center py-4" style="display: none;">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <p class="mt-2">Loading catches...</p>
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
            var catchesData = [];
            var dataTable = null;

            if ($('#event_id').length && typeof $.fn.select2 !== 'undefined') {
                $('#event_id').select2({ width: '100%' });
            }

            $('#event_id').on('change', function () {
                var eventId = $(this).val();
                $('#media-options-section').hide();
                $('#catches-section').hide();
                $('#no-catches-message').hide();
                $('#delete_measure_photo, #delete_glory_photo, #delete_release_video').prop('checked', false);

                if (!eventId) {
                    return;
                }

                $('#loading-catches').show();
                $.get('{{ route('event.delete.catch.media.catches') }}', { event_id: eventId })
                    .done(function (res) {
                        catchesData = res.catches || [];
                        $('#loading-catches').hide();

                        if (catchesData.length === 0) {
                            $('#no-catches-message').show();
                            return;
                        }

                        $('#media-options-section').show();
                        $('#catches-section').show();
                        buildTable(catchesData);
                    })
                    .fail(function () {
                        $('#loading-catches').hide();
                        toastr.error('Failed to load catches.');
                    });
            });

            function buildTable(data) {
                if (dataTable && $.fn.DataTable.isDataTable('#catches-table')) {
                    dataTable.destroy();
                    dataTable = null;
                }
                var $tbody = $('#catches-table tbody');
                $tbody.empty();
                data.forEach(function (row) {
                    var tr = '<tr>' +
                        '<td class="text-center"><input type="checkbox" class="catch-row-checkbox" value="' + row.id + '"></td>' +
                        '<td>' + (row.team_uid || '-') + '</td>' +
                        '<td>' + (row.team_name || '-') + '</td>' +
                        '<td>' + (row.angler_name || '-') + '</td>' +
                        '<td>' + (row.specie_name || '-') + '</td>' +
                        '<td data-order="' + (row.fork_length_sort || 0) + '">' + (row.fork_length || '-') + '</td>' +
                        '<td class="text-center">' + (row.has_measure_photo ? '<i class="ti-check text-success"></i>' : '-') + '</td>' +
                        '<td class="text-center">' + (row.has_glory_photo ? '<i class="ti-check text-success"></i>' : '-') + '</td>' +
                        '<td class="text-center">' + (row.has_release_video ? '<i class="ti-check text-success"></i>' : '-') + '</td>' +
                        '</tr>';
                    $tbody.append(tr);
                });

                dataTable = $('#catches-table').DataTable({
                    order: [[5, 'asc']],
                    columnDefs: [
                        { orderable: false, targets: [0, 6, 7, 8] },
                        { type: 'num', targets: 5 }
                    ],
                    pageLength: 25,
                    lengthMenu: [[25, 50, 100, 200, 500, -1], [25, 50, 100, 200, 500, 'All']],
                    lengthChange: true,
                    searching: true,
                    dom: '<"row"<"col-sm-6"l><"col-sm-6"f>>rtip'
                });

                $('#select-all-catches').off('change').on('change', function () {
                    $('.catch-row-checkbox').prop('checked', $(this).prop('checked'));
                });
            }

            $('#btn-delete-media').on('click', function () {
                var measure = $('#delete_measure_photo').is(':checked');
                var glory = $('#delete_glory_photo').is(':checked');
                var video = $('#delete_release_video').is(':checked');
                if (!measure && !glory && !video) {
                    toastr.warning('Please select at least one media type to delete.');
                    return;
                }

                var ids = [];
                $('.catch-row-checkbox:checked').each(function () {
                    ids.push($(this).val());
                });
                if (ids.length === 0) {
                    toastr.warning('Please select at least one catch.');
                    return;
                }

                if (!confirm('Delete the selected media types for ' + ids.length + ' catch(es)? This cannot be undone.')) {
                    return;
                }

                $.ajax({
                    url: '{{ route('event.delete.catch.media.submit') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        catch_ids: ids,
                        delete_measure_photo: measure ? 1 : 0,
                        delete_glory_photo: glory ? 1 : 0,
                        delete_release_video: video ? 1 : 0
                    },
                    success: function (response) {
                        toastr.success(response.message || 'Media deleted successfully.');
                        $('#event_id').trigger('change');
                    },
                    error: function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Error deleting media.';
                        toastr.error(msg);
                    }
                });
            });
        });
    </script>
@endsection
