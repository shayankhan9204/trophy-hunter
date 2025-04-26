@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Edit Team</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('team.index') }}">Team List</a></li>
                                    <li class="breadcrumb-item active">Edit Team</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('team.update') }}" method="POST" class="add-user-form">
                                    @csrf
                                    <input type="hidden" value="{{ $team->id }}" name="id"/>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Team Name</label>
                                                <input class="form-control" value="{{ $team->name ?? '' }}" type="text"
                                                       name="name">
                                            </div>
                                        </div>
                                        @error('name')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div id="angler-container">
                                        <h4>Team Anglers</h4>

                                        @forelse($team->anglers as $index => $angular)
                                            <div class="angler-box">
                                                <input type="hidden" name="angler_id[]"
                                                       value="{{ $angular->id ?? '' }}">

                                                <div class="row form-group">
                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Name</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_name[]"
                                                                       value="{{ $angular->name }}"
                                                                       placeholder="Type Name ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Email</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_email[]"
                                                                       value="{{ $angular->email }}"
                                                                       placeholder="Type Email ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Phone</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_phone[]"
                                                                       value="{{ $angular->phone }}"
                                                                       placeholder="Type Phone ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Category</label>
                                                                <select class="form-control" name="angler_category[]">
                                                                    <option
                                                                        value="Adult" {{ $angular->category === 'adult' ? 'selected' : '' }}>
                                                                        Adult
                                                                    </option>
                                                                    <option
                                                                        value="Junior" {{ $angular->category === 'junior' ? 'selected' : '' }}>
                                                                        Junior
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    <div
                                                        class="col-1 d-flex justify-content-between align-items-center">
                                                        <button type="button" id="minus-angler"
                                                                class="btn-danger var-btn"
                                                                style="@if($index == 0) display: none @endif ">
                                                            <i class="fas fa-minus"></i>
                                                        </button>

                                                        <button type="button" id="add-angler"
                                                                class="btn-primary var-btn"
                                                                style="@if($index != 0) display: none @endif ">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>

                                            </div>
                                        @empty
                                            <div class="angler-box">
                                                <div class="row form-group">
                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Name</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_name[]"
                                                                       value=""
                                                                       placeholder="Type Name ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Email</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_email[]"
                                                                       value=""
                                                                       placeholder="Type Email ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Phone</label>
                                                                <input type="text" class="form-control"
                                                                       name="angler_phone[]"
                                                                       value=""
                                                                       placeholder="Type Phone ">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-2 p-0">
                                                        <div class="col-lg-12">
                                                            <div class="form-group">
                                                                <label class="m-0">Category</label>
                                                                <select class="form-control" name="angler_category[]">
                                                                    <option
                                                                        value="adult">
                                                                        Adult
                                                                    </option>
                                                                    <option
                                                                        value="junior">
                                                                        Junior
                                                                    </option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>


                                                    <div
                                                        class="col-1 d-flex justify-content-between align-items-center">
                                                        <button type="button" id="minus-angler"
                                                                class="btn-danger var-btn" style="display: none">
                                                            <i class="fas fa-minus"></i>
                                                        </button>

                                                        <button type="button" id="add-angler"
                                                                class="btn-primary var-btn">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
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

            $("#angler-container").on("click", "#add-angler", function () {
                var clone = $(".angler-box:first").clone(true);

                var select = clone.find("select");
                select.val(null).trigger("change");
                clone.find("input[type=text]").val("");
                clone.find("input[name='angler_id[]']").val('');
                clone.find("#minus-angler").show();
                clone.find("#add-angler").hide();
                $("#angler-container").append(clone);

            });

            $("#angler-container").on("click", "#minus-angler", function () {
                $(this).closest(".angler-box").remove();
            });
        });

    </script>

@endsection
