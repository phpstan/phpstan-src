<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PhpParser\Node\Expr;

final class ExpressionResultRequest
{

	public function __construct(public readonly Expr $expr, public readonly FiberScope $scope)
	{
	}

}
