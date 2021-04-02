<?php

namespace Osana\Challenge\Services\GitHub;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Domain\Users\UsersRepository;
use Tightenco\Collect\Support\Collection;

class GitHubUsersRepository implements UsersRepository
{
    const API_BASE = "https://api.github.com";

    public function findByLogin(Login $name, int $limit = 0): Collection
    {
        $result = new Collection();
        
        $url = self::API_BASE;
        $url .= (trim($name->getValue()) === '') ? '/users?': '/search/users?q='.$name->getValue().'&';
        $url .= "per_page=$limit";
        
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url);
        $users = json_decode($response->getBody()->getContents(), true);
        
        if (trim($name->getValue()) <> '') {
            $users = $users['items'];
        }
        
        foreach ($users as $user) {
            $login = new Login($user['login']);
            $result->add($this->getByLogin($login));
        }

        return $result;
    }

    public function getByLogin(Login $name, int $limit = 0): User
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', self::API_BASE.'/users/'.$name->getValue());
        $user = json_decode($response->getBody()->getContents(), true);

        $id = new Id($user['id']);
        $login = new Login($user['login']);
        $type = Type::GitHub();

        $company = new Company($user['company'] ?? '');
        $location = new Location($user['location'] ?? '');
        $name = new Name($user['name'] ?? '');
        $profile = new Profile($name, $company, $location);

        return new User($id, $login, $type, $profile);
    }

    public function add(User $user): void
    {
        throw new OperationNotAllowedException();
    }
}
