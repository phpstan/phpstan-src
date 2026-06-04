<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use ReflectionMethod;
use ReflectionParameter;
use function count;

#[AutowiredService]
final class ReflectionMethodGetParametersReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function getClass(): string
	{
		return ReflectionMethod::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'getParameters';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$calledOnType = $scope->getType($methodCall->var);

		$classType = $calledOnType->getTemplateType(ReflectionMethod::class, 'TClass');
		$classNames = $classType->getObjectClassNames();
		if (count($classNames) !== 1) {
			return null;
		}

		$nameType = $calledOnType->getTemplateType(ReflectionMethod::class, 'TName');
		$methodNames = $nameType->getConstantStrings();
		if (count($methodNames) !== 1) {
			return null;
		}

		if (!$this->reflectionProvider->hasClass($classNames[0])) {
			return null;
		}

		$classReflection = $this->reflectionProvider->getClass($classNames[0]);
		if (!$classReflection->hasNativeMethod($methodNames[0]->getValue())) {
			return null;
		}

		$methodReflection = $classReflection->getNativeMethod($methodNames[0]->getValue());

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($methodReflection->getOnlyVariant()->getParameters() as $parameter) {
			$builder->setOffsetValueType(
				null,
				new GenericObjectType(ReflectionParameter::class, [
					new ConstantStringType($parameter->getName()),
				]),
			);
		}

		return $builder->getArray();
	}

}
