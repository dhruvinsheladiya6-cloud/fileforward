<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\FileReport;
use App\Models\FileEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NewReportController extends Controller
{
    /**
     * Page 1: One row per file with counts.
     */
    public function index()
    {
        // Aggregate per file_entry_id
        $grouped = FileReport::select([
                'file_entry_id',
                DB::raw('COUNT(*) as reports_count'),
                DB::raw('SUM(CASE WHEN admin_has_viewed = 0 THEN 1 ELSE 0 END) as waiting_count'),
                DB::raw('SUM(CASE WHEN admin_has_viewed = 1 THEN 1 ELSE 0 END) as reviewed_count'),
                DB::raw('MAX(created_at) as last_report_at'),
            ])
            ->whereHas('fileEntry', function ($q) {
                $q->notExpired()
                  ->whereNull('deleted_at')
                  ->whereNull('purge_at');
            })
            ->groupBy('file_entry_id')
            ->orderByDesc('last_report_at')
            ->get();

        // Load related FileEntry in one shot
        $fileEntries = FileEntry::whereIn('id', $grouped->pluck('file_entry_id')->all())
            ->get()
            ->keyBy('id');

        // Decorate for view
        $rows = $grouped->map(function ($g) use ($fileEntries) {
            $file = $fileEntries->get($g->file_entry_id);
            return (object)[
                'file_entry'    => $file,
                'reports_count' => (int)$g->reports_count,
                'waiting_count' => (int)$g->waiting_count,
                'reviewed_count'=> (int)$g->reviewed_count,
                'last_report_at'=> $g->last_report_at,
                // Status at file-level: waiting if any report unreviewed
                'status'        => $g->waiting_count > 0 ? 'waiting' : 'reviewed',
            ];
        });

        // Counters at the top (like your current page)
        $waitingReviewFiles = $rows->where('waiting_count', '>', 0)->count();
        $reviewedFiles      = $rows->where('waiting_count', '=', 0)->count();

        return view('backend.newreports.index', [
            'rows'               => $rows,
            'waitingReviewFiles' => $waitingReviewFiles,
            'reviewedFiles'      => $reviewedFiles,
        ]);
    }

    /**
     * Page 2: Aggregated reasons for a specific file.
     */
    public function reasons($fileEntryId)
    {
        $fileEntry = FileEntry::where('id', $fileEntryId)
            ->notExpired()
            ->whereNull('deleted_at')
            ->whereNull('purge_at')
            ->firstOrFail();

        $reasons = FileReport::select([
                'reason',
                DB::raw('COUNT(*) as count_reason'),
            ])
            ->where('file_entry_id', $fileEntryId)
            ->groupBy('reason')
            ->orderByDesc('count_reason')
            ->get();

        $totalReports = FileReport::where('file_entry_id', $fileEntryId)->count();

        // Most common reason (if any)
        $topReason = $reasons->first();

        return view('backend.newreports.reasons', [
            'fileEntry'    => $fileEntry,
            'reasons'      => $reasons,
            'totalReports' => $totalReports,
            'topReason'    => $topReason,
        ]);
    }

    /**
     * Page 3: All reporters for a file (optionally filter by reason).
     * ?reason=<key>
     */
    public function reporters(Request $request, $fileEntryId)
    {
        $fileEntry = FileEntry::where('id', $fileEntryId)
            ->notExpired()
            ->whereNull('deleted_at')
            ->whereNull('purge_at')
            ->firstOrFail();

        $query = FileReport::where('file_entry_id', $fileEntryId)
            ->with('fileEntry')
            ->orderByDesc('created_at');

        if ($request->filled('reason')) {
            $query->where('reason', $request->query('reason'));
        }

        // Use pagination to keep the page light; adjust per your UI
        $reports = $query->paginate(20)->withQueryString();

        return view('backend.newreports.reporters', [
            'fileEntry'    => $fileEntry,
            'reports'      => $reports,
            'reasonFilter' => $request->query('reason'),
        ]);
    }

    /**
     * (Optional) Mark all reports for this file as reviewed.
     */
    public function markAllReviewed($fileEntryId)
    {
        $updated = FileReport::where('file_entry_id', $fileEntryId)
            ->where('admin_has_viewed', 0)
            ->update(['admin_has_viewed' => 1]);

        toastr()->success(__(':n reports marked as reviewed', ['n' => $updated]));
        return back();
    }

    /**
     * Delete flow for this screen:
     *  - Move the file to trash now (deleted_at = now) if not already.
     *  - Schedule permanent purge in 7 days for the file (and its descendants).
     *  - Delete ALL reports for this file.
     *  - Remove related admin notifications (per-report route).
     */
    public function destroyAll($fileEntryId)
    {
        $file = FileEntry::find($fileEntryId);

        if (!$file) {
            toastr()->error(__('File not found'));
            return back();
        }

        // Move to trash (if not already)
        if (is_null($file->deleted_at)) {
            $file->update(['deleted_at' => now()]);
        }

        // Schedule purge in 7 days (recursive)
        $when = Carbon::now()->addDays(7);
        $file->markForPurgeRecursive($when);

        // Delete all reports + their notifications
        $reports = FileReport::where('file_entry_id', $fileEntryId)->get();

        foreach ($reports as $rep) {
            deleteAdminNotification(route('superadmin.reports.view', $rep->id));
        }

        FileReport::where('file_entry_id', $fileEntryId)->delete();

        toastr()->success(__('File scheduled for permanent deletion in 7 days and all reports removed'));
        return redirect()->route('superadmin.newreports.index');
    }

}
