<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

function transitivelyPureFunction(): int
{
	return pureFunction() + 1;
}
