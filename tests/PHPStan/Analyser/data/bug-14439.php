<?php declare(strict_types = 1);

namespace Bug14439;

/**
 * @template T
 */
abstract class AbstractBar
{
}

/**
 * @template T
 * @extends parent<T>
 */
abstract class AbstractFoo extends AbstractBar
{
}

/**
 * @template T of int
 * @extends parent<T>
 */
class Foo extends AbstractFoo
{
}
