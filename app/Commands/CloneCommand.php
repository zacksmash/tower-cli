<?php

namespace App\Commands;

use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class CloneCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'clone
                            {name? : The name of the project to create}
                            {directory? : The directory to clone files to}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Clone a repository locally';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name') ?: $this->ask("What's the name of the repository?");
        $organization = config('user.github_organization');
        $directory = $this->argument('directory') ?: $name;

        if (File::exists($name)) {
            $this->warn('That folder already exists.');
            exit;
        }

        $this->info('Cloning repository...');

        passthru("git clone git@github.com:{$organization}/{$name}.git {$directory} --quiet;");

        chdir($name);
    }
}
