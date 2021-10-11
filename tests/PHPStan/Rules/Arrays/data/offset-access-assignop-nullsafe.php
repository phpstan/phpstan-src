<?php  // lint >= 8.0
declare(strict_types=1);

namespace OffsetAccessAssignOpNullsafe;

class Bar
{
	public const INDEX = 'b';

	/** @phpstan-var Bar::INDEX */
	public string $index = self::INDEX;
}

function doFoo(?Bar $bar)
{
	/** @var array<string,int> $array */
	$array = [
		'a' => 123,
	];

	$array['b'] += 'str';
}
