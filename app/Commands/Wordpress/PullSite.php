<?php

namespace App\Commands\Wordpress;

use Illuminate\Support\Facades\File;

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
        $this->console->info('Pull a wordpress site...');

        $this->console->info('Cloning repository');

        $organization = config('user.github_organization');

        passthru("git clone git@github.com:{$organization}/{$this->name}.git --quiet;");

        chdir($this->name);

        $db_user = config('user.db_user');
        $db_pass = config('user.db_pass');
        $db_host = config('user.db_host');
        $stg_user = config('user.stg_user');
        $stg_pass = config('user.stg_pass');
        $stg_host = config('user.stg_host');

        $this->console->info('Creating local database');
        passthru("
            mysql -h {$db_host} -u{$db_user} -p${db_pass} -e 'CREATE DATABASE IF NOT EXISTS `db_'{$this->name}'`' 2>null;
            rm null;
        ");

        if ($this->console->option('db')) {
            $this->console->info('Downloading remote database');
            passthru("
                ## Export database from remote server
                mysqldump -h {$stg_host} -u{$stg_user} -p${stg_pass}  db_{$this->name} > db.sql 2>null

                ## Import database locally
                mysql -h {$db_host} -u{$db_user} -p${db_pass} db_{$this->name} < db.sql 2>null
                rm db.sql null
            ");
        }

        $this->console->info('Creating local config file');
        $_local = str_replace(
            ['{siteName}', '{dbUser}', '{dbPass}', '{dbHost}'],
            [$this->name, $db_user, $db_pass, $db_host],
            File::get(base_path('stubs/wp-config-local.php.stub'))
        );

        File::put('wp-config-local.php', $_local);

        $this->console->info('Installing dependencies');
        passthru('
            cd wp-content/themes/signal;
            npm i --silent;
            npm run watch;
        ');
    }
}
