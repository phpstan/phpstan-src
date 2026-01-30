<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class ForeachSourceTracking
{

	public function __construct(
		public readonly string $valueVarName,
		public readonly Expr $arrayExpr,
		public readonly Type $originalArrayType,
	)
	{
	}

}
