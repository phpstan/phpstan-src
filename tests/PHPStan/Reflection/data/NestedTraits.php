<?php

namespace NestedTraits;

trait FooTrait
{
}

trait BarTrait
{
    use FooTrait;
}

trait BazTrait
{
    use BarTrait;
}

class NoTrait
{
}

class Foo
{
    use FooTrait;
}

class Bar
{
    use BarTrait;
}

class Baz
{
    use BazTrait;
}

class BazChild extends Baz
{
}