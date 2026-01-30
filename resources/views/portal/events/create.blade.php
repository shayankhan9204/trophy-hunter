@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Add Event</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event List</a></li>
                                    <li class="breadcrumb-item active">Add Event</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('event.store') }}" method="POST" enctype="multipart/form-data"
                                      class="add-user-form">
                                    @csrf

                                    <h4>Event Basic Information</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Name</label>
                                                <input class="form-control" required type="text" name="name"
                                                       value="{{ old('name') }}">
                                            </div>
                                            @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fish Bag Size</label> ( <strong>NOTE:</strong> Leave empty if you dont want to set fish bag size limit  )
                                                <input class="form-control" type="number" name="fish_bag_size"
                                                       value="{{ old('fish_bag_size') ?? '' }}">
                                            </div>
                                            @error('fish_bag_size')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Location</label>
                                                <input class="form-control" required type="text" name="location" style="height: 50px !important;"
                                                       value="{{ old('location') }}">
                                            </div>
                                            @error('location')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Minimum size for release video</label>
                                                <input class="form-control" required type="number" name="minimum_release_size" style="height: 50px !important;"
                                                       value="{{ old('minimum_release_size') }}">
                                            </div>
                                            @error('minimum_release_size')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

{{--                                        <div class="col-md-6">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <label>Teams</label>--}}
{{--                                                <select name="teams[]" id="angler_select" class="form-control select2"--}}
{{--                                                        multiple="multiple">--}}
{{--                                                    @foreach($teams as $team)--}}
{{--                                                        <option value="{{ $team->id }}">--}}
{{--                                                            {{ $team->name }}--}}
{{--                                                        </option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </div>--}}
{{--                                        </div>--}}
{{--                                        @error('teams')--}}
{{--                                        <span class="text-danger">{{ $message }}</span>--}}
{{--                                        @enderror--}}

                                    </div>

                                    @php
                                        $dates = old('date', []);
                                        $start_times = old('start_time', []);
                                        $end_times = old('end_time', []);
                                    @endphp

                                    <div id="dates-container">
                                        <h4>Event Dates</h4>
                                        @for($i = 0; $i < max(count($dates), 1); $i++)
                                            <div class="dates-box">
                                            <div class="row form-group">
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Event Date</label>
                                                        <input class="form-control" required type="date" name="date[]"
                                                               value="{{ $dates[$i] ?? '' }}">
                                                    </div>
                                                </div>
                                                @error('date')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Start Time</label>
                                                        <input class="form-control" required type="time" name="start_time[]"
                                                               value="{{ $start_times[$i] ?? '' }}">
                                                    </div>
                                                </div>
                                                @error('start_time')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>End Time</label>
                                                        <input class="form-control" required type="time" name="end_time[]"
                                                               value="{{ $end_times[$i] ?? '' }}">
                                                    </div>
                                                    @error('end_time')
                                                    <span class="text-danger">{{ $message }}</span>
                                                    @enderror
                                                </div>


                                                <div
                                                    class="col-1 d-flex justify-content-between align-items-center">
                                                    <button type="button" id="minus-date"
                                                            class="btn-danger var-btn" style="@if($i == 0) display: none @endif ">
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <button type="button" id="add-date" style="@if($i != 0) display: none @endif "
                                                            class="btn-primary var-btn">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                        @endfor
                                    </div>

                                    <h4>Event Sponsors</h4>
                                    <p>
                                        <strong>Note:</strong> Please upload all sponsor files at once. Make sure that
                                        each file's name matches the sponsor's name for accurate identification.
                                    </p>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Sponsors</label>
                                                <input class="form-control" type="file" name="sponsors[]" multiple>
                                            </div>
                                        </div>
                                        @error('sponsors')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <h4>Tagged Event</h4>
                                    <p>
                                        <strong>Note:</strong> Check this box to enable the tagged system for this
                                        event,
                                        or leave it unchecked to disable.
                                    </p>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="">
                                                <input class="" type="checkbox" value="1" name="is_tagged" {{ old('is_tagged') ? 'checked' : '' }}>
                                                <label>Is Tagged?</label>
                                            </div>
                                        </div>
                                        @error('is_tagged')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <h4>Event Species</h4>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Species</label>
                                                <select name="species[]" id="angler_select" class="form-control select2"
                                                        multiple="multiple">
                                                    @foreach($species as $specie)
                                                        <option value="{{ $specie->id }}" {{ collect(old('species'))->contains($specie->id) ? 'selected' : '' }}>
                                                            {{ $specie->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error('species')
                                                <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div id="contact-container">
                                        <h4>Event Committee Contacts </h4>
                                        <p>
                                            <strong>Note:</strong> If a committee member doesn’t have an email or phone
                                            number, you can simply leave those fields blank.
                                        </p>
                                        @php
                                            $names = old('contact_name', ['']);
                                            $emails = old('contact_email', ['']);
                                            $phones = old('contact_phone', ['']);
                                        @endphp

                                        @foreach($names as $i => $val)
                                           <div class="contact-box">
                                            <div class="row form-group">
                                                <div class="col-3 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Name</label>
                                                            <input type="text" class="form-control"
                                                                   name="contact_name[]"
                                                                   value="{{ $val }}"
                                                                   placeholder="Type Name ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-3 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Email</label>
                                                            <input type="text" class="form-control"
                                                                   name="contact_email[]"
                                                                   value="{{ $emails[$i] ?? '' }}"
                                                                   placeholder="Type Email ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-3 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Phone</label>
                                                            <input type="text" class="form-control"
                                                                   name="contact_phone[]"
                                                                   value="{{ $phones[$i] ?? '' }}"
                                                                   placeholder="Type Phone ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="col-1 d-flex justify-content-between align-items-center">
                                                    <button type="button" id="minus-contact"
                                                            class="btn-danger var-btn" style="@if($i == 0) display: none @endif ">
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <button type="button" id="add-contact"
                                                            style="@if($i != 0) display: none @endif "
                                                            class="btn-primary var-btn">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                        @endforeach
                                    </div>

                                    <div id="rule-container">
                                        <h4>Event Rules </h4>
                                        @php
                                            $titles = old('event_title', ['']);
                                            $descs = old('description', ['']);
                                        @endphp

                                        @foreach($titles as $i => $title)
                                            <div class="rule-box">
                                            <div class="row form-group">
                                                <div class="col-9 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Title</label>
                                                            <input type="text" class="form-control"
                                                                   name="event_title[]"
                                                                   value="{{ $title }}"
                                                                   placeholder="Type Name ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="col-1 d-flex justify-content-between align-items-center">
                                                    <button type="button" id="minus-rule"
                                                            class="btn-danger var-btn" style="@if($i == 0) display: none @endif ">
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <button type="button" id="add-rule"
                                                            style="@if($i != 0) display: none @endif "
                                                            class="btn-primary var-btn">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>

                                            </div>
                                            <div class="row form-group">
                                                <div class="col-9  p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Description</label>
                                                            <textarea class="form-control texteditor"
                                                                      name="description[]">{{ $descs[$i] ?? '' }}
                                                            </textarea>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            </div>
                                        @endforeach
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
            $("#dates-container").on("click", "#add-date", function () {
                var clone = $(".dates-box:first").clone(true);

                var select = clone.find("select");
                select.val(null).trigger("change");
                clone.find("input[type=date]").val("");
                clone.find("input[type=time]").val("");
                clone.find("#minus-date").show();
                clone.find("#add-date").hide();
                $("#dates-container").append(clone);

            });

            $("#dates-container").on("click", "#minus-date", function () {
                $(this).closest(".dates-box").remove();
            });

            $("#contact-container").on("click", "#add-contact", function () {
                var clone = $(".contact-box:first").clone(true);

                var select = clone.find("select");
                select.val(null).trigger("change");
                clone.find("input[type=text]").val("");
                clone.find("#minus-contact").show();
                clone.find("#add-contact").hide();
                $("#contact-container").append(clone);

            });

            $("#contact-container").on("click", "#minus-contact", function () {
                $(this).closest(".contact-box").remove();
            });

            $("#rule-container").on("click", "#add-rule", function () {
                var clone = $(".rule-box:first").clone(true);

                var select = clone.find("select");
                select.val(null).trigger("change");
                clone.find("input[type=text]").val("");
                clone.find("#minus-rule").show();
                clone.find("#add-rule").hide();
                $("#rule-container").append(clone);

            });

            $("#rule-container").on("click", "#minus-rule", function () {
                $(this).closest(".rule-box").remove();
            });
        });

    </script>

@endsection
