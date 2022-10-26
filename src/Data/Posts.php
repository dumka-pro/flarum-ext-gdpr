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

use Flarum\Post\Post;
use Illuminate\Support\Arr;
use PhpZip\ZipFile;

class Posts extends Type
{
    public function export(ZipFile $zip): void
    {
        Post::query()
            ->where('user_id', $this->user->id)
            ->each(function (Post $post) use ($zip) {
                $zip->addFromString(
                    "post-{$post->id}.json",
                    json_encode(
                        $this->sanitize($post),
                        JSON_PRETTY_PRINT
                    )
                );
            });
    }

    public function output(): array
    {
        $output = [];

        Post::query()
            ->where('user_id', $this->user->id)
            ->each(function (Post $post) use (&$output) {
                $output[$post->id] = $this->sanitize($post);
            });

        return ['posts' => $output];
    }

    protected function sanitize(Post $post)
    {
        return Arr::only($post->toArray(), [
            'content', 'created_at',
            'ip_address',
        ]);
    }

    public function anonymize(): void
    {
        Post::query()
            ->where('user_id', $this->user->id)
            ->update(['ip_address' => null]);
    }

    public function delete(): void
    {
        Post::query()->where('user_id', $this->user->id)->delete();
    }
}
