<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

/** @api */
final class TemplateUnionType extends UnionType implements TemplateType
{

	/** @use TemplateTypeTrait<UnionType> */
	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		UnionType $bound,
		?Type $default,
	)
	{
		parent::__construct($bound->getTypes());

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
		$this->default = $default;
	}

}
