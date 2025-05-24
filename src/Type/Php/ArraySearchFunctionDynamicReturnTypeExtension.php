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
final class ArraySearchFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_search';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$argsCount = count($functionCall->getArgs());
		if ($argsCount < 2) {
			return null;
		}

		$haystackArgType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($haystackArgType->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		if ($argsCount < 3) {
			$strictArgType = new ConstantBooleanType(false);
		} else {
			$strictArgType = $scope->getType($functionCall->getArgs()[2]->value);
		}

		$needleArgType = $scope->getType($functionCall->getArgs()[0]->value);

		return $haystackArgType->searchArray($needleArgType, $strictArgType->isTrue());
	}

}
