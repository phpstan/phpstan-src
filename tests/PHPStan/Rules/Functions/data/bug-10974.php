<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug10974;

function non(): void {}
function single(string $str): void {}
/** @param non-empty-array<string> $strs */
function multiple(array $strs): void {}

/** @param array<string> $arr */
function test(array $arr): void
{
	match (count($arr))
	{
		0 => non(),
		1 => single(reset($arr)),
		default => multiple($arr)
	};

	if (empty($arr)) {
		non();
	} elseif (count($arr) === 1) {
		single(reset($arr));
	} else {
		multiple($arr);
	}
}
