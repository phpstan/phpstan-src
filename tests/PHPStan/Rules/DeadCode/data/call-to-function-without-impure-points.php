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

function (): void {
	myFunc();
	throwingFunc();
	funcWithRef();
};
