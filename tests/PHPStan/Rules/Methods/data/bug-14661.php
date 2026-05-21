<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug14661;

class A
{
    public function mixedOrder(
        ?string $other = null,
        ?string $target = null,
    ): void {}

    public function sameOrder(
        ?string $other = null,
        ?string $target = null,
    ): void {}

    public function differentTypes(
        int $a,
        string $b,
    ): void {}
}

class B
{
    public function mixedOrder(
        ?string $target = null,
        ?string $other = null,
    ): void {}

    public function sameOrder(
        ?string $other = null,
        ?string $target = null,
    ): void {}

    public function differentTypes(
        string $b,
        int $a,
    ): void {}
}

class C
{
    public function mixedOrder(
        ?string $target = null,
        ?string $extra = null,
        ?string $other = null,
    ): void {}
}

function mixedOrder(A|B $obj): void
{
    $obj->mixedOrder(target: 'value');
}

function sameOrder(A|B $obj): void
{
    $obj->sameOrder(target: 'value');
}

function mixedOrderBothArgs(A|B $obj): void
{
    $obj->mixedOrder(other: 'a', target: 'b');
    $obj->mixedOrder(target: 'b', other: 'a');
}

function differentTypes(A|B $obj): void
{
    $obj->differentTypes(a: 1, b: 'hello');
    $obj->differentTypes(b: 'hello', a: 1);
}

function differentTypesErrors(A|B $obj): void
{
    $obj->differentTypes(a: 'hello', b: 1);
    $obj->differentTypes(b: 1, a: 'hello');
}

function threeWayUnion(A|B|C $obj): void
{
    $obj->mixedOrder(target: 'value');
    $obj->mixedOrder(other: 'a', target: 'b');
}
