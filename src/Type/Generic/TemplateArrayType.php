<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ArrayType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateArrayType extends ArrayType implements TemplateType
{

	/** @use TemplateTypeTrait<ArrayType> */
	use TemplateTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		ArrayType $bound,
		?Type $default,
	)
	{
		parent::__construct($bound->getKeyType(), $bound->getItemType());
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

	protected function shouldGeneralizeInferredType(): bool
	{
		return false;
	}

}
