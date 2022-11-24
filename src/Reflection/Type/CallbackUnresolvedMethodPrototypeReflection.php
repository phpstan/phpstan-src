<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParameterReflectionWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\DummyParameter;
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

		return $this->transformedMethod = new ResolvedMethodReflection(
			$this->transformMethodWithStaticType($this->resolvedDeclaringClass, $this->methodReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap,
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
		$variants = array_map(fn (ParametersAcceptor $acceptor): ParametersAcceptor => new FunctionVariant(
			$acceptor->getTemplateTypeMap(),
			$acceptor->getResolvedTemplateTypeMap(),
			array_map(
				function (ParameterReflection $parameter): ParameterReflection {
					if ($parameter instanceof ParameterReflectionWithPhpDocs) {
						return new DummyParameterWithPhpDocs(
							$parameter->getName(),
							$this->transformStaticType($parameter->getType()),
							$parameter->isOptional(),
							$parameter->passedByReference(),
							$parameter->isVariadic(),
							$parameter->getDefaultValue(),
							$parameter->getNativeType(),
							$parameter->getPhpDocType(),
							$parameter->getOutType(),
						);
					}

					return new DummyParameter(
						$parameter->getName(),
						$this->transformStaticType($parameter->getType()),
						$parameter->isOptional(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue(),
					);
				},
				$acceptor->getParameters(),
			),
			$acceptor->isVariadic(),
			$this->transformStaticType($acceptor->getReturnType()),
		), $method->getVariants());

		return new ChangedTypeMethodReflection($declaringClass, $method, $variants);
	}

	private function transformStaticType(Type $type): Type
	{
		$callback = $this->transformStaticTypeCallback;
		return $callback($type);
	}

}
