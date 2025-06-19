<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArraySliceFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_slice';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if ($arrayType->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		$offsetType = $scope->getType($args[1]->value);
		$lengthType = isset($args[2]) ? $scope->getType($args[2]->value) : new NullType();
		$preserveKeysType = isset($args[3]) ? $scope->getType($args[3]->value) : new ConstantBooleanType(false);

		return $arrayType->sliceArray($offsetType, $lengthType, $preserveKeysType->isTrue());
	}

}
