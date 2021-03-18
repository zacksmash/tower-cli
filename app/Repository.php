<?php

namespace App;

use Github\Client;

class Repository
{
    private $client;

    private $organization;

    private $github_as;

    public function __construct()
    {
        $this->client = new Client();

        $this->client->authenticate(config('user.github_token'), Client::AUTH_ACCESS_TOKEN);

        $this->organization = config('user.github_organization');

        $this->github_as = config('user.github_as');
    }

    public function exists($name)
    {
        $exists = true;

        try {
            $this->client->api('repo')->show($this->organization, $name);
        } catch (\Throwable $th) {
            $exists = false;
        }

        return $exists;
    }

    public function create($name)
    {
        return $this->client->api('repo')->create($name, '', '', false, $this->github_as === 'organization' ? $this->organization : null);
    }

    public function delete($name)
    {
        return $this->client->api('repo')->remove($this->organization, $name);
    }
}
