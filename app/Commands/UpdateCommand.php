<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class UpdateCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'update';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Update Tower CLI';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Updating Tower CLI.\n");
        passthru('composer global update zacksmash/tower-cli --quiet');
    }
}
