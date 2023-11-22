<?php declare(strict_types=1);

namespace InvalidUnionWithNever;

class Foo
{
    public function bar(string $a): ?never
    {
        if ($a === null) {
            return null;
        }

        throw new \RuntimeException($a);
    }

    public function baz(string $b): never|string
    {
        if ($b === '') {
            throw new \RuntimeException('Error.');
        }

        return $b;
    }
}
