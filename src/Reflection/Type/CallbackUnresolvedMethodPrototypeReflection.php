<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class CallbackUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	/** @var callable(Type): Type */
	private $transformStaticTypeCallback;

	private ?ExtendedMethodReflection $transformedMethod = null;

	private ?self $cachedDoNotResolveTemplateTypeMapToBounds = null;

	/**
	 * @param callable(Type): Type $transformStaticTypeCallback
	 */
	public function __construct(
		private ExtendedMethodReflection $methodReflection,
		private ClassReflection $resolvedDeclaringClass,
		private bool $resolveTemplateTypeMapToBounds,
		callable $transformStaticTypeCallback,
	)
	{
		$this->transformStaticTypeCallback = $transformStaticTypeCallback;
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
			$this->transformStaticTypeCallback,
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
		return new CalledOnTypeUnresolvedMethodPrototypeReflection(
			$this->methodReflection,
			$this->resolvedDeclaringClass,
			$this->resolveTemplateTypeMapToBounds,
			$type,
		);
	}

	private function transformMethodWithStaticType(ClassReflection $declaringClass, ExtendedMethodReflection $method): ExtendedMethodReflection
	{
		$selfOutType = $method->getSelfOutType() !== null ? $this->transformStaticType($method->getSelfOutType()) : null;

		$variants = [];
		foreach ($method->getVariants() as $variant) {
			$variants[] = $this->transformVariant($variant, $selfOutType);
		}

		$namedVariants = null;
		$namedArgumentVariants = $method->getNamedArgumentsVariants();
		if ($namedArgumentVariants !== null) {
			$namedVariants = [];
			foreach ($namedArgumentVariants as $namedArgumentVariant) {
				$namedVariants[] = $this->transformVariant($namedArgumentVariant, $selfOutType);
			}
		}

		return new ChangedTypeMethodReflection(
			$declaringClass,
			$method,
			$variants,
			$namedVariants,
			$selfOutType,
		);
	}

	private function transformVariant(ExtendedParametersAcceptor $acceptor, ?Type &$selfOutType): ExtendedParametersAcceptor
	{
		$originalReturnType = $acceptor->getReturnType();
		if ($originalReturnType instanceof ThisType && $selfOutType !== null) {
			$returnType = TypeCombinator::intersect($selfOutType, $this->transformStaticType($originalReturnType));
			$selfOutType = $returnType;
		} else {
			$returnType = $this->transformStaticType($originalReturnType);
		}

		$parameters = [];
		foreach ($acceptor->getParameters() as $parameter) {
			$parameters[] = new ExtendedDummyParameter(
				$parameter->getName(),
				$this->transformStaticType($parameter->getType()),
				$parameter->isOptional(),
				$parameter->passedByReference(),
				$parameter->isVariadic(),
				$parameter->getDefaultValue(),
				$parameter->getNativeType(),
				$this->transformStaticType($parameter->getPhpDocType()),
				$parameter->getOutType() !== null ? $this->transformStaticType($parameter->getOutType()) : null,
				$parameter->isImmediatelyInvokedCallable(),
				$parameter->getClosureThisType() !== null ? $this->transformStaticType($parameter->getClosureThisType()) : null,
				$parameter->getAttributes(),
			);
		}

		return new ExtendedFunctionVariant(
			$acceptor->getTemplateTypeMap(),
			$acceptor->getResolvedTemplateTypeMap(),
			$parameters,
			$acceptor->isVariadic(),
			$returnType,
			$this->transformStaticType($acceptor->getPhpDocReturnType()),
			$this->transformStaticType($acceptor->getNativeReturnType()),
			$acceptor->getCallSiteVarianceMap(),
		);
	}

	private function transformStaticType(Type $type): Type
	{
		$callback = $this->transformStaticTypeCallback;
		return $callback($type);
	}

}
