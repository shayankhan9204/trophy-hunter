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
                                    <input type="hidden" name="removed_media_ids" id="removed_media_ids" value="">

                                    <h4>Event Basic Information</h4>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Event Name</label>
                                                <input class="form-control" required type="text" name="name"
                                                       value="{{ $event->name ?? old('name') }}">
                                            </div>
                                            @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Fish Bag Size</label>
                                                <input class="form-control" type="number" name="fish_bag_size"
                                                       value="{{ $event->fish_bag_size ?? ('fish_bag_size')  }}">
                                            </div>
                                            @error('fish_bag_size')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label>Event Location</label>
                                                <input class="form-control" required type="text" name="location" style="height: 50px !important;"
                                                       value="{{ $event->location ?? old('location') }}">
                                            </div>
                                            @error('location')
                                            <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>


{{--                                        <div class="col-md-6">--}}
{{--                                            <div class="form-group">--}}
{{--                                                <label>Teams</label>--}}
{{--                                                <select name="teams[]" id="angler_select" class="form-control select2"--}}
{{--                                                        multiple="multiple">--}}
{{--                                                    @foreach($teams as $team)--}}
{{--                                                        <option value="{{ $team->id }}"--}}
{{--                                                            {{ $event->teams->contains('id', $team->id) ? 'selected' : '' }}>--}}
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

                                    <div id="dates-container">
                                        <h4>Event Dates</h4>
                                        @forelse($event->dates as $i => $date)
                                            <div class="dates-box">
                                                <div class="row form-group">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Event Date</label>
                                                            <input class="form-control" required type="date" name="date[]"
                                                                   value="{{ $date->date ?? '' }}">
                                                        </div>
                                                        @error('date')
                                                        <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Start Time</label>
                                                            <input class="form-control" required type="time" name="start_time[]"
                                                                   value="{{ $date->start_time ?? '' }}">
                                                        </div>
                                                        @error('start_time')
                                                        <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>

                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>End Time</label>
                                                            <input class="form-control" required type="time" name="end_time[]"
                                                                   value="{{ $date->end_time ?? '' }}">
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
                                            @empty
                                                <div class="dates-box">
                                                    <div class="row form-group">
                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label>Event Date</label>
                                                                <input class="form-control" required type="date" name="date[]"
                                                                       value="">
                                                            </div>
                                                        </div>
                                                        @error('date')
                                                        <span class="text-danger">{{ $message }}</span>
                                                        @enderror

                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label>Start Time</label>
                                                                <input class="form-control" required type="time" name="start_time[]"
                                                                       value="">
                                                            </div>
                                                            @error('start_time')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>


                                                        <div class="col-md-3">
                                                            <div class="form-group">
                                                                <label>End Time</label>
                                                                <input class="form-control" required type="time" name="end_time[]"
                                                                       value="">
                                                            </div>
                                                            @error('end_time')
                                                            <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>


                                                        <div
                                                            class="col-1 d-flex justify-content-between align-items-center">
                                                            <button type="button" id="minus-date"
                                                                    class="btn-danger var-btn" style="display: none">
                                                                <i class="fas fa-minus"></i>
                                                            </button>

                                                            <button type="button" id="add-date"
                                                                    class="btn-primary var-btn">
                                                                <i class="fas fa-plus"></i>
                                                            </button>
                                                        </div>
                                                    </div>

                                                </div>
                                        @endforelse
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
                                                <input class="form-control" type="file" name="sponsors[]" id="sponsor-upload" multiple>
                                            </div>
                                        </div>
                                        @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="row mb-3">
                                        <label class="col-md-12">Existing Sponsors</label>
                                        @foreach($event->getMedia('sponsors') as $media)
                                            <div class="col-md-3 text-center" id="media-{{ $media->id }}" style="position:relative;">
                                                <img src="{{ $media->getUrl() }}" alt="Sponsor" class="img-thumbnail" style="height: 120px; width: 100%; object-fit: contain">
                                                <button type="button" class="btn btn-sm text-danger" onclick="removeMedia({{ $media->id }})"
                                                        style="position:absolute; top: 5px; right:5px;">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="row mb-3" id="new-sponsor-previews">
                                    </div>

                                    <h4>Tagged Event</h4>
                                    <p>
                                        <strong>Note:</strong> Check this box to enable the tagged system for this event,
                                        or leave it unchecked to disable.
                                    </p>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="">
                                                <input class="" @if($event->is_tagged == 1) checked @endif
                                                       type="checkbox" value="1" name="is_tagged" multiple>
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
                                                        <option {{ $event->species->contains('id', $specie->id) ? 'selected' : '' }}
                                                                value="{{ $specie->id }}">
                                                            {{ $specie->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
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
        function removeMedia(mediaId) {
            document.getElementById('media-' + mediaId).remove();

            const input = document.getElementById('removed_media_ids');
            let current = input.value ? input.value.split(',') : [];

            if (!current.includes(mediaId.toString())) {
                current.push(mediaId);
            }

            input.value = current.join(',');
        }

        const input = document.getElementById('sponsor-upload');
        const previewContainer = document.getElementById('new-sponsor-previews');

        let filesToUpload = [];

        input.addEventListener('change', function(e) {
            for (const file of e.target.files) {
                filesToUpload.push(file);
            }
            updatePreviews();
            updateInputFiles();
        });

        function updatePreviews() {
            previewContainer.innerHTML = '';

            filesToUpload.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'col-md-3 text-center position-relative';
                    div.id = 'preview-' + index;
                    div.innerHTML = `
                <img src="${e.target.result}" class="img-thumbnail" style="height:120px; width:100%; object-fit:contain;">
                <button type="button" class="btn btn-sm text-danger" style="position:absolute; top:5px; right:5px;" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        function removeFile(index) {
            filesToUpload.splice(index, 1);
            updatePreviews();
            updateInputFiles();
        }

        function updateInputFiles() {
            const dataTransfer = new DataTransfer();

            filesToUpload.forEach(file => {
                dataTransfer.items.add(file);
            });

            input.files = dataTransfer.files;
        }

    </script>

    <script>
        $(document).ready(function () {

            $("#dates-container").on("click", "#add-date", function () {
                var clone = $(".dates-box:first").clone(true);
                var select = clone.find("select");

                select.val(null).trigger("change");
                clone.find("input[type=text]").val("");
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
