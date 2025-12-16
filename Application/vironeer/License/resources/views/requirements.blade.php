@extends('vironeer::layouts.app')
@section('title', 'Requirements')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('Server requirements') }}</p>
        <div class="col-xl-9">
            <p class="vironeer-form-info-text">
                {{ __('Here we will check if your server has the requirements to run the script or not, if any of this requirement is not enabled on your server please enable it before you can continue.') }}
            </p>
        </div>
    </div>
    <div class="vironeer-requirements">
        <div class="card">
            <div class="card-header bg-dark text-white"><i class="fas fa-server me-2"></i>{{ __('Requirements') }}</div>
            <div class="card-body p-0 border-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th class="text-center">{{ __('Extension') }}</th>
                            <th class="text-center">{{ __('Action') }}</th>
                            <th class="text-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($extensions as $extension)
                            <tr>
                                <td class="text-center">{{ $extension }}</td>
                                <td class="text-center">
                                    @if (extensionAvailability($extension))
                                        {{ __('Available') }}
                                    @else
                                        {{ __('Required') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if (extensionAvailability($extension))
                                        <i class="fas fa-check me-2"></i> {{ __('Enabled') }}
                                    @else
                                        <i class="fas fa-times me-2"></i> {{ __('Disabled') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-0">
            @if (!$error)
                <div class="alert alert-success">
                    {{ __('Congrats all extensions are enabled you can continue to next step') }}
                </div>
                <form action="{{ route('install.requirements') }}" method="POST">
                    @csrf
                    <button class="btn btn-primary">{{ __('Continue') }}</button>
                </form>
            @else
                <div class="alert alert-danger">
                    {{ __('Some extensions are required please enable them before you can continue.') }}</div>
                <button class="btn btn-primary" disabled>{{ __('Continue') }}</button>
            @endif
        </div>
    </div>
@endsection
