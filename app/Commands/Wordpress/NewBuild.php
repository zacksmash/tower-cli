<?php

namespace App\Commands\Wordpress;

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
        $this->console->info('Building a new Wordpress site...');

        // Setup DB Configs
        $db_user = config('user.db_user');
        $db_pass = config('user.db_pass');
        $db_host = config('user.db_host');

        $this->console->info("Generating login credentials.\n");

        $username = 'signaladmin';
        $password = trim(Str::random(4) . '-' . Str::random(4) .  '-' . Str::random(4) . '-' . Str::random(4));

        $this->console->comment("Username: {$username}\n");
        $this->console->comment("Password: {$password}\n");

        passthru("
            echo {$password} | pbcopy;
            open -a 1Password\ 7 2>null;
            rm null;
        ");

        $this->console->info('Creating local database');
        // Create local database
        passthru("
            mysql -h {$db_host} -u{$db_user} -p${db_pass} -e 'CREATE DATABASE IF NOT EXISTS `db_'{$this->name}'`' 2>null;
            rm null;
        ");

        $this->console->info('Downloading Wordpress and Signal theme');
        // create directory
        mkdir($this->name);
        chdir($this->name);
        passthru('
            wp core download --quiet
            rm -rf wp-content/plugins/akismet wp-content/plugins/hello.php .git
            rm -rf wp-content/themes/twenty*
        ');

        // Clone Theme
        passthru('
            git clone git@github.com:zacksmash/signal-wp.git wp-content/themes/signal --quiet;
            rm -rf wp-content/themes/signal/.git;
            rm wp-content/themes/signal/.gitignore;
            cd wp-content/themes/signal;
            composer install --quiet;
        ');

        $this->console->info('Configure WP Database Connection');
        // configure WP database connection
        passthru("wp core config --dbname=db_{$this->name} --dbuser={$db_user} --dbpass={$db_pass} --dbhost={$db_host} --dbprefix=signal_ --quiet");

        $this->console->info('Creating Config File');

        // Add Custom config file
        File::copy(base_path('stubs/wp-config.php.stub'), 'wp-config.php');

        $this->console->info('Creating Local Config File');

        // add local config file
        $_local = str_replace(
            ['{siteName}', '{dbUser}', '{dbPass}', '{dbHost}'],
            [$this->name, $db_user, $db_pass, $db_host],
            File::get(base_path('stubs/wp-config-local.php.stub'))
        );

        File::put('wp-config-local.php', $_local);

        $this->console->info('Installing Wordpress and Plugins');
        // Install wordpress and plugins
        $site_name = Str::title(str_replace('-', ' ', $this->name));

        passthru("
            wp core install --url='http://'{$this->name}'.test' --title='{$site_name}' --admin_user={$username} --admin_password={$password} --admin_email='info@website.com' --quiet --skip-email;

            wp plugin install advanced-custom-fields-font-awesome acf-to-rest-api admin-menu-editor toolbar-publish-button imsanity --quiet;

            git clone --depth=1 --branch=main https://github.com/roots/soil.git wp-content/plugins/soil --quiet;
            rm -rf wp-content/plugins/soil/.git;

            acf_zip_file=\"wp-content/plugins/acf-pro.zip\";
            curl -o \${acf_zip_file} \"http://connect.advancedcustomfields.com/index.php?p=pro&a=download&k=b3JkZXJfaWQ9NjcwMjJ8dHlwZT1kZXZlbG9wZXJ8ZGF0ZT0yMDE1LTEwLTIwIDE4OjMzOjMy\" --silent;
            wp --quiet plugin --quiet install --quiet \${acf_zip_file} --quiet;
            rm \${acf_zip_file};

            wp plugin update --all --quiet;
            wp theme activate signal --quiet;
            wp rewrite structure '/%postname%/' --hard --quiet;
            wp user update 1 --show_admin_bar_front=false --user_email='admin@example.com' --quiet;
            wp post update 2 --post_title=Home --post_name=home --quiet
            wp option update show_on_front page --quiet
            wp option update page_on_front 2 --quiet
            wp rewrite flush --hard --quiet;
            wp plugin activate --all --quiet;
            wp config shuffle-salts --quiet;
        ");

        $this->console->info('Installing dependencies');
        passthru("
            cd wp-content/themes/signal;
            npm i;
            npm run dev;
            find . -type f -name 'webpack.mix.js' -exec sed -i '' s/CHANGE_ME.test/{$this->name}.test/ {} +
        ");

        File::copy(base_path('stubs/wp-gitignore.stub'), '.gitignore');

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

        passthru('cd wp-content/themes/signal; npm run watch;');
    }
}
