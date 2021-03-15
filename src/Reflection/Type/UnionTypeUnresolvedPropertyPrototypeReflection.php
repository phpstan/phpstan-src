<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Type;

class UnionTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private string $propertyName;

	/** @var UnresolvedPropertyPrototypeReflection[] */
	private array $propertyPrototypes;

	/**
	 * @param UnresolvedPropertyPrototypeReflection[] $propertyPrototypes
	 */
	public function __construct(
		string $methodName,
		array $propertyPrototypes
	)
	{
		$this->propertyName = $methodName;
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
		$methods = array_map(static function (UnresolvedPropertyPrototypeReflection $prototype): PropertyReflection {
			return $prototype->getTransformedProperty();
		}, $this->propertyPrototypes);

		return new UnionTypePropertyReflection($methods);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new self($this->propertyName, array_map(static function (UnresolvedPropertyPrototypeReflection $prototype) use ($type): UnresolvedPropertyPrototypeReflection {
			return $prototype->withFechedOnType($type);
		}, $this->propertyPrototypes));
	}

}
