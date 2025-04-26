@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Edit Event</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('event.index') }}">Event List</a></li>
                                    <li class="breadcrumb-item active">Edit Event</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('event.update') }}" method="POST" enctype="multipart/form-data"
                                      class="add-user-form">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $event->id }}">

                                    <h4>Event Basic Information</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Name</label>
                                                <input class="form-control" required type="text" name="name"
                                                       value="{{ $event->name ?? old('name') }}">
                                            </div>
                                        </div>
                                        @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Date</label>
                                                <input class="form-control" required type="date" name="date"
                                                       value="{{ $event->date ?? old('date') }}">
                                            </div>
                                        </div>
                                        @error('date')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Location</label>
                                                <input class="form-control" required type="text" name="location"
                                                       value="{{ $event->location ?? old('location') }}">
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
                                                        <option value="{{ $team->id }}"
                                                            {{ $event->teams->contains('id', $team->id) ? 'selected' : '' }}>
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
                                                       value="{{ $event->start_time ?? old('start_time') }}">
                                            </div>
                                        </div>
                                        @error('start_time')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Start Time</label>
                                                <input class="form-control" required type="time" name="end_time"
                                                       value="{{ $event->end_time ?? old('end_time') }}">
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

                                        @forelse($event->contacts as $index => $contact)
                                            <div class="contact-box">
                                                <div class="row form-group">
                                                    <div class="col-3 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Name</label>
                                                                <input type="text" class="form-control"
                                                                       name="contact_name[]"
                                                                       value="{{ $contact->name }}"
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
                                                                       value="{{ $contact->email }}"
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
                                                                       value="{{ $contact->phone }}"
                                                                       placeholder="Type Phone ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div
                                                        class="col-1 d-flex justify-content-between align-items-center">
                                                        <button type="button" id="minus-contact"
                                                                class="btn-danger var-btn"
                                                                style="@if($index == 0) display: none @endif ">
                                                            <i class="fas fa-minus"></i>
                                                        </button>

                                                        <button type="button" id="add-contact"
                                                                class="btn-primary var-btn"
                                                                style="@if($index != 0) display: none @endif ">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>

                                        @empty
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

                                        @endforelse
                                    </div>

                                    <div id="rule-container">
                                        <h4>Event Rules</h4>

                                        @forelse($event->rules as $index => $rule)
                                            <div class="rule-box">
                                                <div class="row form-group">
                                                    <div class="col-9 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Title</label>
                                                                <input type="text" class="form-control"
                                                                       name="title[]"
                                                                       value="{{ $rule->title ?? '' }}"
                                                                       placeholder="Type Name">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-1 d-flex justify-content-between align-items-center">
                                                        <button type="button" class="minus-rule btn-danger var-btn"
                                                                style="@if($index == 0) display: none @endif ">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <button type="button" class="add-rule btn-primary var-btn" id="add-rule"
                                                                style="@if($index != 0) display: none @endif ">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row form-group">
                                                    <div class="col-9 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Description</label>
                                                                <textarea name="description[]"
                                                                          class="form-control ckeditor"
                                                                          id="editor_{{ $index }}">{{ $rule->description ?? '' }}</textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="rule-box">
                                                <div class="row form-group">
                                                    <div class="col-9 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Title</label>
                                                                <input type="text" class="form-control"
                                                                       name="title[]"
                                                                       value=""
                                                                       placeholder="Type Name">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-1 d-flex justify-content-between align-items-center">
                                                        <button type="button" class="minus-rule btn-danger var-btn" style="display: none">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <button type="button" class="add-rule btn-primary var-btn" id="add-rule">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="row form-group">
                                                    <div class="col-9 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Description</label>
                                                                <textarea name="description[]"
                                                                          class="form-control ckeditor"
                                                                          id="editor_0"></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforelse
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

            function initCKEditor() {
                $('.ckeditor').each(function () {
                    if (!$(this).hasClass('ckeditor-initialized')) {
                        CKEDITOR.replace(this);
                        $(this).addClass('ckeditor-initialized');
                    }
                });
            }
            $("#rule-container").on("click", "#add-rule", function () {
                // Get the current count of rule boxes to generate unique IDs
                const ruleCount = $(".rule-box").length;
                const newId = "editor_" + ruleCount;

                // Create new HTML template
                const newRuleHtml = `
    <div class="rule-box">
        <div class="row form-group">
            <div class="col-9 p-0">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="m-0">Title</label>
                        <input type="text" class="form-control"
                               name="title[]"
                               value=""
                               placeholder="Type Title">
                    </div>
                </div>
            </div>
            <div class="col-1 d-flex justify-content-between align-items-center">
                <button type="button" class="minus-rule btn-danger var-btn">
                    <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="add-rule btn-primary var-btn" style="display: none">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        <div class="row form-group">
            <div class="col-9 p-0">
                <div class="col-lg-12">
                    <div class="form-group">
                        <label class="m-0">Description</label>
                        <textarea name="description[]"
                                  class="form-control ckeditor"
                                  id="${newId}"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;

                // Append the new HTML
                $("#rule-container").append(newRuleHtml);

                // Hide the plus button on the previous rule box
                // $(this).closest(".rule-box").find(".add-rule").hide();
                // $(this).closest(".rule-box").find(".minus-rule").show();

                // Initialize CKEditor for the new textarea
                if (typeof CKEDITOR !== 'undefined') {
                    CKEDITOR.replace(newId);
                }
            });

            $("#rule-container").on("click", ".minus-rule", function () {
                const box = $(this).closest(".rule-box");

                // Destroy CKEditor instance
                const editorId = box.find("textarea").attr('id');
                if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[editorId]) {
                    CKEDITOR.instances[editorId].destroy();
                }

                box.remove();

                // Show the plus button on the last remaining rule box
                // if ($(".rule-box").length > 0) {
                //     $(".rule-box:last").find(".add-rule").show();
                //     $(".rule-box:last").find(".minus-rule").hide();
                // }
            });
        });

    </script>

@endsection
