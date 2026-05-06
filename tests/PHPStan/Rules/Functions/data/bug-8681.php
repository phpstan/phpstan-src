<?php declare(strict_types = 1);

namespace Bug8681Functions;

/**
 * @return array<string, string>
 */
function test(): array
{
	/** @var array $a */
	$a = [];
	return $a;
}

function testClosure(): void
{
	/** @var array $a */
	$a = [];

	/**
	 * @return array<string, string>
	 */
	$closure = function () use ($a): array {
		return $a;
	};

	$closure();
}
