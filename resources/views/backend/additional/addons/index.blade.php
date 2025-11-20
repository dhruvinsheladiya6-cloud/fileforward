@extends('backend.layouts.grid')
@section('title', __('Addons Manager'))
@section('add_modal', 'Add New Addon')
@section('content')
    <div class="custom-card card">
        <table id="datatable" class="table w-100">
            <thead>
                <tr>
                    <th class="tb-w-1x">{{ __('#') }}</th>
                    <th class="tb-w-3x">{{ __('Logo') }}</th>
                    <th class="tb-w-7x">{{ __('Name') }}</th>
                    <th class="tb-w-3x">{{ __('Version') }}</th>
                    <th class="tb-w-3x">{{ __('Status') }}</th>
                    <th class="tb-w-7x">{{ __('Added at') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($addons as $addon)
                    <tr class="item">
                        <td>{{ $addon->id }}</td>
                        <td><a href="{{ route('superadmin.additional.addons.edit', $addon->id) }}">
                                <img src="{{ asset($addon->logo) }}" alt="{{ $addon->name }}" height="35">
                            </a>
                        </td>
                        <td>{{ $addon->name }}</td>
                        <td><span class="badge bg-dark">{{ $addon->version }}</span></td>
                        <td>
                            @if ($addon->status)
                                <span class="badge bg-success">{{ __('Active') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('Disabled') }}</span>
                            @endif
                        </td>
                        <td>{{ vDate($addon->created_at) }}</td>
                        <td>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown"
                                    aria-expanded="true">
                                    <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-sm-end" data-popper-placement="bottom-end">
                                    <li>
                                        <a class="dropdown-item"
                                            href="{{ route('superadmin.additional.addons.edit', $addon->id) }}"><i
                                                class="fa fa-edit me-2"></i>{{ __('Edit') }}</a>
                                    </li>
                                    @if ($addon->action_text)
                                        <li>
                                            <hr class="dropdown-divider" />
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ url($addon->action_link) }}"><i
                                                    class="fas fa-external-link-alt me-2"></i>{{ $addon->action_text }}</a>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModallLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">{{ __('New Addon') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addNewForm" action="{{ route('superadmin.additional.addons.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">{{ __('Addon purchase code') }} : <span
                                    class="red">*</span></label>
                            <input type="text" name="purchase_code" class="form-control"
                                placeholder="{{ __('Purchase code') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">{{ __('Addon files (Zip)') }} : <span class="red">*</span></label>
                            <input type="file" name="addon_files" class="form-control" accept=".zip" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button form="addNewForm" class="btn btn-primary">{{ __('Install') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
