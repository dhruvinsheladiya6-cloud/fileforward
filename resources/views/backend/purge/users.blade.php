@extends('backend.layouts.grid')
@section('title', __('Scheduled deletions by user'))

@section('content')
    {{-- Reuse your counters --}}
    @include('backend.purge._counters', ['stats' => $stats])

    <div class="card custom-card">
        <div class="table-responsive">
            <table class="table w-100 align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ __('User') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Items') }}</th>
                        <th>{{ __('Total size') }}</th>
                        <th>{{ __('Next purge at') }}</th>
                        <th>{{ __('Last deleted at') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($users as $row)
                    @php
                        $u = $row->user;
                        $overdue = $row->next_purge_at && now()->greaterThan($row->next_purge_at);
                    @endphp
                    <tr>
                        <td>{{ $u?->id ?? '-' }}</td>
                        <td>
                            @if($u)
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold">{{ trim(($u->firstname.' '.$u->lastname)) ?: ($u->username ?? $u->email) }}</span>
                                    <small class="text-muted">{{ __('ID:') }} {{ $u->id }}</small>
                                </div>
                            @else
                                <span class="badge bg-secondary">{{ __('Guest') }}</span>
                            @endif
                        </td>
                        <td>{{ $u?->email ?? '-' }}</td>
                        <td>{{ formatNumber($row->items_count) }}</td>
                        <td>{{ formatBytes($row->total_bytes) }}</td>
                        <td>
                            @if($row->next_purge_at)
                                {{ vDate($row->next_purge_at) }}
                                @if($overdue)
                                    <span class="badge bg-danger ms-1">{{ __('Overdue') }}</span>
                                @endif
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $row->last_deleted_at ? vDate($row->last_deleted_at) : '-' }}</td>
                        <td class="text-end">
                            <div class="btn-group">
                                <a class="btn btn-sm btn-primary"
                                   href="{{ route('superadmin.purge.users.show', $u?->id ?? 0) }}">
                                    {{ __('View items') }}
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form action="{{ route('superadmin.purge.users.restore-all', $u?->id ?? 0) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item text-success">
                                                <i class="far fa-check-circle me-2"></i>{{ __('Restore all (user)') }}
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('superadmin.purge.users.cancel-all', $u?->id ?? 0) }}" method="POST">
                                            @csrf
                                            <button class="dropdown-item">
                                                <i class="fa fa-undo me-2"></i>{{ __('Cancel all schedules') }}
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('superadmin.purge.users.purge-all', $u?->id ?? 0) }}" method="POST">
                                            @csrf @method('DELETE')
                                            <button class="dropdown-item text-danger"
                                                onclick="return confirm('{{ __('Permanently delete ALL scheduled items for this user? This cannot be undone.') }}')">
                                                <i class="far fa-trash-alt me-2"></i>{{ __('Purge all now') }}
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted p-5">{{ __('No users with scheduled deletions.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $users->links() }}
        </div>
    </div>
@endsection
