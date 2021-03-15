<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Type\Type;

class CallbackUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private PropertyReflection $propertyReflection;

	private ClassReflection $resolvedDeclaringClass;

	private bool $resolveTemplateTypeMapToBounds;

	/** @var callable(Type): Type */
	private $transformStaticTypeCallback;

	/**
	 * @param PropertyReflection $propertyReflection
	 * @param ClassReflection $resolvedDeclaringClass
	 * @param bool $resolveTemplateTypeMapToBounds
	 * @param callable(Type): Type $transformStaticTypeCallback
	 */
	public function __construct(
		PropertyReflection $propertyReflection,
		ClassReflection $resolvedDeclaringClass,
		bool $resolveTemplateTypeMapToBounds,
		callable $transformStaticTypeCallback
	)
	{
		$this->propertyReflection = $propertyReflection;
		$this->resolvedDeclaringClass = $resolvedDeclaringClass;
		$this->resolveTemplateTypeMapToBounds = $resolveTemplateTypeMapToBounds;
		$this->transformStaticTypeCallback = $transformStaticTypeCallback;
	}

	public function doNotResolveTemplateTypeMapToBounds(): self
	{
		return new self(
			$this->propertyReflection,
			$this->resolvedDeclaringClass,
			false,
			$this->transformStaticTypeCallback
		);
	}

	public function getNakedProperty(): PropertyReflection
	{
		return $this->propertyReflection;
	}

	public function getTransformedProperty(): PropertyReflection
	{
		$templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();

		return new ResolvedPropertyReflection(
			$this->transformPropertyWithStaticType($this->resolvedDeclaringClass, $this->propertyReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap
		);
	}

	public function withFechedOnType(Type $type): UnresolvedPropertyPrototypeReflection
	{
		return new CalledOnTypeUnresolvedPropertyPrototypeReflection(
			$this->propertyReflection,
			$this->resolvedDeclaringClass,
			$this->resolveTemplateTypeMapToBounds,
			$type
		);
	}

	protected function transformPropertyWithStaticType(ClassReflection $declaringClass, PropertyReflection $property): PropertyReflection
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
