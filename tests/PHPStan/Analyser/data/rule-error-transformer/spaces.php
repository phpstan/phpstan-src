<?php declare(strict_types = 1);

namespace RuleErrorTransformerSpaces;

class Foo
{

    public function doFoo(int $a, int $b): int
    {
        $c = $a + $b;
        $d = $c * $a;

        return $c + $d;
    }

}
