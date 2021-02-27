<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

final class TemplateGenericObjectType extends GenericObjectType implements TemplateType
{

	use UndecidedComparisonCompoundTypeTrait;
	use TemplateTypeTrait;

	/**
	 * @param Type[] $types
	 */
	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		string $mainType,
		array $types
	)
	{
		parent::__construct($mainType, $types);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = new GenericObjectType($mainType, $types);
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
			$this->name,
			$this->getClassName(),
			$this->getTypes()
		);
	}

	protected function recreate(string $className, array $types, ?Type $subtractedType): GenericObjectType
	{
		return new self(
			$this->scope,
			$this->strategy,
			$this->variance,
			$this->name,
			$className,
			$types
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
			$properties['name'],
			$properties['className'],
			$properties['types']
		);
	}

}
