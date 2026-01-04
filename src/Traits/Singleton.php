<?php

namespace Hudsxn\Introspection\Traits;

trait Singleton
{
    private static self $instance;

    private function __construct()
    {
        self::$instance = $this;
    }

    public static function getSelf(): static
    {
        return self::$instance ?? new self();
    }
}