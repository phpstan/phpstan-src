<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function implode;
use function sprintf;

class ConditionalExpressionHolder
{

	/**
	 * @param array<string, Type> $conditionExpressionTypes
	 */
	public function __construct(
		private array $conditionExpressionTypes,
		private ExpressionTypeHolder $typeHolder,
	)
	{
		if (count($conditionExpressionTypes) === 0) {
			throw new ShouldNotHappenException();
		}
	}

	/**
	 * @return array<string, Type>
	 */
	public function getConditionExpressionTypes(): array
	{
		return $this->conditionExpressionTypes;
	}

	public function getTypeHolder(): ExpressionTypeHolder
	{
		return $this->typeHolder;
	}

	public function getKey(): string
	{
		$parts = [];
		foreach ($this->conditionExpressionTypes as $exprString => $type) {
			$parts[] = $exprString . '=' . $type->describe(VerbosityLevel::precise());
		}

		return sprintf(
			'%s => %s (%s)',
			implode(' && ', $parts),
			$this->typeHolder->getType()->describe(VerbosityLevel::precise()),
			$this->typeHolder->getCertainty()->describe(),
		);
	}

}
