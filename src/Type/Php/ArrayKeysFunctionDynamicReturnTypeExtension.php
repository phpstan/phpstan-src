<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;
use function strtolower;

#[AutowiredService]
final class ArrayKeysFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return strtolower($functionReflection->getName()) === 'array_keys';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if ($arrayType->isArray()->no()) {
			return $this->phpVersion->arrayFunctionsReturnNullWithNonArray() ? new NullType() : new NeverType();
		}

		if (count($args) >= 2) {
			$filterType = $scope->getType($args[1]->value);

			$strict = TrinaryLogic::createNo();
			if (count($args) >= 3) {
				$strict = $scope->getType($args[2]->value)->isTrue();
			}

			return $arrayType->getKeysArrayFiltered($filterType, $strict);
		}

		return $arrayType->getKeysArray();
	}

}
