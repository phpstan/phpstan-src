<?php

namespace OffsetAccessValueAssignmentOnArrayAccessIntersection;

class Foo
{
    /** @var \ArrayAccess<int, string> */
    private $collection1;

    /** @var \ArrayAccess<int, string>&\Countable&iterable<int, string> */
    private $collection2;

    public function foo(): void
    {
        $this->collection1[] = 1;
        $this->collection2[] = 2;
    }
}
