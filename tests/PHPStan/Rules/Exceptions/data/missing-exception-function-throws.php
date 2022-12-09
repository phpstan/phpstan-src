<?php

namespace MissingExceptionFunctionThrows;

/** @throws \InvalidArgumentException */
function doFoo(): void
{
	throw new \InvalidArgumentException(); // ok
}

/** @throws \LogicException */
function doBar(): void
{
	throw new \InvalidArgumentException(); // ok
}

/** @throws \RuntimeException */
function doBaz(): void
{
	throw new \InvalidArgumentException(); // error
}

/** @throws \RuntimeException */
function doLorem(): void
{
	throw new \InvalidArgumentException(); // error
}

function doLorem2(): void
{
	throw new \InvalidArgumentException(); // error
}

function doLorem3(): void
{
	try {
		throw new \InvalidArgumentException(); // ok
	} catch (\InvalidArgumentException $e) {

	}
}

function doIpsum(): void
{
	throw new \PHPStan\ShouldNotHappenException(); // ok
}

/** @throws \InvalidArgumentException */
function doBar2(): void
{
	throw new \LogicException(); // error
}

/** @throws void */
function doBar3(): void
{
	throw new \LogicException(); // error
}

function testImmediatelyCalledFunctionsWithClosure(): void
{
	array_map(function () {
		throw new \RuntimeException();
	}, ['']);

	array_filter([''], function () {
		throw new \LogicException();
	});
}
