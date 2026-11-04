<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypePropertyReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\ResolvedPropertyReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function count;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/CalledOnTypeUnresolvedPropertyPrototypeReflection.cpp')]
final class CalledOnTypeUnresolvedPropertyPrototypeReflection implements UnresolvedPropertyPrototypeReflection
{

	private ?ExtendedPropertyReflection $transformedProperty = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	public function __construct(
		private ExtendedPropertyReflection $propertyReflection,
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

	public function getNakedProperty(): ExtendedPropertyReflection
	{
		return $this->propertyReflection;
	}

	public function getTransformedProperty(): ExtendedPropertyReflection
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

	private function transformPropertyWithStaticType(ClassReflection $declaringClass, ExtendedPropertyReflection $property): ExtendedPropertyReflection
	{
		$readableType = $this->transformStaticType($property->getReadableType());
		$writableType = $this->transformStaticType($property->getWritableType(), true);
		$phpDocType = $this->transformStaticType($property->getPhpDocType());
		$nativeType = $this->transformStaticType($property->getNativeType());

		return new ChangedTypePropertyReflection($declaringClass, $property, $readableType, $writableType, $phpDocType, $nativeType);
	}

	/**
	 * A writable type is compared against assigned types, whose template arguments never carry
	 * the "exactly this class" flavour of `new Foo()` - so `static` resolved inside it must not either.
	 */
	private function transformStaticType(Type $type, bool $isWritableType = false): Type
	{
		return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($isWritableType): Type {
			if ($type instanceof GenericStaticType) {
				$fetchedOnType = $this->getFetchedOnType($isWritableType);
				$fetchedOnTypeReflections = $fetchedOnType->getObjectClassReflections();
				if (count($fetchedOnTypeReflections) === 1) {
					return $traverse($type->changeBaseClass($fetchedOnTypeReflections[0])->getStaticObjectType());
				}

				return $fetchedOnType;
			}
			if ($type instanceof StaticType) {
				return $this->getFetchedOnType($isWritableType);
			}

			return $traverse($type);
		});
	}

	private function getFetchedOnType(bool $isWritableType): Type
	{
		if ($isWritableType) {
			return TemplateTypeHelper::removeFinalByKeywordOverrides($this->fetchedOnType);
		}

		return $this->fetchedOnType;
	}

}
