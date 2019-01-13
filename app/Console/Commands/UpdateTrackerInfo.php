<?php

namespace App\Console\Commands;

use App\Extras\GithubScript\Git;
use Illuminate\Console\Command;
use App\Extras\TrackersManager;
use Storage;

class UpdateTrackerInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'UpdateTrackerInfo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->updateTodaysDate();
        $this->info(" ************ Running the cron script ************ ");
        $init = new TrackersManager();
        $init->getGitHubBlacklistTrackersUpdate();
        $init->getGitHubTrackersUpdate();
        $init->getTrackerIpLocationInfo();

        try {
            $repo = Git::windows_mode();
            $repo = Git::open(storage_path('app/public/storage')); // -or- Git::create('/path/to/repo')
            $repo->pull();
        } catch (Exception $e) {
            print_r($e->message);
        }
        
        $init->getSubmittedTrackersUpdate();

        try {
            $repo->add('.');
            $repo->commit('@Ranveer - Tracker Updates - ' . date('Y-m-d h:i:s', strtotime('now')));
            $repo->push('origin', 'master');
        } catch (Exception $e) {
            print_r($e->message);
        }
    }

    public function updateTodaysDate()
    {
        $this->info(" Adding Today's Date ");
        Storage::disk('public')->put('codestatus.json', json_encode(array("current_date" => date('d-m-Y', strtotime('now'))), JSON_PRETTY_PRINT));
        // file_put_contents("tlist/codestatus.json", );
    }
}