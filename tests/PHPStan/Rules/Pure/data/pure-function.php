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

}

function voidFunction(): void
{

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
