<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\ChangedTypeMethodReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\ResolvedMethodReflection;
use PHPStan\Type\Type;

class CallbackUnresolvedMethodPrototypeReflection implements UnresolvedMethodPrototypeReflection
{

	private MethodReflection $methodReflection;

	private ClassReflection $resolvedDeclaringClass;

	private bool $resolveTemplateTypeMapToBounds;

	/** @var callable(Type): Type */
	private $transformStaticTypeCallback;

	/**
	 * @param MethodReflection $methodReflection
	 * @param ClassReflection $resolvedDeclaringClass
	 * @param bool $resolveTemplateTypeMapToBounds
	 * @param callable(Type): Type $transformStaticTypeCallback
	 */
	public function __construct(
		MethodReflection $methodReflection,
		ClassReflection $resolvedDeclaringClass,
		bool $resolveTemplateTypeMapToBounds,
		callable $transformStaticTypeCallback
	)
	{
		$this->methodReflection = $methodReflection;
		$this->resolvedDeclaringClass = $resolvedDeclaringClass;
		$this->resolveTemplateTypeMapToBounds = $resolveTemplateTypeMapToBounds;
		$this->transformStaticTypeCallback = $transformStaticTypeCallback;
	}

	public function doNotResolveTemplateTypeMapToBounds(): UnresolvedMethodPrototypeReflection
	{
		return new self(
			$this->methodReflection,
			$this->resolvedDeclaringClass,
			false,
			$this->transformStaticTypeCallback
		);
	}

	public function getNakedMethod(): MethodReflection
	{
		return $this->methodReflection;
	}

	public function getTransformedMethod(): MethodReflection
	{
		$templateTypeMap = $this->resolvedDeclaringClass->getActiveTemplateTypeMap();

		return new ResolvedMethodReflection(
			$this->transformMethodWithStaticType($this->resolvedDeclaringClass, $this->methodReflection),
			$this->resolveTemplateTypeMapToBounds ? $templateTypeMap->resolveToBounds() : $templateTypeMap
		);
	}

	public function withCalledOnType(Type $type): UnresolvedMethodPrototypeReflection
	{
		return new CalledOnTypeUnresolvedMethodPrototypeReflection(
			$this->methodReflection,
			$this->resolvedDeclaringClass,
			$this->resolveTemplateTypeMapToBounds,
			$type
		);
	}

	private function transformMethodWithStaticType(ClassReflection $declaringClass, MethodReflection $method): MethodReflection
	{
		$variants = array_map(function (ParametersAcceptor $acceptor): ParametersAcceptor {
			return new FunctionVariant(
				$acceptor->getTemplateTypeMap(),
				$acceptor->getResolvedTemplateTypeMap(),
				array_map(function (ParameterReflection $parameter): ParameterReflection {
					return new DummyParameter(
						$parameter->getName(),
						$this->transformStaticType($parameter->getType()),
						$parameter->isOptional(),
						$parameter->passedByReference(),
						$parameter->isVariadic(),
						$parameter->getDefaultValue()
					);
				}, $acceptor->getParameters()),
				$acceptor->isVariadic(),
				$this->transformStaticType($acceptor->getReturnType())
			);
		}, $method->getVariants());

		return new ChangedTypeMethodReflection($declaringClass, $method, $variants);
	}

	private function transformStaticType(Type $type): Type
	{
		$callback = $this->transformStaticTypeCallback;
		return $callback($type);
	}

}
