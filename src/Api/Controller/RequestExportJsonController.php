<?php

/*
 * This file is part of blomstra/flarum-gdpr
 *
 * Copyright (c) 2021 Blomstra Ltd
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Gdpr\Api\Controller;

use Blomstra\Gdpr\Exporter;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Support\Arr;
use Illuminate\Validation\UnauthorizedException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestExportJsonController implements RequestHandlerInterface
{
    public function __construct(Exporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * @inheritDoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var User $actor */
        $actor = RequestUtil::getActor($request);

        $actor->assertCan('gdpr.exportJson');

        $user_id = Arr::get($request->getQueryParams(), 'id');

        if (!$user_id) {
            throw new OutOfRangeException();
        }

        /** @var User */
        $user = User::find($user_id);

        if (!$actor || !$user) {
            throw new UnauthorizedException();
        }

        $output = $this->exporter->output($user);

        return new JsonResponse($output);
    }
}
