<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Company;
use Osana\Challenge\Domain\Users\Id;
use Osana\Challenge\Domain\Users\Location;
use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Name;
use Osana\Challenge\Domain\Users\Profile;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tightenco\Collect\Support\Collection;

class StoreUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $requestBody = json_decode($request->getBody()->getContents(), true);

        $lastId = $this->localUsersRepository->count();
        $lastId++;

        $id = new Id('CSV'.$lastId);
        $login = new Login($requestBody['login']);
        $type = Type::Local();

        $company = new Company($requestBody['profile']['company']);
        $location = new Location($requestBody['profile']['location']);
        $name = new Name($requestBody['profile']['name']);
        $profile = new Profile($name, $company, $location);

        $user = new User($id, $login, $type, $profile);

        $this->localUsersRepository->add(new User($id, $login, $type, $profile));

        $result = new Collection();
        $userResponse = [
            'id' => $user->getId()->getValue(),
            'login' => $user->getLogin()->getValue(),
            'type' => $user->getType()->getValue(),
            'profile' => [
                'name' => $user->getProfile()->getName()->getValue(),
                'company' => $user->getProfile()->getCompany()->getValue(),
                'location' => $user->getProfile()->getLocation()->getValue(),
            ]
        ];
        $result->add($userResponse);

        $response->getBody()->write($result->toJson());

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(201, 'Created');
    }
}
