<!-- resources/views/profile/edit.blade.php -->
@extends('layouts.app')

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit Profile</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea class="form-control" id="address" name="address" rows="3">{{ $user->address }}</textarea>
                </div>

                <div class="form-group">
                    <label for="profile_pic">Profile Picture</label>
                    <input type="file" class="form-control-file" id="profile_pic" name="profile_pic">
                    @if ($user->profile_pic)
                        <img src="{{ asset('storage/profile_pics/' . $user->profile_pic) }}" alt="Profile Picture" class="img-thumbnail mt-2" style="max-width: 200px;">
                    @endif
                </div>

                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
    </div>
@endsection