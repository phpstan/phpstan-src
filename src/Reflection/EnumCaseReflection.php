<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\Reflection\Deprecation\DeprecationProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;

/**
 * @api
 */
final class EnumCaseReflection
{

	private bool $isDeprecated;

	private ?string $deprecatedDescription;

	/**
	 * @param list<AttributeReflection> $attributes
	 */
	public function __construct(
		private ClassReflection $declaringEnum,
		private ReflectionEnumUnitCase|ReflectionEnumBackedCase $reflection,
		private ?Type $backingValueType,
		private array $attributes,
		DeprecationProvider $deprecationProvider,
	)
	{
		$deprecation = $deprecationProvider->getEnumCaseDeprecation($reflection);
		if ($deprecation !== null) {
			$this->isDeprecated = true;
			$this->deprecatedDescription = $deprecation->getDescription();

		} elseif ($reflection->isDeprecated()) {
			$attributes = $this->reflection->getBetterReflection()->getAttributes();
			$this->isDeprecated = true;
			$this->deprecatedDescription = DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
		} else {
			$this->isDeprecated = false;
			$this->deprecatedDescription = null;
		}
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
		return TrinaryLogic::createFromBoolean($this->isDeprecated);
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->deprecatedDescription;
	}

	/**
	 * @return list<AttributeReflection>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

}
