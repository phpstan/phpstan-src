<?php declare(strict_types=1);

namespace PHPStan\Type\Test\E;

class A {}
class B extends A {}
class C extends B {}

/** @template T */
class Foo {}
