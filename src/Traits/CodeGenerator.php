<?php

namespace Hudsxn\Introspection\Traits;

trait CodeGenerator
{
    public function toSource(array &$lines)
    {
        $lines[] = '/* cannot generate ' . get_class($this) . '*/';
    }
}