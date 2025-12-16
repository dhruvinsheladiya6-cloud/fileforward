<div class="card custom-card">
    <table id="datatable" class="table w-100">
        <thead>
            <tr>
                <th class="tb-w-3x"><input type="checkbox" id="selectAll"></th>
                <th class="tb-w-3x">{{ __('#') }}</th>
                <th class="tb-w-20x">{{ __('File details') }}</th>
                <th class="tb-w-3x">{{ __('Owner') }}</th>
                <th class="tb-w-3x">{{ __('Type') }}</th>
                <th class="tb-w-7x">{{ __('Deleted at') }}</th>
                <th class="tb-w-7x">{{ __('Scheduled purge at') }}</th>
                <th class="tb-w-5x">{{ __('Time left') }}</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach ($entries as $entry)
            @php
                $left = \Carbon\Carbon::now()->diffForHumans($entry->purge_at, ['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'join' => true]);
                $overdue = now()->greaterThan($entry->purge_at);
            @endphp
            <tr class="item">
                <td><input type="checkbox" class="row-check" form="bulkForm" name="file_ids[]" value="{{ $entry->shared_id }}"></td>
                <td>{{ $entry->id }}</td>
                <td>
                    <div class="vironeer-content-box">
                        <div class="vironeer-content-image text-center">{!! fileIcon($entry->extension) !!}</div>
                        <div>
                            <span class="text-reset">{{ shortertext($entry->name, 60) }}</span>
                            <p class="text-muted mb-0">{{ $entry->mime ?? __('Unknown') }} • {{ formatBytes($entry->size) }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    @if ($entry->user)
                        <div class="vironeer-content-box">
                            <div>
                                <span>{{ shortertext(($entry->user->firstname.' '.$entry->user->lastname) ?: ($entry->user->username ?? $entry->user->email), 32) }}</span>
                                <p class="text-muted mb-0">{{ $entry->user->email }}</p>
                            </div>
                        </div>
                    @else
                        <span class="badge bg-secondary">{{ __('Guest') }}</span>
                    @endif
                </td>
                <td>{{ ucfirst($entry->type) }}</td>
                <td>{{ $entry->deleted_at ? vDate($entry->deleted_at) : '-' }}</td>
                <td>{{ vDate($entry->purge_at) }}</td>
                <td>
                    @if ($overdue)
                        <span class="badge bg-danger">{{ __('Overdue') }}</span>
                    @else
                        <span class="badge bg-c-7">{{ $left }}</span>
                    @endif
                </td>
                <td>
                    <div class="text-end">
                        <button type="button" class="btn btn-sm rounded-3" data-bs-toggle="dropdown" aria-expanded="true">
                            <i class="fa fa-ellipsis-v fa-sm text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-sm-end dropdown-menu-lg">
                            <li>
                                <form action="{{ route('superadmin.purge.cancel', $entry->shared_id) }}" method="POST">@csrf
                                    <button class="dropdown-item"><i class="fa fa-undo me-2"></i>{{ __('Cancel schedule') }}</button>
                                </form>
                            </li>
                            <li>
                                <form action="{{ route('superadmin.purge.restore', $entry->shared_id) }}" method="POST">@csrf
                                    <button class="dropdown-item text-success"><i class="far fa-check-circle me-2"></i>{{ __('Restore') }}</button>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider" /></li>
                            <li>
                                <form action="{{ route('superadmin.purge.purge-now', $entry->shared_id) }}" method="POST">@csrf @method('DELETE')
                                    <button class="vironeer-able-to-delete dropdown-item text-danger" onclick="return confirm('{{ __('Permanently delete this item? This cannot be undone.') }}')">
                                        <i class="far fa-trash-alt me-2"></i>{{ __('Purge now') }}
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="card-footer">{{ $entries->links() }}</div>
</div>

@push('styles_libs')
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/vironeer/vironeer-icons.min.css') }}">
@endpush
@push('scripts')
<script>
    const selectAll = document.getElementById('selectAll');
    selectAll?.addEventListener('change', (e) => {
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = e.target.checked);
    });
    function submitBulk(action) {
        document.getElementById('bulkAction').value = action;
        document.getElementById('bulkForm').submit();
    }
</script>
@endpush
