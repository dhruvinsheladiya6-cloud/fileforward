@extends('backend.layouts.grid')
@section('title', __('Scheduled deletions'))

@section('content')

    {{-- Top counters (unchanged) --}}
    @include('backend.purge._counters', ['stats' => $stats])

    {{-- Tabs --}}
    @php $tab = $tab ?? request('tab','items'); @endphp
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $tab==='items' ? 'active' : '' }}"
               href="{{ route('superadmin.purge.index', array_merge(request()->query(), ['tab'=>'items','user_id'=>null])) }}">
               {{ __('Items') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab==='users' ? 'active' : '' }}"
               href="{{ route('superadmin.purge.index', array_merge(request()->query(), ['tab'=>'users'])) }}">
               {{ __('By user') }}
            </a>
        </li>
    </ul>

    @if($tab === 'users')
        {{-- ===== USERS GRID (your old users.blade condensed into a partial) --}}
        <div class="card custom-card mb-3">
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
                                    {{-- Switch the right pane to this user's tree --}}
                                    <a class="btn btn-sm btn-primary"
                                    href="{{ route('superadmin.purge.index', ['tab'=>'users','user_id'=>$u?->id, 'scheduled'=>1]) }}">
                                        {{ __('View items') }}
                                    </a>

                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <form action="{{ route('superadmin.purge.users.restore-all', $u?->id ?? 0) }}" method="POST">@csrf
                                                <button class="dropdown-item text-success"><i class="far fa-check-circle me-2"></i>{{ __('Restore all (user)') }}</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="{{ route('superadmin.purge.users.cancel-all', $u?->id ?? 0) }}" method="POST">@csrf
                                                <button class="dropdown-item"><i class="fa fa-undo me-2"></i>{{ __('Cancel all schedules') }}</button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('superadmin.purge.users.purge-all', $u?->id ?? 0) }}" method="POST">@csrf @method('DELETE')
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

        {{-- ===== RIGHT BELOW: USER TREE (only if a user is selected) --}}
        @if(isset($user) && $user)
            {{-- mini header + switches --}}
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-2 gap-2">
                <div>
                    <h5 class="mb-1">{{ trim(($user->firstname.' '.$user->lastname)) ?: ($user->username ?? $user->email) }}</h5>
                    <div class="text-muted">
                        {{ $user->email }} •
                        {{ __('Folders:') }} {{ $userStats['folders'] }} •
                        {{ __('Files:') }} {{ $userStats['files'] }}
                        <span class="badge bg-secondary ms-2">
                            {{ $onlyScheduled ? __('Only scheduled') : __('All trashed') }}
                        </span>
                    </div>
                </div>
                <div class="btn-group">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="{{ route('superadmin.purge.index', array_merge(request()->query(), ['tab'=>'users','user_id'=>$user->id,'scheduled' => $onlyScheduled ? 0 : 1])) }}">
                       {{ $onlyScheduled ? __('Show all trashed') : __('Show only scheduled') }}
                    </a>
                    <form action="{{ route('superadmin.purge.users.restore-all', $user->id) }}" method="POST">@csrf
                        <button class="btn btn-sm btn-success">{{ __('Restore all') }}</button>
                    </form>
                    <form action="{{ route('superadmin.purge.users.cancel-all', $user->id) }}" method="POST">@csrf
                        <button class="btn btn-sm btn-outline-secondary">{{ __('Cancel all schedules') }}</button>
                    </form>
                    <form action="{{ route('superadmin.purge.users.purge-all', $user->id) }}" method="POST">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-danger"
                            onclick="return confirm('{{ __('Permanently delete ALL scheduled items for this user? This cannot be undone.') }}')">
                            {{ __('Purge all now') }}
                        </button>
                    </form>
                </div>
            </div>

            @include('backend.purge._user_browser', [
                'user'          => $user,
                'entries'       => $entries,
                'currentFolder' => $currentFolder,
                'breadcrumbs'   => $breadcrumbs,
                'onlyScheduled' => $onlyScheduled,
            ])


            @push('styles')
            <style>
                .v-tree li { margin:.25rem 0 .25rem 1rem; }
                .v-tree .node-header { display:flex; align-items:center; gap:.5rem; }
                .v-tree .toggle { cursor:pointer; user-select:none; width:1.25rem; text-align:center; }
                .v-tree .meta { color: var(--bs-gray-600); font-size:.875rem; }
                .v-tree .badge { vertical-align: middle; }
                .v-icon { width:22px; text-align:center; }
            </style>
            @endpush
            @push('scripts')
            <script>
                document.addEventListener('click', (e) => {
                    const t = e.target.closest('[data-toggle]');
                    if (!t) return;
                    const body = t.parentElement.nextElementSibling;
                    body.classList.toggle('d-none');
                    t.textContent = body.classList.contains('d-none') ? '+' : '–';
                });
            </script>
            @endpush
        @endif

    @else
        {{-- ===== ITEMS TAB (your original table; unchanged except wrapped here) --}}
        @include('backend.purge._items_table', ['entries'=>$entries])
    @endif
@endsection
