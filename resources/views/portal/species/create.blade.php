@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Add Specie</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('specie.index') }}">Specie List</a></li>
                                    <li class="breadcrumb-item active">Add Specie</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('specie.store') }}" method="POST" class="add-user-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input class="form-control" required type="text" name="name" value="{{ old('name') }}">
                                            </div>
                                        </div>
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Formula</label>
                                                <input class="form-control" required type="text" name="formula" value="{{ old('formula') }}">
                                            </div>
                                        </div>
                                        @error('formula')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Validation Rule</label>
                                                <input class="form-control" required type="text" name="validation_rule" value="{{ old('validation_rule') }}">
                                            </div>
                                        </div>
                                        @error('validation_rule')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Minimum Validation Rule</label>
                                                <input class="form-control" required type="text" name="min_validation_rule" value="{{ old('min_validation_rule') }}">
                                            </div>
                                        </div>
                                        @error('min_validation_rule')
                                        <span class="text-danger">{{ $message }}</span>
                                        @enderror
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
