<?php

namespace App\Commands\Shopify;

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
        $this->console->info('Pull a shopify site...');

        // ask for Shopify url e.g. 'something.myshopify.com'
        $store_name = $this->console->ask('Shopify store name');
        // ask for Shopify API password
        $api_password = $this->console->ask('API Password');
        // ask for theme ID
        $theme_id = $this->console->ask('Theme ID');

        $this->console->info('Cloning repository');

        $organization = config('user.github_organization');

        passthru("
            git clone git@github.com:{$organization}/{$this->name}.git --quiet;
            cd {$this->name};
            yarn --silent;
        ");

        $this->console->info('Creating .env file');
        $_env = str_replace(
            ['{storeName}', '{apiPassword}', '{themeId}'],
            [$store_name, $api_password, $theme_id],
            File::get(base_path('stubs/shopify_env.stub'))
        );

        File::put("{$this->name}/.env", $_env);

        $this->console->info('Plugging some holes in the ship');
        passthru("
            cd {$this->name};
            new_one=./src/_repair/analytics.stub;
            old_one=./node_modules/@shopify/slate-analytics/index.js;
            new_two=./src/_repair/dev-server.stub;
            old_two=./node_modules/@shopify/slate-tools/tools/dev-server/index.js;

            cp \${new_one} \${old_one};
            cp \${new_two} \${old_two};
        ");

        $this->console->info('All done! Remember to run `yarn run watch` to begin development');
    }
}
