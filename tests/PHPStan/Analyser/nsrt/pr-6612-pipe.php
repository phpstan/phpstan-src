<?php // lint >= 8.5

namespace Pr6612Pipe;

use function PHPStan\Testing\assertType;

class Helper
{

	/** @return non-empty-string */
	public static function run(string $s): string
	{
		return $s . 'x';
	}

}

/** @return list<int> */
function listy(string $s): array
{
	return [1];
}

function g(string $s): void
{
	$a = $s |> Helper::run(...) |> (fn ($v) => $v);
	assertType('non-empty-string', $a);

	$b = $s |> listy(...) |> (fn ($v) => $v);
	assertType('list<int>', $b);

	$c = $s |> Helper::run(...) |> (function ($v) {
		return $v;
	});
	assertType('non-empty-string', $c);
}
