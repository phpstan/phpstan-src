<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateConstantStringType extends ConstantStringType implements TemplateType
{

	/** @use TemplateTypeTrait<ConstantStringType> */
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
		ConstantStringType $bound,
		?Type $default,
	)
	{
		parent::__construct($bound->getValue());
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

}
