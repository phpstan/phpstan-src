<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\ObjectShapeType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;

/** @api */
final class TemplateObjectShapeType extends ObjectShapeType implements TemplateType
{

	/** @use TemplateTypeTrait<ObjectShapeType> */
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
		ObjectShapeType $bound,
	)
	{
		parent::__construct($bound->getProperties(), $bound->getOptionalProperties());
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
