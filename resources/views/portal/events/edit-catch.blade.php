@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Edit Event Catch</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event List</a></li>
                                    <li class="breadcrumb-item active">Edit Event Catch</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('event.catch.update') }}" method="POST"
                                      enctype="multipart/form-data"
                                      class="add-user-form">
                                    @csrf

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Name</label>
                                                <h3>{{ $event->name ?? '' }}</h3>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                               <div class="d-flex justify-content-between flex-row mb-3">
                                                   <h4 class="mt-4">Event Catch</h4>
                                                   <button type="button" id="delete-selected" class="btn btn-danger mt-2">
                                                       Delete Selected
                                                   </button>
                                               </div>

                                                <table class="table table-bordered table-striped">
                                                    <thead>
                                                    <tr>
                                                        <th>Select</th>
                                                        <th>Team Number</th>
                                                        <th>Team Name</th>
                                                        <th>Angular Name</th>
                                                        <th>Catch Time</th>
                                                        <th>Specie</th>
                                                        <th>Fork Length (mm)</th>
                                                        <th>Points</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    @forelse($event->catches as $index => $catch)
                                                        <tr>
                                                            <td><input type="checkbox" class="catch-checkbox"
                                                                       value="{{ $catch->id }}"></td>
                                                            <td>{{ $catch->team->team_uid ?? '-' }}</td>
                                                            <td>{{ $catch->team->name ?? '-' }}</td>
                                                            <td>{{ $catch->angler->name ?? '-' }}</td>
                                                            <td>{{ $catch->catch_timestamp ?? '-' }}</td>
                                                            <td>{{ $catch->specie->name ?? '-' }}</td>
                                                            <td>
                                                                <input type="number"
                                                                       name="fork_length[{{ $catch->id }}]"
                                                                       value="{{ $catch->fork_length }}"
                                                                       class="form-control">
                                                            </td>
                                                            <td>
                                                                <input type="number"
                                                                       name="points[{{ $catch->id }}]"
                                                                       value="{{ $catch->points }}"
                                                                       class="form-control">
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6">No Catch Data Yet!</td>
                                                        </tr>
                                                    @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <button class="btn btn-gradient-primary" type="submit">Submit</button>
                                </form>
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
            // Master checkbox to select all
            $('#select-all').on('change', function () {
                $('.catch-checkbox').prop('checked', $(this).prop('checked'));
            });

            // Delete button logic
            $('#delete-selected').on('click', function () {
                let selected = [];

                $('.catch-checkbox:checked').each(function () {
                    selected.push($(this).val());
                });

                if (selected.length === 0) {
                    alert('Please select at least one catch to delete.');
                    return;
                }

                if (!confirm('Are you sure you want to delete selected catches?')) {
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
                        toastr.success(response.message)
                        location.reload();
                    },
                    error: function (xhr) {
                        toastr.error('Error deleting catches.')
                    }
                });
            });
        });
    </script>


@endsection
