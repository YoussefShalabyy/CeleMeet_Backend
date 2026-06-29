<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ExpireStoriesCommand extends Command
{
    protected $signature = 'stories:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Soft deletes stories that have passed their expiration time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = \App\Models\Story::where('expires_at', '<=', now())->delete();
        $this->info("Expired {$count} stories successfully.");
    }
}
