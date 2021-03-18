<?php

namespace App\Commands;

use App\Repository;
use LaravelZero\Framework\Commands\Command;

class DeleteRepoCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'delete-repo
                            {name : The name of the repository you want to delete}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Delete a repository';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $repo = new Repository();

        $name = $this->argument('name');

        $confirm_one = $this->confirm("Are you sure you want to delete the {$name} repository", 'yes');

        if (!$confirm_one) {
            exit;
        }

        $confirm_two = $this->confirm("Like, for real. Are you totally sure you want to delete {$name}?", 'yes');

        if (!$confirm_two) {
            exit;
        }

        $this->warn('Deleting Repo');

        $repo->delete($name);
    }
}
