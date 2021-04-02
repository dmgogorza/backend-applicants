<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\Type;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tightenco\Collect\Support\Collection;

class ShowUserController
{
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    /** @var GitHubUsersRepository */
    private $gitHubUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository, GitHubUsersRepository $gitHubUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
        $this->gitHubUsersRepository = $gitHubUsersRepository;
    }

    public function __invoke(Request $request, Response $response, array $params): Response
    {
        $type = new Type($params['type']);
        $login = new Login($params['login']);

        switch ($type) {
            case Type::LOCAL:
                $user = $this->localUsersRepository->getByLogin($login);
                break;
            case Type::GITHUB:
                $user = $this->gitHubUsersRepository->getByLogin($login);
                break;
            default:
                // Exception
        }

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
            ->withStatus(200, 'OK');
    }
}
