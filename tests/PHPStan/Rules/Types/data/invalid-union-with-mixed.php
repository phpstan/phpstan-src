<?php declare(strict_types=1);

namespace InvalidUnionWithMixed;

class Foo
{

    public ?string $bar = null;
    public ?mixed $baz = null;

    public int|null $lorem = null;
    public mixed|string $ipsum = '';

    public function dolor(
        string|int $sit,
        bool|mixed $amet
    ): mixed|string
    {
        return '';
    }

    public function lorem(): mixed|string
    {
        return '';
    }

}

function funcWithMixed(mixed $a, ?mixed $b): ?mixed
{
    return $a;
}

static fn (int $a): ?mixed => $a;
