<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;

final class TemplateObjectType extends ObjectType implements TemplateType
{

	use UndecidedComparisonCompoundTypeTrait;
	use TemplateTypeTrait {
		isSubTypeOf as isSubTypeOfTrait;
	}

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		string $class
	)
	{
		parent::__construct($class);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = new ObjectType($class);
	}

	public function isSubTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof ObjectWithoutClassType) {
			return TrinaryLogic::createYes();
		}

		return $this->isSubTypeOfTrait($type);
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
			$this->name,
			$this->getClassName()
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
			$properties['className']
		);
	}

}
