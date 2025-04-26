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
                                        </div>
                                        @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Date</label>
                                                <input class="form-control" required type="date" name="date"
                                                       value="{{ old('date') }}">
                                            </div>
                                        </div>
                                        @error('date')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Location</label>
                                                <input class="form-control" required type="text" name="location"
                                                       value="{{ old('location') }}">
                                            </div>
                                        </div>
                                        @error('location')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Teams</label>
                                                <select name="teams[]" id="angler_select" class="form-control select2"
                                                        multiple="multiple">
                                                    @foreach($teams as $team)
                                                        <option value="{{ $team->id }}">
                                                            {{ $team->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        @error('teams')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Start Time</label>
                                                <input class="form-control" required type="time" name="start_time"
                                                       value="{{ old('start_time') }}">
                                            </div>
                                        </div>
                                        @error('start_time')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Start Time</label>
                                                <input class="form-control" required type="time" name="end_time"
                                                       value="{{ old('end_time') }}">
                                            </div>
                                        </div>
                                        @error('end_time')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror


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
                                        @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div id="contact-container">
                                        <h4>Event Committee Contacts </h4>
                                        <p>
                                            <strong>Note:</strong> If a committee member doesn’t have an email or phone
                                            number, you can simply leave those fields blank.
                                        </p>

                                        <div class="contact-box">
                                            <div class="row form-group">
                                                <div class="col-3 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Name</label>
                                                            <input type="text" class="form-control"
                                                                   name="contact_name[]"
                                                                   value=""
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
                                                                   value=""
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
                                                                   value=""
                                                                   placeholder="Type Phone ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="col-1 d-flex justify-content-between align-items-center">
                                                    <button type="button" id="minus-contact"
                                                            class="btn-danger var-btn" style="display: none">
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <button type="button" id="add-contact"
                                                            class="btn-primary var-btn">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div id="rule-container">
                                        <h4>Event Rules </h4>

                                        <div class="rule-box">
                                            <div class="row form-group">
                                                <div class="col-9 p-0">
                                                    <div class="col-lg-12">
                                                        <div class="form-group">
                                                            <label class="m-0">Title</label>
                                                            <input type="text" class="form-control"
                                                                   name="event_title[]"
                                                                   value=""
                                                                   placeholder="Type Name ">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="col-1 d-flex justify-content-between align-items-center">
                                                    <button type="button" id="minus-rule"
                                                            class="btn-danger var-btn" style="display: none">
                                                        <i class="fas fa-minus"></i>
                                                    </button>

                                                    <button type="button" id="add-rule"
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
                                                                      name="description[]">{{ old('description') }}
                                                            </textarea>
                                                        </div>
                                                    </div>
                                                </div>

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
