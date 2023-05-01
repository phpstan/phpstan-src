<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateGenericObjectType extends GenericObjectType implements TemplateType
{

	use UndecidedComparisonCompoundTypeTrait;
	/** @use TemplateTypeTrait<GenericObjectType> */
	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		GenericObjectType $bound,
	)
	{
		parent::__construct($bound->getClassName(), $bound->getTypes(), null, null, $bound->getVariances());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	protected function recreate(string $className, array $types, ?Type $subtractedType, array $variances = []): GenericObjectType
	{
		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			$this->getBound(),
		);
	}

}
