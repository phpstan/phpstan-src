<?php declare(strict_types = 1);

namespace Bug10172;

class HelloWorld
{
    /**
     * @template T of array{data: array<mixed>}
     *
     * @param T $a
     *
     * @return T
     */
    public function foo(array $a): array
    {
        foreach ($a['data'] as $i) {
        }

        return $a;
    }
}
