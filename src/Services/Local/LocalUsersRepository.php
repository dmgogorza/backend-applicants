<?php

namespace Osana\Challenge\Services\Local;

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

class LocalUsersRepository implements UsersRepository
{
    public function findByLogin(Login $login, int $limit = 0): Collection
    {
        $result = new Collection();

        $users = array_map('str_getcsv', file('data/users.csv'));
        unset($users[0]); // Remove header

        $profiles = array_map('str_getcsv', file('data/profiles.csv'));
        unset($profiles[0]); // Remove header
        
        $query = $login->getValue();

        foreach ($users as $order => $user) {
            if (($limit > 0) && ((trim($query) === '') || (strncmp($user[1], $query, strlen($query)) == 0))) {
                $id = new Id($user[0]);
                $login = new Login($user[1]);
                $type = Type::Local();

                $company = new Company($profiles[$order][1]);
                $location = new Location($profiles[$order][2]);
                $name = new Name($profiles[$order][3]);
                $profile = new Profile($name, $company, $location);

                $result->add(new User($id, $login, $type, $profile));
                $limit--;
            }
        }

        return $result;
    }

    public function getByLogin(Login $login, int $limit = 0): User
    {
        $users = array_map('str_getcsv', file('data/users.csv'));
        unset($users[0]); // Remove header

        $profiles = array_map('str_getcsv', file('data/profiles.csv'));
        unset($profiles[0]); // Remove header

        $query = $login->getValue();

        foreach ($users as $order => $user) {
            if ($user[1] == $query) {
                $id = new Id($user[0]);
                $login = new Login($user[1]);
                $type = Type::Local();

                $company = new Company($profiles[$order][1]);
                $location = new Location($profiles[$order][2]);
                $name = new Name($profiles[$order][3]);
                $profile = new Profile($name, $company, $location);

                $user = new User($id, $login, $type, $profile);
                
                break;
            }

            // Exception;
        }

        return $user;
    }

    public function add(User $user): void
    {
        $userRow = [
            'id' => $user->getId()->getValue(),
            'login' => $user->getLogin()->getValue(),
            'type' => Type::Local(),
        ];

        try {
            $usersCSV = fopen('data/users.csv', 'a');
            fputcsv($usersCSV, $userRow);        
            fclose($usersCSV);
        } catch (\Exception $e) {
            // Exception
        }

        $profileRow = [
            'id' => $user->getId()->getValue(),
            'company' => $user->getProfile()->getCompany()->getValue(),
            'location' => $user->getProfile()->getLocation()->getValue(),
            'name' => $user->getProfile()->getName()->getValue(),
        ];
        try {
            $profilesCSV = fopen('data/profiles.csv', 'a');
            fputcsv($profilesCSV, $profileRow);        
            fclose($profilesCSV);
        } catch (\Exception $e) {
            // Exception
        }
    }

    public function count(): int
    {
        $users = array_map('str_getcsv', file('data/users.csv'));
        unset($users[0]); // Remove header
        return count($users);
    }
}
