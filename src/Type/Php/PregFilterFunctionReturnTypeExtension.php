<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

#[AutowiredService]
final class PregFilterFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'preg_filter';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();
		$defaultReturn = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$args,
			$functionReflection->getVariants(),
		)->getReturnType();

		$argsCount = count($args);
		if ($argsCount < 3) {
			return $defaultReturn;
		}

		$subjectType = $scope->getType($args[2]->value);

		if ($subjectType->isArray()->yes()) {
			return new ArrayType(new IntegerType(), new StringType());
		}
		if ($subjectType->isString()->yes()) {
			return new UnionType([new StringType(), new NullType()]);
		}

		return $defaultReturn;
	}

}
