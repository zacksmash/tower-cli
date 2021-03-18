<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class SecretCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'secret';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Don\'t use this one... I\'m super cereal...';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->warn('You asked for it...');
        sleep(3);
        passthru('open https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    }
}
