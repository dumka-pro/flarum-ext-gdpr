<?php

/*
 * This file is part of blomstra/flarum-gdpr
 *
 * Copyright (c) 2021 Blomstra Ltd
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Gdpr;

use Blomstra\Gdpr\Api\Serializer\ExportSerializer;
use Blomstra\Gdpr\Api\Serializer\RequestErasureSerializer;
use Blomstra\Gdpr\Models\ErasureRequest;
use Flarum\Api\Controller\ShowUserController;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Extend;
use Flarum\Foundation\Paths;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Illuminate\Console\Scheduling\Event;

return [
    (new Extend\Frontend('admin'))->js(__DIR__.'/js/dist/admin.js'),

    (new Extend\Frontend('forum'))->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Locales(__DIR__.'/resources/locale')),

    (new Extend\Routes('forum'))
        ->get('/gdpr/export/{file}', 'gdpr.export', Http\Controller\ExportController::class)
        ->get('/gdpr/erasure/confirm/{token}', 'gdpr.erasure.confirm', Http\Controller\ConfirmErasureController::class),

    (new Extend\Routes('api'))
        ->post('/gdpr/export', 'gdpr.request-export', Api\Controller\RequestExportController::class)
        ->post('/gdpr/export-json/{id}', 'gdpr.request-export-json', Api\Controller\RequestExportJsonController::class)
        ->get('/user-erasure-requests', 'gdpr.erasure.index', Api\Controller\ListErasureRequestsController::class)
        ->post('/user-erasure-requests', 'gdpr.erasure.create', Api\Controller\RequestErasureController::class)
        ->patch('/user-erasure-requests/{id}', 'gdpr.erasure.process', Api\Controller\ProcessErasureController::class)
        ->delete('/user-erasure-requests/{id}', 'gdpr.erasure.cancel', Api\Controller\CancelErasureController::class),

    (new Extend\Notification())
        ->type(Notifications\ExportAvailableBlueprint::class, ExportSerializer::class, ['alert', 'email'])
        ->type(Notifications\ConfirmErasureBlueprint::class, RequestErasureSerializer::class, ['email'])
        ->type(Notifications\ErasureRequestCancelledBlueprint::class, RequestErasureSerializer::class, ['alert', 'email']),

    (new Extend\Model(User::class))
        ->hasOne('erasureRequest', ErasureRequest::class),

    (new Extend\ApiController(ShowUserController::class))
        ->addInclude('erasureRequest'),

    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function ($serializer, $model, $attributes) {
            $attributes['canProcessErasureRequests'] = $serializer->getActor()->can('processErasure');

            if ($attributes['canProcessErasureRequests']) {
                $attributes['erasureRequestCount'] = ErasureRequest::where('status', 'user_confirmed')->count();
            }

            return $attributes;
        }),

    (new Extend\ApiSerializer(UserSerializer::class))
        ->hasOne('erasureRequest', RequestErasureSerializer::class),

    (new Extend\Settings())
        ->serializeToForum('erasureAnonymizationAllowed', 'blomstra-gdpr.allow-anonymization')
        ->serializeToForum('erasureDeletionAllowed', 'blomstra-gdpr.allow-deletion'),

    (new Extend\View())->namespace('gdpr', __DIR__.'/resources/views'),

    (new Extend\Console())
        ->command(Console\DestroyExportsCommand::class)
        ->schedule(Console\ProcessEraseRequests::class, function (Event $event) {
            $event->daily()->withoutOverlapping();
        }),

    (new Extend\ServiceProvider())->register(Providers\GdprProvider::class),

    (new Extend\Filesystem())->disk('gdpr-export', function (Paths $paths, UrlGenerator $url) {
        return [
            'root'   => "$paths->storage/gdpr-exports",
        ];
    }),
];
