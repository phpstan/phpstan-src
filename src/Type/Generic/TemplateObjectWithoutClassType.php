<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
class TemplateObjectWithoutClassType extends ObjectWithoutClassType implements TemplateType
{

	use UndecidedComparisonCompoundTypeTrait;
	/** @use TemplateTypeTrait<ObjectWithoutClassType> */
	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		ObjectWithoutClassType $bound,
		?Type $default,
	)
	{
		parent::__construct();

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

}
