<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\GenericStaticType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use function array_map;
use function count;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/CalledOnTypeUnresolvedMethodPrototypeReflection.cpp')]
final class CalledOnTypeUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	private ?ExtendedMethodReflection $transformedMethod = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	public function __construct(
		private ExtendedMethodReflection $methodReflection,
		private ClassReflection $resolvedDeclaringClass,
		private bool $resolveTemplateTypeMapToBounds,
		private Type $calledOnType,
	)
	{
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		if ($this->cachedDoNotResolveTemplateTypeMapToBounds !== null) {
			return $this->cachedDoNotResolveTemplateTypeMapToBounds;
		}

		return $this->cachedDoNotResolveTemplateTypeMapToBounds = new self(
			$this->methodReflection,
			$this->resolvedDeclaringClass,
			false,
			$this->calledOnType,
		);
	}

	public function getNakedMethod(): ExtendedMethodReflection
	{
		return $this->methodReflection;
	}

	public function getTransformedMethod(): ExtendedMethodReflection
	{
		if ($this->transformedMethod !== null) {
			return $this->transformedMethod;
		}
		$templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();
		$callSiteVarianceMap = $this->resolvedDeclaringClass->getCallSiteVarianceMap();

		return $this->transformedMethod = new ResolvedMethodReflection(
			$this->transformMethodWithStaticType($this->resolvedDeclaringClass, $this->methodReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap,
			$callSiteVarianceMap,
		);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new self(
			$this->methodReflection,
			$this->resolvedDeclaringClass,
			$this->resolveTemplateTypeMapToBounds,
			$type,
		);
	}

	private function transformMethodWithStaticType(ClassReflection $declaringClass, ExtendedMethodReflection $method): ExtendedMethodReflection
	{
		$selfOutType = $method->getSelfOutType() !== null ? $this->transformStaticType($method->getSelfOutType()) : null;
		$variantFn = function (ExtendedParametersAcceptor $acceptor) use ($selfOutType): ExtendedParametersAcceptor {
			$originalReturnType = $acceptor->getReturnType();
			if ($originalReturnType instanceof ThisType && $selfOutType !== null) {
				$returnType = $selfOutType;
			} else {
				$returnType = $this->transformStaticType($originalReturnType);
			}
			return new ExtendedFunctionVariant(
				$acceptor->getTemplateTypeMap(),
				$acceptor->getResolvedTemplateTypeMap(),
				array_map(
					fn (ExtendedParameterReflection $parameter): ExtendedParameterReflection => new ExtendedDummyParameter(
						$parameter->getName(),
						$this->transformStaticType($parameter->getType(), true),
						$parameter->isOptional(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue(),
						$parameter->getNativeType(),
						$this->transformStaticType($parameter->getPhpDocType(), true),
						$parameter->getOutType() !== null ? $this->transformStaticType($parameter->getOutType()) : null,
						$parameter->isImmediatelyInvokedCallable(),
						$parameter->getClosureThisType() !== null ? $this->transformStaticType($parameter->getClosureThisType()) : null,
						$parameter->getAttributes(),
						$parameter->getAllowedConstants(),
						$parameter->isPureUnlessCallableIsImpureParameter(),
						$parameter->isPureUnlessParameterPassedParameter(),
					),
					$acceptor->getParameters(),
				),
				$acceptor->isVariadic(),
				$returnType,
				$this->transformStaticType($acceptor->getPhpDocReturnType()),
				$this->transformStaticType($acceptor->getNativeReturnType()),
				$acceptor->getCallSiteVarianceMap(),
			);
		};
		$variants = array_map($variantFn, $method->getVariants());
		$namedArgumentsVariants = $method->getNamedArgumentsVariants();
		$namedArgumentsVariants = $namedArgumentsVariants !== null
			? array_map($variantFn, $namedArgumentsVariants)
			: null;
		$throwType = $method->getThrowType();
		$throwType = $throwType !== null
			? $this->transformStaticType($throwType)
			: null;

		return new ChangedTypeMethodReflection(
			$declaringClass,
			$method,
			$variants,
			$namedArgumentsVariants,
			$selfOutType,
			$throwType,
			$method->getAsserts()->mapTypes(fn (Type $type): Type => $this->transformStaticType($type)),
		);
	}

	/**
	 * A parameter type is compared against argument types, whose template arguments never carry
	 * the "exactly this class" flavour of `new Foo()` - so `static` resolved inside it must not either.
	 */
	private function transformStaticType(Type $type, bool $isParameterType = false): Type
	{
		return TypeTraverser::mapMemoized($type, function (Type $type, callable $traverse) use ($isParameterType): Type {
			if ($type instanceof GenericStaticType) {
				$calledOnType = $this->getCalledOnType($isParameterType);
				$calledOnTypeReflections = $calledOnType->getObjectClassReflections();
				if (count($calledOnTypeReflections) === 1) {
					$calledOnTypeReflection = $calledOnTypeReflections[0];

					return $traverse($type->changeBaseClass($calledOnTypeReflection)->getStaticObjectType());
				}

				return $calledOnType;
			}
			if ($type instanceof StaticType) {
				return $this->getCalledOnType($isParameterType);
			}

			return $traverse($type);
		});
	}

	private function getCalledOnType(bool $isParameterType): Type
	{
		if ($isParameterType) {
			return TemplateTypeHelper::removeFinalByKeywordOverrides($this->calledOnType);
		}

		return $this->calledOnType;
	}

}
