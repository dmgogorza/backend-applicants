<?php

namespace Osana\Challenge\Http\Controllers;

use Osana\Challenge\Domain\Users\Login;
use Osana\Challenge\Domain\Users\User;
use Osana\Challenge\Services\GitHub\GitHubUsersRepository;
use Osana\Challenge\Services\Local\LocalUsersRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FindUsersController
{
    const LIMIT_DEFAULT = 20;
    
    /** @var LocalUsersRepository */
    private $localUsersRepository;

    /** @var GitHubUsersRepository */
    private $gitHubUsersRepository;

    public function __construct(LocalUsersRepository $localUsersRepository, GitHubUsersRepository $gitHubUsersRepository)
    {
        $this->localUsersRepository = $localUsersRepository;
        $this->gitHubUsersRepository = $gitHubUsersRepository;
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['q'] ?? '';
        $limit = $request->getQueryParams()['limit'] ?? self::LIMIT_DEFAULT;

        $login = new Login($query);

        $localUsers = $this->localUsersRepository->findByLogin($login, $limit);

        // Obtener el límite máximo de usuarios GitHub que faltan
        $limitGitHub = max(floor($limit / 2), $limit - count($localUsers));
        $githubUsers = $this->gitHubUsersRepository->findByLogin($login, $limitGitHub);

        // Cortar parte de usuarios locales para respetar el limite
        if ($limit < (count($localUsers) + count($githubUsers))) {
            $limitLocal = $limit - $limitGitHub;
            $localUsers = $localUsers->slice(0, $limitLocal);
        }

        $users = $localUsers->merge($githubUsers)->map(function (User $user) {
            return [
                'id' => $user->getId()->getValue(),
                'login' => $user->getLogin()->getValue(),
                'type' => $user->getType()->getValue(),
                'profile' => [
                    'name' => $user->getProfile()->getName()->getValue(),
                    'company' => $user->getProfile()->getCompany()->getValue(),
                    'location' => $user->getProfile()->getLocation()->getValue(),
                ]
            ];
        });

        $response->getBody()->write($users->toJson());

        return $response->withHeader('Content-Type', 'application/json')
            ->withStatus(200, 'OK');
    }
}
