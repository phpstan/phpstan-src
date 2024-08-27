<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

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
