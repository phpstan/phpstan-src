<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\IterableType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

/** @api */
final class TemplateIterableType extends IterableType implements TemplateType
{

	/** @use TemplateTypeTrait<IterableType> */
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
		IterableType $bound,
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

}
