<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/**
 * Tracks the source of a foreach loop value variable
 *
 * This class stores information about where a foreach value variable comes from,
 * enabling bidirectional type narrowing between the array and its values.
 */
class ForeachSourceTracking
{

	public function __construct(
		public readonly string $valueVarName,
		public readonly Expr $arrayExpr,
		public readonly Type $originalArrayType
	) {
	}

}
