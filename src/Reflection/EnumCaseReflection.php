<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * @api
 */
final class EnumCaseReflection
{

	public function __construct(private ClassReflection $declaringEnum, private ReflectionEnumUnitCase|ReflectionEnumBackedCase $reflection, private ?Type $backingValueType)
	{
	}

	public function getDeclaringEnum(): ClassReflection
	{
		return $this->declaringEnum;
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getBackingValueType(): ?Type
	{
		return $this->backingValueType;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->reflection->isDeprecated());
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->reflection->isDeprecated()) {
			$attributes = $this->reflection->getBetterReflection()->getAttributes();
			return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
		}

		return null;
	}

}
