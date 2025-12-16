@extends('vironeer::layouts.app')
@section('title', 'General Information - Database Info')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('General Information') }} <i
                class="text-muted fas fa-angle-right me-2 ms-2"></i>
            {{ __('Database Info') }}</p>
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
        <form action="{{ route('install.information.database') }}" method="POST">
            @csrf
            <div class="card mb-3">
                <div class="card-header bg-dark text-white"><i
                        class="fas fa-database me-2"></i>{{ __('Database Information') }}
                </div>
                <div class="card-body py-4">
                    <div class="row g-3">
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Database host') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="db_host" class="form-control"
                                    placeholder="{{ __('Database host') }}" value="{{ old('db_host') ?? 'localhost' }}"
                                    required>
                                <span class="input-group-text"><i class="fas fa-server"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Database username') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="db_user" class="form-control"
                                    placeholder="{{ __('Database username') }}" value="{{ old('db_user') }}"
                                    autocomplete="off" required>
                                <span class="input-group-text"><i class="fa fa-user"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Database name') }} : <span class="red">*</span></label>
                            <div class="input-group rtl">
                                <input type="text" name="db_name" class="form-control"
                                    placeholder="{{ __('Database name') }}" value="{{ old('db_name') }}"
                                    autocomplete="off" required>
                                <span class="input-group-text"><i class="fas fa-question-circle"></i></span>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label">{{ __('Database password') }} : </label>
                            <div class="input-group rtl">
                                <input type="password" name="db_pass" class="form-control"
                                    placeholder="{{ __('Database password') }}" autocomplete="off">
                                <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <button class="btn btn-primary">{{ __('Continue') }}</button>
        </form>
    </div>
@endsection
