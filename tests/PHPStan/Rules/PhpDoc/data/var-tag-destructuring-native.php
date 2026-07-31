<?php declare(strict_types = 1);

namespace VarTagDestructuringNative;

/** @return mixed[] */
function makeArray(): array
{
	return [];
}

function doFoo(): void
{
	/** @var array{array{int}} $arr */
	$arr = makeArray();
	foreach ($arr as $item) {
		/** @var string $s */
		[$s] = $item;
	}
}
