@extends('backend.layouts.grid')
@section('title', __('Trash & scheduled items for :u', ['u' => $user->email]))

@section('content')
    @include('backend.purge._counters', ['stats' => [
        'total'   => $stats['scheduled'],
        'today'   => $entries->whereNotNull('purge_at')->whereBetween('purge_at',[now()->startOfDay(), now()->endOfDay()])->count(),
        'next7'   => $entries->whereNotNull('purge_at')->whereBetween('purge_at',[now(), now()->addDays(7)])->count(),
        'overdue' => $stats['overdue'],
    ]])

    {{-- User header + bulk --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h5 class="mb-1">{{ trim(($user->firstname.' '.$user->lastname)) ?: ($user->username ?? $user->email) }}</h5>
            <div class="text-muted">
                {{ $user->email }} •
                {{ __('Folders:') }} {{ $stats['folders'] }} •
                {{ __('Files:') }} {{ $stats['files'] }}
                @if(!$onlyScheduled)
                    <span class="badge bg-secondary ms-2">{{ __('Showing all trashed (not only scheduled)') }}</span>
                @endif
            </div>
        </div>
        <div class="btn-group">
            <a class="btn btn-sm btn-outline-secondary"
               href="{{ route('superadmin.purge.users.show', [$user->id, 'scheduled'=> $onlyScheduled ? 0 : 1]) }}">
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

    {{-- Nested tree --}}
    <div class="card custom-card">
        <div class="card-body">
            @if($tree->isEmpty())
                <div class="text-center text-muted py-5">{{ __('No items to show.') }}</div>
            @else
                <ul class="list-unstyled v-tree">
                    @foreach ($tree as $node)
                        @include('backend.purge._node', ['node' => $node])
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    @push('styles')
    <style>
        .v-tree li { margin: .25rem 0 .25rem 1rem; }
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
@endsection
