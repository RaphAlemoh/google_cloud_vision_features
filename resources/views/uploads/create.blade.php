@extends('layouts.app')

@section('content')
<section class="vh-100" style="background-color: #eee;">
    <div class="container h-100">
        <div class="row d-flex justify-content-center align-items-center h-100">
            <div class="col-lg-12 col-xl-11">
                <div class="card text-black" style="border-radius: 25px;">
                    <div class="card-body p-md-5 mt-4">
                        @if ($errors->any())
                        <ul class="notification red">
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @endif
                        @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <p>{{ $message }}</p>
                        </div>
                        @endif
                        @if ($message = Session::get('danger'))
                        <div class="alert alert-danger">
                            <p>{{ $message }}</p>
                        </div>
                        @endif
                        <div class="row justify-content-between">

                            <div class="col-md-10 col-lg-6 col-xl-5">

                                <p class="text-center h1 fw-bold mb-5 mx-1 mx-md-4 mt-4">Upload Profile Picture</p>
                                <!-- <p class="text-center h1 fw-bold mb-5 mx-1 mx-md-4 mt-4">Upload Document/PDF</p> -->


                                <form class="mx-1 mx-md-4" method="POST" action="{{ route('uploads.store') }}" enctype="multipart/form-data">

                                    @csrf

                                    <div class="d-flex flex-row align-items-center mb-4">
                                        <i class="fas fa-camera fa-lg me-3 fa-fw"></i>
                                        <div class="form-outline flex-fill mb-0">
                                            <label class="form-label" for="form3Example3c">Profile picture</label>
                                            <!-- <label class="form-label" for="form3Example3c">Upload document/PDF</label> -->
                                            <input type="file" id="form3Example3c" class="form-control @error('avatar') is-invalid @enderror" name="avatar" value="{{ old('avatar') }}" accept="image/*" required />
                                            <!-- <input type="file" id="form3Example3c" class="form-control @error('avatar') is-invalid @enderror" name="avatar" value="{{ old('avatar') }}" required /> -->

                                            @error('avatar')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>


                                    <div class="d-flex justify-content-center mx-4 mb-3 mb-lg-4">
                                        <button type="submit" class="btn btn-primary btn-lg">Update Profile Pic</button>
                                        <!-- <button type="submit" class="btn btn-primary btn-lg">Update Document/PDF</button> -->
                                    </div>

                                </form>

                            </div>
                            <div class="col-md-1 col-lg-2 col-xl-4">
                                <img src="{{ Auth::user()->avatar }}" alt="" height="250" width="250">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
