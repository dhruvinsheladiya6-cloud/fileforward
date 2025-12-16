<?php

namespace App\Http\Controllers\Frontend\User;

use App\Http\Controllers\Controller;
use App\Models\FileEntry;
use App\Models\UploadSettings;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $activeFiles = FileEntry::currentUser()
            ->notExpired()
            ->whereNull('deleted_at')
            ->count();
        
        $trashedFiles = FileEntry::currentUser()
            ->whereNotNull('deleted_at')
            ->whereNull('purge_at')
            ->count();
        
        $totalStorageUsed = FileEntry::currentUser()
            ->notExpired()
            ->whereNull('deleted_at')
            ->sum('size');
        
        $totalDownloads = FileEntry::currentUser()
            ->notExpired()
            ->whereNull('deleted_at')
            ->sum('downloads');
        
        $totalViews = FileEntry::currentUser()
            ->notExpired()
            ->whereNull('deleted_at')
            ->sum('views');
        
        $sharedFiles = FileEntry::currentUser()
            ->whereNull('deleted_at')
            ->whereHas('shares', function($query) {
                $query->whereNull('revoked_at');
            })
            ->count();
        
        $uploadMode = UploadSettings::getUploadMode();
        
        return view('frontend.user.dashboard.index', [
            'activeFiles' => $activeFiles,
            'trashedFiles' => $trashedFiles,
            'totalStorageUsed' => $totalStorageUsed,
            'totalDownloads' => $totalDownloads,
            'totalViews' => $totalViews,
            'sharedFiles' => $sharedFiles,
            'uploadMode' => $uploadMode,
        ]);
    }

    public function uploadsChart()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();
        $dates = chartDates($startDate, $endDate);
        $monthlyUploads = FileEntry::currentUser()->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->notExpired()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date');
        $monthlyUploadsData = $dates->merge($monthlyUploads);
        $uploadsChartLabels = [];
        $uploadsChartData = [];
        foreach ($monthlyUploadsData as $key => $value) {
            $uploadsChartLabels[] = Carbon::parse($key)->format('d F');
            $uploadsChartData[] = $value;
        }
        $suggestedMax = (max($uploadsChartData) > 9) ? max($uploadsChartData) + 2 : 10;
        return ['uploadsChartLabels' => $uploadsChartLabels, 'uploadsChartData' => $uploadsChartData, 'suggestedMax' => $suggestedMax];
    }
}
