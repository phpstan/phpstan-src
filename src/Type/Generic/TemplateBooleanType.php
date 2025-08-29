<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\BooleanType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateBooleanType extends BooleanType implements TemplateType
{

	/** @use TemplateTypeTrait<BooleanType> */
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
		BooleanType $bound,
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
