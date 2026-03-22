<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $root = dirname(__DIR__);

        if (! file_exists($root.'/.env') && file_exists($root.'/.env.example')) {
            copy($root.'/.env.example', $root.'/.env');
        }

        parent::setUp();

        if (blank(config('app.key'))) {
            config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        }

        if (! Str::startsWith((string) config('app.key'), 'base64:')) {
            config()->set('app.key', 'base64:'.base64_encode((string) config('app.key')));
        }
    }
}
