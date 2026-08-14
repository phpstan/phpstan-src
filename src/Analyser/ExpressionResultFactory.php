<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;

interface ExpressionResultFactory
{

	/**
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param (callable(bool): Type)|null $typeCallback
	 * @param callable(TypeSpecifierContext, bool): SpecifiedTypes $specifyTypesCallback
	 * @param (callable(Type, TypeSpecifierContext, bool): SpecifiedTypes)|null $createTypesCallback
	 */
	public function create(
		MutatingScope $scope,
		MutatingScope $beforeScope,
		Expr $expr,
		bool $hasYield,
		bool $isAlwaysTerminating,
		array $throwPoints,
		array $impurePoints,
		?callable $typeCallback,
		callable $specifyTypesCallback,
		bool $containsNullsafe = false,
		?IssetabilityDescriptor $issetabilityDescriptor = null,
		?MutatingScope $truthyScopeOverride = null,
		?MutatingScope $falseyScopeOverride = null,
		?callable $createTypesCallback = null,
		?Type $type = null,
		?Type $nativeType = null,
	): ExpressionResult;

}
