<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;

class IntersectionTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private string $propertyName;

	/** @var UnresolvedPropertyPrototypeReflection[] */
	private array $propertyPrototypes;

	/**
	 * @param UnresolvedPropertyPrototypeReflection[] $propertyPrototypes
	 */
	public function __construct(
		string $propertyName,
		array $propertyPrototypes
	)
	{
		$this->propertyName = $propertyName;
		$this->propertyPrototypes = $propertyPrototypes;
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedPropertyPrototypeReflection
	{
		return new self($this->propertyName, array_map(static function (UnresolvedPropertyPrototypeReflection $prototype): UnresolvedPropertyPrototypeReflection {
			return $prototype->doNotResolveTemplateTypeMapToBounds();
		}, $this->propertyPrototypes));
	}

	public function getNakedProperty(): PropertyReflection
	{
		return $this->getTransformedProperty();
	}

	public function getTransformedProperty(): PropertyReflection
	{
		$properties = array_map(static function (UnresolvedPropertyPrototypeReflection $prototype): PropertyReflection {
			return $prototype->getTransformedProperty();
		}, $this->propertyPrototypes);

		return new IntersectionTypePropertyReflection($properties);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new self($this->propertyName, array_map(static function (UnresolvedPropertyPrototypeReflection $prototype) use ($type): UnresolvedPropertyPrototypeReflection {
			return $prototype->withFechedOnType($type);
		}, $this->propertyPrototypes));
	}

}
