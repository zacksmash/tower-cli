<?php

namespace App\Commands;

use App\Commands\Wordpress\PullSite as Wordpress;
use App\Commands\Statamic\PullSite as Statamic;
use App\Commands\Shopify\PullSite as Shopify;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class PullCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pull
                            {name? : The name of the project to create}
                            {type? : The type of project to create}
                            {--db : Pull the remote database down locally}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Pull a project down to work on locally.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name') ?: $this->ask("What's the name of the project?");

        if (File::exists($name)) {
            $this->warn('That folder already exists.');
            exit;
        }

        $type = $this->argument('type') ?: $this->choice(
            'What type of project is it?',
            // ['Wordpress', 'Statamic', 'Shopify'],
            ['Wordpress', 'Statamic'],
            'Wordpress'
        );

        switch ($type) {
            case 'Wordpress':
                $pull_build = (new Wordpress($name, $this))->start();
                break;

            case 'Statamic':
                $pull_build = (new Statamic($name, $this))->start();
                break;

            case 'Shopify':
                $pull_build = (new Shopify($name, $this))->start();
                break;
        }
    }
}
