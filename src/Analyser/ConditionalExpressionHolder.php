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

	public function equals(self $other): bool
	{
		if (!$this->typeHolder->equals($other->typeHolder)) {
			return false;
		}

		if (count($this->conditionExpressionTypes) !== count($other->conditionExpressionTypes)) {
			return false;
		}

		foreach ($this->conditionExpressionTypes as $exprString => $type) {
			if (!array_key_exists($exprString, $other->conditionExpressionTypes)) {
				return false;
			}

			if (!$type->equals($other->conditionExpressionTypes[$exprString])) {
				return false;
			}
		}

		return true;
	}

}
