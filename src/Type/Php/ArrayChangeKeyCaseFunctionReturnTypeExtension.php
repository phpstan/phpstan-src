<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
use const CASE_LOWER;

#[AutowiredService]
final class ArrayChangeKeyCaseFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_change_key_case';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[0])) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if (!isset($args[1])) {
			$case = CASE_LOWER;
		} else {
			$caseType = $scope->getType($args[1]->value);
			$scalarValues = $caseType->getConstantScalarValues();
			if (count($scalarValues) === 1) {
				$case = (int) $scalarValues[0];
			} else {
				$case = null;
			}
		}

		return $arrayType->changeKeyCaseArray($case);
	}

}
