<?php declare(strict_types = 1);

namespace MethodTagTemplate;

use stdClass;

/**
 * @template T
 *
 * @method void sayHello<T, U of Nonexisting, stdClass>(T $a, U $b, stdClass $c)
 * @method void typeAlias<TypeAlias of mixed>(TypeAlias $a)
 */
class HelloWorld
{
}
