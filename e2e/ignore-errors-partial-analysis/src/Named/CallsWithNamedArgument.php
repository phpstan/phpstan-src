<?php declare(strict_types = 1);

namespace IgnoreErrorsPartialAnalysis;

function callWithNamedArgument(NamedArgumentBase $base): void
{
	$base->doFoo(value: 1);
}
