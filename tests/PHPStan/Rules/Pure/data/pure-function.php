<?php

namespace PureFunction;

/**
 * @phpstan-pure
 */
function doFoo(&$p)
{
	echo 'test';
}

/**
 * @phpstan-pure
 */
function doFoo2(): void
{
	exit;
}

/**
 * @phpstan-pure
 */
function doFoo3(object $obj)
{
	$obj->foo = 'test';
}

/**
 * @phpstan-pure
 */
function pureFunction()
{

}

/**
 * @phpstan-impure
 */
function impureFunction()
{
	echo '';
}

function voidFunction(): void
{
	echo 'test';
}

function possiblyImpureFunction()
{

}

/**
 * @phpstan-pure
 */
function testThese(string $s)
{
	$s();
	pureFunction();
	impureFunction();
	voidFunction();
	possiblyImpureFunction();
	unknownFunction();
}

/**
 * @phpstan-impure
 */
function actuallyPure()
{

}

function voidFunctionThatThrows(): void
{
	if (rand(0, 1)) {
		throw new \Exception();
	}
}

function emptyVoidFunction(): void
{

}
