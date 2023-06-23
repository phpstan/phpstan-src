<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

class CalledOnTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private ?PropertyReflection $transformedProperty = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	public function __construct(
		private PropertyReflection $propertyReflection,
		private ClassReflection $resolvedDeclaringClass,
		private bool $resolveTemplateTypeMapToBounds,
		private Type $fetchedOnType,
	)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedPropertyPrototypeReflection
	{
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}

		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self(
			$this->propertyReflection,
			$this->resolvedDeclaringClass,
			false,
			$this->fetchedOnType,
		);
	}

	public function getNakedProperty(): PropertyReflection
	{
		return $this->propertyReflection;
	}

	public function getTransformedProperty(): PropertyReflection
	{
		if ($this->transformedProperty !== null) {
			return $this->transformedProperty;
		}
		$templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();
		$callSiteVarianceMap = $this->resolvedDeclaringClass->getCallSiteVarianceMap();

		return $this->transformedProperty = new ResolvedPropertyReflection(
			$this->transformPropertyWithStaticType($this->resolvedDeclaringClass, $this->propertyReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap,
			$callSiteVarianceMap,
		);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new self(
			$this->propertyReflection,
			$this->resolvedDeclaringClass,
			$this->resolveTemplateTypeMapToBounds,
			$type,
		);
	}

	private function transformPropertyWithStaticType(ClassReflection $declaringClass, PropertyReflection $property): PropertyReflection
	{
		$readableType = $this->transformStaticType($property->getReadableType());
		$writableType = $this->transformStaticType($property->getWritableType());

		return new ChangedTypePropertyReflection($declaringClass, $property, $readableType, $writableType);
	}

	private function transformStaticType(Type $type): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof StaticType) {
				return $this->fetchedOnType;
			}

			return $traverse($type);
		});
	}

}
