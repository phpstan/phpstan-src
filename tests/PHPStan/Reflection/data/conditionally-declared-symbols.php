<?php declare(strict_types = 1);

namespace ConditionallyDeclaredSymbols;

if (!function_exists('ConditionallyDeclaredSymbols\guardedByFunctionExists')) {
	function guardedByFunctionExists(): void
	{
	}

	class GuardedClass
	{
	}

	interface GuardedInterface
	{
	}

	trait GuardedTrait
	{
	}
}

if (PHP_VERSION_ID < 80000) {
	function guardedByPhpVersionId(): void
	{
	}
} elseif (PHP_VERSION_ID < 80100) {
	function declaredInElseIf(): void
	{
	}
} else {
	function declaredInElse(): void
	{
	}
}

function declaredUnconditionally(): void
{
}

class UnconditionalClass
{
}
