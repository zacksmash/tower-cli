<?php

namespace App\Commands;

use Illuminate\Support\Facades\File;
use LaravelZero\Framework\Commands\Command;

class InitCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Initialize Tower CLI Config';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Initiallizing Tower CLI Config');

        $proceed_with_token = $this->confirm('To continue, you\'ll need to generate a GitHub access token. Ready?', 'yes');

        if ($proceed_with_token) {
            shell_exec('open "https://github.com/settings/tokens/new?scopes=repo,delete_repo,user,admin:org,user&description=Tower%20CLI"');
            $github_token = $this->secret('Enter your GitHub personal access token');
        } else {
            $github_token = null;
            $this->warn('You will need to edit your tower.json config file and enter your GitHub token later.');
        }

        $github_organization = $this->ask('What is your Github username or organization to create repositories under.');
        $github_as = $this->choice('Who should we use Github as?', ['user', 'organization'], 'user');
        $db_user = $this->ask('What is your local DB username?', 'root');
        $db_pass = $this->ask('What is your local DB password?', 'root');
        $db_host = $this->ask('What is your local DB hostname', '127.0.0.1');
        $stg_user = $this->ask('What is your staging DB username?', 'forge');
        $stg_pass = $this->ask('What is your staging DB password?', 'some_random_characters');
        $stg_host = $this->ask('What is your staging DB hostname', '100.100.100.100');

        $config = str_replace(
            [
                '{githubToken}',
                '{githubOrganization}',
                '{githubAs}',
                '{dbUser}',
                '{dbPass}',
                '{dbHost}',
                '{stgUser}',
                '{stgPass}',
                '{stgHost}'
            ],
            [
                $github_token,
                $github_organization,
                $github_as,
                $db_user,
                $db_pass,
                $db_host,
                $stg_user,
                $stg_pass,
                $stg_host
            ],
            File::get(base_path('stubs/config.json.stub'))
        );

        $config_path = config('home_dir') . config('app.config_path');
        $config_file = config('app.config_file');

        if (!is_dir($config_path)) {
            mkdir($config_path);
        }

        File::put($config_path . $config_file, $config);

        $this->info('All done! Use the `tower` command to see a list of available helpers.');
    }
}
