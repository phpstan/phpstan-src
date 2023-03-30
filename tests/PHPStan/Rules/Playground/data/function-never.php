<?php

namespace FunctionNever;

function doFoo(): never
{
	throw new \Exception();
}

/**
 * @return never
 */
function doFoo2()
{
	throw new \Exception();
}

function doBar(): void
{
	throw new \Exception();
}

function callsNever()
{
	doFoo();
}

function doBaz()
{
	while (true) {

	}
}

function onlySometimes()
{
	if (rand(0, 1)) {
		return;
	}

	throw new \Exception();
}
