<?php

namespace App\Console\Commands;

use App\Services\WorkSessionService;
use Illuminate\Console\Command;

class CloseStaleWorkSessions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'worksessions:close-stale';

    /**
     * The console command description.
     */
    protected $description = 'Close all stale work sessions from previous days';

    /**
     * Execute the console command.
     */
    public function handle(WorkSessionService $workSessionService): int
    {
        $this->info('Closing stale work sessions...');

        $closedCount = $workSessionService->closeStaleSession();

        $this->info("Closed {$closedCount} stale session(s).");

        return Command::SUCCESS;
    }
}
