<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

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
	)
	{
		parent::__construct($bound->getKeyTypes(), $bound->getValueTypes(), $bound->getNextAutoIndexes(), $bound->getOptionalKeys(), $bound->isList());
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	protected function shouldGeneralizeInferredType(): bool
	{
		return false;
	}

}
