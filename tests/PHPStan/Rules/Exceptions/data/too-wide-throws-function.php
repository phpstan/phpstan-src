<?php

namespace TooWideThrowsFunction;

use DomainException;

/** @throws \InvalidArgumentException */
function doFoo(): void // ok
{
	throw new \InvalidArgumentException();
}

/** @throws \LogicException */
function doFoo2(): void // ok
{
	throw new \InvalidArgumentException();
}

/** @throws \InvalidArgumentException */
function doFoo3(): void // new LogicException cannot be InvalidArgumentException
{
	throw new \LogicException();
}

/** @throws \InvalidArgumentException|\DomainException */
function doFoo4(): void // error - DomainException unused
{
	throw new \InvalidArgumentException();
}

/** @throws void */
function doFoo5(): void // ok - picked up by different rule
{
	throw new \InvalidArgumentException();
}

/** @throws \InvalidArgumentException|\DomainException */
function doFoo6(): void // ok
{
	if (rand(0, 1)) {
		throw new \InvalidArgumentException();
	}

	throw new DomainException();
}

/** @throws \DomainException */
function doFoo7(): void // error - DomainException unused
{
	throw new \InvalidArgumentException();
}

/**
 * @throws \InvalidArgumentException
 * @throws \DomainException
 */
function doFoo8(): void // error - DomainException unused
{
	throw new \InvalidArgumentException();
}

/** @throws \DomainException */
function doFoo9(): void // error - DomainException unused
{

}

/** @throws \InvalidArgumentException */
function doFoo10(\LogicException $e): void // ok
{
	throw $e;
}
