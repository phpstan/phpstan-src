<?php

namespace CallToFunctionWithoutImpurePoints;

function myFunc()
{
}

function throwingFunc()
{
	throw new \Exception();
}

function funcWithRef(&$a)
{
}

/** @phpstan-impure */
function impureFunc()
{
}

function callingImpureFunc()
{
	impureFunc();
}

function (): void {
	myFunc();
	throwingFunc();
	funcWithRef();
	impureFunc();
	callingImpureFunc();

	$a = myFunc();
};
