{{-- BREADCRUMBS (shared_id-based) --}}
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
    <span class="text-muted">{{ __('Path:') }}</span>

    <a href="{{ route('superadmin.purge.index', ['tab'=>'users','user_id'=>$user->id] + ($onlyScheduled ? ['scheduled'=>1] : [])) }}"
       class="text-decoration-none fw-semibold">
        {{ __('Root') }}
    </a>

    @foreach($breadcrumbs as $crumb)
        <span class="text-muted">/</span>
        <a href="{{ route('superadmin.purge.index', [
                'tab'=>'users','user_id'=>$user->id,'folder'=>$crumb->shared_id
            ] + ($onlyScheduled ? ['scheduled'=>1] : [])) }}"
           class="text-decoration-none">
            {{ shortertext($crumb->name, 40) }}
        </a>
    @endforeach

    @if($currentFolder)
        <span class="text-muted">/</span>
        <span class="fw-semibold">{{ shortertext($currentFolder->name, 40) }}</span>
    @endif
</div>

<div class="card custom-card">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th class="tb-w-3x"></th>
                    <th class="tb-w-15x">{{ __('Name') }}</th>
                    <th class="tb-w-10x">{{ __('Type') }}</th>
                    <th class="tb-w-10x">{{ __('Size') }}</th>
                    <th class="tb-w-13x">{{ __('Deleted at') }}</th>
                    <th class="tb-w-13x">{{ __('Scheduled purge at') }}</th>
                    <th class="tb-w-8x">{{ __('Status') }}</th>
                    <th class="tb-w-8x">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            {{-- "Up" row --}}
            @if($currentFolder)
                <tr>
                    <td class="text-center"><i class="fas fa-level-up-alt"></i></td>
                    <td colspan="7">
                        @php
                            $upParams = ['tab'=>'users','user_id'=>$user->id] + ($onlyScheduled ? ['scheduled'=>1] : []);
                            if ($currentFolder->parent) { $upParams['folder'] = $currentFolder->parent->shared_id; }
                        @endphp
                        <a href="{{ route('superadmin.purge.index', $upParams) }}">
                            .. {{ $currentFolder->parent ? __('Up to') .' '. shortertext($currentFolder->parent->name,40) : __('Up to Root') }}
                        </a>
                    </td>
                </tr>
            @endif

            @forelse($entries as $item)
                @php
                    $isFolder = $item->type === 'folder';
                    $overdue  = $item->purge_at && now()->greaterThan($item->purge_at);
                    $left = $item->purge_at
                        ? \Carbon\Carbon::now()->diffForHumans($item->purge_at, [
                            'parts'=>2,'short'=>true,'syntax'=>\Carbon\CarbonInterface::DIFF_ABSOLUTE,'join'=>true
                          ])
                        : null;
                @endphp
                <tr>
                    <td class="text-center">
                        @if($isFolder)
                            <i class="fas fa-folder" style="font-size: 20px; color: #ffc107;"></i>
                        @else
                            {!! fileIcon($item->extension) !!}
                        @endif
                    </td>
                    <td>
                        @if($isFolder)
                            {{-- DEMO STYLE: folder link by shared_id --}}
                            <a href="{{ route('superadmin.purge.index', [
                                    'tab'=>'users','user_id'=>$user->id,'folder'=>$item->shared_id
                                ] + ($onlyScheduled ? ['scheduled'=>1] : [])) }}"
                               class="filemanager-link text-decoration-none">
                                <span class="fw-semibold">{{ shortertext($item->name, 80) }}</span>
                            </a>
                        @else
                            <span class="fw-normal">{{ shortertext($item->name, 80) }}</span>
                        @endif
                        <div class="text-muted small">
                            {{ $item->mime ?? __('Unknown') }}
                            @if(!$isFolder && $item->parent)
                                • {{ __('In:') }} {{ shortertext($item->parent->name,30) }}
                            @endif
                        </div>
                    </td>
                    <td>{{ ucfirst($item->type) }}</td>
                    <td>{{ formatBytes($item->size) }}</td>
                    <td>{{ $item->deleted_at ? vDate($item->deleted_at) : '-' }}</td>
                    <td>{{ $item->purge_at ? vDate($item->purge_at) : '-' }}</td>
                    <td>
                        @if($item->purge_at)
                            @if($overdue)
                                <span class="badge bg-danger">{{ __('Overdue') }}</span>
                            @else
                                <span class="badge bg-c-7">{{ $left }}</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">{{ __('Not scheduled') }}</span>
                        @endif
                    </td>
                    <td>
                        {{-- Cleaner action dropdown (one button) --}}
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown">
                                <i class="fa fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if($item->purge_at)
                                <li>
                                    <form action="{{ route('superadmin.purge.cancel', $item->shared_id) }}" method="POST">@csrf
                                        <button class="dropdown-item">
                                            <i class="fa fa-undo me-2"></i>{{ __('Cancel schedule') }}
                                        </button>
                                    </form>
                                </li>
                                @endif
                                <li>
                                    <form action="{{ route('superadmin.purge.restore', $item->shared_id) }}" method="POST">@csrf
                                        <button class="dropdown-item text-success">
                                            <i class="far fa-check-circle me-2"></i>{{ __('Restore') }}
                                        </button>
                                    </form>
                                </li>
                                <li><hr class="dropdown-divider" /></li>
                                <li>
                                    <form action="{{ route('superadmin.purge.purge-now', $item->shared_id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button class="dropdown-item text-danger"
                                            onclick="return confirm('{{ __('Permanently delete this item? This cannot be undone.') }}')">
                                            <i class="far fa-trash-alt me-2"></i>{{ __('Purge now') }}
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted p-5">{{ __('No items here.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $entries->links() }}
    </div>
</div>

@push('styles')
<style>
    .btn-toolbar .btn, .btn-toolbar form { margin-bottom: .25rem; }
    .filemanager-link:hover { text-decoration: underline; }
    .table tbody tr:hover { background-color: rgba(0,0,0,.015); }
</style>
@endpush
