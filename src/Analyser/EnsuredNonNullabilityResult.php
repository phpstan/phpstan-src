<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Turbo\CompiledIntoTurboExtension;

#[CompiledIntoTurboExtension(new: ['pt_ensured_non_nullability_result_new', 'val'])]
final class EnsuredNonNullabilityResult
{

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	public function __construct(private MutatingScope $scope, private array $specifiedExpressions)
	{
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	/**
	 * @return EnsuredNonNullabilityResultExpression[]
	 */
	public function getSpecifiedExpressions(): array
	{
		return $this->specifiedExpressions;
	}

}
