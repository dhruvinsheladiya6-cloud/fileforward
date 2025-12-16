@extends('vironeer::layouts.app')
@section('title', 'General Information - Necessary details')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('General Information') }} <i
                class="text-muted fas fa-angle-right me-2 ms-2"></i>
            {{ __('Necessary details') }}</p>
        <p class="vironeer-form-info-text">
            {{ __('Congrats your website is almost be done, now you need to import your database and provide some information about your website and setting the admin access details.') }}
        </p>
    </div>
    <div class="vironeer-requirements">
        @if ($errors->any())
            <div class="alert alert-danger mb-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </div>
        @endif
        <form id="buildingForm" action="{{ route('install.information.building') }}" method="POST">
            @csrf
            <div class="card mb-3">
                <div class="card-header bg-dark text-white"><i
                        class="fas fa-question-circle me-2"></i>{{ __('Website Information') }}</div>
                <div class="card-body py-4">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Website name') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="website_name" value="{{ old('website_name') }}"
                                    class="form-control" placeholder="{{ __('Website name') }}" autocomplete="off" required>
                                <span class="input-group-text"><i class="fas fa-globe"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Website URL') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="website_url" value="{{ old('website_url') ?? url('/') }}"
                                    class="form-control" placeholder="{{ __('Website URL') }}" required>
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white"><i
                        class="fa fa-user me-2"></i>{{ __('Admin Information') }}</div>
                <div class="card-body py-4">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Firstname') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="firstname" value="{{ old('firstname') }}" class="form-control"
                                    placeholder="{{ __('First Name') }}" autocomplete="off" required>
                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Lastname') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="lastname" value="{{ old('lastname') }}" class="form-control"
                                    placeholder="{{ __('Last Name') }}" required>
                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-12">
                            <label class="form-label">{{ __('Admin email') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="email" name="email" value="{{ old('email') }}" class="form-control"
                                    placeholder="john@example.com" autocomplete="off" required>
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Admin password') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="password" name="password" class="form-control"
                                    placeholder="{{ __('Password') }}" autocomplete="off" required>
                                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Confirm password') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="password" name="password_confirmation" class="form-control"
                                    placeholder="{{ __('Confirm password') }}" autocomplete="off" required>
                                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <form class="d-inline" action="{{ route('install.information.building.back') }}" method="POST">
            @csrf
            <button class="btn btn-dark border-0" style="padding: 10px 25px;"><i
                    class="fas fa-arrow-left me-2"></i>{{ __('Back') }}</button>
        </form>
        <button form="buildingForm" class="btn btn-primary">{{ __('Finish installation') }}</button>
    </div>
@endsection
