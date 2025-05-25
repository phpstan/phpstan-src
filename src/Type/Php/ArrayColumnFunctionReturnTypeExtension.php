<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArrayColumnFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private ArrayColumnHelper $arrayColumnHelper,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_column';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$numArgs = count($functionCall->getArgs());
		if ($numArgs < 2) {
			return null;
		}

		$arrayType = $scope->getType($functionCall->getArgs()[0]->value);
		$columnType = $scope->getType($functionCall->getArgs()[1]->value);
		$indexType = $numArgs >= 3 ? $scope->getType($functionCall->getArgs()[2]->value) : new NullType();

		$constantArrayTypes = $arrayType->getConstantArrays();
		if (count($constantArrayTypes) === 1) {
			$type = $this->arrayColumnHelper->handleConstantArray($constantArrayTypes[0], $columnType, $indexType, $scope);
			if ($type !== null) {
				return $type;
			}
		}

		return $this->arrayColumnHelper->handleAnyArray($arrayType, $columnType, $indexType, $scope);
	}

}
