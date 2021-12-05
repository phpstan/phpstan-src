<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class EnsuredNonNullabilityResult
{

	private MutatingScope $scope;

	/** @var EnsuredNonNullabilityResultExpression[] */
	private array $specifiedExpressions;

	/**
	 * @param EnsuredNonNullabilityResultExpression[] $specifiedExpressions
	 */
	public function __construct(MutatingScope $scope, array $specifiedExpressions)
	{
		$this->scope = $scope;
		$this->specifiedExpressions = $specifiedExpressions;
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
