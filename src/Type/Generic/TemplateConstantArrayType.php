<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateConstantArrayType extends ConstantArrayType implements TemplateType
{

	/** @use TemplateTypeTrait<ConstantArrayType> */
	use TemplateTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		ConstantArrayType $bound,
		?Type $default,
	)
	{
		parent::__construct($bound->getKeyTypes(), $bound->getValueTypes(), $bound->getNextAutoIndexes(), $bound->getOptionalKeys(), $bound->isList());
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		if ($this->isOffsetWithinBound($offsetType, $valueType)) {
			return $this;
		}

		return parent::setOffsetValueType($offsetType, $valueType, $unionValues);
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		if ($this->isOffsetWithinBound($offsetType, $valueType)) {
			return $this;
		}

		return parent::setExistingOffsetValueType($offsetType, $valueType);
	}

	private function isOffsetWithinBound(?Type $offsetType, Type $valueType): bool
	{
		if ($offsetType === null) {
			return false;
		}

		$boundKeyTypes = $this->bound->getKeyTypes();
		$boundValueTypes = $this->bound->getValueTypes();

		foreach ($boundKeyTypes as $i => $boundKeyType) {
			if (!$offsetType->equals($boundKeyType)) {
				continue;
			}

			return $boundValueTypes[$i]->accepts($valueType, true)->yes();
		}

		return false;
	}

}
