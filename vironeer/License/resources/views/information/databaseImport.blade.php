@extends('vironeer::layouts.app')
@section('title', 'General Information - Database Import')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('General Information') }} <i
                class="text-muted fas fa-angle-right me-2 ms-2"></i>
            {{ __('Database Import') }}</p>
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
        <div class="card">
            <div class="card-header bg-dark text-white"><i class="fas fa-upload me-2"></i> {{ __('Database Import') }}
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active text-success" id="auto-tab" data-bs-toggle="tab"
                            data-bs-target="#auto" type="button" role="tab" aria-controls="auto"
                            aria-selected="true"><i class="fas fa-file-import me-2"></i>{{ __('Auto Import') }}</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link text-secondary" id="manual-tab" data-bs-toggle="tab"
                            data-bs-target="#manual" type="button" role="tab" aria-controls="manual"
                            aria-selected="false"><i
                                class="fas fa-file-download me-2"></i>{{ __('Manual Import') }}</button>
                    </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                    <div class="tab-pane fade show active" id="auto" role="tabpanel" aria-labelledby="auto-tab">
                        <div class="card-body border-top-0 p-4">
                            <h3 class="text-muted mb-3">
                                {{ __('Importing your database automatically, click import now') }}
                            </h3>
                            <div class="mb-3">
                                <form action="{{ route('install.information.databaseImport') }}" method="POST">
                                    @csrf
                                    <button class="btn btn-success btn-lg"><i
                                            class="fas fa-upload me-2"></i>{{ __('Import Now') }}</button>
                                </form>
                            </div>
                            <div class="alert alert-danger mb-0">
                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <img src="https://cdn.vironeer.com/applications/installer/xq5HxEVrN2uIty35M2QW.png"
                                            alt="500 error" width="100%">
                                    </div>
                                    <div class="col-lg-8">
                                        <strong>{{ __('Important Notice !') }} :</strong>
                                        <hr>
                                        <p>{{ __('Auto import is not supported on some servers, if you click import and you get 500 Error that means your server does not support it, please use the manual import.') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                        <div class="card-body border-top-0 p-4">
                            <h3 class="text-muted mb-3">
                                {{ __('Importing your database Manually, follow the steps') }}
                            </h3>
                            <hr>
                            <div class="mb-0">
                                <h6 class="text-muted">{{ __('1 - Download the SQL file') }}</h6>
                                <form action="{{ route('install.information.databaseImport.download.sql') }}"
                                    method="POST">
                                    @csrf
                                    <button class="btn btn-warning btn-lg"><i
                                            class="fas fa-download me-2"></i>{{ __('Download SQL file') }}</button>
                                </form>
                                <hr>
                                <h6 class="text-muted">{{ __('2 - Follow this steps') }}</h6>
                                <img src="https://cdn.vironeer.com/applications/installer/0pyUf9UaDU354XzcjUQk.png"
                                    alt="steps" style="width:100%;max-width: 500px">
                                <div class="alert alert-warning mb-0 mt-3">
                                    <p class="mb-0">
                                        {{ __('Check this video to know how you can import the database') }} : <a
                                            href="https://www.youtube.com/watch?v=jW5lrS6EUPM&ab_channel=HostGator"
                                            target="_blank">https://www.youtube.com/watch?v=jW5lrS6EUPM&ab_channel=HostGator</a>
                                    </p>
                                </div>
                                <hr>
                                <h6 class="text-muted">
                                    {{ __('3 - After import the database, click Skip to next step') }}</h6>
                                <form action="{{ route('install.information.databaseImport.skip') }}" method="POST">
                                    @csrf
                                    <button class="btn btn-primary">{{ __('Skip to next step') }}</button>
                                </form>
                                <div class="alert alert-danger mt-3 mb-0">
                                    {{ __('Make sure you import the database before clicking skip to next step') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
