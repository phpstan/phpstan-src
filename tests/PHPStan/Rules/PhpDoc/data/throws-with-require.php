<?php

namespace ThrowsWithRequire;

/**
 * @phpstan-require-extends \Exception
 */
interface RequiresExtendsExceptionInterface {}

/**
 * @phpstan-require-implements \Throwable
 */
trait RequiresImplementsThrowableTrait {}

/**
 * @phpstan-require-extends \stdClass
 */
interface RequiresExtendsStdClassInterface {}

/**
 * @phpstan-require-implements \DateTimeInterface
 */
trait RequiresImplementsDateTimeInterfaceTrait {}

/**
 * @throws RequiresExtendsExceptionInterface
 */
function requiresExtendsExceptionThrows()
{
}

/**
 * @throws RequiresImplementsThrowableTrait
 */
function requiresImplementsThrowableThrows()
{
}

/**
 * @throws RequiresExtendsStdClassInterface
 */
function requiresExtendsStdClassThrows()
{
}

/**
 * @throws RequiresImplementsDateTimeInterfaceTrait
 */
function requiresImplementsDateTimeInterfaceThrows()
{
}

/**
 * @throws \Exception|RequiresExtendsExceptionInterface
 */
function unionExceptionAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \Exception|RequiresImplementsThrowableTrait
 */
function unionExceptionAndRequiresImplementsThrowableThrows()
{
}

/**
 * @throws \DateTimeInterface|RequiresExtendsExceptionInterface
 */
function notThrowableUnionDateTimeInterfaceAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \DateTimeInterface|RequiresImplementsThrowableTrait
 */
function notThrowableUnionDateTimeInterfaceAndRequiresImplementsThrowableThrows()
{
}

/**
 * @throws \Exception|RequiresExtendsStdClassInterface
 */
function notThrowableUnionExceptionAndRequiresExtendsStdClassThrows()
{
}

/**
 * @throws \Exception|RequiresImplementsDateTimeInterfaceTrait
 */
function notThrowableUnionExceptionAndRequiresImplementsDateTimeInterfaceTraitThrows()
{
}

/**
 * @throws \Exception&RequiresExtendsExceptionInterface
 */
function intersectionExceptionAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \Exception&RequiresImplementsThrowableTrait
 */
function intersectionExceptionAndRequiresImplementsThrowableThrows()
{
}

/**
 * @throws \DateTimeInterface&RequiresExtendsExceptionInterface
 */
function intersectionDateTimeInterfaceAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \DateTimeInterface&RequiresImplementsThrowableTrait
 */
function intersectionDateTimeInterfaceAndRequiresImplementsThrowableThrows()
{
}

/**
 * @throws \Exception&RequiresExtendsStdClassInterface
 */
function intersectionExceptionAndRequiresExtendsStdClassThrows()
{
}

/**
 * @throws \Exception&RequiresImplementsDateTimeInterfaceTrait
 */
function intersectionExceptionAndRequiresImplementsDateTimeInterfaceTraitThrows()
{
}

/**
 * @throws \Iterator&RequiresExtendsStdClassInterface
 */
function notThrowableIntersectionIteratorAndRequiresExtendsStdClassThrows()
{
}

/**
 * @throws \Iterator&RequiresImplementsDateTimeInterfaceTrait
 */
function notThrowableIntersectionIteratorAndRequiresImplementsDateTimeInterfaceTraitThrows()
{
}
