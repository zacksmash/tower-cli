<?php

namespace App\Commands\Shopify;

use App\Repository;
use Illuminate\Support\Facades\File;

class NewBuild
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
        $this->console->info('Building a new Shopify site...');

        // ask for Shopify url e.g. 'something.myshopify.com'
        $store_name = $this->console->ask('Shopify store name');

        // ask for Shopify API password
        $api_password = $this->console->ask('API Password');

        // ask for theme ID
        $theme_id = $this->console->ask('Theme ID');

        $this->console->info('Creating Slate Theme');
        passthru("yarn create slate-theme {$this->name} zacksmash/signal-shopify");

        chdir($this->name);

        $this->console->info('Creating .env file');
        $_env = str_replace(
            ['{storeName}', '{apiPassword}', '{themeId}'],
            [$store_name, $api_password, $theme_id],
            File::get(base_path('stubs/shopify_env.stub'))
        );

        File::put('.env', $_env);

        $this->console->info('Plugging some holes in the ship');
        passthru("
            new_one=./src/_repair/analytics.stub;
            old_one=./node_modules/@shopify/slate-analytics/index.js;
            new_two=./src/_repair/dev-server.stub;
            old_two=./node_modules/@shopify/slate-tools/tools/dev-server/index.js;

            cp \${new_one} \${old_one};
            cp \${new_two} \${old_two};

            yarn deploy --skipPrompts --replace;
        ");

        if (!$this->console->option('no-repo')) {
            $this->console->info('Creating Repository');
            $repo = new Repository();

            if ($repo->exists($this->name)) {
                $this->console->warn("Could not create repo, it looks like that one already exists.\n");
            } else {
                $repo->create($this->name);

                $organization = config('user.github_organization');

                passthru("
                    git init --quiet;
                    git remote add origin git@github.com:{$organization}/{$this->name}.git;
                    git add . -A;
                    git commit -m 'Initial Commit' --quiet;
                    git push -u origin master --quiet;
                ");
            }
        }

        $this->console->info('All done! Remember to run `yarn run watch` to begin development');
    }
}
