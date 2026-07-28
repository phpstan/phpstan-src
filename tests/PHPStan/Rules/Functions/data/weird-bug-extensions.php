<?php

namespace WeirdBugExtensions;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function PHPStan\Testing\assertType;

class MethodParameterClosureTypeExtension implements \PHPStan\Type\MethodParameterClosureTypeExtension
{

	public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		return $methodReflection->getDeclaringClass()->getName() === \WeirdBug\Builder::class &&
			$parameter->getName() === 'callback' &&
			$methodReflection->getName() === 'methodWithCallback';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		ParameterReflection $parameter,
		Scope $scope
	): ?Type {
		return new CallableType(
			[
				new NativeParameterReflection('test', false, new GenericObjectType(\WeirdBug\Builder::class, [new ObjectType(\WeirdBug\SubModel::class)]), PassedByReference::createNo(), false, null), // @phpstan-ignore-line
			]
		);
	}
}

class BuilderForwardsCallsToAnotherBuilderExtensions implements MethodsClassReflectionExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		return $classReflection->getName() === \WeirdBug\Builder::class && $this->reflectionProvider->getClass(\WeirdBug\AnotherBuilder::class)->hasNativeMethod($methodName);
	}

	public function getMethod(
		ClassReflection $classReflection,
		string $methodName
	): MethodReflection {
		$ref = $this->reflectionProvider->getClass(\WeirdBug\AnotherBuilder::class)->getNativeMethod($methodName);

		/** @var ObjectType $model */
		$model = $classReflection->getActiveTemplateTypeMap()->getType('T');

		return new BuilderMethodReflection(
			$methodName,
			$classReflection,
			$ref->getVariants()[0]->getParameters(),
			$model->getMethod('getBuilder', new OutOfClassScope())->getVariants()[0]->getReturnType(),
			$ref->getVariants()[0]->isVariadic()
		);
	}
}

final class BuilderMethodReflection implements MethodReflection
{
	private Type $returnType;

	/** @param  ParameterReflection[] $parameters */
	public function __construct(private string $methodName, private ClassReflection $classReflection, private array $parameters, Type|null $returnType = null, private bool $isVariadic = false)
	{
		$this->returnType = $returnType ?? new ObjectType(\WeirdBug\Builder::class);
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->classReflection;
	}

	public function isStatic(): bool
	{
		return true;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return $this->methodName;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getVariants(): array
	{
		return [
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				null,
				$this->parameters,
				$this->isVariadic,
				$this->returnType,
			),
		];
	}

	public function getDocComment(): string|null
	{
		return null;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): string|null
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): Type|null
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}
}
