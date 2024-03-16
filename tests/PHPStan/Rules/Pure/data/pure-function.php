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
