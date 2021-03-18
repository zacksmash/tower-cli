<?php

namespace App\Providers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (function_exists('posix_getuid')) {
            // Mac or Linux
            $path = posix_getpwuid(posix_getuid())['dir'];
        } else {
            // Windows
            $path = exec('echo %USERPROFILE%');
        }

        config()->set([
            'home_dir' => $path,
        ]);

        $config_path = config('home_dir') . config('app.config_path') . config('app.config_file');

        if (file_exists($config_path)) {
            $config = File::get($config_path);
            $config = (array) json_decode($config);

            config()->set([
                'user' => $config
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
