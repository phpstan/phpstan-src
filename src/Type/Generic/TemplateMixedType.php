<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

final class TemplateMixedType extends MixedType implements TemplateType
{

	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name
	)
	{
		parent::__construct(true);

		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = new MixedType(true);
	}

	public function isSuperTypeOfMixed(MixedType $type): TrinaryLogic
	{
		return $this->isSuperTypeOf($type);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		$isSuperType = $this->isSuperTypeOf($acceptingType);
		if ($isSuperType->no()) {
			return $isSuperType;
		}
		return TrinaryLogic::createYes();
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
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
			$properties['name']
		);
	}

}
