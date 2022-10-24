<?php

/*
 * This file is part of blomstra/flarum-gdpr
 *
 * Copyright (c) 2021 Blomstra Ltd
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Gdpr\Notifications;

use Blomstra\Gdpr\Models\ErasureRequest;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ErasureCompletedBlueprint implements BlueprintInterface, MailableInterface
{
    /** @var ErasureRequest $request */
    private $request;

    private $username;

    public function __construct(ErasureRequest $request, $username)
    {
        $this->request = $request;
        $this->username = $username;
    }

    public function getFromUser()
    {
        // .. we leave this empty for this message.
    }

    public function getSubject()
    {
        return $this->request;
    }

    public function getData()
    {
        return [
            'username' => $this->username,
        ];
    }

    public static function getType()
    {
        return 'gdpr_erasure_completed';
    }

    public static function getSubjectModel()
    {
        return ErasureRequest::class;
    }

    public function getEmailView()
    {
        return 'gdpr::erasure-completed';
    }

    public function getEmailSubject(TranslatorInterface $translator)
    {
        return $translator->trans('blomstra-gdpr.email.erasure_completed.subject');
    }
}
