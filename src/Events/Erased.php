<?php

/*
 * This file is part of blomstra/flarum-gdpr
 *
 * Copyright (c) 2021 Blomstra Ltd
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Blomstra\Gdpr\Events;

class Erased
{
    /** @var string $username */
    public $username;

    /** @var string $email */
    public $email;

    /** @var string $mode */
    public $mode;

    public function __construct(
        string $username,
        string $email,
        string $mode
    ) {
        $this->$username = $username;
        $this->$email = $email;
        $this->$mode = $mode;
    }
}
