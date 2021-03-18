<?php

namespace App\Commands;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class PushCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'push
                            {--db : Push the local database to the remote server}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Build for production and push to repository';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Pushing up compiled code.');

        if (File::exists('wp_config.php')) {
            // Wordpress
            passthru('
                cd wp-content/themes/signal;
                npm run prod;
            ');

            if ($this->option('db')) {
                $db_user = config('user.db_user');
                $db_pass = config('user.db_pass');
                $db_host = config('user.db_host');
                $stg_user = config('user.stg_user');
                $stg_pass = config('user.stg_pass');
                $stg_host = config('user.stg_host');


                $proj_name = explode('/', getcwd());
                $proj_name = $proj_name[count($proj_name) - 1];

                passthru("
                    ## Export local database and create db.sql file
                    mysqldump -h {$db_host} -u{$db_user} -p${db_pass} db_{$proj_name} > db.sql 2>null

                    ## Create database
                    mysql -h {$stg_host} -u{$stg_user} -p${stg_pass} -e 'CREATE DATABASE IF NOT EXISTS `db_{$proj_name}`' 2>null;

                    ## Import db.sql file to remote database
                    mysql -h {$stg_host} -u{$stg_user} -p${stg_pass}  db_{$proj_name} < db.sql 2>null

                    ## Clean up files
                    rm db.sql null
                ");
            }
        } elseif (File::exists('artisan')) {
            // Statamic/Laravel
            passthru('npm run prod;');
        } elseif (File::exists('slate.config.js')) {
            // Shopify/Slate
            passthru('yarn run build;');
        }

        // Push Site Code
        $build_date = Carbon::now()
            ->setTimezone('America/Denver')
            ->format('Y-m-d H:i:s');

        shell_exec("
            git commit -am 'Build: {$build_date}';
            git push;
        ");

        $this->info('Code was pushed.');
    }
}
