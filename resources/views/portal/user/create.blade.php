@extends('layouts.portal.app')

@section('content')
    <div class="page-wrapper sifu-cform">

        <div class="page-content">

            <div class="container-fluid">
                <!-- Page-Title -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="page-title-box">
                            <h4 class="page-title">Add User</h4>
                            <div class="float-left">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('user.index') }}">User List</a></li>
                                    <li class="breadcrumb-item active">Add User</li>
                                </ol>
                            </div>
                        </div><!--end page-title-box-->
                    </div><!--end col-->
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <form action="{{ route('user.store') }}" method="POST" class="add-user-form">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Name</label>
                                                <input class="form-control" required type="text" name="full_name" value="{{ old('full_name') }}">
                                            </div>
                                        </div>
                                        @error('name')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Email address</label>
                                                <input class="form-control" required type="text" name="email" value="{{ old('email') }}">
                                            </div>
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Password</label>
                                                <input class="form-control" required type="password" name="password">
                                            </div>
                                            @error('password')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>User Role</label>
                                                <select required class="form-control select2" name="role">
                                                    <option value="">Select User Role</option>
                                                    @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                                            {{ $role->name }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @error('role')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
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
        document.addEventListener('DOMContentLoaded', function() {
            const maritalStatus = document.getElementById('maritalStatus');
            const childrenField = document.getElementById('childrenField');

            const toggleChildrenField = () => {
                if (maritalStatus.value === 'Single') {
                    childrenField.style.display = 'none';
                } else {
                    childrenField.style.display = 'block';
                }
            };

            toggleChildrenField();

            maritalStatus.addEventListener('change', toggleChildrenField);
        });
    </script>
@endsection
