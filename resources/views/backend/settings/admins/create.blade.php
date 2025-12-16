@extends('backend.layouts.form')
@section('title', __('Add New Admin'))
@section('section', __('Settings'))
@section('container', 'container-max-lg')
@section('back', route('admins.index'))
@section('content')
    <form id="vironeer-submited-form"
        action="{{ isset($forcedRole) && $forcedRole === 'report_admin' ? route('report-admins.store') : route('admins.store') }}"
        method="POST" enctype="multipart/form-data">
        @csrf
        @isset($forcedRole)
            <input type="hidden" name="role" value="{{ $forcedRole }}">
        @endisset
        
        <div class="card p-2">
            <div class="card-body">
                <div class="avatar text-center py-4">
                    <img id="filePreview" src="{{ asset('images/avatars/default.png') }}" class="rounded-circle mb-3"
                        width="120px" height="120px">
                    <button id="selectFileBtn" type="button"
                        class="btn btn-secondary d-flex m-auto">{{ __('Choose Image') }}</button>
                    <input id="selectedFileInput" type="file" name="avatar" accept="image/png, image/jpg, image/jpeg"
                        hidden>
                </div>
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">{{ __('First Name') }} : <span
                                    class="red">*</span></label>
                            <input type="firstname" class="form-control" name="firstname" required autofocus>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="mb-3">
                            <label class="form-label">{{ __('Last Name') }} : <span
                                    class="red">*</span></label>
                            <input type="lastname" class="form-control" name="lastname" required>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Email Address') }} : <span
                            class="red">*</span></label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Role') }}</label>
                    <select name="role" class="form-select">
                        <option value="admin">{{ __('Admin') }}</option>
                        <option value="report_admin">{{ __('Report Admin') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Password') }} : <span class="red">*</span></label>
                    <input type="text" class="form-control" name="password" value="{{ $password }}" required>
                </div>
            </div>
        </div>
    </form>
@endsection
