<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
final class TemplateConstantStringType extends ConstantStringType implements TemplateType
{

	/** @use TemplateTypeTrait<ConstantStringType> */
	use TemplateTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		ConstantStringType $bound,
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
