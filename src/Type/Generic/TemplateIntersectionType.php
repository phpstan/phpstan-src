<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\IntersectionType;

/** @api */
final class TemplateIntersectionType extends IntersectionType implements TemplateType
{

	/** @use TemplateTypeTrait<IntersectionType> */
	use TemplateTypeTrait;

	/**
	 * @param non-empty-string $name
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		IntersectionType $bound,
	)
	{
		parent::__construct($bound->getTypes());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

}
