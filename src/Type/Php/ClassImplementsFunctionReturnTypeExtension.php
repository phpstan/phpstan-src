<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeWithClassName;
use function count;

class ClassImplementsFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'class_implements';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$objectOrClassType = $scope->getType($functionCall->getArgs()[0]->value);
		$autoload = !isset($functionCall->getArgs()[1]) || $scope->getType($functionCall->getArgs()[1]->value)->equals(new ConstantBooleanType(true));

		if ($objectOrClassType instanceof TypeWithClassName) {
			$className = $objectOrClassType->getClassName();
		} elseif ($objectOrClassType instanceof ConstantStringType && $autoload) {
			$className = $objectOrClassType->getValue();
		} elseif ($objectOrClassType instanceof ClassStringType && $autoload) {
			return TypeCombinator::remove(
				ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType(),
				new ConstantBooleanType(false),
			);
		} else {
			return null;
		}

		if (!$this->reflectionProvider->hasClass($className)) {
			return new ConstantBooleanType(false);
		}

		$builder = ConstantArrayTypeBuilder::createEmpty();
		foreach ($this->reflectionProvider->getClass($className)->getInterfaces() as $interface) {
			$interfaceNameType = new ConstantStringType($interface->getName());
			$builder->setOffsetValueType($interfaceNameType, $interfaceNameType);
		}

		return $builder->getArray();
	}

}
