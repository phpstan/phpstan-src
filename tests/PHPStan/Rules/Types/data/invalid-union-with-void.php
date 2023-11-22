<?php declare(strict_types=1);

namespace InvalidUnionWithVoid;

class Foo
{

    public function prepare(
        string $query,
        string ...$args
    ): string|void
    {
    }

    public function execute(string $query): ?void
    {
    }

}
