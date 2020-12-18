<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class ConditionalExpressionHolder
{

	/** @var array<string, Type> */
	private array $conditionExpressionTypes;

	private VariableTypeHolder $typeHolder;

	/**
	 * @param array<string, Type> $conditionExpressionTypes
	 * @param VariableTypeHolder $typeHolder
	 */
	public function __construct(
		array $conditionExpressionTypes,
		VariableTypeHolder $typeHolder
	)
	{
		if (count($conditionExpressionTypes) === 0) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		$this->conditionExpressionTypes = $conditionExpressionTypes;
		$this->typeHolder = $typeHolder;
	}

	/**
	 * @return array<string, Type>
	 */
	public function getConditionExpressionTypes(): array
	{
		return $this->conditionExpressionTypes;
	}

	public function getTypeHolder(): VariableTypeHolder
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
			$this->typeHolder->getCertainty()->describe()
		);
	}

}
