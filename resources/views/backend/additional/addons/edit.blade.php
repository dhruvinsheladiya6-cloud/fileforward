@extends('backend.layouts.form')
@section('title', __('Edit Addon | ') . $addon->name)
@section('container', 'container-max-lg')
@section('back', route('superadmin.additional.addons.index'))
@section('content')
    <form id="vironeer-submited-form" action="{{ route('superadmin.additional.addons.update', $addon->id) }}" method="POST"
        enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="vironeer-file-preview-box bg-light mb-3 p-4 text-center">
                    <div class="file-preview-box mb-3">
                        <img id="filePreview" src="{{ asset($addon->logo) }}" height="100">
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-lg-6">
                        <label class="form-label">{{ __('Name') }} : </label>
                        <input class="form-control" value="{{ $addon->name }}" readonly>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">{{ __('Addon Update Files (Zip)') }} : <span
                                class="red">*</span></label>
                        <input class="form-control" type="file" name="addon_files">
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
