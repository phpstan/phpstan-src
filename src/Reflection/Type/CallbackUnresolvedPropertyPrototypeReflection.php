<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Type\Type;

class CallbackUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	/** @var callable(Type): Type */
	private $transformStaticTypeCallback;

	private ?PropertyReflection $transformedProperty = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	/**
	 * @param callable(Type): Type $transformStaticTypeCallback
	 */
	public function __construct(
		private PropertyReflection $propertyReflection,
		private ClassReflection $resolvedDeclaringClass,
		private bool $resolveTemplateTypeMapToBounds,
		callable $transformStaticTypeCallback,
	)
	{
		$this->transformStaticTypeCallback = $transformStaticTypeCallback;
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
			$this->transformStaticTypeCallback,
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

		return $this->transformedProperty = new ResolvedPropertyReflection(
			$this->transformPropertyWithStaticType($this->resolvedDeclaringClass, $this->propertyReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap,
		);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new CalledOnTypeUnresolvedPropertyPrototypeReflection(
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
		$callback = $this->transformStaticTypeCallback;
		return $callback($type);
	}

}
