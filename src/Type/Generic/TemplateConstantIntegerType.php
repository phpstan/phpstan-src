<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
final class TemplateConstantIntegerType extends ConstantIntegerType implements TemplateType
{

	/** @use TemplateTypeTrait<ConstantIntegerType> */
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
		ConstantIntegerType $bound,
	)
	{
		parent::__construct($bound->getValue());
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
