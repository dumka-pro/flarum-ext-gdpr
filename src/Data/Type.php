<?php

/*
 * This file is part of blomstra/flarum-gdpr
 *
 * Copyright (c) 2021 Blomstra Ltd
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Gdpr\Data;

use Blomstra\Gdpr\Contracts\DataType;
use Flarum\User\User;

abstract class Type implements DataType
{
    /** @var User $user */
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
