<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;
use function array_map;

class UnionTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private string $propertyName;

	private ?PropertyReflection $transformedProperty = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	/**
	 * @param UnresolvedPropertyPrototypeReflection[] $propertyPrototypes
	 */
	public function __construct(
		string $propertyName,
		private array $propertyPrototypes,
	)
	{
		$this->propertyName = $propertyName;
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedPropertyPrototypeReflection
	{
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}
		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self($this->propertyName, array_map(static fn (UnresolvedPropertyPrototypeReflection $prototype): UnresolvedPropertyPrototypeReflection => $prototype->doNotResolveTemplateTypeMapToBounds(), $this->propertyPrototypes));
	}

	public function getNakedProperty(): PropertyReflection
	{
		return $this->getTransformedProperty();
	}

	public function getTransformedProperty(): PropertyReflection
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
