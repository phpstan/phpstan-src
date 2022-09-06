<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\Type;

/** @api */
final class TemplateStrictMixedType extends StrictMixedType implements TemplateType
{

	/** @use TemplateTypeTrait<StrictMixedType> */
	use TemplateTypeTrait;

	public function __construct(
		TemplateTypeScope $scope,
		TemplateTypeStrategy $templateTypeStrategy,
		TemplateTypeVariance $templateTypeVariance,
		string $name,
		StrictMixedType $bound,
	)
	{
		$this->scope = $scope;
		$this->strategy = $templateTypeStrategy;
		$this->variance = $templateTypeVariance;
		$this->name = $name;
		$this->bound = $bound;
	}

	public function isSuperTypeOfMixed(MixedType $type): TrinaryLogic
	{
		return $this->isSuperTypeOf($type);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): TrinaryLogic
	{
		return $this->isSubTypeOf($acceptingType);
	}

}
