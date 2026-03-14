<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;

interface ExpressionResultFactory
{

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(): MutatingScope)|null $truthyScopeCallback
	 * @param (callable(): MutatingScope)|null $falseyScopeCallback
	 */
	public function create(
		Expr $expr,
		MutatingScope $scope,
		bool $hasYield,
		bool $isAlwaysTerminating,
		array $throwPoints,
		array $impurePoints,
		?callable $truthyScopeCallback = null,
		?callable $falseyScopeCallback = null,
	): ExpressionResult;

}
