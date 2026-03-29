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
		$result = parent::setOffsetValueType($offsetType, $valueType, $unionValues);

		if ($this->getBound()->isSuperTypeOf($result)->yes()) {
			return $this;
		}

		return $result;
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		$result = parent::setExistingOffsetValueType($offsetType, $valueType);

		if ($this->getBound()->isSuperTypeOf($result)->yes()) {
			return $this;
		}

		return $result;
	}

	public function unsetOffset(Type $offsetType, bool $preserveListCertainty = false): Type
	{
		$result = parent::unsetOffset($offsetType, $preserveListCertainty);

		if ($this->getBound()->isSuperTypeOf($result)->yes()) {
			return $this;
		}

		return $result;
	}

}
