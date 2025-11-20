@php
    $isFolder = $node->type === 'folder';
    $overdue = $node->purge_at && now()->greaterThan($node->purge_at);
    $left = $node->purge_at
        ? \Carbon\Carbon::now()->diffForHumans($node->purge_at, [
            'parts'=>2,'short'=>true,'syntax'=>\Carbon\CarbonInterface::DIFF_ABSOLUTE,'join'=>true
          ])
        : null;
@endphp

<li>
    <div class="node-header">
        <span class="toggle" data-toggle>{{ $isFolder ? '–' : '' }}</span>
        <span class="v-icon">{!! fileIcon($node->extension) !!}</span>
        <span class="flex-grow-1">{{ shortertext($node->name, 80) }}</span>

        <span class="meta me-2">
            {{ $node->mime ?? __('Unknown') }} • {{ formatBytes($node->size) }}
            @if($node->deleted_at) • {{ __('Deleted:') }} {{ vDate($node->deleted_at) }} @endif
            @if($node->purge_at) • {{ __('Purge:') }} {{ vDate($node->purge_at) }} @endif
        </span>

        @if($node->purge_at)
            @if($overdue)
                <span class="badge bg-danger me-2">{{ __('Overdue') }}</span>
            @else
                <span class="badge bg-c-7 me-2">{{ $left }}</span>
            @endif
        @endif

        <div class="dropdown">
            <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-v"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
                @if($node->purge_at)
                <li>
                    <form action="{{ route('superadmin.purge.cancel', $node->shared_id) }}" method="POST">@csrf
                        <button class="dropdown-item"><i class="fa fa-undo me-2"></i>{{ __('Cancel schedule') }}</button>
                    </form>
                </li>
                @endif
                <li>
                    <form action="{{ route('superadmin.purge.restore', $node->shared_id) }}" method="POST">@csrf
                        <button class="dropdown-item text-success"><i class="far fa-check-circle me-2"></i>{{ __('Restore') }}</button>
                    </form>
                </li>
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <form action="{{ route('superadmin.purge.purge-now', $node->shared_id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="dropdown-item text-danger"
                            onclick="return confirm('{{ __('Permanently delete this item? This cannot be undone.') }}')">
                            <i class="far fa-trash-alt me-2"></i>{{ __('Purge now') }}
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    @if($isFolder && $node->children_nodes?->count())
        <ul class="list-unstyled ms-4">
            @foreach($node->children_nodes as $child)
                @include('backend.purge._node', ['node'=>$child])
            @endforeach
        </ul>
    @endif
</li>
