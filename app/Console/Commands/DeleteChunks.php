<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteChunks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:delete-chunks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the chunk parts from the chunks folder in storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $chunkDirectory = storage_path('app/chunks');
        $threeHoursAgo = Carbon::now()->subHours(5);
        foreach (File::files($chunkDirectory) as $file) {
            $fileModifiedTime = Carbon::createFromTimestamp(File::lastModified($file));
            if ($fileModifiedTime->lte($threeHoursAgo)) {
                File::delete($file);
            }
        }
    }
}
