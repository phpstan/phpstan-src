<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
final class TemplateObjectType extends ObjectType implements TemplateType
{

	use UndecidedComparisonCompoundTypeTrait;
	/** @use TemplateTypeTrait<ObjectType> */
	use TemplateTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		ObjectType $bound,
	)
	{
		parent::__construct($bound->getClassName());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

}
