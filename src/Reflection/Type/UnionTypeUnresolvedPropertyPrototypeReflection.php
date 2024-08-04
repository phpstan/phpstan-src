<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;
use function array_map;

final class UnionTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private ?ExtendedPropertyReflection $transformedProperty = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	/**
	 * @param UnresolvedPropertyPrototypeReflection[] $propertyPrototypes
	 */
	public function __construct(
		private string $propertyName,
		private array $propertyPrototypes,
	)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedPropertyPrototypeReflection
	{
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}
		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->propertyName, array_map(static fn (UnresolvedPropertyPrototypeReflection $prototype): UnresolvedPropertyPrototypeReflection => $prototype->doNotResolveTemplateTypeMapToBounds(), $this->propertyPrototypes));
	}

	public function getNakedProperty(): ExtendedPropertyReflection
	{
		return $this->getTransformedProperty();
	}

	public function getTransformedProperty(): ExtendedPropertyReflection
	{
		if ($this->transformedProperty !== null) {
			return $this->transformedProperty;
		}

		$methods = array_map(static fn (UnresolvedPropertyPrototypeReflection $prototype): PropertyReflection => $prototype->getTransformedProperty(), $this->propertyPrototypes);

		return $this->transformedProperty = new UnionTypePropertyReflection($methods);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new self($this->propertyName, array_map(static fn (UnresolvedPropertyPrototypeReflection $prototype): UnresolvedPropertyPrototypeReflection => $prototype->withFechedOnType($type), $this->propertyPrototypes));
	}

}
