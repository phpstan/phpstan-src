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

#[AutowiredService]
final class ArrayReverseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_reverse';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (!isset($functionCall->getArgs()[0])) {
			return null;
		}

		$type = $scope->getType($functionCall->getArgs()[0]->value);
		if ($type->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		$preserveKeysType = isset($functionCall->getArgs()[1]) ? $scope->getType($functionCall->getArgs()[1]->value) : new ConstantBooleanType(false);

		return $type->reverseArray($preserveKeysType->isTrue());
	}

}
