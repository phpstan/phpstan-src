<?php declare(strict_types=1);

namespace Count;

class Foo 
{
    public function doFoo(int $mode): void
    {
        count([1, 2, 3], -1);
        count([1, 2, 3], $mode);       
    }
}