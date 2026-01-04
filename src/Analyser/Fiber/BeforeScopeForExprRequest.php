<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;

final class BeforeScopeForExprRequest
{

	public function __construct(public readonly Expr $expr, public readonly MutatingScope $scope)
	{
	}

}
