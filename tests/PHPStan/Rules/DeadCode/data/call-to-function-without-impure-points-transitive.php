<?php

namespace CallToFunctionWithoutImpurePointsTransitive;

function pureBase(): int
{
	return 1 + 1;
}

function pureTransitive(): int
{
	return pureBase();
}

function pureTransitive2(): int
{
	return pureTransitive();
}

/** @phpstan-impure */
function impureBase(): void
{
	echo 'x';
}

function callsImpure(): void
{
	impureBase();
}

function (): void {
	pureBase();
	pureTransitive();
	pureTransitive2();
	impureBase();
	callsImpure();
};
