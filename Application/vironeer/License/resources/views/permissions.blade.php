@extends('vironeer::layouts.app')
@section('title', 'Permission')
@section('content')
    <div class="vironeer-form-info mb-4">
        <p class="vironeer-form-info-title">{{ __('File Permissions') }}</p>
        <div class="col-xl-9">
            <p class="vironeer-form-info-text">
                {{ __('File permissions is important or the website functions will not working, file uploads, reading files and more...') }}
            </p>
        </div>
    </div>
    <div class="vironeer-requirements">
        <div class="card">
            <div class="card-header bg-dark text-white"><i class="fas fa-folder-open me-2"></i>{{ __('File Permissions') }}
            </div>
            <div class="card-body p-0 border-0">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('Files') }}</th>
                            <th class="text-center">{{ __('Permission status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td><i
                                        class="fas fa-folder-open me-2"></i>{{ str_replace(base_path() . '/', '', $permission) }}
                                </td>
                                <td class="text-center">
                                    @if (filePermissionValidation($permission))
                                        <i class="fas fa-check me-2"></i> {{ __('Enabled Permission') }}
                                    @else
                                        <i class="fas fa-times me-2"></i> {{ __('Permission Required') }}
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
                    {{ __('Congrats all permissions are enabled you can continue to next step') }}
                </div>
                <form action="{{ route('install.permissions') }}" method="POST">
                    @csrf
                    <button class="btn btn-primary">{{ __('Continue') }}</button>
                </form>
            @else
                <div class="alert alert-danger">
                    {{ __('Some permissions are missing please give 0775 permission to all files above.') }}</div>
                <button class="btn btn-primary" disabled>{{ __('Continue') }}</button>
            @endif
        </div>
    </div>
@endsection
