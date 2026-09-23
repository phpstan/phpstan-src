<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\Type;

#[AutowiredService]
final class VersionCompareFunctionDynamicThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'version_compare';
	}

	public function getThrowTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $funcCall,
		Scope $scope,
	): ?Type
	{
		if ($scope->getPhpVersion()->throwsValueErrorForInternalFunctions()->no()) {
			return null;
		}

		$args = $funcCall->getArgs();
		if (!isset($args[2])) {
			return null;
		}

		$operatorStrings = $scope->getType($args[2]->value)->getConstantStrings();
		if (VersionCompareFunctionDynamicReturnTypeExtension::mightBeInvalidOperator($operatorStrings)) {
			return $functionReflection->getThrowType();
		}

		return null;
	}

}
