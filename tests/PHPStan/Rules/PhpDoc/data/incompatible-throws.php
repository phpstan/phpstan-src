<?php

namespace InvalidPhpDoc;

function noDoc() : void
{
}

/**
 * No tag here.
 */
function noThrowsTag()
{
}

/**
 * @throws \Exception
 */
function singleClassThrows()
{
}

/**
 * @throws \RuntimeException Some comment.
 */
function commentedThrows()
{
}

/**
 * @throws \RuntimeException|\LogicException
 */
function unionThrows()
{
}

/**
 * @throws \Throwable&\DateTimeInterface
 */
function intersectThrows()
{
}

/**
 * @throws (\RuntimeException&\Throwable)|\TypeError
 */
function unionAndIntersectThrows()
{
}

/**
 * @throws \Undefined
 */
function undefinedThrows()
{
}

/**
 * @throws bool
 */
function scalarThrows()
{
}

/**
 * @throws \DateTimeImmutable
 */
function notThrowableThrows()
{
}

/**
 * @throws \DateTimeImmutable|\Throwable
 */
function notThrowableInUnionThrows()
{
}

/**
 * @throws \DateTimeImmutable&\IteratorAggregate
 */
function notThrowableInIntersectThrows()
{
}

/**
 * @throws void
 */
function voidThrows()
{
}

/**
 * @throws \Throwable|void
 */
function voidUnionThrows()
{
}

/**
 * @throws \stdClass|void
 */
function voidUnionWithNotThrowableThrows()
{
}

/**
 * @template T of \Exception
 * @throws T
 */
function exceptionTemplateThrows()
{
}

function inlineThrows()
{
	/** @throws \stdClass */
	$i = 1;
}

/**
 * @param int $x
 * @throws ($x is 0 ? \Exception : void)
 */
function conditionalThrows($x)
{
}

/**
 * @param int $x
 * @throws ($x is 0 ? \Exception : \RuntimeException)
 */
function conditionalThrowsBothBranches($x)
{
}

/**
 * @param int $x
 * @throws ($x is 0 ? \stdClass : void)
 */
function conditionalThrowsInvalidBranch($x)
{
}

/**
 * @template TKey of int|string
 * @param TKey $key
 * @throws (TKey is int ? void : \Exception)
 */
function conditionalThrowsForTemplate($key)
{
}

/**
 * @template TKey of int|string
 * @param TKey $key
 * @throws (TKey is int ? void : \stdClass)
 */
function conditionalThrowsForTemplateInvalidBranch($key)
{
}

/**
 * @param int $x
 * @param int $y
 * @throws ($x is 0 ? \Exception : ($y is 0 ? \RuntimeException : void))
 */
function nestedConditionalThrows($x, $y)
{
}

/**
 * @param int $x
 * @param int $y
 * @throws ($x is 0 ? \Exception : ($y is 0 ? \stdClass : void))
 */
function nestedConditionalThrowsInvalidBranch($x, $y)
{
}
