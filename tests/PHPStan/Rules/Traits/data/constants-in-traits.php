<?php // lint >= 8.2

namespace ConstantsInTraits;

trait FooBar
{
    const FOO = 'foo';
    public const BAR = 'bar', QUX = 'qux';
}

class Consumer
{
    use FooBar;
}
