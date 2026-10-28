<?php declare(strict_types = 1);

namespace UnionTemplateScalarConstant;

use function PHPStan\Testing\assertType;

/**
 * @template TInt of int
 * @template TBool of bool
 * @template TFloat of float
 * @param TInt $i
 * @param TBool $b
 * @param TFloat $f
 */
function test($i, $b, $f, bool $c, int $int, bool $bool, float $float): void
{
	assertType('5|TInt of int (function UnionTemplateScalarConstant\test(), argument)', $c ? $i : 5);
	assertType('TBool of bool (function UnionTemplateScalarConstant\test(), argument)|true', $c ? $b : true);
	assertType('1.5|TFloat of float (function UnionTemplateScalarConstant\test(), argument)', $c ? $f : 1.5);

	assertType('int', $c ? $int : 5);
	assertType('bool', $c ? $bool : true);
	assertType('float', $c ? $float : 1.5);
}
