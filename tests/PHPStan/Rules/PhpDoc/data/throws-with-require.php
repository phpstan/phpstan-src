<?php

namespace ThrowsWithRequire;

/**
 * @phpstan-require-extends \Exception
 */
interface RequiresExtendsExceptionInterface {}

/**
 * @phpstan-require-extends \stdClass
 */
interface RequiresExtendsStdClassInterface {}

/**
 * @throws RequiresExtendsExceptionInterface
 */
function requiresExtendsExceptionThrows()
{
}

/**
 * @throws RequiresExtendsStdClassInterface
 */
function requiresExtendsStdClassThrows()
{
}

/**
 * @throws \Exception|RequiresExtendsExceptionInterface
 */
function unionExceptionAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \DateTimeInterface|RequiresExtendsExceptionInterface
 */
function notThrowableUnionDateTimeInterfaceAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \Exception|RequiresExtendsStdClassInterface
 */
function notThrowableUnionExceptionAndRequiresExtendsStdClassThrows()
{
}

/**
 * @throws \Exception&RequiresExtendsExceptionInterface
 */
function intersectionExceptionAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \DateTimeInterface&RequiresExtendsExceptionInterface
 */
function intersectionDateTimeInterfaceAndRequiresExtendsExceptionThrows()
{
}

/**
 * @throws \Exception&RequiresExtendsStdClassInterface
 */
function intersectionExceptionAndRequiresExtendsStdClassThrows()
{
}

/**
 * @throws \Iterator&RequiresExtendsStdClassInterface
 */
function notThrowableIntersectionIteratorAndRequiresExtendsStdClassThrows()
{
}
