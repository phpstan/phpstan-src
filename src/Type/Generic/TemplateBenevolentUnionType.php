<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Type;

final class TemplateBenevolentUnionType extends BenevolentUnionType implements TemplateType
{

	use TemplateTypeTrait;

	/**
	 * @param Type[] $types
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		array $types,
		string $name
	)
	{
		parent::__construct($types);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = new BenevolentUnionType($types);
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
			$this->getTypes(),
			$this->name
		);
	}

	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self(
			$properties['scope'],
			$properties['strategy'],
			$properties['variance'],
			$properties['types'],
			$properties['name']
		);
	}

}
