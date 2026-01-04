<?php

namespace TestClasses;

use Hudsxn\Introspection\Tests\TestAttribute;

#[TestAttribute]
class FullTestClass implements FullTestInterface {
    #[TestAttribute]
    public string $prop1 = 'default';

    public static int $prop2;

    const CONST1 = 123;

    #[TestAttribute]
    const CONST2 = 456;

    #[TestAttribute]
    public function foo(#[TestAttribute] string $arg1, #[TestAttribute] ?int $arg2 = null, ...$args) {}

    private static function bar(int &$param = 5): string { return 'ok'; }
}

#[TestAttribute]
interface FullTestInterface {
    public function foo(string $arg1, ?int $arg2 = null, ...$args);
}

#[TestAttribute]
trait FullTestTrait {
    public function traitMethod(#[TestAttribute] string $arg) {}
}
