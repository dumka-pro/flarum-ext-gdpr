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

use Blomstra\Gdpr\Api\Serializer\RequestErasureSerializer;
use Blomstra\Gdpr\Jobs\ErasureJob;
use Blomstra\Gdpr\Models\ErasureRequest;
use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Flarum\Notification\NotificationSyncer;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Tobscure\JsonApi\Document;

class AnonymizeUserController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = RequestErasureSerializer::class;

    /**
     * @var NotificationSyncer
     */
    protected $notifications;

    /**
     * @var Queue
     */
    protected $queue;

    public function __construct(Queue $queue, NotificationSyncer $notifications, Container $container)
    {
        $this->queue = $queue;
        $this->notifications = $notifications;

        $container->alias('translator', TranslatorInterface::class);
    }

    /**
     * @inheritDoc
     */
    public function data(ServerRequestInterface $request, Document $document)
    {
        /** @var User $actor */
        $actor = RequestUtil::getActor($request);

        $actor->assertCan('processErasure');

        $user_id = Arr::get($request->getQueryParams(), 'id');

        if (!$user_id) {
            throw new OutOfRangeException();
        }

        /** @var User */
        $user = User::find($user_id);

        if (!$actor || !$user) {
            throw new UnauthorizedException();
        }

        ErasureRequest::unguard();

        $erasureRequest = ErasureRequest::firstOrNew([
            'user_id' => $user->id,
        ]);

        $erasureRequest->user_id = $user->id;
        $erasureRequest->status = 'awaiting_user_confirmation';
        $erasureRequest->status = 'processed';
        $erasureRequest->created_at = Carbon::now();
        $erasureRequest->processed_mode = 'anonymization';
        $erasureRequest->processed_at = Carbon::now();
        $erasureRequest->processed_by = $actor->id;

        $erasureRequest->save();

        ErasureRequest::reguard();

        $this->queue->push(new ErasureJob($erasureRequest));

        return $erasureRequest;
    }
}
