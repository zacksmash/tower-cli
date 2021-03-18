<?php

namespace App\Commands;

use App\Commands\Wordpress\NewBuild as Wordpress;
use App\Commands\Statamic\NewBuild as Statamic;
use App\Commands\Shopify\NewBuild as Shopify;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new
                            {name? : The name of the project to create}
                            {type? : The type of project to create}
                            {--no-repo : Skip creating a repo for this project}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Generate a new Wordpress, Shopify or Statamic project';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name') ?: $this->ask("What's the name of your new project?");

        if (File::exists($name)) {
            $this->warn('That folder already exists.');
            exit;
        }

        $type = $this->argument('type') ?: $this->choice(
            'What type of project are you building?',
            // ['Wordpress', 'Statamic', 'Shopify'],
            ['Wordpress', 'Statamic'],
            'Wordpress'
        );

        switch ($type) {
            case 'Wordpress':
                $new_build = (new Wordpress($name, $this))->start();
                break;

            case 'Statamic':
                $new_build = (new Statamic($name, $this))->start();
                break;

            case 'Shopify':
                $new_build = (new Shopify($name, $this))->start();
                break;
        }
    }
}
