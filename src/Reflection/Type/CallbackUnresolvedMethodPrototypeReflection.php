<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\Php\DummyParameterWithPhpDocs;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Type\Type;
use function array_map;

class CallbackUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
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
		$variantFn = fn (ParametersAcceptorWithPhpDocs $acceptor): ParametersAcceptorWithPhpDocs => new FunctionVariantWithPhpDocs(
			$acceptor->getTemplateTypeMap(),
			$acceptor->getResolvedTemplateTypeMap(),
			array_map(
				fn (ParameterReflectionWithPhpDocs $parameter): ParameterReflectionWithPhpDocs => new DummyParameterWithPhpDocs(
					$parameter->getName(),
					$this->transformStaticType($parameter->getType()),
					$parameter->isOptional(),
					$parameter->passedByReference(),
					$parameter->isVariadic(),
					$parameter->getDefaultValue(),
					$parameter->getNativeType(),
					$parameter->getPhpDocType(),
					$parameter->getOutType(),
					$parameter->isImmediatelyInvokedCallable(),
				),
				$acceptor->getParameters(),
			),
			$acceptor->isVariadic(),
			$this->transformStaticType($acceptor->getReturnType()),
			$this->transformStaticType($acceptor->getPhpDocReturnType()),
			$this->transformStaticType($acceptor->getNativeReturnType()),
			$acceptor->getCallSiteVarianceMap(),
		);
		$variants = array_map($variantFn, $method->getVariants());
		$namedArgumentVariants = $method->getNamedArgumentsVariants();
		$namedArgumentVariants = $namedArgumentVariants !== null
			? array_map($variantFn, $namedArgumentVariants)
			: null;

		return new ChangedTypeMethodReflection($declaringClass, $method, $variants, $namedArgumentVariants);
	}

	private function transformStaticType(Type $type): Type
	{
		$callback = $this->transformStaticTypeCallback;
		return $callback($type);
	}

}
