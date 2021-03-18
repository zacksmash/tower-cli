<?php

namespace App\Commands\Statamic;

use App\Repository;
use Illuminate\Support\Str;
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
        $this->console->info('Building a new Statamic site...');

        $this->console->info("Generating login credentials.\n");

        $email = 'admin@example.com';
        $password = trim(Str::random(4) . '-' . Str::random(4) .  '-' . Str::random(4) . '-' . Str::random(4));

        $this->console->comment("Email: {$email}\n");
        $this->console->comment("Password: {$password}\n");

        passthru("
            echo {$password} | pbcopy;
            open -a 1Password\ 7 2>null;
            rm null;
        ");

        $this->console->info('Creating Composer Project');
        passthru("composer create-project statamic/statamic {$this->name} --prefer-dist --stability=dev --quiet;");

        chdir($this->name);

        $_user = str_replace('{password}', $password, File::get(base_path('stubs/statamic_user.yaml.stub')));

        File::put("users/{$email}.yaml", $_user);

        passthru('mkdir app/Tags');

        File::copy(base_path('stubs/statamic_CurrentView.php.stub'), 'app/Tags/CurrentView.php');

        $this->console->info('Setting up theme files');

        passthru("
            rm -rf content resources package.json webpack.mix.js --quiet;

            git clone git@github.com:zacksmash/signal-statamic.git tmp --quiet;

            mv tmp/content content;
            mv tmp/resources resources;
            mv tmp/package.json package.json;
            mv tmp/webpack.mix.js webpack.mix.js;
            mv tmp/.editorconfig .editorconfig;

            rm -rf public/css;
            rm -rf public/js;

            rm -rf tmp;

            npm i;

            npm run dev;

            find . -type f -name 'webpack.mix.js' -exec sed -i '' s/CHANGE_ME.test/{$this->name}.test/ {} +
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

        passthru('npm run watch');
    }
}
