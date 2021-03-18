<?php

namespace App\Commands\Statamic;

class PullSite
{
    public $name;
    public $console;

    public function __construct($name, $console)
    {
        $this->name = $name;
        $this->console = $console;
    }

    public function start()
    {
        $this->console->info('Pull a statamic site...');

        $this->console->info('Cloning repository');

        $organization = config('user.github_organization');

        passthru("git clone git@github.com:{$organization}/{$this->name}.git  --quiet;");

        chdir($this->name);

        $this->console->info('Installing dependencies');
        passthru("
            composer install --quiet;
            cp .env.example .env ;
            php artisan key:generate;
            npm i --quiet;
            npm run watch;
        ");
    }
}
