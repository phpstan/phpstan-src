<?php declare(strict_types = 1);

namespace MethodTagTraitTemplate;

/**
 * @template T
 *
 * @method void sayHello<T, U of Nonexisting, stdClass>(T $a, U $b, stdClass $c)
 * @method void typeAlias<TypeAlias of mixed>(TypeAlias $a)
 */
trait HelloWorld
{
}
